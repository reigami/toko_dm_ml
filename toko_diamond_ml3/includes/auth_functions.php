<?php
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Silakan login terlebih dahulu';
        header('Location: login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}
?>