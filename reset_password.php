<?php
include "db.php";

$message = "";

if (isset($_POST['reset'])) {
    $email = $_POST['email'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $message = "Passwords do not match";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);

        // --- USERS TABLE ---
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);
            $update->execute();
            $message = "Password updated successfully for user";
        } 
        // --- HEALTH WORKERS TABLE ---
        else {
            $stmt = $conn->prepare("SELECT staff_id FROM health_workers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $update = $conn->prepare("UPDATE health_workers SET password = ? WHERE email = ?");
                $update->bind_param("ss", $hashed, $email);
                $update->execute();
                $message = "Password updated successfully for health worker";
            } 
            // --- SUPER ADMINS TABLE ---
            else {
                $stmt = $conn->prepare("SELECT id FROM super_admins WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $update = $conn->prepare("UPDATE super_admins SET password = ? WHERE email = ?");
                    $update->bind_param("ss", $hashed, $email);
                    $update->execute();
                    $message = "Password updated successfully for super admin";
                } else {
                    $message = "Email not found in the system";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BiteCare | Reset Password</title>
    <link rel="stylesheet" href="stylee.css">
</head>
<body>

<div class="reset-card">
    <div class="top-bar"></div>

    <h1>Reset Password</h1>
    <p class="subtitle">Enter your email and new password</p>

    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>New Password</label>
        <div class="password-box">
            <input type="password" id="newPass" name="new_password" required>
            <span onclick="toggle('newPass', this)">Show</span>
        </div>

        <label>Confirm New Password</label>
        <div class="password-box">
            <input type="password" id="confirmPass" name="confirm_password" required>
            <span onclick="toggle('confirmPass', this)">Show</span>
        </div>

        <button type="submit" name="reset">Update Password</button>
    </form>

    <a href="index.php" class="back">Back to Login</a>
</div>

<script src="scriptt.js"></script>
</body>
</html>