<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'NGO') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$ngo_id = $_SESSION['user_id'];
$locations = $conn->query("SELECT * FROM locations WHERE user_id = '$ngo_id'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Locations - FoodResQ</title>
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

    <!-- Sidebar - All Features Included -->
    <div class="sidebar">
        <ul>
            <li class="active"><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="collection_schedule_ngo.php"><i class="fas fa-calendar"></i> Collection Schedule</a></li>
            <li><a href="locations_ngo.php"><i class="fas fa-map-marker"></i> Locations</a></li>
            <li><a href="food_inventory_ngo.php"><i class="fas fa-utensils"></i> Food Inventory</a></li>
            <li><a href="donations_ngo.php"><i class="fas fa-gift"></i> Donations</a></li>
            <li><a href="alerts_ngo.php"><i class="fas fa-bell"></i> Alerts</a></li>
            <li><a href="feedback_ngo.php"><i class="fas fa-comment"></i> Feedback</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Locations Content -->
    <div class="main-content">
        <div class="dashboard-title">
            <h1>Locations</h1>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Address</th>
                        <th>Coordinates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $locations->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= $row['latitude'] ?>, <?= $row['longitude'] ?></td>
                        <td>
                            <a href="https://maps.google.com/?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>" target="_blank" class="btn-action btn-view">
                                <i class="fas fa-map-marker-alt"></i> View Map
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>