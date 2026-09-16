<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../includes/functions.php';
$_SESSION = [];
session_destroy();
redirect('modules/auth/login.php');
