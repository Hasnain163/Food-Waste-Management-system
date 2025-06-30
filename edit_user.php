<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name FROM users WHERE user_id = '$admin_id' AND user_type = 'Admin'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data ? $admin_data['name'] : 'Admin';

// Get user ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = $conn->real_escape_string($_GET['id']);

// Initialize variables
$name = $email = $user_type = '';
$errors = [];

// Fetch user data
$user_query = $conn->query("SELECT * FROM users WHERE user_id = '$user_id'");
if ($user_query->num_rows === 0) {
    header("Location: manage_users.php");
    exit();
}

$user = $user_query->fetch_assoc();
$name = $user['name'];
$email = $user['email'];
$user_type = $user['user_type'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'];
    
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email already exists (excluding current user)
        $check_email = $conn->query("SELECT * FROM users WHERE email = '$email' AND user_id != '$user_id'");
        if ($check_email->num_rows > 0) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    if (!empty($password) && strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if (empty($user_type)) {
        $errors['user_type'] = 'User type is required';
    }
    
    // If no errors, update user
    if (empty($errors)) {
        $update_query = "UPDATE users SET 
                        name = '$name', 
                        email = '$email', 
                        user_type = '$user_type'";
        
        // Only update password if provided
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query .= ", password = '$hashed_password'";
        }
        
        $update_query .= " WHERE user_id = '$user_id'";
        
        if ($conn->query($update_query)) {
            $_SESSION['message'] = 'User updated successfully';
            header("Location: manage_users.php");
            exit();
        } else {
            $errors['database'] = 'Error updating user: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User - FoodResQ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="features.css?v=<?= time(); ?>">
</head>
<body>
    <!-- Navigation Bar -->
    <header>
        <div class="logo">FoodResQ</div>
        <i class="fas fa-bars fa-4x" id="bar-icon"></i>
        <nav id="menu" class="hidden">
            <ul>
                <li>
                    <div class="acess_information profile-btn">
                        <a class="nav_link btn" href="profile.php">
                            <img src="admin.png" alt="Profile" class="profile-icon"> Profile
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Sidebar - All Features Included -->
    <div class="sidebar">
        <ul>
            <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="active"><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="waste_logs.php"><i class="fas fa-trash"></i> Waste Logs</a></li>
            <li><a href="waste_categories.php"><i class="fas fa-list"></i> Waste Categories</a></li>
            <li><a href="waste_quality.php"><i class="fas fa-check-circle"></i> Waste Quality</a></li>
            <li><a href="collection_schedule.php"><i class="fas fa-calendar"></i> Collection Schedule</a></li>
            <li><a href="locations.php"><i class="fas fa-map-marker"></i> Locations</a></li>
            <li><a href="food_inventory.php"><i class="fas fa-utensils"></i> Food Inventory</a></li>
            <li><a href="returnable_items.php"><i class="fas fa-recycle"></i> Returnable Items</a></li>
            <li><a href="donations.php"><i class="fas fa-gift"></i> Donations</a></li>
            <li><a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a></li>
            <li><a href="processing_plants.php"><i class="fas fa-industry"></i> Processing Plants</a></li>
            <li><a href="resource_usage.php"><i class="fas fa-chart-pie"></i> Resource Usage</a></li>
            <li><a href="ngos.php"><i class="fas fa-hands-helping"></i> NGOs</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment"></i> Feedback</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="page-title">
            <h1>Edit User</h1>
            <p>Welcome, <?= $admin_name ?></p>
        </div>

        <?php if (!empty($errors['database'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $errors['database'] ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= htmlspecialchars($name) ?>" required>
                    <?php if (!empty($errors['name'])): ?>
                        <div class="error-text"><?= $errors['name'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($email) ?>" required>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="error-text"><?= $errors['email'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password" class="form-control">
                    <?php if (!empty($errors['password'])): ?>
                        <div class="error-text"><?= $errors['password'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="user_type">User Type</label>
                    <select id="user_type" name="user_type" class="form-control" disabled>
                        <option value="Admin" <?= $user_type === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Donor" <?= $user_type === 'Donor' ? 'selected' : '' ?>>Donor</option>
                        <option value="Collector" <?= $user_type === 'Collector' ? 'selected' : '' ?>>Collector</option>
                        <option value="NGO" <?= $user_type === 'NGO' ? 'selected' : '' ?>>NGO</option>
                        <option value="PlantOperator" <?= $user_type === 'PlantOperator' ? 'selected' : '' ?>>Plant Operator</option>
                    </select>
                    <!-- Hidden field to actually submit the value -->
                    <input type="hidden" name="user_type" value="<?= $user_type ?>">
                </div>


                <div class="form-actions">
                    <a href="manage_users.php" class="btn-action btn-view">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn-action btn-edit">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('bar-icon').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
            document.getElementById('menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>