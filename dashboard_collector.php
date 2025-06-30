<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Collector') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name FROM users WHERE user_id = '$admin_id' AND user_type = 'Admin'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data ? $admin_data['name'] : 'Admin';


$total_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type != 'Admin'")->fetch_assoc()['total'];
$donorCount = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'Donor'")->fetch_assoc()['total'];
$ngoCount = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'NGO'")->fetch_assoc()['total'];
$collectorCount = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'Collector'")->fetch_assoc()['total'];
$plantOperatorCount = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'PlantOperator'")->fetch_assoc()['total'];

$wasteLogs = $conn->query("SELECT COUNT(*) as total FROM waste_log")->fetch_assoc()['total'];
$foodItems = $conn->query("SELECT COUNT(*) as total FROM food_inventory")->fetch_assoc()['total'];
$donations = $conn->query("SELECT COUNT(*) as total FROM donations")->fetch_assoc()['total'];
$alerts = $conn->query("SELECT COUNT(*) as total FROM alerts")->fetch_assoc()['total'];
$feedbacks = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
$schedules = $conn->query("SELECT COUNT(*) as total FROM collection_schedule")->fetch_assoc()['total'];
$returnables = $conn->query("SELECT COUNT(*) as total FROM returnable_items")->fetch_assoc()['total'];
$plants = $conn->query("SELECT COUNT(*) as total FROM processing_plants")->fetch_assoc()['total'];
$resources = $conn->query("SELECT COUNT(*) as total FROM resource_usage")->fetch_assoc()['total'];
$locations = $conn->query("SELECT COUNT(*) as total FROM locations")->fetch_assoc()['total'];
$categories = $conn->query("SELECT COUNT(*) as total FROM waste_categories")->fetch_assoc()['total'];
$qualities = $conn->query("SELECT COUNT(*) as total FROM waste_quality")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - FoodResQ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="dashboard.css?v=<?= time(); ?>">
</head>
<body>
    <!-- Navigation Bar -->
    <header>
        <div class="logo">FoodResQ</div>
        <i class="fas fa-bars fa-4x" id="bar-icon"></i>
        <nav  id="menu" class="hidden">
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
    <?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <ul>
        <li class="<?= ($current_page == 'dashboard_admin.php') ? 'active' : '' ?>"><a href="dashboard_admin.php">Dashboard</a></li>
        <li class="<?= ($current_page == 'collection_schedule.php') ? 'active' : '' ?>"><a href="collection_schedule.php">Collection Schedule</a></li>
        <li class="<?= ($current_page == 'locations_allusers.php') ? 'active' : '' ?>"><a href="locations_allusers.php">Locations</a></li>
        <li class="<?= ($current_page == 'alerts_collector.php') ? 'active' : '' ?>"><a href="alerts_collector.php">Alerts</a></li>
        <li class="<?= ($current_page == 'ngos.php') ? 'active' : '' ?>"><a href="ngos.php">NGOs</a></li>
        <li class="<?= ($current_page == 'feedback.php') ? 'active' : '' ?>"><a href="feedback_collector.php">Feedback</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

    <!-- Main content -->
    <div class="main-content">
        <div class="dashboard-title">
            <h1>Collector's Dashboard</h1> <br>
        </div>

        <div class="cards">
            <a href="feedback_collector.php">
                <div class="card">
                    <h2><?= $feedbacks ?></h2>
                    <p>Feedbacks</p>
                    <i class="fas fa-comment card-icon"></i>
                </div>
            </a>

            <a href="collection_schedule.php">
                <div class="card">
                    <h2><?= $schedules ?></h2>
                    <p>Collection Schedule</p>
                    <i class="fas fa-calendar card-icon"></i>
                </div>
            </a>

            <a href="locations.php">
                <div class="card">
                    <h2><?= $locations ?></h2>
                    <p>Locations</p>
                    <i class="fas fa-map-marker card-icon"></i>
                </div>
            </a>

            <a href="alerts_collector.php">
                <div class="card">
                    <h2><?= $alerts ?></h2>
                    <p>Alerts</p>
                    <i class="fas fa-bell card-icon"></i>
                </div>
            </a>
            
            <a href="ngos.php">
                <div class="card">
                    <h2><?= $ngoCount ?></h2>
                    <p>NGOs</p>
                    <i class="fas fa-hands-helping card-icon"></i>
                </div>
            </a>
        </div>
    </div>
</body>
</html>
