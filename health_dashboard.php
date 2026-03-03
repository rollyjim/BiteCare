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

$stmt = $conn->prepare('SELECT profile_image FROM health_workers WHERE staff_id=?');
$stmt->bind_param('i', $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt -> close();

$profile_image = ! empty($data['profile_image']) ? "uploads/" .  $data['profile_image'] :"uploads/default_profile.png";

// Example statistics (you can connect real queries later)
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_reports = $conn->query("SELECT COUNT(*) as total FROM bite_reports")->fetch_assoc()['total'];
$total_vaccinated = $conn->query("SELECT COUNT(*) as total FROM bite_reports WHERE status='Vaccinated'")->fetch_assoc()['total'];

// USERS PER MONTH
$usersData = $conn->query("
    SELECT MONTH(created_at) as month, COUNT(*) as total
    FROM users
    GROUP BY MONTH(created_at)
");

$users = array_fill(1, 12, 0);
while($row = $usersData->fetch_assoc()){
    $users[$row['month']] = $row['total'];
}

// REPORTS PER MONTH
$reportsData = $conn->query("
    SELECT MONTH(created_at) as month, COUNT(*) as total
    FROM bite_reports
    GROUP BY MONTH(created_at)
");

$reports = array_fill(1, 12, 0);
while($row = $reportsData->fetch_assoc()){
    $reports[$row['month']] = $row['total'];
}

// PENDING PER MONTH
$pendingData = $conn->query("
    SELECT MONTH(created_at) as month, COUNT(*) as total
    FROM bite_reports
    WHERE status='Pending'
    GROUP BY MONTH(created_at)
");

$pending = array_fill(1, 12, 0);
while($row = $pendingData->fetch_assoc()){
    $pending[$row['month']] = $row['total'];
}

// HIGH RISK PER MONTH
$highRiskData = $conn->query("
    SELECT MONTH(created_at) as month, COUNT(*) as total
    FROM bite_reports
    GROUP BY MONTH(created_at)
");

$highRisk = array_fill(1, 12, 0);
while($row = $highRiskData->fetch_assoc()){
    $highRisk[$row['month']] = $row['total'];
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Health Dashboard</title>
    <link rel="stylesheet" href="health_dashboard.css">
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

    <div class="main">

        <div class="topbar">
            <h1>Health Worker Dashboard</h1>
        </div>
        <br>
        <div class="charts">

            <div class="chart-box">
                <h3>Total Users Per Month</h3>
                <canvas id="usersChart"></canvas>
            </div>

            <div class="chart-box">
                <h3>Total Bite Reports Per Month</h3>
                <canvas id="reportsChart"></canvas>
            </div>

            <div class="chart-box">
                <h3>Pending Reports Per Month</h3>
                <canvas id="pendingChart"></canvas>
            </div>

            <div class="chart-box">
                <h3>High Risk Cases Per Month</h3>
                <canvas id="highRiskChart"></canvas>
            </div>

        </div>

    </div>
</div>

</body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const months = [
'Jan','Feb','Mar','Apr','May','Jun',
'Jul','Aug','Sep','Oct','Nov','Dec'
];

// USERS CHART
new Chart(document.getElementById('usersChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Users',
            data: <?php echo json_encode(array_values($users)); ?>,
            borderWidth: 2,
            fill: false
        }]
    }
});

// REPORTS CHART
new Chart(document.getElementById('reportsChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Reports',
            data: <?php echo json_encode(array_values($reports)); ?>,
            borderWidth: 1
        }]
    }
});

// PENDING CHART
new Chart(document.getElementById('pendingChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Pending',
            data: <?php echo json_encode(array_values($pending)); ?>,
            borderWidth: 1
        }]
    }
});

// HIGH RISK CHART
new Chart(document.getElementById('highRiskChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'High Risk',
            data: <?php echo json_encode(array_values($highRisk)); ?>,
            borderWidth: 2,
            fill: false
        }]
    }
});
function toggleProfile() {
    const profile = document.getElementById("profileInfo");
    if (profile.style.display === "block") {
        profile.style.display = "none";
    } else {
        profile.style.display = "block";
    }
}

</script>
</html>