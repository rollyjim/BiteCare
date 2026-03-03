<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "user") {
    header("Location: index.php");
    exit();
}

// Get logged-in user info
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT user_id, full_name, email, profile_pic FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) die("Error: User not found.");

// Handle bite report submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_submit'])) {
    $full_name = $_POST['full_name'];
    $contact = $_POST['contact'];
    $bite_date = $_POST['bite_date'];
    $location = $_POST['location'];
    $animal_type = $_POST['animal_type'];
    $description = $_POST['description'];

    $photo = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo = 'uploads/' . time() . '_' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    $stmt = $conn->prepare("INSERT INTO bite_reports (users_id, full_name, contact, bite_date, location, animal_type, description, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user['user_id'], $full_name, $contact, $bite_date, $location, $animal_type, $description, $photo);
    if ($stmt->execute()) $message = "Report submitted successfully!";
    else $message = "Error: " . $stmt->error;
    $stmt->close();
}

// Cancel report
if (isset($_GET['cancel']) && intval($_GET['cancel']) > 0) {
    $report_id = intval($_GET['cancel']);
    $stmt = $conn->prepare("DELETE FROM bite_reports WHERE id=? AND users_id=?");
    $stmt->bind_param("ii", $report_id, $user['user_id']);
    $stmt->execute();
    $stmt->close();
    $message = "Report canceled successfully!";
}

// Fetch all reports
$stmt = $conn->prepare("SELECT * FROM bite_reports WHERE users_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$reports = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Animal Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="report_bite.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="profile-section">
            <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'uploads/default_profile.png') ?>" alt="Profile" class="sidebar-profile-img">
            <h3><?= htmlspecialchars($user['full_name'])?></h3>
            <p class="role">User</p>
            <p><?= htmlspecialchars($user['email']) ?></p>
        </div>
        <ul class="menu">
            <li><a href="users_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="report_bite.php" class="active"><i class="fas fa-paw"></i> Report Bite</a></li>
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
            <h1>Report Animal Bite</h1>
        </div>

        <?php if($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <!-- REPORT FORM -->
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

                <label>Contact Number</label>
                <input type="text" name="contact" placeholder="Enter contact" required>

                <label>Date of Bite</label>
                <input type="date" name="bite_date" required>

                <label>Location</label>
                <input type="text" name="location" placeholder="Enter location" required>

                <label>Animal Type</label>
                <select name="animal_type" required>
                    <option value="">Select Animal</option>
                    <option value="Dog">Dog</option>
                    <option value="Cat">Cat</option>
                    <option value="Other">Other</option>
                </select>

                <label>Description</label>
                <textarea name="description" placeholder="Describe the incident" required></textarea>

                <label>Upload Photo (optional)</label>
                <input type="file" name="photo" accept="image/*">

                <button type="submit" name="report_submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Report</button>
            </form>
        </div>

        <!-- USER REPORTS -->
        <h3>Your Reports</h3>
        <?php if ($reports->num_rows > 0): ?>
            <?php while($r = $reports->fetch_assoc()): ?>
                <div class="report-card">
                    <p><strong>Date:</strong> <?= htmlspecialchars($r['bite_date']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($r['location']) ?></p>
                    <p><strong>Animal:</strong> <?= htmlspecialchars($r['animal_type']) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($r['description']) ?></p>
                    <?php if($r['photo']): ?>
                        <p><img src="<?= htmlspecialchars($r['photo']) ?>" alt="Bite photo" class="report-photo"></p>
                    <?php endif; ?>
                    <p><strong>Status:</strong> <?= htmlspecialchars($r['status']) ?></p>
                    <a href="?cancel=<?= $r['id'] ?>" class="cancel-btn" onclick="return confirm('Cancel this report?')"><i class="fas fa-times-circle"></i> Cancel</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reports submitted yet.</p>
        <?php endif; ?>

    </div>
</div>

</body>
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
</html>