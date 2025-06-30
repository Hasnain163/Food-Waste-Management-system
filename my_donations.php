<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Donor') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';
$donor_id = $_SESSION['user_id'];

// Handle deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_food_id'])) {
    $food_id_to_delete = $_POST['delete_food_id'];

    // Delete from donations table
    $delete_donation_sql = "DELETE FROM donations WHERE donor_id = ? AND food_id = ?";
    $delete_stmt = $conn->prepare($delete_donation_sql);
    $delete_stmt->bind_param("ii", $donor_id, $food_id_to_delete);
    $delete_stmt->execute();
    $delete_stmt->close();

    //  delete from food_inventory (if one-time food_id)
    $delete_inventory_sql = "DELETE FROM food_inventory WHERE food_id = ?";
    $inv_stmt = $conn->prepare($delete_inventory_sql);
    $inv_stmt->bind_param("i", $food_id_to_delete);
    $inv_stmt->execute();
    $inv_stmt->close();

    header("Location: my_donations.php");
    exit();
}

// Get donor's donations
$sql = "
    SELECT fi.food_id, fi.food_name, fi.expiry_date, fi.quantity AS inventory_quantity,
           d.food_category, d.collection_status, d.donation_date
    FROM donations AS d
    JOIN food_inventory AS fi 
    ON d.food_id = fi.food_id
    WHERE d.donor_id = ?
    ORDER BY d.donation_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Donations - FoodResQ</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <style>
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .delete-btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>

<body class="my_donation">

<!-- Header -->
<header class="donor_header">
    <div class="logo">FoodResQ</div>
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

<div class="my_container">
    <h2>🧾 My Donations</h2>

    <?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Food Item</th>
                <th>Quantity</th>
                <th>Category</th>
                <th>Expiry Date</th>
                <th>Donation Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $status_class = strtolower($row['collection_status']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['food_name']) ?></td>
                    <td><?= htmlspecialchars($row['inventory_quantity']) ?></td>
                    <td><?= htmlspecialchars($row['food_category']) ?></td>
                    <td><?= htmlspecialchars($row['expiry_date']) ?></td>
                    <td><?= htmlspecialchars($row['donation_date']) ?></td>
                    <td class="status-<?= $status_class ?>">
                        <?= htmlspecialchars($row['collection_status']) ?>
                    </td>
                    <td>
                        <form method="POST" action="my_donations.php" onsubmit="return confirm('Are you sure you want to delete this donation?');">
                            <input type="hidden" name="delete_food_id" value="<?= $row['food_id'] ?>">
                            <button type="submit" name="delete" class="delete-btn">🗑 Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center; margin-top:20px;">You haven't donated any food yet.</p>
    <?php endif; ?>

    <div style="text-align:center; margin-top: 30px;">
        <a href="dashboard_donor.php" style="text-decoration:none; color:#28a745;">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
