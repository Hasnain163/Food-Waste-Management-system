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
$foodItems = $conn->query("SELECT * FROM food_inventory WHERE user_id = '$ngo_id' ORDER BY expiry_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Food Inventory - FoodResQ</title>
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


    <!-- Food Inventory Content -->
    <div class="main-content">
        <div class="dashboard-title">
            <h1>Food Inventory</h1>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Food Name</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $foodItems->fetch_assoc()): 
                        $expiry_class = (strtotime($row['expiry_date']) < strtotime('+3 days')) ? 'expiring-soon' : '';
                    ?>
                    <tr class="<?= $expiry_class ?>">
                        <td><?= htmlspecialchars($row['food_name']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= date('M d, Y', strtotime($row['expiry_date'])) ?></td>
                        <td>
                            <?= (strtotime($row['expiry_date']) < time()) ? 'Expired' : 'Good' ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('bar-icon').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });
    </script>
</body>
</html>