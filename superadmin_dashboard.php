<head>
    <meta charset="UTF-8">
    <title> Super Admin</title>
    <link rel=" stylesheet" href="superadmin_style.css">
</head>

<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "superadmin") {
    header("Location: index.php");
    exit();
}

$message = "";
$admin_id = 0;
$admin_name = "";

// Get superadmin info
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id, full_name FROM super_admins WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $admin_id = $row['id'];
    $admin_name = $row['full_name'];
}
$stmt->close();

/* =======================
   HANDLE CONVERT
======================= */
if (isset($_GET['convert_to'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $type = $_GET['convert_to'];

    if ($type === "health") {
        // Convert User -> Health Worker
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $stmt = $conn->prepare("INSERT INTO health_workers (admin_id, staff_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $admin_id, $user['full_name'], $user['email'], $user['password']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $message = "User converted to Health Worker.";
        }
    } elseif ($type === "user") {
        // Convert Health Worker -> User
        $stmt = $conn->prepare("SELECT * FROM health_workers WHERE staff_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $health = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($health) {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $health['staff_name'], $health['email'], $health['password']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM health_workers WHERE staff_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $message = "Health Worker converted to User.";
        }
    }
}
/* ======================
   DELETE
====================== */
if (isset($_GET['delete']) && isset($_GET['id'])) {

    $id = intval($_GET['id']);
    $type = $_GET['delete'];

    if ($type === "user") {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    if ($type === "health") {
        $stmt = $conn->prepare("DELETE FROM health_workers WHERE staff_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: superadmin_dashboard.php");
    exit();
}

/* =======================
   SEARCH
======================= */
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if ($search != "") {
    $stmt = $conn->prepare("SELECT * FROM users WHERE full_name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%')");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $users = $stmt->get_result();

    $stmt2 = $conn->prepare("SELECT * FROM health_workers WHERE staff_name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%')");
    $stmt2->bind_param("ss", $search, $search);
    $stmt2->execute();
    $health = $stmt2->get_result();
} else {
    $users = $conn->query("SELECT * FROM users");
    $health = $conn->query("SELECT * FROM health_workers");
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="superadmin_dashboard.css">
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
            <a href="superadmin_dashboard.php" class="active">Dashboard</a>
            <a href="registration_logs.php">Registration Logs</a>
            <a href="staff_logs.php">Staff Logs</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">

        <header class="top-header">
            <h1>Super Admin Dashboard</h1>
        </header>

        <?php if($message): ?>
            <div class="alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- SEARCH -->
        <div class="card">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search name or email" value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- USERS -->
        <div class="card">
            <h2>Users</h2>
            <button class="toggle-btn" data-target="users-table">Hide Table</button>
            <div class="table-wrapper" id="users-table">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>

                    <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td>
                            <a class="btn-edit" href="superadmin_edit.php?type=user&id=<?= $u['user_id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                            <a class="btn-convert" href="?convert_to=health&id=<?= $u['user_id'] ?>" title="Convert"><i class="fas fa-exchange-alt"></i></a>
                            <a class="btn-delete" href="?delete=user&id=<?= $u['user_id'] ?>" title="Delete"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

        <!-- HEALTH WORKERS -->
        <div class="card">
            <h2>Health Workers</h2>
            <button class="toggle-btn" data-target="health-table">Hide Table</button>
            <div class="table-wrapper" id="health-table">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Admin ID</th>
                        <th>Actions</th>
                    </tr>

                    <?php while($h = $health->fetch_assoc()): ?>
                    <tr>
                        <td><?= $h['staff_id'] ?></td>
                        <td><?= htmlspecialchars($h['staff_name']) ?></td>
                        <td><?= htmlspecialchars($h['email']) ?></td>
                        <td><?= $h['admin_id'] ?></td>
                        <td>
                            <a class="btn-edit" href="superadmin_edit.php?type=health&id=<?= $h['staff_id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                            <a class="btn-convert" href="?convert_to=user&id=<?= $h['staff_id'] ?>" title="Convert"><i class="fas fa-exchange-alt"></i></a>
                            <a class="btn-delete" href="?delete=health&id=<?= $h['staff_id'] ?>" title="Delete"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

    </main>

</div>

</body>
<script>
const toggleButtons = document.querySelectorAll('.toggle-btn');

toggleButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const tableWrapper = document.getElementById(targetId);
        if (tableWrapper.style.display === 'none') {
            tableWrapper.style.display = 'block';
            btn.textContent = 'Hide Table';
        } else {
            tableWrapper.style.display = 'none';
            btn.textContent = 'Show Table';
        }
    });
});
</script>
</html>