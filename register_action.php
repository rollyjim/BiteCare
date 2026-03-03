<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $role = $_POST['role']; // user, health, superadmin
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert into registration logs, not directly into tables
    $stmt = $conn->prepare("INSERT INTO registration_logs 
        (role, full_name, age, gender, birthday, medical_history, phone, email, facebook, staff_name, admin_id, password, date_registered) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    // For users
    $full_name  = $_POST ['full_name'] ?? '';
    $age = $role === 'user' ? $_POST['age'] : null;
    $gender = $role === 'user' ? $_POST['gender'] : null;
    $birthday = $role === 'user' ? $_POST['birthday'] : null;
    $medical_history = $role === 'user' ? $_POST['medical_history'] : null;
    $phone = $role === 'user' ? $_POST['phone'] : null;
    $facebook = $role === 'user' ? $_POST['facebook'] : null;

    // For health worker
    $staff_name = $role === 'health' ? $_POST['full_name'] : "";
    $admin_id = $role === 'health' ? $_POST['admin_id'] : null;

    $stmt->bind_param(
        "ssssssssssis",
        $role,
        $full_name,
        
        $age,
        $gender,
        $birthday,
        $medical_history,
        $phone,
        $email,
        $facebook,
        $staff_name,
        $admin_id,
        $password
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: index.php?msg=registration_pending");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
