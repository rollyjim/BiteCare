<!DOCTYPE html>
<html>
<head>
    <title>BiteCare | Register</title>
    <link rel="stylesheet" href="stylee.css">
</head>
<body>

<div class="login-container">
<h1>Register</h1>

<form action="register_action.php" method="POST">
    <label>Role</label>
    <select name="role" onchange="changeRole()" id="roleSelect" required>
        <option value="">Select Role</option>
        <option value="user">User</option>
        <option value="health">Health Worker</option>
        <option value="superadmin">Super Admin</option>
    </select>

    <div id="dynamicFields"></div>

    <button type="submit">Register</button>
    <a href="index.php" class="login-btn">back to login</a>
</form>
</div>

<script src="scriptt.js?v=2"></script>
</body>
</html>