<?php
session_start();
include 'db.php';

// AUTO 1-DAY-BEFORE REMINDER SYSTEM
$tomorrow = date("Y-m-d", strtotime("+1 day"));

$reminderQuery = "
    SELECT * FROM appointments
    WHERE vaccination_status = 'Ready'
    AND reminder_sent = 0
    AND appointment_date = '$tomorrow'
";

$reminderResult = $conn->query($reminderQuery);

if ($reminderResult && $reminderResult->num_rows > 0) {
    while ($row = $reminderResult->fetch_assoc()) {

        $email = $row['email'];
        $name = $row['full_name'];
        $user_id = $row['user_id'];

        $subject = "Vaccination Reminder - BiteCare";
        $message = "Hello $name,\n\nReminder: Your vaccination is scheduled tomorrow.\n\nThank you,\nBiteCare Health Team";
        $headers = "From: bitecare@gmail.com";

        // 🔔 INSERT NOTIFICATION FOR USER
        $notifMsg = "Reminder: Your vaccination is scheduled tomorrow.";
        $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmtNotif->bind_param("is", $user_id, $notifMsg);
        $stmtNotif->execute();
        $stmtNotif->close();

        // Mark reminder sent
        $update = "UPDATE appointments SET reminder_sent = 1 WHERE id = ".$row['id'];
        $conn->query($update);
    }
}

$reminderResult = $conn->query($reminderQuery);

if ($reminderResult->num_rows > 0) {
    while ($row = $reminderResult->fetch_assoc()) {

        $email = $row['email'];
        $name = $row['full_name'];

        $subject = "Vaccination Reminder - BiteCare";
        $message = "Hello $name,\n\nYour vaccination is now ready. Please visit the health center as scheduled.\n\nThank you,\nBiteCare Health Team";
        $headers = "From: bitecare@gmail.com";

        // Send Email
        mail($email, $subject, $message, $headers);

        // Mark as reminder sent
        $update = "UPDATE appointments SET reminder_sent = 1 WHERE id = ".$row['id'];
        $conn->query($update);
    }
}

// Check if user is logged in as health worker
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'health') {
    header("Location: login.php");
    exit();
}

$staff_name = $_SESSION['staff_name'];
$staff_email = $_SESSION['email'];
$staff_id = $_SESSION['staff_id'];
$admin_id = $_SESSION['admin_id'];

// Fetch profile image
$stmt = $conn->prepare('SELECT profile_image FROM health_workers WHERE staff_id=?');
$stmt->bind_param('i', $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

$profile_image = !empty($data['profile_image']) ? "uploads/" . $data['profile_image'] : "uploads/default_profile.png";

/* =====================
   SEARCH + DATE FILTER + PAGINATION
===================== */
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : "";
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : "";

$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
$types = "";

// Search by name or email
if (!empty($search)) {
    $where .= " AND (full_name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%'))";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// Filter by date range
if (!empty($start_date)) {
    $where .= " AND appointment_date >= ?";
    $params[] = $start_date;
    $types .= "s";
}
if (!empty($end_date)) {
    $where .= " AND appointment_date <= ?";
    $params[] = $end_date;
    $types .= "s";
}

/* COUNT TOTAL */
$countQuery = "SELECT COUNT(*) as total FROM appointments $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalAppointments = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalAppointments / $limit);

/* FETCH APPOINTMENTS */
$query = "SELECT * FROM appointments $where ORDER BY appointment_date ASC, appointment_time ASC LIMIT ? OFFSET ?";
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
<title>Health Schedule</title>
<link rel="stylesheet" href="health_schedule.css">
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


    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <h1>Health Worker Schedule</h1>
        </div>

        <!-- Search & Date Filter -->
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>

        <div class="table-container">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Appointment Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['appointment_date'])); ?></td>
                            <td><?php echo date("h:i A", strtotime($row['appointment_time'])); ?></td>
                            <td><?php echo htmlspecialchars($row['reason']); ?></td>

                            <td>
                                <form method="POST" action="update_status.php">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                                    <select name="status" onchange="this.form.submit()">
                                        <option value="Pending" <?php if($row['vaccination_status']=="Pending") echo "selected"; ?>>Pending</option>
                                        <option value="Ready" <?php if($row['vaccination_status']=="Ready") echo "selected"; ?>>Ready</option>
                                        <option value="Completed" <?php if($row['vaccination_status']=="Completed") echo "selected"; ?>>Completed</option>
                                    </select>
                                </form>
                            </td>

                            <td>
                                <?php if($row['vaccination_status'] != "Completed"): ?>
                                <form method="POST" action="mark_completed.php">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" style="background:green;color:white;border:none;padding:5px 10px;border-radius:5px;">
                                        Mark Completed
                                    </button>
                                </form>
                                <?php else: ?>
                                <span style="color:green;font-weight:bold;">Done</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                        <tr>
                            <td colspan="7">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"
                   class="<?php echo ($i==$page)?'active-page':''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>

    </div>
</div>

<script>
function toggleProfile() {
    const profile = document.getElementById("profileInfo");
    profile.style.display = (profile.style.display === "block") ? "none" : "block";
}
</script>
</body>
</html>