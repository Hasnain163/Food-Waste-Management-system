<?php
session_start();
include 'db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Restrict to Admins only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get admin name
$name_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$name_query->bind_param("i", $admin_id);
$name_query->execute();
$name_result = $name_query->get_result();
$admin_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['name'] : 'Admin';

// Handle Deletion of Alerts
if (isset($_GET['delete'])) {
    $alert_id = $_GET['delete'];
    if ($conn->query("DELETE FROM alerts WHERE alert_id = $alert_id")) {
        header("Location: alerts.php");
        exit();
    } else {
        echo "<div class='message error'>Error deleting alert: " . $conn->error . "</div>";
    }
}

// Handle Alert Creation
if (isset($_POST['create_alert'])) {
    $user_id = $_POST['user_id'];
    $alert_type = $_POST['alert_type'];
    $message = trim($_POST['message']);

    if (!empty($user_id) && !empty($alert_type) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO alerts (user_id, alert_type, message, alert_date) VALUES (?, ?, ?, NOW())");

        if ($stmt === false) {
            die("Error preparing query: " . $conn->error);
        }

        $stmt->bind_param("iss", $user_id, $alert_type, $message);

        if ($stmt->execute()) {
            header("Location: alerts.php");
            exit();
        } else {
            echo "<div class='message error'>Failed to send alert: " . $stmt->error . "</div>";
        }
    } else {
        echo "<div class='message error'>All fields are required.</div>";
    }
}

// Fetch All Alerts for Admin
$query = $conn->query("
    SELECT alerts.*, users.name AS user_name, users.user_type 
    FROM alerts 
    LEFT JOIN users ON alerts.user_id = users.user_id 
    ORDER BY alerts.alert_date DESC
");

// Fetch All Users (excluding Admins) for Alert Creation
$users = $conn->query("SELECT user_id, name, user_type FROM users WHERE user_type != 'Admin'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Alerts</title>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="features.css">
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

    <!-- Sidebar - All Features Included -->
    <div class="sidebar">
        <ul>
            <li class="active"><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="waste_logs.php"><i class="fas fa-trash"></i> Waste Logs</a></li>
            <li><a href="waste_categories.php"><i class="fas fa-list"></i> Waste Categories</a></li>
            <li><a href="waste_quality.php"><i class="fas fa-check-circle"></i> Waste Quality</a></li>
            <li><a href="collection_schedule.php"><i class="fas fa-calendar"></i> Collection Schedule</a></li>
            <li><a href="locations.php"><i class="fas fa-map-marker"></i> Locations</a></li>
            <li><a href="food_inventory.php"><i class="fas fa-utensils"></i> Food Inventory</a></li>
            <li><a href="returnable_items.php"><i class="fas fa-recycle"></i> Returnable Items</a></li>
            <li><a href="donations.php"><i class="fas fa-gift"></i> Donations</a></li>
            <li><a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a></li>
            <li><a href="processing_plants.php"><i class="fas fa-industry"></i> Processing Plants</a></li>
            <li><a href="resource_usage.php"><i class="fas fa-chart-pie"></i> Resource Usage</a></li>
            <li><a href="ngos.php"><i class="fas fa-hands-helping"></i> NGOs</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment"></i> Feedback</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

<div class="main-content">
    <div class="page-title">
        <h1>Admin Alerts Dashboard</h1>
        <p>Send and delete alerts for users.</p>
    </div>

    <!-- Alert Sending Form -->
    <div class="form-container">
        <h2>Send New Alert</h2>
        <form method="POST">
            <div class="form-group">
                <label for="user_id">Select User</label>
                <select name="user_id" required>
                    <option value="">-- Select --</option>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <option value="<?= $u['user_id'] ?>">
                            <?= htmlspecialchars($u['name']) ?> (<?= $u['user_type'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="alert_type">Alert Type</label>
                <select name="alert_type" required>
                    <option value="Expiry">Expiry</option>
                    <option value="MissedPickup">Missed Pickup</option>
                    <option value="DonationOpportunity">Donation Opportunity</option>
                </select>
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea name="message" required></textarea>
            </div>

            <button type="submit" name="create_alert" class="btn-submit">Send Alert</button>
        </form>
    </div>

    <!-- All Alerts Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Type</th>
                <th>Message</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($query->num_rows > 0): ?>
                <?php while ($row = $query->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['user_type']) ?></td>
                        <td><?= htmlspecialchars($row['alert_type']) ?></td>
                        <td><?= htmlspecialchars($row['message']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($row['alert_date'])) ?></td>
                        <td>
                            <!-- Delete Button -->
                            <a href="?delete=<?= $row['alert_id'] ?>" onclick="return confirm('Delete this alert?')" class="btn-action btn-delete">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No alerts available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
