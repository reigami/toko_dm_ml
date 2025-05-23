<?php
session_start();
$_SESSION['notifikasi'] = "Berhasil logout!";
session_unset();
session_destroy();
header("Location: ../admin/dashboard.php");
exit;
