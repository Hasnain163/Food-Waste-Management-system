<?php
session_start();
include 'db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Restrict to logged-in users
if (!isset($_SESSION['user_type'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Admin access: Show all schedules, otherwise show only the current user's schedules
if ($user_type === 'Admin') {
    $query = $conn->query("SELECT cs.*, u.name as user_name 
                           FROM collection_schedule cs 
                           JOIN users u ON cs.user_id = u.user_id 
                           ORDER BY cs.collection_date DESC");
} else {
    // Non-Admin users: Show only their own schedules
    $query = $conn->prepare("SELECT cs.*, u.name as user_name 
                             FROM collection_schedule cs 
                             JOIN users u ON cs.user_id = u.user_id 
                             WHERE cs.user_id = ? 
                             ORDER BY cs.collection_date DESC");
    $query->bind_param("i", $user_id); // Bind the current user's ID
    $query->execute();
    $result = $query->get_result();
}

// Fetch schedules into an associative array
$schedules = $result->fetch_all(MYSQLI_ASSOC);

// Fetch the current user's name (used in the welcome message)
$user_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_name = $user_result->fetch_assoc()['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Collection Schedule - FoodResQ</title>
    <link rel="stylesheet" href="features.css?v=<?= time(); ?>">
</head>
<body>

    <!-- Navigation Bar -->
    <header>
        <div class="logo">FoodResQ</div>
        <i class="fas fa-bars fa-4x" id="bar-icon"></i>
        <nav id="menu" class="hidden">
            <ul>
                <li>
                    <div class="acess_information profile-btn">
                        <a class="nav_link btn" href="profile.php">
                            <img src="admin.png" alt="Profile" class="profile-icon"> Profile
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul>
            <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="collection_schedule.php"><i class="fas fa-calendar"></i> Collection Schedule</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-title">
            <h1>Collection Schedules</h1>
            <p>Welcome, <?= htmlspecialchars($user_name) ?></p>
        </div>

        <!-- If the user is an Admin, provide an option to add a new schedule -->
        <?php if ($user_type === 'Admin'): ?>
        <div class="action-buttons">
            <a href="add_schedule.php" class="btn-submit">Add New Schedule</a>
        </div>
        <?php endif; ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Collection Date</th>
                    <th>Time Slot</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($schedules)): ?>
                    <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?= $schedule['schedule_id'] ?></td>
                        <td><?= htmlspecialchars($schedule['user_name']) ?></td>
                        <td><?= $schedule['collection_date'] ?></td>
                        <td><?= $schedule['time_slot'] ?></td>
                        <td><?= $schedule['status'] ?></td>
                        <td>
                            <?php if ($user_type === 'Admin'): ?>
                            <a href="edit_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn-action btn-edit">Edit</a>
                            <a href="delete_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php else: ?>
                            <!-- Non-Admin users can only view their schedules (no edit/delete options) -->
                            <a href="view_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn-action btn-view">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No schedules available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
