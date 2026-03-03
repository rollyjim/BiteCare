<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "superadmin") {
    header("Location: index.php");
    exit();
}

$message = "";

// Handle approve/decline
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // Fetch the pending registration
    $stmt = $conn->prepare("SELECT * FROM registration_logs WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $reg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($reg) {
        if ($action === 'approve') {
            // Move to proper table
            if ($reg['role'] === 'user') {
                $stmt = $conn->prepare("INSERT INTO users 
                    (full_name, age, gender, birthday, medical_history, phone, email, facebook, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "sisssssss",
                    $reg['full_name'],
                    $reg['age'],
                    $reg['gender'],
                    $reg['birthday'],
                    $reg['medical_history'],
                    $reg['phone'],
                    $reg['email'],
                    $reg['facebook'],
                    $reg['password']
                );
                $stmt->execute();
                $stmt->close();

            } elseif ($reg['role'] === 'health') {
                $stmt = $conn->prepare("INSERT INTO health_workers 
                    (staff_name, email, password, admin_id)
                    VALUES (?, ?, ?, ?)");
                $stmt->bind_param(
                    "sssi",
                    $reg['staff_name'],
                    $reg['email'],
                    $reg['password'],
                    $reg['admin_id']
                );
                $stmt->execute();
                $stmt->close();

            } elseif ($reg['role'] === 'superadmin') {
                $stmt = $conn->prepare("INSERT INTO super_admins 
                    (full_name, email, password)
                    VALUES (?, ?, ?)");
                $stmt->bind_param(
                    "sss",
                    $reg['full_name'],
                    $reg['email'],
                    $reg['password']
                );
                $stmt->execute();
                $stmt->close();
            }

            // Delete from registration logs
            $conn->query("DELETE FROM registration_logs WHERE id=$id");
            $message = "Registration approved.";

        } elseif ($action === 'decline') {
            $conn->query("DELETE FROM registration_logs WHERE id=$id");
            $message = "Registration declined.";
        }
    }
}

// Handle search
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM registration_logs 
        WHERE full_name LIKE CONCAT('%', ?, '%') 
        OR staff_name LIKE CONCAT('%', ?, '%') 
        OR email LIKE CONCAT('%', ?, '%') 
        OR role LIKE CONCAT('%', ?, '%') 
        ORDER BY date_registered DESC");
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $logs = $stmt->get_result();
    $stmt->close();
} else {
    $logs = $conn->query("SELECT * FROM registration_logs ORDER BY date_registered DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration Logs</title>
    <link rel="stylesheet" href="registration_logs.css">
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
            <a href="registration_logs.php" class="active">Registration Logs</a>
            <a href="staff_logs.php">Staff Logs</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">

        <header class="top-header">
            <h1>Registration Logs</h1>
        </header>

        <?php if($message): ?>
            <div class="alert-success"><?= $message ?></div>
        <?php endif; ?>

        <!-- SEARCH FORM -->
        <div class="card search-card">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search name, email or role"
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <button id="toggleTableBtn" class="toggle-btn">Hide Table</button>

        <!-- TABLE -->
        <div class="card table-card">
            <div class="table-wrapper">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Admin ID</th>
                        <th>Date Registered</th>
                        <th>Actions</th>
                    </tr>

                    <?php while($r = $logs->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['full_name'] ?: $r['staff_name']) ?></td>
                        <td><?= htmlspecialchars($r['role']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['admin_id'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['date_registered']) ?></td>
                        <td>
                            <a href="?action=approve&id=<?= $r['id'] ?>" class="btn-approve" onclick="return confirm('Approve this registration?')">Approve</a>
                            <a href="?action=decline&id=<?= $r['id'] ?>" class="btn-decline" onclick="return confirm('Decline this registration?')">Decline</a>
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
const toggleBtn = document.getElementById('toggleTableBtn');
const tableCard = document.querySelector('.table-card');

toggleBtn.addEventListener('click', () => {
    if (tableCard.style.display === 'none') {
        tableCard.style.display = 'block';
        toggleBtn.textContent = 'Hide Table';
    } else {
        tableCard.style.display = 'none';
        toggleBtn.textContent = 'Show Table';
    }
});
</script>
</html>