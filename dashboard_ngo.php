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
$ngo_query = $conn->query("SELECT name FROM users WHERE user_id = '$ngo_id' AND user_type = 'NGO'");
$ngo_data = $ngo_query->fetch_assoc();
$ngo_name = $ngo_data ? $ngo_data['name'] : 'Admin';

$foodItems = $conn->query("SELECT COUNT(*) as total FROM food_inventory")->fetch_assoc()['total'];
$donations = $conn->query("SELECT COUNT(*) as total FROM donations")->fetch_assoc()['total'];
$alerts = $conn->query("SELECT COUNT(*) as total FROM alerts")->fetch_assoc()['total'];
$feedbacks = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
$schedules = $conn->query("SELECT COUNT(*) as total FROM collection_schedule")->fetch_assoc()['total'];
$locations = $conn->query("SELECT COUNT(*) as total FROM locations")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>NGO Dashboard - FoodResQ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="dashboard.css?v=<?= time(); ?>">
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

    <!-- Main content - All Cards Included -->
    <div class="main-content">
        <div class="dashboard-title">
            <h1>NGO's Dashboard</h1>
            <p>Welcome, <?= $ngo_name ?></p>
        </div>

        <div class="cards">
            <a href="food_inventory_ngo.php">
                <div class="card">
                    <h2><?= $foodItems ?></h2>
                    <p>Food Inventory</p>
                    <i class="fas fa-box-open card-icon"></i>
                </div>
            </a>

            <a href="donations_ngo.php">
                <div class="card">
                    <h2><?= $donations ?></h2>
                    <p>Donations</p>
                    <i class="fas fa-gift card-icon"></i>
                </div>
            </a>
        </div>

        <div class="cards">
            <a href="alerts_ngo.php">
                <div class="card">
                    <h2><?= $alerts ?></h2>
                    <p>Alerts</p>
                    <i class="fas fa-bell card-icon"></i>
                </div>
            </a>

            <a href="feedback_ngo.php">
                <div class="card">
                    <h2><?= $feedbacks ?></h2>
                    <p>Feedbacks</p>
                    <i class="fas fa-comment card-icon"></i>
                </div>
            </a>
        
            <a href="collection_schedule_ngo.php">
                <div class="card">
                    <h2><?= $schedules ?></h2>
                    <p>Collection Schedule</p>
                    <i class="fas fa-calendar card-icon"></i>
                </div>
            </a>

            <a href="locations_ngo.php">
                <div class="card">
                    <h2><?= $locations ?></h2>
                    <p>Locations</p>
                    <i class="fas fa-map-marker card-icon"></i>
                </div>
            </a>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('bar-icon').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
            document.getElementById('menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>