<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Donor') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';
$donor_id = $_SESSION['user_id'];

// Approximate join: match donation date to pickup date, donor_id to donations, collector_id to collection_schedule
$sql = "
SELECT 
    fi.food_name,
    d.donation_date,
    cs.collection_date,
    cs.time_slot,
    cs.status AS pickup_status,
    u.name AS collector_name
FROM donations d
JOIN food_inventory fi ON d.food_id = fi.food_id
JOIN collection_schedule cs ON cs.collection_date = d.donation_date
JOIN users u ON cs.user_id = u.user_id AND u.user_type = 'Collector'
WHERE d.donor_id = ?
ORDER BY cs.collection_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Pickup Schedule</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <style>
        body { background-color: #f8f9fa; font-family: Arial; }
        .container {
            max-width: 900px;
            margin: 40px auto;
             margin-top: 80px;
            background: #fff;
            padding:180px 130px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
           
        }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 25px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        tr:hover { background-color: #f1f1f1; }
        .status-pending { color: orange; font-weight: bold; }
        .status-completed { color: green; font-weight: bold; }
        .status-cancelled { color: red; font-weight: bold; }
    </style>
    
</head>
<body>

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

<div class="container">
    <h2>📋 My Pickup Schedule</h2>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Food Item</th>
                    <th>Donation Date</th>
                    <th>Pickup Date</th>
                    <th>Time Slot</th>
                    <th>Collector</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $status_class = strtolower($row['pickup_status']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['food_name']) ?></td>
                    <td><?= htmlspecialchars($row['donation_date']) ?></td>
                    <td><?= htmlspecialchars($row['collection_date']) ?></td>
                    <td><?= htmlspecialchars($row['time_slot']) ?></td>
                    <td><?= htmlspecialchars($row['collector_name']) ?></td>
                    <td class="status-<?= $status_class ?>">
                        <?= htmlspecialchars($row['pickup_status']) ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;">You have no scheduled pickups yet.</p>
    <?php endif; ?>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
