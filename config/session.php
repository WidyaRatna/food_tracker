<?php
session_start();

function checkAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getAdminData() {
    if (isset($_SESSION['admin_data'])) {
        return $_SESSION['admin_data'];
    }
    return null;
}
?>
