<?php
session_start();
include 'db.php';

if (isset($_SESSION['role']) && $_SESSION['role'] == 'health') {
    $staff_id = $_SESSION['staff_id'];

    // Update the latest login record with logout_time
    $stmt = $conn->prepare("UPDATE health_workers_logs SET logout_time = NOW() WHERE staff_id = ? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->close();
}

// Destroy session
session_destroy();
header("Location: index.php");
exit();
?>
