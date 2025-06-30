<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "foodresq";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Basic input validation
    if (empty($email) || empty($password)) {
        echo "All fields are required!";
        exit();
    }

    // Prepare query to find user by email
    $stmt = $conn->prepare("SELECT user_id, name, password, user_type FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $name, $hashed_password, $user_type);

    // Fetch the user record
    if ($stmt->fetch()) {
        if (password_verify($password, $hashed_password)) {
            // ✅ Login successful
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_type'] = $user_type;

            // Redirect based on user_type
            switch ($user_type) {
                case 'Admin':
                    header("Location: dashboard_admin.php");
                    break;
                case 'NGO':
                    header("Location: dashboard_ngo.php");
                    break;
                case 'Donor':
                    header("Location: dashboard_donor.php");
                    break;
                case 'Collector':
                    header("Location: dashboard_collector.php");
                    break;
                case 'PlantOperator':
                    header("Location: dashboard_plantoperator.php");
                    break;
                default:
                    echo "Unknown user type!";
            }
            exit();
        } else {
            echo "Incorrect password!";
        }
    } else {
        echo "No account found with this email!";
    }

    $stmt->close();
}

$conn->close();
?>
