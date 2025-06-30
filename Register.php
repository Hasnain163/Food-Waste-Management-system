<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "foodresq";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // === 1. Check for empty fields ===
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        echo "All fields are required!";
        exit();
    }

    // === 2. Validate name format ===
    if (!preg_match("/^[a-zA-Z\s\-]+$/", $name)) {
        echo "Name can only contain letters, spaces, and hyphens!";
        exit();
    }

    // === 3. Validate email ===
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format!";
        exit();
    }

    // === 4. Password length check ===
    if (strlen($password) < 7) {
        echo "Password must be at least 6 characters long!";
        exit();
    }

    // === 5. Confirm password match ===
    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit();
    }

    // === 6. Check if email already exists ===
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_type= ?");
    $check_stmt->bind_param("ss", $email, $role);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "A user with this email already exists!";
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    // === 7. Hash password securely ===
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // === 8. Insert user into database ===
    $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $role);


    if ($insert_stmt->execute()) {
       echo "<script>
    alert('Registration successful! Please login to continue.');
    window.location.href = 'login.html';
     </script>";
         exit();

    } else {
        echo "Registration Error: " . $insert_stmt->error;
    }

    $insert_stmt->close();
}

$conn->close();
?>
