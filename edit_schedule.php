<?php
session_start();
include 'db_connect.php';

// Restrict to Admins only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

// Get the schedule ID from the URL
if (isset($_GET['id'])) {
    $schedule_id = $_GET['id'];

    // Fetch the current schedule data
    $stmt = $conn->prepare("SELECT * FROM collection_schedule WHERE schedule_id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();

    if (!$schedule) {
        echo "Schedule not found!";
        exit();
    }

    // Fetch users (excluding Admins) for dropdown
    $users_query = $conn->query("SELECT user_id, name FROM users WHERE user_type != 'Admin'");
    $users = $users_query->fetch_all(MYSQLI_ASSOC);

    // Handle form submission (update schedule)
    if (isset($_POST['update_schedule'])) {
        $user_id = $_POST['user_id'];
        $collection_date = $_POST['collection_date'];
        $time_slot = $_POST['time_slot'];
        $status = $_POST['status'];

        // Update the schedule in the database
        $update_stmt = $conn->prepare("UPDATE collection_schedule SET user_id = ?, collection_date = ?, time_slot = ?, status = ? WHERE schedule_id = ?");
        $update_stmt->bind_param("isssi", $user_id, $collection_date, $time_slot, $status, $schedule_id);

        if ($update_stmt->execute()) {
            header("Location: collection_schedule.php");  // Redirect after update
            exit();
        } else {
            echo "Error updating schedule: " . $update_stmt->error;
        }
    }
} else {
    echo "Invalid schedule ID.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Schedule - FoodResQ</title>
    <link rel="stylesheet" href="features.css?v=<?= time(); ?>">
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
            <h1>Edit Collection Schedule</h1>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="user_id">Select User</label>
                <select name="user_id" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['user_id'] ?>" <?= $user['user_id'] == $schedule['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="collection_date">Collection Date</label>
                <input type="date" name="collection_date" value="<?= $schedule['collection_date'] ?>" required>
            </div>

            <div class="form-group">
                <label for="time_slot">Time Slot</label>
                <input type="text" name="time_slot" value="<?= $schedule['time_slot'] ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" required>
                    <option value="Pending" <?= $schedule['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Completed" <?= $schedule['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $schedule['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <button type="submit" name="update_schedule" class="btn-submit">Update Schedule</button>
        </form>
    </div>

</body>
</html>
