<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = intval($_GET['id']);

// Prevent deleting Admin users
$user_check = $conn->query("SELECT user_type FROM users WHERE user_id = $user_id");
if ($user_check->num_rows === 0) {
    die("User not found.");
}

$user = $user_check->fetch_assoc();
if ($user['user_type'] === 'Admin') {
    die("You cannot delete an Admin account.");
}

// First: delete from related tables (order matters due to FK constraints)
$conn->query("DELETE FROM alerts WHERE user_id = $user_id");
$conn->query("DELETE FROM feedback_ratings WHERE rater_id = $user_id");
$conn->query("DELETE FROM feedback WHERE user_id = $user_id");
$conn->query("DELETE FROM donations WHERE donor_id = $user_id OR receiver_id = $user_id");
$conn->query("DELETE FROM returnable_items WHERE user_id = $user_id");
$conn->query("DELETE FROM food_inventory WHERE user_id = $user_id");
$conn->query("DELETE FROM collection_schedule WHERE user_id = $user_id");
$conn->query("DELETE FROM locations WHERE user_id = $user_id");

// Then: delete from ngos table if applicable
$conn->query("DELETE FROM ngos WHERE ngo_id = $user_id");

// Finally: delete from users
$delete_user = $conn->query("DELETE FROM users WHERE user_id = $user_id");

if ($delete_user) {
    header("Location: ngos.php?msg=deleted");
    exit();
} else {
    echo "Error deleting user: " . $conn->error;
}
?>
