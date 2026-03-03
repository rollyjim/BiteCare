<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    /*
    ==============================
    1️⃣ CHECK USERS
    ==============================
    */
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['role'] = 'user';
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];

            header("Location: users_dashboard.php");
            exit();
        }
    }

    /*
    ==============================
    2️⃣ CHECK HEALTH WORKERS
    ==============================
    */
    $stmt = $conn->prepare("SELECT * FROM health_workers WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $staff = $result->fetch_assoc();

        if (password_verify($password, $staff['password'])) {

            $_SESSION['role'] = 'health';
            $_SESSION['staff_id'] = $staff['staff_id'];
            $_SESSION['staff_name'] = $staff['staff_name'];
            $_SESSION['email'] = $staff['email'];
            $_SESSION['admin_id'] = $staff['admin_id'];

            // Insert login log
            $log = $conn->prepare("INSERT INTO health_workers_logs (staff_id, staff_name, email, admin_id, login_time) VALUES (?, ?, ?, ?, NOW())");
            $log->bind_param("issi", $staff['staff_id'], $staff['staff_name'], $staff['email'], $staff['admin_id']);
            $log->execute();
            $log->close();

            header("Location: health_dashboard.php");
            exit();
        }
    }

    /*
    ==============================
    3️⃣ CHECK SUPER ADMIN
    ==============================
    */
    $stmt = $conn->prepare("SELECT * FROM super_admins WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['password'])) {

            $_SESSION['role'] = 'superadmin';
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['email'] = $admin['email'];

            header("Location: superadmin_dashboard.php");
            exit();
        }
    }

    echo "Invalid email or password.";
}
?>