<?php
session_start();
include 'db_connect.php';
$success_msg = ''; 

if (!isset($_SESSION['user_type'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Now allow any valid user role to access (admin, donor, etc.)
// Load user data
$user = $conn->query("SELECT * FROM users WHERE user_id = '$user_id'")->fetch_assoc();
$profile = $conn->query("SELECT * FROM profiles WHERE user_id = '$user_id'")->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $country = $_POST['country'];
    $bio = $_POST['bio'];

    if ($profile) {
        $conn->query("UPDATE profiles SET phone='$phone', address='$address', city='$city', country='$country', bio='$bio' WHERE user_id = '$user_id'");
        $success_msg = "Profile updated.";
    } else {
        $conn->query("INSERT INTO profiles (user_id, phone, address, city, country, bio) VALUES ('$user_id', '$phone', '$address', '$city', '$country', '$bio')");
        $success_msg = "Profile created.";
    }

    $profile = $conn->query("SELECT * FROM profiles WHERE user_id = '$user_id'")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - FoodResQ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Your CSS -->
    <link rel="stylesheet" href="dashboard.css?v=<?= time(); ?>">
    <style>
        .profile-form {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            max-width: 700px;
            margin: auto;
        }

        .profile-form h2 {
            margin-top: 0;
            color: #333;
        }

        .profile-form .form-group {
            margin-bottom: 15px;
        }

        .profile-form label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .profile-form input,
        .profile-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        .profile-form input[readonly] {
            background-color: #f5f5f5;
        }

        .profile-form textarea {
            resize: vertical;
            height: 80px;
        }

        .btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #218838;
        }

        .success-msg {
            color: green;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .main-content h1 {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="dashboard_body">

<!-- Header -->
<header class="donor_header">
    <div class="logo">FoodResQ</div>
    <i class="fas fa-bars fa-2x" id="bar-icon"></i>
    <nav id="menu" class="hidden">
        <ul>
            <li>
                <div class="profile-btn">
                    <a class="nav_link btn" href="profile.php">
                        <img src="admin.png" alt="Profile" class="profile-icon"> Profile
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</header>

    <div class="sidebar">
        <ul>
            <li class="active"><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
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

<!-- Main Content -->
<div class="main-content">
    <h1>My Profile</h1>

    <div class="profile-form">
        <?php if ($success_msg): ?>
            <div class="success-msg"><?= $success_msg ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Address:</label>
                <input type="text" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>City:</label>
                <input type="text" name="city" value="<?= htmlspecialchars($profile['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Country:</label>
                <input type="text" name="country" value="<?= htmlspecialchars($profile['country'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Bio:</label>
                <textarea name="bio"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>

            <input type="submit" value="Save Profile" class="btn">
        </form>
    </div>
</div>

</body>
</html>
