<?php  
session_start();  
include 'db.php';  

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "user") {  
    header("Location: index.php");  
    exit();  
}  

$email = $_SESSION['email'];  

// Handle profile picture upload  
$message = "";  
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {  
    $target_dir = "uploads/";  
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);  

    $file_name = basename($_FILES["profile_pic"]["name"]);  
    $target_file = $target_dir . time() . "_" . $file_name;  
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);  
    if ($check !== false) {  
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {  
            $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE email=?");  
            $stmt->bind_param("ss", $target_file, $email);  
            $stmt->execute();  
            $stmt->close();  
            $message = "Profile picture updated successfully!";  
        } else {  
            $message = "Error uploading your file.";  
        }  
    } else {  
        $message = "File is not an image.";  
    }  
}  

// Fetch user data  
$stmt = $conn->prepare("SELECT user_id, full_name, age, gender, birthday, medical_history, phone, email, facebook, profile_pic FROM users WHERE email=?");  
$stmt->bind_param("s", $email);  
$stmt->execute();  
$result = $stmt->get_result();  
$user = $result->fetch_assoc();  
$stmt->close();  
?>  

<!DOCTYPE html>  
<html lang="en">  
<head>  
<meta charset="UTF-8">  
<title>User Profile</title>  
<meta name="viewport" content="width=device-width, initial-scale=1.0">  
<link rel="stylesheet" href="user_profile.css">  
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
            <li><a href="users_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="report_bite.php"><i class="fas fa-paw"></i> Report Bite</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule Appointment</a></li>
            <li><a href="dss.php"><i class="fas fa-brain"></i> DSS Assessment</a></li>
            <li><a href="bite_map.php"><i class="fas fa-map"></i> High-Risk Areas</a></li>
            <li><a href="user_profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="topbar">
            <!-- Hamburger button (M0bile Only) -->
            <button class="menu-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
            <h1>User Profile</h1>
        </div>

        <div class="profile-card">

            <?php if($message) echo "<p class='success-msg'>$message</p>"; ?>

            <!-- Profile Picture -->
            <div class="profile-pic">
                <img id="profile-pic" src="<?= htmlspecialchars($user['profile_pic'] ?? 'uploads/default_profile.png') ?>" alt="Profile Picture">
            </div>

            <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="file" name="profile_pic" accept="image/*" required>
                <button type="submit"><i class="fas fa-upload"></i> Change Profile Picture</button>
            </form>

            <div class="profile-details">
                <div class="profile-item">
                    <span class="label"><i class="fas fa-id-badge"></i> Full Name</span>
                    <span><?= htmlspecialchars($user['full_name']) ?></span>
                </div>
                <div class="profile-item">
                    <span class="label"><i class="fas fa-birthday-cake"></i> Age</span>
                    <span><?= htmlspecialchars($user['age']) ?></span>
                </div>
                <div class="profile-item">
                    <span class="label"><i class="fas fa-venus-mars"></i> Gender</span>
                    <span><?= htmlspecialchars($user['gender']) ?></span>
                </div>
                <div class="profile-item">
                    <span class="label"><i class="fas fa-calendar-alt"></i> Birthday</span>
                    <span><?= htmlspecialchars($user['birthday']) ?></span>
                </div>
                <div class="profile-item">
                    <span class="label"><i class="fas fa-notes-medical"></i> Medical History</span>
                    <span><?= htmlspecialchars($user['medical_history']) ?></span>
                </div>
                <div class="profile-item">
                    <span class="label"><i class="fas fa-phone"></i> Phone</span>
                    <span><?= htmlspecialchars($user['phone']) ?></span>
                </div>
                <div class="profile-item">
                    <span class="label"><i class="fas fa-envelope"></i> Email</span>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="profile-item">
                    <span class="label"><i class="fab fa-facebook"></i> Facebook</span>
                    <span><?= htmlspecialchars($user['facebook']) ?></span>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
// Profile pic zoom
const profilePic = document.getElementById('profile-pic');
profilePic.addEventListener('click', () => alert("Zoom feature can be added here"));

// Optional: you can add JS for toggling sidebar if mobile

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