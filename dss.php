<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "user") {
    header("Location: index.php");
    exit();
}

// Fetch user info
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT user_id, full_name, profile_pic FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$risk_result = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $severity = $_POST['severity'];
    $animal = $_POST['animal'];
    $wound_type = $_POST['wound_type'];
    $days_since_bite = intval($_POST['days_since_bite']);

    // Simple DSS logic
    $score = 0;
    if ($severity === "Severe") $score += 3;
    elseif ($severity === "Moderate") $score += 2;
    else $score += 1;

    if ($animal === "Dog") $score += 2;
    elseif ($animal === "Cat") $score += 1;
    else $score += 0;

    if ($wound_type === "Multiple deep bites") $score += 3;
    elseif ($wound_type === "Single deep bite") $score += 2;
    else $score += 1;

    if ($days_since_bite > 7) $score += 2;

    // Determine risk level
    if ($score >= 7) $risk_result = "High Risk: Go to the nearest health center immediately.";
    elseif ($score >= 4) $risk_result = "Moderate Risk: Schedule a visit to the health center soon.";
    else $risk_result = "Low Risk: Monitor and follow up if symptoms appear.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DSS Assessment | BiteCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="dss_style.css">
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="profile-section">
            <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'uploads/default_profile.png') ?>" alt="Profile" class="sidebar-profile-img">
            <h3><?= htmlspecialchars($user['full_name']) ?></h3>
            <p class="role">User</p>
        </div>
        <ul class="menu">
            <li><a href="users_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="report_bite.php"><i class="fas fa-paw"></i> Report Bite</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule Appointment</a></li>
            <li><a href="dss.php" class="active"><i class="fas fa-brain"></i> DSS Assessment</a></li>
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
            <h2><i class="fas fa-brain"></i> DSS Assessment</h2>
            <p><?= htmlspecialchars($user['full_name']) ?></p>
        </div>

        <!-- DSS FORM -->
        <form method="POST" class="dss-form">
            <label><i class="fas fa-exclamation-triangle"></i> Bite Severity</label>
            <select name="severity" required>
                <option value="">Select severity</option>
                <option value="Mild">Mild</option>
                <option value="Moderate">Moderate</option>
                <option value="Severe">Severe</option>
            </select>

            <label><i class="fas fa-paw"></i> Type of Animal</label>
            <select name="animal" required>
                <option value="">Select animal</option>
                <option value="Dog">Dog</option>
                <option value="Cat">Cat</option>
                <option value="Other">Other</option>
            </select>

            <label><i class="fas fa-hand-rock"></i> Wound Type</label>
            <select name="wound_type" required>
                <option value="">Select wound type</option>
                <option value="Superficial">Superficial scratch</option>
                <option value="Single deep bite">Single deep bite</option>
                <option value="Multiple deep bites">Multiple deep bites</option>
            </select>

            <label><i class="fas fa-clock"></i> Days Since Bite</label>
            <input type="number" name="days_since_bite" min="0" placeholder="Days since bite" required>

            <button type="submit" class="submit-btn"><i class="fas fa-check"></i> Assess Risk</button>
        </form>

        <!-- RESULT -->
        <?php if($risk_result): ?>
            <div class="dss-result">
                <h3><i class="fas fa-info-circle"></i> Recommendation</h3>
                <p><?= $risk_result ?></p>
            </div>
        <?php endif; ?>

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