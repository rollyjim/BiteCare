<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "superadmin") {
    header("Location: index.php");
    exit();
}

$message = "";
$admin_id = 0;
$admin_name = "";

// Get current superadmin info
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id, full_name FROM super_admins WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $admin_id = $row['id'];
    $admin_name = $row['full_name'];
}
$stmt->close();

// Get type and ID
$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

// Fetch existing data
$data = [];
if ($type === 'user' && $id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($type === 'health' && $id) {
    $stmt = $conn->prepare("SELECT * FROM health_workers WHERE staff_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($type === 'superadmin' && $id) {
    $stmt = $conn->prepare("SELECT * FROM super_admins WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = !empty($_POST['new_password']) ? password_hash($_POST['new_password'], PASSWORD_DEFAULT) : null;

    if ($type === 'user') {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, age=?, gender=?, birthday=?, medical_history=?, phone=?, email=?, facebook=?"
            . ($new_password ? ", password=?" : "") . " WHERE user_id=?");

        $params = [
            $_POST['full_name'],
            $_POST['age'],
            $_POST['gender'],
            $_POST['birthday'],
            $_POST['medical_history'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['facebook']
        ];

        if ($new_password) $params[] = $new_password;
        $params[] = $id;

        $types = "sissssssi";
        if ($new_password) $types .= "s";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        $message = "User updated successfully.";

    } elseif ($type === 'health') {
        $stmt = $conn->prepare("UPDATE health_workers SET staff_name=?, email=?, admin_id=?"
            . ($new_password ? ", password=?" : "") . " WHERE staff_id=?");

        $params = [$_POST['staff_name'], $_POST['email'], $_POST['admin_id']];
        if ($new_password) $params[] = $new_password;
        $params[] = $id;

        $types = "ssi";
        if ($new_password) $types .= "s";
        $types .= "i";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        $message = "Health Worker updated successfully.";

    } elseif ($type === 'superadmin') {
        $stmt = $conn->prepare("UPDATE super_admins SET full_name=?, email=?"
            . ($new_password ? ", password=?" : "") . " WHERE id=?");

        $params = [$_POST['full_name'], $_POST['email']];
        if ($new_password) $params[] = $new_password;
        $params[] = $id;

        $types = "ss";
        if ($new_password) $types .= "s";
        $types .= "i";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        $message = "Super Admin updated successfully.";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_type = $_GET['delete'];
    if ($del_type === 'user') $conn->query("DELETE FROM users WHERE user_id=$id");
    elseif ($del_type === 'health') $conn->query("DELETE FROM health_workers WHERE staff_id=$id");
    elseif ($del_type === 'superadmin') $conn->query("DELETE FROM super_admins WHERE id=$id");
    header("Location: superadmin_dashboard.php");
    exit();
}

// Handle convert
if (isset($_GET['convert_to'])) {
    $convert_to = $_GET['convert_to'];
    if ($convert_to === 'health' && $type === 'user') {
        $stmt = $conn->prepare("INSERT INTO health_workers (staff_name, email, password, admin_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $data['full_name'], $data['email'], $data['password'], $admin_id);
        $stmt->execute();
        $stmt->close();
        $conn->query("DELETE FROM users WHERE user_id=$id");
        header("Location: superadmin_dashboard.php");
        exit();
    } elseif ($convert_to === 'user' && $type === 'health') {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $data['staff_name'], $data['email'], $data['password']);
        $stmt->execute();
        $stmt->close();
        $conn->query("DELETE FROM health_workers WHERE staff_id=$id");
        header("Location: superadmin_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit <?= ucfirst($type) ?></title>
    <link rel="stylesheet" href="superadmin_edit.css">
</head>
<body>
<div class="container">
    <h1>Edit <?= ucfirst($type) ?></h1>

    <?php if($message): ?>
        <p class="success"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" class="edit-form">
        <?php if($type === 'user'): ?>
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($data['full_name'] ?? '') ?>" required>

            <label>Age</label>
            <input type="number" name="age" value="<?= htmlspecialchars($data['age'] ?? '') ?>" required>

            <label>Gender</label>
            <select name="gender">
                <option value="Male" <?= ($data['gender'] ?? '')==='Male'?'selected':'' ?>>Male</option>
                <option value="Female" <?= ($data['gender'] ?? '')==='Female'?'selected':'' ?>>Female</option>
                <option value="Other" <?= ($data['gender'] ?? '')==='Other'?'selected':'' ?>>Other</option>
            </select>

            <label>Birthday</label>
            <input type="date" name="birthday" value="<?= htmlspecialchars($data['birthday'] ?? '') ?>">

            <label>Medical History</label>
            <input type="text" name="medical_history" value="<?= htmlspecialchars($data['medical_history'] ?? '') ?>">

            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($data['phone'] ?? '') ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>

            <label>Facebook</label>
            <input type="text" name="facebook" value="<?= htmlspecialchars($data['facebook'] ?? '') ?>">

        <?php elseif($type === 'health'): ?>
            <label>Staff Name</label>
            <input type="text" name="staff_name" value="<?= htmlspecialchars($data['staff_name'] ?? '') ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>

            <label>Admin ID</label>
            <input type="number" name="admin_id" value="<?= htmlspecialchars($data['admin_id'] ?? $admin_id) ?>">

        <?php elseif($type === 'superadmin'): ?>
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($data['full_name'] ?? '') ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
        <?php endif; ?>

        <label>New Password (leave blank to keep current password)</label>
        <input type="password" name="new_password">

        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="type" value="<?= $type ?>">

        <button type="submit" class="btn">Update</button>
        <a href="superadmin_dashboard.php" class="btn btn-back">Back to Dashboard</a>
    </form>
</div>
</body>
</html>