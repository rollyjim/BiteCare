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
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$message = "";

/* =========================
   ADD NEW APPOINTMENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $purpose = $_POST['purpose'];

    $stmt = $conn->prepare("INSERT INTO appointments (user_id, full_name, email, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user['user_id'], $user['full_name'], $user['email'], $date, $time, $purpose);

    if ($stmt->execute()) {
        $message = "Appointment scheduled successfully.";
    } else {
        $message = "Error scheduling appointment.";
    }
    $stmt->close();
}

/* =========================
   CANCEL APPOINTMENT
========================= */
if (isset($_GET['cancel'])) {
    $appt_id = intval($_GET['cancel']);
    $conn->query("DELETE FROM appointments WHERE id=$appt_id AND user_id=".$user['user_id']);
    header("Location: schedule.php");
    exit();
}

/* =========================
   RESCHEDULE APPOINTMENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule'])) {
    $appt_id = intval($_POST['appt_id']);
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];

    $stmt = $conn->prepare("UPDATE appointments SET appointment_date=?, appointment_time=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssii", $new_date, $new_time, $appt_id, $user['user_id']);

    if ($stmt->execute()) {
        $message = "Appointment rescheduled successfully.";
    } else {
        $message = "Error rescheduling appointment.";
    }
    $stmt->close();
}

/* =========================
   FETCH APPOINTMENTS
========================= */
$stmt = $conn->prepare("SELECT * FROM appointments WHERE user_id=? AND vaccination_status != 'Completed' ORDER BY appointment_date, appointment_time");
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule | BiteCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="users_schedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="profile-section">
            <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'uploads/default_profile.png') ?>" alt="Profile" class="sidebar-profile-img">
            <h3><?= htmlspecialchars($user['full_name']) ?></h3>
            <p class="role">User</p>
            <p><?= htmlspecialchars($user['email']) ?></p>
        </div>
        <ul class="menu">
            <li><a href="users_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="report_bite.php"><i class="fas fa-paw"></i> Report Bite</a></li>
            <li><a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i> Schedule Appointment</a></li>
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
            <h2><i class="fas fa-calendar-alt"></i> Your Schedule</h2>
            <p><?= htmlspecialchars($user['full_name']) ?></p>
        </div>

        <!-- MESSAGE -->
        <?php if($message): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- SCHEDULE FORM -->
        <div class="schedule-form">
            <form method="POST">
                <label><i class="fas fa-calendar-day"></i> Date</label>
                <input type="date" name="date" required>

                <label><i class="fas fa-clock"></i> Time</label>
                <input type="time" name="time" required>

                <label><i class="fas fa-sticky-note"></i> Purpose</label>
                <textarea name="purpose" rows="2" required></textarea>

                <button type="submit" name="schedule">
                    <i class="fas fa-plus-circle"></i> Schedule
                </button>
            </form>
        </div>

        <!-- APPOINTMENTS LIST -->
        <div class="appointments-list">
            <?php if($appointments->num_rows > 0): ?>
                <?php while($appt = $appointments->fetch_assoc()): ?>
                    <div class="appointment-card">

                        <p><strong><i class="fas fa-calendar-day"></i> Date:</strong> <?= htmlspecialchars($appt['appointment_date']) ?></p>
                        <p><strong><i class="fas fa-clock"></i> Time:</strong> <?= htmlspecialchars($appt['appointment_time']) ?></p>
                        <p><strong><i class="fas fa-sticky-note"></i> Purpose:</strong> <?= htmlspecialchars($appt['reason']) ?></p>

                        <!-- RESCHEDULE FORM -->
                        <form method="POST" class="reschedule-form">
                            <input type="hidden" name="appt_id" value="<?= $appt['id'] ?>">

                            <label><i class="fas fa-calendar"></i> New Date</label>
                            <input type="date" name="new_date" required>

                            <label><i class="fas fa-clock"></i> New Time</label>
                            <input type="time" name="new_time" required>

                            <button type="submit" name="reschedule">
                                <i class="fas fa-edit"></i> Reschedule
                            </button>
                        </form>

                        <a href="?cancel=<?= $appt['id'] ?>" class="cancel-btn">
                            <i class="fas fa-times-circle"></i> Cancel
                        </a>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center;">No appointments scheduled.</p>
            <?php endif; ?>
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