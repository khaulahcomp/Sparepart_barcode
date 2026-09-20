<?php
declare(strict_types=1);

/**
 * Encoder CODE128 (Code Set B) murni PHP.
 * Tidak butuh GD/Imagick/Composer — cocok untuk shared hosting cPanel apa pun.
 * Output berupa SVG vektor sehingga tetap tajam pada resolusi cetak berapa pun.
 *
 * Mendukung karakter ASCII 32-126 (spasi s/d tilde), sudah lebih dari cukup
 * untuk kode_custom (huruf, angka, strip, underscore).
 */
final class Barcode128
{
    /** Tabel lebar modul (bar,space,bar,space,bar,space) untuk tiap simbol 0-102,
     *  lalu START A(103), START B(104), START C(105), STOP(106), TERMINATOR(107). */
    private static array $table = [
        '212222','222122','222221','121223','121322','131222','122213','122312','132212','221213',
        '221312','231212','112232','122132','122231','113222','123122','123221','223211','221132',
        '221231','213212','223112','312131','311222','321122','321221','312212','322112','322211',
        '212123','212321','232121','111323','131123','131321','112313','132113','132311','211313',
        '231113','231311','112133','112331','132131','113123','113321','133121','313121','211331',
        '231131','213113','213311','213131','311123','311321','331121','312113','312311','332111',
        '314111','221411','431111','111224','111422','121124','121421','141122','141221','112214',
        '112412','122114','122411','142112','142211','241211','221114','413111','241112','134111',
        '111242','121142','121241','114212','124112','124211','411212','421112','421211','212141',
        '214121','412121','111143','111341','131141','114113','114311','411113','411311','113141',
        '114131','311141','411131',
        '211412', // 103 START A
        '211214', // 104 START B
        '211232', // 105 START C
        '233111', // 106 STOP
        '200000', // 107 TERMINATOR (bar penutup)
    ];

    /**
     * Validasi apakah string bisa dienkode dengan Code Set B (ASCII 32-126).
     */
    public static function isEncodable(string $code): bool
    {
        for ($i = 0, $len = strlen($code); $i < $len; $i++) {
            $ord = ord($code[$i]);
            if ($ord < 32 || $ord > 126) {
                return false;
            }
        }
        return $code !== '';
    }

    /**
     * Encode string menjadi array simbol (indeks tabel), lengkap dengan
     * start code, check digit, stop, dan terminator.
     * @return int[]
     */
    public static function encode(string $code): array
    {
        if (!self::isEncodable($code)) {
            throw new InvalidArgumentException('Karakter tidak didukung CODE128 Set B: ' . $code);
        }

        $START_B = 104;
        $data = [$START_B];
        for ($i = 0, $len = strlen($code); $i < $len; $i++) {
            $data[] = ord($code[$i]) - 32;
        }

        // Checksum: start + sum(value * posisi) mod 103, posisi dimulai dari 1
        $sum = $START_B;
        foreach ($data as $idx => $val) {
            if ($idx === 0) {
                continue; // start code sudah dihitung terpisah di atas
            }
            $sum += $val * $idx;
        }
        $checksum = $sum % 103;

        $data[] = $checksum;
        $data[] = 106; // STOP
        $data[] = 107; // TERMINATOR

        return $data;
    }

