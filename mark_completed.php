<?php
include 'db.php';

if(isset($_POST['id'])){

    $id = $_POST['id'];

    // Get user_id first (for notification)
    $getUser = $conn->prepare("SELECT user_id FROM appointments WHERE id=?");
    $getUser->bind_param("i", $id);
    $getUser->execute();
    $result = $getUser->get_result();
    $data = $result->fetch_assoc();
    $user_id = $data['user_id'];
    $getUser->close();

    // Mark as completed
    $stmt = $conn->prepare("UPDATE appointments SET vaccination_status='Completed' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Insert notification
    $message = "Your vaccination has been successfully completed. Thank you!";
    $notif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notif->bind_param("is", $user_id, $message);
    $notif->execute();
    $notif->close();
}

header("Location: health_schedule.php");
exit();
?>