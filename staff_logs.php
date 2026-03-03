<head>
    <meta charset="UTF-8">
    <title> Super Admin</title>
    <link rel=" stylesheet" href="superadmin_style.css">
</head>

<?php
session_start();
include 'db.php';

// Only superadmin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "superadmin") {
    header("Location: index.php");
    exit();
}

// SEARCH
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Fetch health worker login/logout logs
if ($search != "") {
    $stmt = $conn->prepare("SELECT * FROM health_workers_logs 
        WHERE staff_name LIKE CONCAT('%', ?, '%') 
        OR email LIKE CONCAT('%', ?, '%') 
        OR admin_id LIKE CONCAT('%', ?, '%')
        ORDER BY login_time DESC");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $logs = $stmt->get_result();
} else {
    $logs = $conn->query("SELECT * FROM health_workers_logs ORDER BY login_time DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Health Worker Logs</title>
    <link rel="stylesheet" href="staff_logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-profile">
            <div class="profile-icon"><i class="fas fa-user-shield"></i></div>
            <h3><?= htmlspecialchars($_SESSION['email']) ?></h3>
            <p>Super Admin</p>
        </div>

        <nav class="menu">
            <a href="superadmin_dashboard.php">Dashboard</a>
            <a href="registration_logs.php">Registration Logs</a>
            <a href="staff_logs.php" class="active">Staff Logs</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">

        <header class="top-header">
            <h1>Health Worker Login/Logout Logs</h1>
        </header>

        <!-- SEARCH -->
        <div class="card">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search by name, email, or admin ID" value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- LOGS TABLE -->
        <div class="card">
            <button id="toggleTableBtn" class="btn-toggle">Hide Table</button>

            <div class="table-wrapper" id="logsTable">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Staff Name</th>
                        <th>Email</th>
                        <th>Admin ID</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                    </tr>

                    <?php while($row = $logs->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['staff_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['admin_id']) ?></td>
                        <td><?= htmlspecialchars($row['login_time']) ?></td>
                        <td><?= htmlspecialchars($row['logout_time']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

    </main>

</div>

</body>
<script>
const toggleBtn = document.getElementById('toggleTableBtn');
const logsTable = document.getElementById('logsTable');

toggleBtn.addEventListener('click', () => {
    if (logsTable.style.display === 'none') {
        logsTable.style.display = 'block';
        toggleBtn.textContent = 'Hide Table';
    } else {
        logsTable.style.display = 'none';
        toggleBtn.textContent = 'Show Table';
    }
});
</script>
</html>