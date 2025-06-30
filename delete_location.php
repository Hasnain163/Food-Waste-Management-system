<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Unauthorized");
}

if (!isset($_GET['id'])) {
    die("Location ID missing.");
}

$location_id = intval($_GET['id']);
$conn->query("DELETE FROM locations WHERE location_id = $location_id");

header("Location: locations.php?success=1");
exit();
?>
