<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function cekLogin($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header("Location: /login.php");
        exit;
    }
}
