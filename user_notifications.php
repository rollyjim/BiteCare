<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

$conn->query("UPDATE notifications SET is_read=1 WHERE user_id='$user_id'");

$result = $conn->query("SELECT * FROM notifications WHERE user_id='$user_id' ORDER BY created_at DESC");
?>

<h2>Notifications</h2>

<?php while($row = $result->fetch_assoc()): ?>
    <div style="background:#f1f1f1;padding:10px;margin:10px 0;border-radius:8px;">
        <?= htmlspecialchars($row['message']) ?>
        <br>
        <small><?= $row['created_at'] ?></small>
    </div>
<?php endwhile; ?>
<li><a href="users_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>