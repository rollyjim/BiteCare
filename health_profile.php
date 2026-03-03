<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'health') {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['staff_name'];
$admin_id = $_SESSION['admin_id'];
$message = "";

/* =========================
   HANDLE IMAGE UPLOAD
========================= */
if (isset($_POST['upload'])) {

    if (!empty($_FILES['profile_image']['name'])) {

        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $targetFile = "uploads/" . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($imageFileType, $allowed)) {

            $oldStmt = $conn->prepare("SELECT profile_image FROM health_workers WHERE staff_id=?");
            $oldStmt->bind_param("i", $staff_id);
            $oldStmt->execute();
            $oldData = $oldStmt->get_result()->fetch_assoc();
            $oldStmt->close();

            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {

                if (!empty($oldData['profile_image']) && file_exists("uploads/" . $oldData['profile_image'])) {
                    unlink("uploads/" . $oldData['profile_image']);
                }

                $stmt = $conn->prepare("UPDATE health_workers SET profile_image=? WHERE staff_id=?");
                $stmt->bind_param("si", $fileName, $staff_id);
                $stmt->execute();
                $stmt->close();

                $message = "Profile picture updated successfully!";
            }
        }
    }
}

/* =========================
   FETCH STAFF DATA
========================= */
$stmt = $conn->prepare("SELECT staff_name, email, admin_id, profile_image FROM health_workers WHERE staff_id=?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_image = "uploads/default.png";
if (!empty($staff['profile_image'])) {
    $profile_image = "uploads/" . $staff['profile_image'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Health Worker Profile</title>
    <link rel="stylesheet" href="health_profile.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="profile-toggle">
            <button onclick="toggleProfile()">☰ My Info</button>

            <div id="profileInfo" class="profile-hidden">
                <img src="<?php echo $profile_image; ?>" class="sidebar-profile-img">
                <h3><?php echo htmlspecialchars($staff_name); ?></h3>
                <p>Health Worker</p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($staff_name); ?></p>
                <p><strong>Admin ID:</strong> <?php echo htmlspecialchars($admin_id); ?></p>
            </div>
        </div>

        <ul class="menu">
            <li><a href="health_dashboard.php">Dashboard</a></li>
            <li><a href="health_profile.php">Profile</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="health_map.php">High Risk Mapping</a></li>
            <li><a href="health_schedule.php">Scheduling</a></li>
            <li><a href="bite_reports_list.php">Bite Reports</a></li>
            <li><a href="user_reminders.php">Reminder</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="topbar">
            <h1>Health Worker Profile</h1>
        </div>

        <div class="profile-card">

            <?php if ($message): ?>
                <p class="success-msg"><?php echo $message; ?></p>
            <?php endif; ?>

            <img src="<?php echo $profile_image; ?>" class="profile-img-large">

            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_image" required>
                <button type="submit" name="upload">Change Picture</button>
            </form>

            <div class="profile-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($staff['staff_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($staff['email']); ?></p>
                <p><strong>Admin ID:</strong> <?php echo htmlspecialchars($staff['admin_id']); ?></p>
            </div>

        </div>

    </div>

</div>

</body>
<script>
    function toggleProfile() {
    var profile = document.getElementById("profileInfo");
    if (profile.style.display === "block") {
        profile.style.display = "none";
    } else {
        profile.style.display = "block";
    }
}
</script>
</html>