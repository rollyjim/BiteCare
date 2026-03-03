<?php
session_start();
include 'db.php';

// Check if user is logged in as health worker
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

/* =====================
   HANDLE STATUS CHANGE & DELETE
===================== */
if (isset($_GET['mark'])) {
    $id = intval($_GET['mark']);
    $new_status = $_GET['status'];
    
    $stmt = $conn->prepare("UPDATE bite_reports SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: bite_reports_list.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM bite_reports WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: bite_reports_list.php");
    exit();
}

/* =====================
   SEARCH + FILTER + PAGINATION
===================== */
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : "All";
$severity_filter = isset($_GET['severity_filter']) ? $_GET['severity_filter'] : "All";

$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
$types = "";

// Search by name or contact
if (!empty($search)) {
    $where .= " AND (full_name LIKE CONCAT('%', ?, '%') OR contact LIKE CONCAT('%', ?, '%'))";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// Filter by status
if ($status_filter != "All") {
    $where .= " AND status=?";
    $params[] = $status_filter;
    $types .= "s";
}

// Filter by severity
if ($severity_filter != "All") {
    $where .= " AND severity=?";
    $params[] = $severity_filter;
    $types .= "s";
}

/* COUNT TOTAL */
$countQuery = "SELECT COUNT(*) as total FROM bite_reports $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalReports = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalReports / $limit);

/* FETCH REPORTS */
$query = "SELECT * FROM bite_reports $where ORDER BY bite_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$paramsWithLimit = $params;
$paramsWithLimit[] = $limit;
$paramsWithLimit[] = $offset;

if (!empty($types)) {
    $stmt->bind_param($types . "ii", ...$paramsWithLimit);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bite Reports</title>
<link rel="stylesheet" href="bite_reports_list.css">
</head>
<body>

<div class="container">

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

    <!-- Main Content -->
    <div class="main">
        <div class="topbar"><h1>Bite Reports</h1></div>

        <!-- Search & Filters -->
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search by name/contact" value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="status_filter">
                <option <?php if($status_filter=="All") echo "selected"; ?>>All</option>
                <option <?php if($status_filter=="Pending") echo "selected"; ?>>Pending</option>
                <option <?php if($status_filter=="Treated") echo "selected"; ?>>Treated</option>
                <option <?php if($status_filter=="Closed") echo "selected"; ?>>Closed</option>
            </select>

            <select name="severity_filter">
                <option <?php if($severity_filter=="All") echo "selected"; ?>>All</option>
                <option <?php if($severity_filter=="High") echo "selected"; ?>>High</option>
                <option <?php if($severity_filter=="Medium") echo "selected"; ?>>Medium</option>
                <option <?php if($severity_filter=="Low") echo "selected"; ?>>Low</option>
            </select>

            <button type="submit">Filter</button>
        </form>

        <!-- Table -->
        <div class="table-container">
            <table class="bite-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>Contact</th>
                        <th>Bite Date</th>
                        <th>Animal Type</th>
                        <th>Location</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows>0): ?>
                        <?php while($row=$result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['bite_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['animal_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td class="severity-<?php echo $row['severity']; ?>"><?php echo $row['severity']; ?></td>
                            <td class="status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></td>
                            <td class="actions">
                                <a href="?mark=<?php echo $row['id']; ?>&status=Treated" class="mark-btn">Mark Treated</a>
                                <a href="?mark=<?php echo $row['id']; ?>&status=Closed" class="mark-btn">Mark Closed</a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this report?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9">No bite reports found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo $status_filter; ?>&severity_filter=<?php echo $severity_filter; ?>" class="<?php echo ($i==$page)?'active-page':''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>

    </div>
</div>

</body>

<script>
function toggleProfile() {
    const profile = document.getElementById("profileInfo");
    profile.style.display = (profile.style.display === "block") ? "none" : "block";
}
</script>
</html>