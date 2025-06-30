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
$admin_query = $conn->prepare("SELECT name FROM users WHERE user_id = ? AND user_type = 'Admin'");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_data = $admin_query->get_result()->fetch_assoc();
$admin_name = $admin_data ? $admin_data['name'] : 'Admin';

// Initialize variables
$name = $email = $password = $user_type = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'];
    
    if (empty($name)) $errors['name'] = 'Name is required';
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    if (empty($password)) $errors['password'] = 'Password is required';
    elseif (strlen($password) < 7) $errors['password'] = 'Password must be at least 7 characters';
    
    if (empty($user_type)) $errors['user_type'] = 'User type is required';
    
    // If no errors, insert new user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $conn->begin_transaction();
        
        try {
            // Insert user
            $insert_user = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
            $insert_user->bind_param("ssss", $name, $email, $hashed_password, $user_type);
            
            // After successful user creation in add_user.php:
            if ($insert_user->execute()) {
                $user_id = $conn->insert_id;
                
                // Handle NGO-specific creation
                if ($user_type === 'NGO') {
                    $insert_ngo = $conn->prepare("INSERT INTO ngos (ngo_id, name) VALUES (?, ?)");
                    $insert_ngo->bind_param("is", $user_id, $name);
                    
                    if (!$insert_ngo->execute()) {
                        throw new Exception("Failed to create NGO record: " . $conn->error);
                    }
                    $_SESSION['ngo_added'] = 'NGO added successfully';
                    $redirect_page = 'ngos.php'; // Redirect to NGOs page for NGO users
                } else {
                    $_SESSION['user_added'] = 'User added successfully';
                    $redirect_page = 'manage_users.php'; // Redirect to Users page for other types
                }
                
                $conn->commit();
                header("Location: $redirect_page");
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = $e->getMessage();
            error_log("User creation error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New User - FoodResQ</title>
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

    <!-- Sidebar -->
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
            <h1>Add New User</h1>
            <p>Welcome, <?= htmlspecialchars($admin_name) ?></p>
        </div>

        <?php if (!empty($errors['database'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($errors['database']) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= htmlspecialchars($name) ?>" required>
                    <?php if (!empty($errors['name'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($email) ?>" required>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="user_type">User Type</label>
                    <select id="user_type" name="user_type" class="form-control" required>
                        <option value="">Select User Type</option>
                        <option value="Donor" <?= $user_type === 'Donor' ? 'selected' : '' ?>>Donor</option>
                        <option value="Collector" <?= $user_type === 'Collector' ? 'selected' : '' ?>>Collector</option>
                        <option value="NGO" <?= $user_type === 'NGO' ? 'selected' : '' ?>>NGO</option>
                        <option value="PlantOperator" <?= $user_type === 'PlantOperator' ? 'selected' : '' ?>>Plant Operator</option>
                    </select>
                    <?php if (!empty($errors['user_type'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['user_type']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <a href="manage_users.php" class="btn-action btn-view">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn-action btn-add">
                        <i class="fas fa-save"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('bar-icon').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
            document.getElementById('menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>