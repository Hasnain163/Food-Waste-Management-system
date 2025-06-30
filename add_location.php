<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $address = trim($_POST['address']);

    if (!empty($user_id) && is_numeric($latitude) && is_numeric($longitude) && !empty($address)) {
        $stmt = $conn->prepare("INSERT INTO locations (user_id, latitude, longitude, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idds", $user_id, $latitude, $longitude, $address);

        if ($stmt->execute()) {
            header("Location: locations.php?success=1");
            exit();
        } else {
            echo "Database Error: " . $stmt->error;
        }
    } else {
        echo "Missing required fields.";
    }
}
?>
