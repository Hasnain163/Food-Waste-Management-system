<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Update settings on form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($_POST as $key => $value) {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $success = "Settings updated successfully!";
}

// Fetch current settings
$settings = [];
$result = $conn->query("SELECT * FROM system_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>System Settings - FoodResQ</title>
    <link rel="stylesheet" href="style2.css?v=<?= time(); ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>

<header>
    <div class="logo">FoodResQ</div>
    <i class="fas fa-bars fa-4x" id="bar-icon"></i>
    <nav id="menu" class="hidden">
        <ul>
            <li>
                <div class="acess_information profile-btn">
                    <a class="nav_link btn" href="profile.php">
                        <img src="admin.png" alt="Profile" class="profile-icon" /> Profile
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</header>

<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <ul>
        <li class="<?= ($current_page == 'dashboard_admin.php') ? 'active' : '' ?>"><a href="dashboard_admin.php">Dashboard</a></li>
        <li class="<?= ($current_page == 'manage_users.php') ? 'active' : '' ?>"><a href="manage_users.php">Users</a></li>
        <li class="<?= ($current_page == 'waste_logs.php') ? 'active' : '' ?>"><a href="waste_logs.php">Waste Logs</a></li>
        <li class="<?= ($current_page == 'waste_categories.php') ? 'active' : '' ?>"><a href="waste_categories.php">Waste Categories</a></li>
        <li class="<?= ($current_page == 'waste_quality.php') ? 'active' : '' ?>"><a href="waste_quality.php">Waste Quality</a></li>
        <li class="<?= ($current_page == 'collection_schedule.php') ? 'active' : '' ?>"><a href="collection_schedule.php">Collection Schedule</a></li>
        <li class="<?= ($current_page == 'locations.php') ? 'active' : '' ?>"><a href="locations.php">Locations</a></li>
        <li class="<?= ($current_page == 'system_settings.php') ? 'active' : '' ?>"><a href="system_settings.php">System Settings</a></li>
        <li class="<?= ($current_page == 'food_inventory.php') ? 'active' : '' ?>"><a href="food_inventory.php">Food Inventory</a></li>
        <li class="<?= ($current_page == 'returnable_items.php') ? 'active' : '' ?>"><a href="returnable_items.php">Returnable Items</a></li>
        <li class="<?= ($current_page == 'donations.php') ? 'active' : '' ?>"><a href="donations.php">Donations</a></li>
        <li class="<?= ($current_page == 'alerts.php') ? 'active' : '' ?>"><a href="alerts.php">Alerts</a></li>
        <li class="<?= ($current_page == 'processing_plants.php') ? 'active' : '' ?>"><a href="processing_plants.php">Processing Plants</a></li>
        <li class="<?= ($current_page == 'resource_usage.php') ? 'active' : '' ?>"><a href="resource_usage.php">Resource Usage</a></li>
        <li class="<?= ($current_page == 'ngos.php') ? 'active' : '' ?>"><a href="ngos.php">NGOs</a></li>
        <li class="<?= ($current_page == 'feedback.php') ? 'active' : '' ?>"><a href="feedback.php">Feedback</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <h1>System Settings</h1>

    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

    <form method="POST" class="form-card">
        <label for="site_name">Site Name</label>
        <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>

        <label for="contact_email">Contact Email</label>
        <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" required>

        <label for="support_phone">Support Phone</label>
        <input type="text" id="support_phone" name="support_phone" value="<?= htmlspecialchars($settings['support_phone'] ?? '') ?>">

        <button type="submit" class="btn-save">Update Settings</button>
    </form>
</div>

</body>
</html>
