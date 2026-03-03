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

/* =====================
   HANDLE EDIT
===================== */
if (isset($_POST['update_user'])) {
    $id = intval($_POST['user_id']);
    $name = $_POST['name'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
    $stmt->bind_param("ssi", $name, $email, $id);
    $stmt->execute();
    $stmt->close();
}

/* =====================
   HANDLE ACTIVATE
===================== */
if (isset($_GET['activate'])) {
    $id = intval($_GET['activate']);

    $stmt = $conn->prepare("UPDATE users SET status='Active' WHERE user_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}

/* =====================
   HANDLE DEACTIVATE
===================== */
if (isset($_GET['deactivate'])) {
    $id = intval($_GET['deactivate']);

    $stmt = $conn->prepare("UPDATE users SET status='Inactive' WHERE user_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}

/* =====================
   HANDLE DELETE
===================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}

/* =====================
   SEARCH + FILTER + PAGINATION
===================== */

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : "All";

$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
$types = "";

/* FILTER CONDITION */
if ($filter == "Active") {
    $where .= " AND status='Active'";
} elseif ($filter == "Inactive") {
    $where .= " AND status='Inactive'";
}

/* SEARCH CONDITION */
if (!empty($search)) {
    $where .= " AND (full_name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%'))";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

/* COUNT QUERY */
$countQuery = "SELECT COUNT(*) as total FROM users $where";
$countStmt = $conn->prepare($countQuery);

if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalUsers / $limit);

/* FETCH USERS */
$query = "SELECT * FROM users $where LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if (!empty($types)) {
    $paramsWithLimit = $params;   // copy original params
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt->bind_param($types . "ii", ...$paramsWithLimit);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="health_manageuser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        <ul class="menu">
            <li><a href="health_dashboard.php">Dashboard</a></li>
            <li><a href="health_profile.php">Profile</a></li>
            <li><a href="manage_users.php" class="active">Manage Users</a></li>
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
            <h1>Manage Users</h1>
        </div>

        <div class="top-controls">

            <!-- Search -->
            <form method="GET" class="search-box">
                <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                <div class="search-input-wrapper"> 
                    <i class = "fa-solid fa-magnifying-glasssearch-icon"></i>
                    <input type="text"
                        name="search"
                        placeholder="Search name or email..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </div>
            </form>

            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="?filter=All&search=<?php echo urlencode($search); ?>"
                    class="<?php echo ($filter=='All')?'active-filter':''; ?>">
                    All
                </a>

                <a href="?filter=Active&search=<?php echo urlencode($search); ?>"
                class="<?php echo ($filter=='Active')?'active-filter':''; ?>">
                    Active
                </a>

                <a href="?filter=Inactive&search=<?php echo urlencode($search); ?>"
                    class="<?php echo ($filter=='Inactive')?'active-filter':''; ?>">
                    Inactive
                </a>
            </div>

        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="users-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Status</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                <tbody>

                            <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <form method="POST">
                                <td class="id-col">
                                    <?php echo $row['user_id']; ?>
                                </td>

                                <td>
                                    <input type="text" name="name"
                                        value="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        class="edit-input">
                                </td>

                                <td>
                                    <input type="email" name="email"
                                        value="<?php echo htmlspecialchars($row['email']); ?>"
                                        class="edit-input">
                                </td>

                                <td>
                                    <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>

                                <td class="actions-col">
                                    <input type="hidden" name="user_id"
                                        value="<?php echo $row['user_id']; ?>">

                                    <button type="submit" name="update_user"
                                        class="btn save-btn">
                                        Save
                                    </button>

                                    <?php if ($row['status'] == 'Active'): ?>
                                        <a href="?deactivate=<?php echo $row['user_id']; ?>"
                                            class="btn deactivate-btn">
                                            Deactivate
                                        </a>
                                    <?php else: ?>
                                        <a href="?activate=<?php echo $row['user_id']; ?>"
                                            class="btn activate-btn">
                                            Activate
                                        </a>
                                    <?php endif; ?>

                                    <a href="?delete=<?php echo $row['user_id']; ?>"
                                        class="btn delete-btn"
                                        onclick="return confirm('Delete permanently?')">
                                        Delete
                                    </a>
                                </td>
                            </form>
                        </tr>
                            <?php endwhile; ?>

                </tbody>
            </table>
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>

    </div>
</div> 

</body>
<script>
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