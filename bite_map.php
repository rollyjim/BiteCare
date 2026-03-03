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

// Fetch all bite reports with location data
$bites = $conn->query("SELECT * FROM bite_reports WHERE latitude IS NOT NULL AND longitude IS NOT NULL");

// Fetch all centers with location data
$centers = $conn->query("SELECT * FROM centers WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>High-Risk Areas | BiteCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="bitemap.css">
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
            <li><a href="dss.php"><i class="fas fa-brain"></i> DSS Assessment</a></li>
            <li><a href="bite_map.php" class="active"><i class="fas fa-map"></i> High-Risk Areas</a></li>
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="topbar">
            <!-- Hamburger button (M0bile Only) -->
            <button class="menu-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
            <h2><i class="fas fa-map"></i> High-Risk Areas</h2>
            <p><?= htmlspecialchars($user['full_name']) ?></p>
        </div>

        <!-- Filter dropdown -->
        <div id="filter-controls">
            <label>Filter by type:</label>
            <select id="centerFilter">
                <option value="All">All</option>
                <option value="Clinic">Clinic</option>
                <option value="Center">Center</option>
                <option value="Hospital">Hospital</option>
            </select>
        </div>

        <!-- Map -->
        <div id="map"></div>

        <!-- Legend -->
        <div class="map-legend">
            <h3>Risk Levels</h3>
            <div><span class="legend-color high"></span>High</div>
            <div><span class="legend-color medium"></span>Medium</div>
            <div><span class="legend-color low"></span>Low</div>
        </div>

        <!-- Centers List Overlay -->
        <div id="center-list">
            <ol id="centerListOl">
                <?php while($center = $centers->fetch_assoc()): ?>
                    <li data-type="<?= htmlspecialchars($center['type'] ?? 'Center') ?>">
                        <?= htmlspecialchars($center['center_name']) ?> - <?= htmlspecialchars($center['contact'] ?? 'No Contact') ?>
                    </li>
                <?php endwhile; ?>
            </ol>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Initialize map centered on Bacolod City
var map = L.map('map').setView([10.6762, 122.9563], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

// Marker icons
var biteIcon = L.icon({ iconUrl:'icons/bite_marker.png', iconSize:[35,35], iconAnchor:[17,35], popupAnchor:[0,-30] });
var centerIcon = L.icon({ iconUrl:'icons/center_marker.png', iconSize:[35,35], iconAnchor:[17,35], popupAnchor:[0,-30] });

// Bite reports
var bites = <?php
$markers = [];
while($row = $bites->fetch_assoc()){
    $markers[] = [
        'name' => $row['full_name'],
        'status' => $row['status'],
        'lat' => $row['latitude'],
        'lng' => $row['longitude']
    ];
}
echo json_encode($markers);
?>;

bites.forEach(function(bite){
    L.marker([bite.lat, bite.lng], {icon:biteIcon}).addTo(map)
     .bindPopup("<b>"+bite.name+"</b><br>Status: "+bite.status);
});

// Centers
var centers = <?php
$markers = [];
$centers = $conn->query("SELECT * FROM centers WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
while($row = $centers->fetch_assoc()){
    $markers[] = [
        'name' => $row['center_name'],
        'lat' => $row['latitude'],
        'lng' => $row['longitude'],
        'type' => $row['type'] ?? 'Center',
        'contact' => $row['contact'] ?? 'No Contact'
    ];
}
echo json_encode($markers);
?>;

var centerMarkers = [];
centers.forEach(function(center, index){
    var marker = L.marker([center.lat, center.lng], {icon:centerIcon})
        .bindPopup("<b>"+center.name+"</b><br>Type: "+center.type+"<br>Contact: "+center.contact+"<br>Center #"+(index+1))
        .addTo(map);
    marker.centerType = center.type; // store type
    centerMarkers.push(marker);
});

// Filter dropdown
document.getElementById('centerFilter').addEventListener('change', function(){
    var filter = this.value;
    centerMarkers.forEach(function(marker){
        if(filter === 'All' || marker.centerType === filter){
            map.addLayer(marker);
        } else {
            map.removeLayer(marker);
        }
    });

    // Filter the list overlay
    document.querySelectorAll('#centerListOl li').forEach(function(li){
        li.style.display = (filter === 'All' || li.dataset.type === filter) ? 'list-item' : 'none';
    });
});

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