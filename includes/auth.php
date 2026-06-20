<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCashier() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'cashier';
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function generateReceiptNo() {
    return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}
?>