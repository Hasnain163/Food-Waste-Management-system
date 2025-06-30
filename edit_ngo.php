<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: ngos.php");
    exit();
}

$ngo_id = (int)$_GET['id'];
$errors = [];
$name = $email = $contact_person = $phone = $address = '';

$query = $conn->query("
    SELECT u.name, u.email, n.contact_person, n.phone, l.address, l.location_id
    FROM users u
    LEFT JOIN ngos n ON u.user_id = n.ngo_id
    LEFT JOIN locations l ON n.location_id = l.location_id
    WHERE u.user_id = $ngo_id AND u.user_type = 'NGO'
");

if ($query->num_rows === 0) {
    die("NGO not found.");
}

$data = $query->fetch_assoc();
$name = $data['name'];
$email = $data['email'];
$contact_person = $data['contact_person'] ?? '';
$phone = $data['phone'] ?? '';
$address = $data['address'] ?? '';
$location_id = $data['location_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_person = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validation
    if (empty($name)) $errors['name'] = "Name is required";
    if (empty($email)) $errors['email'] = "Email is required";

    if (empty($errors)) {
        // Update users table
        $conn->query("UPDATE users SET name='$name', email='$email' WHERE user_id=$ngo_id");

        // Insert or update location
        if (!empty($address)) {
            if ($location_id) {
                $conn->query("UPDATE locations SET address='$address' WHERE location_id=$location_id");
            } else {
                $conn->query("INSERT INTO locations (user_id, address) VALUES ($ngo_id, '$address')");
                $location_id = $conn->insert_id;
            }
        }

        // Insert or update NGO details
        $check_ngo = $conn->query("SELECT * FROM ngos WHERE ngo_id = $ngo_id");
        if ($check_ngo->num_rows > 0) {
            $conn->query("UPDATE ngos SET contact_person='$contact_person', phone='$phone', location_id=" . ($location_id ?: "NULL") . " WHERE ngo_id = $ngo_id");
        } else {
            $conn->query("INSERT INTO ngos (ngo_id, contact_person, phone, location_id) VALUES ($ngo_id, '$contact_person', '$phone', " . ($location_id ?: "NULL") . ")");
        }

        $_SESSION['ngo_updated'] = "NGO updated successfully";
        header("Location: ngos.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit NGO - FoodResQ</title>
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
        <h1>Edit NGO</h1>
    </div>

    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
                <?php if (isset($errors['name'])): ?><div class="error-text"><?= $errors['name'] ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                <?php if (isset($errors['email'])): ?><div class="error-text"><?= $errors['email'] ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" value="<?= htmlspecialchars($contact_person) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address"><?= htmlspecialchars($address) ?></textarea>
            </div>

            <div class="form-actions">
                <a href="ngos.php" class="btn-action btn-view">Cancel</a>
                <button type="submit" class="btn-action btn-edit">Update NGO</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
