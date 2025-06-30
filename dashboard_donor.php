<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Donor') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$donor_id = $_SESSION['user_id'];
$donor_query = $conn->query("SELECT name FROM users WHERE user_id = '$donor_id'");
$donor_data = $donor_query->fetch_assoc();
$donor_name = $donor_data ? $donor_data['name'] : 'Donor';

// Count stats only related to Donor
$my_donations = $conn->query("SELECT COUNT(*) as total FROM donations WHERE donor_id = '$donor_id'")->fetch_assoc()['total'];
$my_pickups = $conn->query("SELECT COUNT(*) as total FROM collection_schedule WHERE user_id = '$donor_id'")->fetch_assoc()['total'];

$my_alerts = $conn->query("SELECT COUNT(*) as total FROM alerts WHERE user_id = '$donor_id'")->fetch_assoc()['total'];
$my_feedbacks = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE user_id = '$donor_id'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donor Dashboard - FoodResQ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Your CSS -->
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>

<body class="dashboard_body">

<!-- Header -->
<header class="donor_header">
    <div class="logo">FoodResQ</div>
    <i class="fas fa-bars fa-2x" id="bar-icon"></i>
    <nav id="menu" class="hidden">
        <ul>
            <li>
                <div class="profile-btn">
                    <a class="nav_link btn" href="profile.php">
                        <img src="admin.png" alt="Profile" class="profile-icon"> Profile
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</header>

<!-- Sidebar -->
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <ul>
        <li class="<?= ($current_page == 'dashboard_donor.php') ? 'active' : '' ?>"><a href="dashboard_donor.php">Dashboard</a></li>
        <li class="<?= ($current_page == 'donor_donations.php') ? 'active' : '' ?>"><a href="donor_donations.php">Donate Food</a></li>
        <li class="<?= ($current_page == 'my_donations.php') ? 'active' : '' ?>"><a href="my_donations.php">My Donations</a></li>
        <li class="<?= ($current_page == 'donor_schedule_pickup.php') ? 'active' : '' ?>"><a href="donor_schedule_pickup.php">Pickup Schedule</a></li>
        <li class="<?= ($current_page == 'alerts.php') ? 'active' : '' ?>"><a href="alerts.php">Alerts</a></li>
        <li class="<?= ($current_page == 'feedback_donor.php') ? 'active' : '' ?>"><a href="feedback_donor.php">Feedback</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-title">
        <h1>Welcome, <?= htmlspecialchars($donor_name) ?>!</h1>
        <p>Your Dashboard</p>
    </div>

    <div class="cards">
        <a href="my_donations.php" class="card">
            <h2><?= $my_donations ?></h2>
            <p>My Donations</p>
            <i class="fas fa-gift card-icon"></i>
        </a>

        <a href="my_schedule.php" class="card">
            <h2><?= $my_pickups ?></h2>
            <p>Pickup Schedule</p>
            <i class="fas fa-calendar-check card-icon"></i>
        </a>

        <a href="alerts.php" class="card">
            <h2><?= $my_alerts ?></h2>
            <p>My Alerts</p>
            <i class="fas fa-bell card-icon"></i>
        </a>

        <a href="feedback_donor.php" class="card">
            <h2><?= $my_feedbacks ?></h2>
            <p>My Feedback</p>
            <i class="fas fa-comment card-icon"></i>
        </a>
    </div>
</div>

</body>
</html>
