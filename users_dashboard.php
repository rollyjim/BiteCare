<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "user") {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch user info
$stmt = $conn->prepare("SELECT user_id, full_name, email, profile_pic FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// 🔔 Get unread notifications
$user_id = $user['user_id'];
$notifStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id=? AND is_read=0");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$notifResult = $notifStmt->get_result()->fetch_assoc();
$notifCount = $notifResult['total'];
$notifStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        <title>User Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="users_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="profile-section">
            <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'uploads/default_profile.png') ?>" alt="Profile" class="sidebar-profile-img">
            <h3><?= htmlspecialchars($user['full_name']) ?></h3>
            <p class="role">User</p>
            <p><?= htmlspecialchars($user['email']) ?></p>
        </div>
        <ul class="menu">
            <li><a href="users_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="report_bite.php"><i class="fas fa-paw"></i> Report Bite</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule Appointment</a></li>
            <li><a href="dss.php"><i class="fas fa-brain"></i> DSS Assessment</a></li>
            <li><a href="bite_map.php"><i class="fas fa-map"></i> High-Risk Areas</a></li>
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="topbar">
            <!-- Hamburger button (M0bile Only) -->
            <button class="menu-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
            <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
            <!-- Notification bell -->
            <a href="user_notifications.php" class="notif-icon">
                <i class="fas fa-bell"></i>
                <?php if($notifCount > 0): ?>
                    <span class="notif-badge"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="dashboard-cards">
            <a href="report_bite.php" class="dashboard-card">
                <i class="fas fa-paw"></i>
                <span>Report Animal Bite</span>
            </a>
            <a href="schedule.php" class="dashboard-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Schedule Appointment</span>
            </a>
            <a href="dss.php" class="dashboard-card">
                <i class="fas fa-brain"></i>
                <span>DSS Assessment</span>
            </a>
            <a href="bite_map.php" class="dashboard-card">
                <i class="fas fa-map"></i>
                <span>High-Risk Areas</span>
            </a>
            <a href="user_profile.php" class="dashboard-card">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>

    </div>
</div>

<script>
function toggleMenu() {
    document.querySelector(".sidebar").classList.toggle("active");
}

const sidebar = document.querySelector(".sidebar");
const toggleBtn = document.querySelector(".menu-toggle");

// Toggle menu
function toggleMenu() {
    sidebar.classList.toggle("active");
}

// Close when clicking outside
document.addEventListener("click", function (event) {

    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickOnToggle = toggleBtn.contains(event.target);

    if (!isClickInsideSidebar && !isClickOnToggle) {
        sidebar.classList.remove("active");
    }
});

// Close when clicking a menu link (mobile)
document.querySelectorAll(".menu a").forEach(link => {
    link.addEventListener("click", () => {
        sidebar.classList.remove("active");
    });
});
</script>

</body>
</html>