<?php
session_start();

if (isset($_POST['login'])) {
    $_SESSION['role'] = $_POST['role'];
    header("Location: dashboard.php");
    exit();

} 
?>

<!DOCTYPE html>
<html>
<head>
    <title>BiteCare | Login</title>
    <link rel="stylesheet" href="stylee.css">
</head>
<body>

<div class="login-container">
    <h1>BiteCare</h1>
    <p>Secure Role-Based Login</p>

    <form method="POST" action="login_action.php">
        <label>Gmail</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" name="login">login</button>
    </form>

    <div class="extra">
        <a href="reset_password.php">Forgot Password?</a><br>
        <a href="register.php">Register</a>
    </div>
</div>

<script src="scriptt.js"></script>
</body>
</html>