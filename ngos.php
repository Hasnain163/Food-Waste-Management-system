<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name FROM users WHERE user_id = '$admin_id' AND user_type = 'Admin'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data ? $admin_data['name'] : 'Admin';

// Fetch NGOs with user information
$ngos_query = $conn->query("
    SELECT 
        u.user_id AS ngo_id,
        u.name,
        u.email,
        u.user_type,
        n.contact_person,
        n.phone,
        l.address
    FROM users u
    LEFT JOIN ngos n ON u.user_id = n.ngo_id
    LEFT JOIN locations l ON n.location_id = l.location_id
    WHERE u.user_type = 'NGO'
    ORDER BY u.user_id DESC
");


if (!$ngos_query) {
    die("Database error: " . $conn->error);
}

$ngos = $ngos_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>NGO Partners - FoodResQ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <li class="active"><a href="ngos.php"><i class="fas fa-hands-helping"></i> NGOs</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment"></i> Feedback</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-title">
            <h1>NGO Partners</h1>
            <p>Welcome, <?= htmlspecialchars($admin_name) ?></p>
        </div>

        <!-- Success message when NGO is added -->
        <?php if (isset($_SESSION['ngo_added'])): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['ngo_added'] ?>
                <?php unset($_SESSION['ngo_added']); ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="add_user.php" class="btn-submit">Add New NGO</a>
        </div>

        <?php if (count($ngos) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ngos as $ngo): ?>
                    <tr>
                        <td><?= $ngo['ngo_id'] ?></td>
                        <td><?= htmlspecialchars($ngo['name']) ?></td>
                        <td><?= htmlspecialchars($ngo['email']) ?></td>
                        <td><?= $ngo['contact_person'] ? htmlspecialchars($ngo['contact_person']) : 'Not set' ?></td>
                        <td><?= $ngo['phone'] ? htmlspecialchars($ngo['phone']) : 'Not set' ?></td>
                        <td><?= $ngo['address'] ? htmlspecialchars($ngo['address']) : 'Not set' ?></td>
                        <td>
                            <a href="edit_ngo.php?id=<?= $ngo['ngo_id'] ?>" class="btn-action btn-edit">Edit</a>
                            <a href="delete_user.php?id=<?= $ngo['ngo_id'] ?>" class="btn-action btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this NGO?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="message info">
                <i class="fas fa-info-circle"></i>
                No NGOs found in the system.
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('bar-icon').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
            document.getElementById('menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>