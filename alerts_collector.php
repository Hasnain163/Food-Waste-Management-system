<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Collector') {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$name_query->bind_param("i", $user_id);
$name_query->execute();
$name_result = $name_query->get_result();
$name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['name'] : 'Collector';

$query = $conn->prepare("SELECT message, alert_type, alert_date FROM alerts WHERE user_id = ? ORDER BY alert_date DESC");
$query->bind_param("i", $user_id);
$query->execute();
$alerts = $query->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donor Alerts</title>
    <link rel="stylesheet" href="features.css">
</head>
<body>

<header>
    <div class="logo">FoodResQ</div>
    <div class="profile-btn">
        <span><?= htmlspecialchars($name) ?> (Donor)</span>
    </div>
</header>

<div class="sidebar">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li class="active"><a href="#">Alerts</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-title">
        <h1>Alert Notifications</h1>
        <p>Stay informed with your latest alerts.</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Alert Type</th>
                <th>Message</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($alerts->num_rows > 0): ?>
                <?php while ($row = $alerts->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['alert_type']) ?></td>
                        <td><?= htmlspecialchars($row['message']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($row['alert_date'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">No alerts available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
