<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Donor') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$donor_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $food_name = $_POST['food_name'];                  // from <input name="food_name">
    $category = $_POST['category'];                    // from <select name="category">
    $quantity = $_POST['quantity'] . ' ' . $_POST['unit']; // merge value and unit
    $reason = $_POST['reason'];
    $disposal = $_POST['disposal'];
    $expiry_date = $_POST['expiry_date'];
    $notes = $_POST['notes'];
    $donation_date = date('Y-m-d');

    // Step 1: Insert into food_inventory
    $stmt1 = $conn->prepare("INSERT INTO food_inventory (user_id, food_name, quantity, expiry_date) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("isss", $donor_id, $food_name, $quantity, $expiry_date);

    if ($stmt1->execute()) {
        $food_id = $conn->insert_id;

        // Step 2: Insert into donations
        $collection_status = "Pending";
        $stmt2 = $conn->prepare("INSERT INTO donations (donor_id, food_id, quantity_kg, collection_status, donation_date,food_category) VALUES (?, ?, ?, ?, ?,?)");
        $stmt2->bind_param("iissss", $donor_id, $food_id, $_POST['quantity'], $collection_status, $donation_date, $category);

        if ($stmt2->execute()) {
            // Redirect to donor dashboard with success message
            $_SESSION['success'] = "✅ Food donation submitted successfully!";
            header("Location: dashboard_donor.php");
            exit();
        } else {
            $message = "❌ Error inserting into donations: " . $stmt2->error;
        }
        $stmt2->close();
    } else {
        $message = "❌ Error inserting into food_inventory: " . $stmt1->error;
    }

    $stmt1->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Donate Food - Food Waste Management</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>

<body>

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

    <main class="donation">
        <div class="container card_donor">
            <h2 class="donation-heading">Donate Food</h2>
            <form class="wasteForm" action="donor_donations.php" method="POST">

                <label for="item">Item Name</label>
                <input type="text" id="item" name="food_name" placeholder="e.g., Tomatoes" required>

                <label for="category">Category</label>
                <select name="category" id="category" required>
                    <option value="">Select a category</option>
                    <option value="Packaged Foods">Packaged Foods</option>
                    <option value="Grains & Cereals">Grains & Cereals</option>
                    <option value="Dairy Products">Dairy Products</option>
                    <option value="Fresh Produce">Fresh Produce</option>
                    <option value="Cooked Meals">Cooked Meals</option>
                    <option value="Bakery Items">Bakery Items</option>
                    <option value="Meat & Poultry">Meat & Poultry</option>
                    <option value="Seafood">Seafood</option>
                    <option value="Beverages">Beverages</option>
                    <option value="Frozen Food">Frozen Food</option>
                    <option value="Condiments & Spices">Condiments & Spices</option>
                    <option value="Ready-to-Eat Items">Ready-to-Eat Items</option>
                    <option value="Others">Others</option>
                </select>

                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" step="0.1">

                <label for="unit">Unit</label>
                <select name="unit" id="unit">
                    <option value="kg">kg</option>
                    <option value="g">g</option>
                    <option value="L">L</option>
                    <option value="mL">mL</option>
                    <option value="pcs">pcs</option>
                </select>

                <!-- <label for="reason">Reason for Waste</label>
                <select name="reason" id="reason" required>
                    <option value="">Select a reason</option>
                    <option value="Expired">Expired</option>
                    <option value="Spoiled">Spoiled</option>
                    <option value="Over Production">Over Production</option>
                    <option value="Damaged">Damaged</option>
                    <option value="Other">Other</option>
                </select>

                <label for="disposal">Disposal Method</label>
                <select name="disposal" id="disposal" required>
                    <option value="">Select a method</option>
                    <option value="Donation">Donation</option>
                    <option value="Trash">Trash</option>
                    <option value="Compost">Compost</option>
                    <option value="Animal Feed">Animal Feed</option>
                    <option value="Other">Other</option>
                </select> -->

                <label for="date">Expiry Date</label>
                <input type="date" id="date" name="expiry_date" required>

                <!-- <label for="notes">Notes (Optional)</label>
                <textarea name="notes" id="notes" placeholder="Any additional information...."></textarea> -->

                <div class="buttons">
                    <button type="submit" class="add-btn">Submit Donation</button>
                    <button type="reset" class="clear-btn">Clear</button>
                </div>
            </form>
        </div>
    </main>


    <!-- Footer -->
    <!-- <footer>
        <p>&copy; 2025 FoodResQ. All Rights Reserved.</p>
    </footer> -->

    <script src="index.js"></script>
</body>

</html>