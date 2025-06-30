<?php
session_start();
include 'db_connect.php'; // Ensure the correct database connection is included

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Restrict to Admins only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Fetch users (excluding Admins) for the dropdown
$users_query = $conn->query("SELECT user_id, name FROM users WHERE user_type != 'Admin'");
$users = $users_query->fetch_all(MYSQLI_ASSOC);

// Handle form submission for new schedule
if (isset($_POST['create_schedule'])) {
    $user_id = $_POST['user_id'];
    $collection_date = $_POST['collection_date'];
    $time_slot = $_POST['time_slot'];
    $status = $_POST['status'];

    // Validate the inputs before inserting into the database
    if (!empty($user_id) && !empty($collection_date) && !empty($time_slot) && !empty($status)) {
        $stmt = $conn->prepare("INSERT INTO collection_schedule (user_id, collection_date, time_slot, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $collection_date, $time_slot, $status);

        // Execute the query and check if insertion is successful
        if ($stmt->execute()) {
            header("Location: collection_schedule.php"); // Redirect to collection schedule page after success
            exit();
        } else {
            echo "<div class='message error'>Failed to add schedule: " . $stmt->error . "</div>";
        }
    } else {
        echo "<div class='message error'>All fields are required.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Collection Schedule</title>
    <link rel="stylesheet" href="features.css?v=<?= time(); ?>"> <!-- Use versioning to avoid caching -->
</head>
<body>

    <!-- Navigation Bar -->
    <header>
        <div class="logo">FoodResQ</div>
    </header>

    <div class="sidebar">
        <ul>
            <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="collection_schedule.php"><i class="fas fa-calendar"></i> Collection Schedule</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-title">
            <h1>Add New Collection Schedule</h1>
        </div>

        <!-- New Collection Form -->
        <form method="POST">
            <div class="form-group">
                <label for="user_id">Select User</label>
                <select name="user_id" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="collection_date">Collection Date</label>
                <input type="date" name="collection_date" required>
            </div>

            <div class="form-group">
                <label for="time_slot">Time Slot</label>
                <input type="text" name="time_slot" placeholder="e.g., 9:00 AM - 12:00 PM" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" required>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <button type="submit" name="create_schedule" class="btn-submit">Create Schedule</button>
        </form>
    </div>

</body>
</html>