    /**
     * Render barcode sebagai elemen SVG (string) lengkap dengan teks kode di bawahnya.
     *
     * @param string $code       Teks yang akan di-encode (kode_custom)
     * @param int    $moduleW    Lebar 1 modul dalam px (mempengaruhi lebar total)
     * @param int    $height     Tinggi batang barcode dalam px
     * @param bool   $showText   Tampilkan teks kode di bawah barcode
     * @param int    $quietZone  Margin kiri-kanan (quiet zone) dalam modul (standar min. 10x module width)
     */
    public static function renderSVG(
        string $code,
        int $moduleW = 2,
        int $height = 60,
        bool $showText = true,
        int $quietZone = 10
    ): string {
        $symbols = self::encode($code);

        // Hitung total lebar dalam modul
        $totalModules = 0;
        $bars = []; // list of [widthInModules, isBar]
        foreach ($symbols as $symIndex) {
            $pattern = self::$table[$symIndex];
            for ($j = 0; $j < 6; $j++) {
                $w = (int)$pattern[$j];
                if ($w === 0) {
                    continue;
                }
                $isBar = ($j % 2) === 0;
                $bars[] = [$w, $isBar];
                $totalModules += $w;
            }
        }

        $quietPx   = $quietZone * $moduleW;
        $barsWidth = $totalModules * $moduleW;
        $textH     = $showText ? 22 : 0;
        $svgWidth  = $barsWidth + (2 * $quietPx);
        $svgHeight = $height + $textH + 6;

        $x = $quietPx;
        $rects = '';
        foreach ($bars as [$w, $isBar]) {
            $wpx = $w * $moduleW;
            if ($isBar) {
                $rects .= sprintf(
                    '<rect x="%d" y="0" width="%d" height="%d" fill="black"/>',
                    $x,
                    $wpx,
                    $height
                );
            }
            $x += $wpx;
        }

        $textEl = '';
        if ($showText) {
            $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            $textEl = sprintf(
                '<text x="%d" y="%d" font-family="monospace" font-size="16" letter-spacing="2" text-anchor="middle" fill="black">%s</text>',
                (int)($svgWidth / 2),
                $height + 18,
                $safeCode
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges">'
            . '<rect x="0" y="0" width="%d" height="%d" fill="white"/>%s%s</svg>',
            $svgWidth,
            $svgHeight,
            $svgWidth,
            $svgHeight,
            $svgWidth,
            $svgHeight,
            $rects,
            $textEl
        );
    }

    /**
     * === PERBAIKAN BUG: barcode tidak terbaca USB scanner ===
     *
     * Akar masalah: renderSVG() dipakai dengan moduleW tetap (2px), lalu di halaman cetak
     * SVG-nya dipaksa CSS "width:100%" mengikuti ukuran fisik label (30-50mm). Untuk
     * kode_custom yang agak panjang (mis. "JL-BEAT-KR-001"), lebar barcode alami bisa >100mm,
     * sehingga saat dipepetkan ke label 30-50mm, lebar 1 modul (bar tertipis) yang tercetak
     * jadi < 0.25mm — di bawah ambang minimum yang bisa dikenali scanner laser 1D (termasuk
     * scanner BS-1808 tipe genggam), sehingga barcode gagal ter-scan meski tercetak jelas.
     *
     * Fungsi ini TIDAK mengubah logika encode()/tabel/checksum sama sekali (semua itu sudah
     * benar dan tetap dipakai apa adanya via renderSVG()). Ia hanya MEMILIH moduleW yang pas
     * agar lebar barcode alami sudah sefisik mungkin muat di label, dengan lebar modul minimum
     * yang dijamin tetap terbaca scanner. Kalau kode terlalu panjang untuk label yang dipilih,
     * moduleW TIDAK diturunkan lagi di bawah batas aman itu — barcode boleh sedikit melebihi
     * kotak label (lebih baik terbaca scanner walau agak lebar, daripada rapi tapi tak terbaca).
     *
     * @param string $code          Teks kode_custom yang akan di-encode
     * @param float  $targetWidthMM Lebar area cetak yang tersedia untuk barcode, dalam mm
     *                               (lebar label dikurangi padding kiri-kanan)
     * @param int    $height        Tinggi batang barcode dalam px (sama seperti renderSVG)
     * @param bool   $showText      Tampilkan teks kode di bawah barcode
     * @param int    $quietZone     Margin kiri-kanan (quiet zone) dalam modul
     * @param int    $minModuleW    Lebar modul minimum dalam px yang dijamin (1px ≈ 0.26mm,
     *                               ambang X-dimension standar industri untuk scanner laser 1D
     *                               umum/retail; jangan diturunkan lagi di bawah ini)
     * @return array{svg:string, fits:bool} 'svg' = markup SVG siap pakai, 'fits' = false kalau
     *                               kode ternyata lebih lebar dari label (butuh label lebih besar)
     */
    public static function renderSVGFit(
        string $code,
        float $targetWidthMM,
        int $height = 55,
        bool $showText = true,
        int $quietZone = 10,
        int $minModuleW = 1
    ): array {
        // Ukur total lebar barcode dalam satuan "modul" memakai renderSVG yang sudah ada
        // (moduleW=1 sehingga viewBox width = total lebar dalam modul), tanpa duplikasi logika.
        $probeSvg = self::renderSVG($code, 1, $height, false, $quietZone);
        $totalModuleUnits = 0;
        if (preg_match('/viewBox="0 0 (\d+) /', $probeSvg, $m)) {
            $totalModuleUnits = (int)$m[1];
        }

        $pxPerMM = 96 / 25.4; // 1 CSS px = 1/96 inch, standar konversi px<->mm saat cetak
        $targetWidthPx = $targetWidthMM * $pxPerMM;

        $moduleW = $minModuleW;
        $fits = true;
        if ($totalModuleUnits > 0) {
            $fitModuleW = (int) floor($targetWidthPx / $totalModuleUnits);
            if ($fitModuleW >= $minModuleW) {
                $moduleW = $fitModuleW; // kode cukup pendek, boleh pakai modul lebih lebar & tajam
            } else {
                $fits = false; // kode terlalu panjang utk label ini walau sudah pakai modul minimum
            }
        }

        return [
            'svg'  => self::renderSVG($code, $moduleW, $height, $showText, $quietZone),
            'fits' => $fits,
        ];
    }
}
