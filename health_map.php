<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'health') {
    header("Location: login.php");
    exit();
}

$staff_name = $_SESSION['staff_name'];
$staff_email = $_SESSION['email'];
$admin_id = $_SESSION['admin_id'];
$staff_id = $_SESSION['staff_id'];

// Get profile image
$stmt = $conn->prepare('SELECT profile_image FROM health_workers WHERE staff_id=?');
$stmt->bind_param('i', $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

$profile_image = !empty($data['profile_image']) ? "uploads/" . $data['profile_image'] : "uploads/default_profile.png";

// Fetch bite reports for map
$reports = [];
$query = "SELECT location, latitude, longitude, severity, created_at FROM bite_reports WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>High Risk Mapping - BiteCare</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css"/>
    <link rel="stylesheet" href="maph.css">
</head>
<body>
<div class="container">

    <!-- Sidebar -->
    <div class="sidebar">
         <!-- Hidden Profile Info Button -->
        <div class="profile-toggle">
            <button onclick="toggleProfile()">☰ My Info</button>

            <div id="profileInfo" class="profile-hidden">
                <img src="<?php echo $profile_image; ?>" class="sidebar-profile-img" alt="Profile Picture">

                <div class="profile-details">
                    <h3><?php echo htmlspecialchars($staff_name); ?></h3>
                    <p class="role">Health Worker</p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($staff_email); ?></p>
                    <p><strong>Admin ID:</strong> <?php echo htmlspecialchars($admin_id); ?></p>
                </div>
            </div>
        </div>

        <!-- Menu -->
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

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <h1>High Risk Mapping</h1>
        </div>

        <!-- Map -->
        <div id="map" class="map-container"></div>

        <!-- Legend -->
        <div class="map-legend">
            <h3>Risk Levels</h3>
            <div><span class="legend-color high"></span>High</div>
            <div><span class="legend-color medium"></span>Medium</div>
            <div><span class="legend-color low"></span>Low</div>
        </div>

    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script>
    const map = L.map('map').setView([10.6760, 122.9446], 12); // Bacolod City

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    const reports = <?php echo json_encode($reports); ?>;

    const markers = L.markerClusterGroup();

    reports.forEach(report => {
        let color = 'green';
        if (report.severity === 'High') color = 'red';
        else if (report.severity === 'Medium') color = 'orange';
        else if (report.severity === 'Low') color = 'green';

        const marker = L.circleMarker([report.latitude, report.longitude], {
            radius: 8,
            fillColor: color,
            color: '#000',
            weight: 1,
            opacity: 1,
            fillOpacity: 0.8
        });

        marker.bindPopup(`
            <b>Location:</b> ${report.location}<br>
            <b>Risk Level:</b> ${report.severity}<br>
            <b>Date:</b> ${report.created_at}
        `);

        markers.addLayer(marker);
    });

    map.addLayer(markers);

    function toggleProfile() {
        const profile = document.getElementById("profileInfo");
        profile.style.display = profile.style.display === "block" ? "none" : "block";
    }
</script>
</body>
</html>