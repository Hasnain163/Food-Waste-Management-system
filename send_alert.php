<?php
function sendAlert($conn, $user_id, $user_type, $message) {
    $stmt = $conn->prepare("INSERT INTO alerts (user_id, user_type, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $user_type, $message);
    $stmt->execute();
    $stmt->close();
}
?>
