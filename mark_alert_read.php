<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alert_id'])) {
    $alert_id = intval($_POST['alert_id']);
    $stmt = $conn->prepare("UPDATE alerts SET status = 'read' WHERE alert_id = ?");
    $stmt->bind_param("i", $alert_id);
    $stmt->execute();
}
header("Location: alerts.php");
exit();
