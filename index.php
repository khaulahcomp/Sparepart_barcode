<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
if ($user) {
    redirect('modules/sparepart/list.php');
} else {
    redirect('modules/auth/login.php');
}
