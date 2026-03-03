<?php
session_start();

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] === "user") {
    header("Location: users_dashboard.php");
    exit();
}

if ($_SESSION['role'] === "health") {
    header("Location: health_dashboard.php");
    exit();
}

if ($_SESSION['role'] === "superadmin") {
    header("Location: superadmin_dashboard.php");
    exit();
}
?>
