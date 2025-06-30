<?php
session_start();
include 'db_connect.php';

// Restrict to Admins only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

// Get schedule ID from URL
if (isset($_GET['id'])) {
    $schedule_id = $_GET['id'];

    // Delete the schedule from the collection_schedule table
    $stmt = $conn->prepare("DELETE FROM collection_schedule WHERE schedule_id = ?");
    $stmt->bind_param("i", $schedule_id);

    if ($stmt->execute()) {
        header("Location: collection_schedule.php");  // Redirect back to the collection schedule list
        exit();
    } else {
        echo "Error deleting schedule: " . $stmt->error;
    }
} else {
    echo "Invalid schedule ID.";
}
?>
