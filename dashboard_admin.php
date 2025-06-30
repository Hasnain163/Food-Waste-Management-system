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

// Get all counts
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
        <nav id="menu" class="hidden">
            <ul>
                <li>
                    <div class="acess_information profile-btn">
                        <a class="nav_link btn" href="admin_profile.php">
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

    <!-- Main content - All Cards Included -->
    <div class="main-content">
        <div class="dashboard-title">
            <h1>Admin's Dashboard</h1>
            <p>Welcome, <?= $admin_name ?></p>
        </div>

        <!-- First Row of Cards -->
        <div class="cards">
            <a href="manage_users.php">
                <div class="card">
                    <h2><?= $donorCount ?></h2>
                    <p>Donors</p>
                    <i class="fas fa-hand-holding-heart card-icon"></i>
                </div>
            </a>
        </div>

        <div class="cards">
            <a href="manage_users.php">
                <div class="card">
                    <h2><?= $collectorCount ?></h2>
                    <p>Collectors</p>
                    <i class="fas fa-truck card-icon"></i>
                </div>
            </a>

            <a href="ngos.php">
                <div class="card">
                    <h2><?= $ngoCount ?></h2>
                    <p>NGOs</p>
                    <i class="fas fa-hands-helping card-icon"></i>
                </div>
            </a>

            <a href="manage_users.php">
                <div class="card">
                    <h2><?= $plantOperatorCount ?></h2>
                    <p>Plant Operators</p>
                    <i class="fas fa-cogs card-icon"></i>
                </div>
            </a>
        </div>

        <div class="cards">
            <a href="waste_logs.php">
                <div class="card">
                    <h2><?= $wasteLogs ?></h2>
                    <p>Waste Logs</p>
                    <i class="fas fa-trash card-icon"></i>
                </div>
            </a>

            <a href="waste_categories.php">
                <div class="card">
                    <h2><?= $categories ?></h2>
                    <p>Waste Categories</p>
                    <i class="fas fa-list card-icon"></i>
                </div>
            </a>

            <a href="waste_quality.php">
                <div class="card">
                    <h2><?= $qualities ?></h2>
                    <p>Waste Quality</p>
                    <i class="fas fa-chart-line card-icon"></i>
                </div>
            </a>

            <a href="food_inventory.php">
                <div class="card">
                    <h2><?= $foodItems ?></h2>
                    <p>Food Inventory</p>
                    <i class="fas fa-box-open card-icon"></i>
                </div>
            </a>

            <a href="donations.php">
                <div class="card">
                    <h2><?= $donations ?></h2>
                    <p>Donations</p>
                    <i class="fas fa-gift card-icon"></i>
                </div>
            </a>
        </div>

        <div class="cards">
            <a href="alerts.php">
                <div class="card">
                    <h2><?= $alerts ?></h2>
                    <p>Alerts</p>
                    <i class="fas fa-bell card-icon"></i>
                </div>
            </a>

            <a href="feedback.php">
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

            <a href="returnable_items.php">
                <div class="card">
                    <h2><?= $returnables ?></h2>
                    <p>Returnable Items</p>
                    <i class="fas fa-recycle card-icon"></i>
                </div>
            </a>

            <a href="processing_plants.php">
                <div class="card">
                    <h2><?= $plants ?></h2>
                    <p>Processing Plants</p>
                    <i class="fas fa-industry card-icon"></i>
                </div>
            </a>

            <a href="resource_usage.php">
                <div class="card">
                    <h2><?= $resources ?></h2>
                    <p>Resource Usage</p>
                    <i class="fas fa-chart-pie card-icon"></i>
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