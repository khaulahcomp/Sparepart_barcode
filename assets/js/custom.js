// Auto-focus input scan agar scanner USB (mode keyboard/HID) langsung terbaca
document.addEventListener('DOMContentLoaded', function () {
  var scanInput = document.getElementById('scanInput');
  if (scanInput) {
    scanInput.focus();
    document.addEventListener('click', function (e) {
      // Jangan re-focus kalau user memang klik elemen interaktif lain
      if (!e.target.closest('button, a, input, select, textarea')) {
        scanInput.focus();
      }
    });
  }

  // Konfirmasi hapus data
  document.querySelectorAll('.btn-confirm-delete').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!confirm(btn.dataset.confirmMsg || 'Yakin ingin menghapus data ini?')) {
        e.preventDefault();
      }
    });
  });
});
