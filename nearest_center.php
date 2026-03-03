<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "user") {
    header("Location: index.php");
    exit();
}

// Fetch logged-in user info
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

// Search function
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $stmt = $conn->prepare("SELECT * FROM centers WHERE center_name LIKE CONCAT('%', ?, '%') OR address LIKE CONCAT('%', ?, '%')");
    $stmt->bind_param("ss", $search, $search);
} else {
    $stmt = $conn->prepare("SELECT * FROM centers");
}
$stmt->execute();
$result = $stmt->get_result();

// Store results in array
$centers = [];
while ($row = $result->fetch_assoc()) {
    $centers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nearest Center | BiteCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- CSS -->
<link rel="stylesheet" href="nearest_center.css">

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

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
            <li><a href="nearest_center.php" class="active"><i class="fas fa-map-marker-alt"></i> Nearest Center</a></li>
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
            <span class="hamburger" id="hamburger"><i class="fas fa-bars"></i></span>
            <h2>Nearest Centers</h2>
        </div>

        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Search center..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- MAP -->
        <div id="map"></div>

        <!-- CENTER LIST -->
        <div class="center-list">
            <?php if(count($centers) > 0): ?>
                <?php foreach($centers as $row): ?>
                    <div class="center-card">
                        <h3>
                            <?php
                                if ($row['type'] === 'Hospital') echo '<i class="fas fa-hospital"></i>';
                                elseif ($row['type'] === 'Clinic') echo '<i class="fas fa-clinic-medical"></i>';
                                else echo '<i class="fas fa-map-marker-alt"></i>';
                            ?>
                            <?= htmlspecialchars($row['center_name']) ?>
                        </h3>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['address']) ?></p>
                        <?php if(!empty($row['contact'])): ?>
                            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($row['contact']) ?></p>
                        <?php endif; ?>
                        <?php if(!empty($row['latitude']) && !empty($row['longitude'])): ?>
                            <a target="_blank"
                               href="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>"
                               class="map-btn">
                                <i class="fas fa-location-arrow"></i> View on Google Map
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center;">No centers found.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    // Leaflet Map
    var map = L.map('map').setView([10.6762, 122.9476], 13); // Bacolod city

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Custom icons
    var hospitalIcon = L.icon({
        iconUrl: 'icons/hospital.png',
        iconSize: [30, 30]
    });
    var clinicIcon = L.icon({
        iconUrl: 'icons/clinic.png',
        iconSize: [30, 30]
    });
    var centerIcon = L.icon({
        iconUrl: 'icons/center.png',
        iconSize: [30, 30]
    });

    <?php foreach($centers as $row): 
        if(!empty($row['latitude']) && !empty($row['longitude'])):
            $icon = 'centerIcon';
            if($row['type'] === 'Hospital') $icon = 'hospitalIcon';
            elseif($row['type'] === 'Clinic') $icon = 'clinicIcon';
    ?>
        var marker = L.marker([<?= $row['latitude'] ?>, <?= $row['longitude'] ?>], {icon: <?= $icon ?>}).addTo(map);
        marker.bindPopup(`
            <strong><?= htmlspecialchars($row['center_name']) ?></strong><br>
            <?= htmlspecialchars($row['address']) ?><br>
            <?= htmlspecialchars($row['contact'] ?? '') ?><br>
            Type: <?= htmlspecialchars($row['type']) ?>
        `);
    <?php endif; endforeach; ?>

    // Hamburger toggle
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');

    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    document.addEventListener('click', function(event) {
        if(!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
            sidebar.classList.remove('open');
        }
    });
</script>

</body>
</html>