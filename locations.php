<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$locations_query = $conn->query("SELECT l.*, u.name as user_name 
    FROM locations l 
    LEFT JOIN users u ON l.user_id = u.user_id");
$locations = $locations_query->fetch_all(MYSQLI_ASSOC);

$user_options = $conn->query("SELECT user_id, name FROM users ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Locations - FoodResQ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="features.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
  <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
    }
    .modal-content {
        background: #fff;
        margin: 5% auto;
        padding: 20px;
        width: 500px;
        height: 500px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        position: relative;
    }
    .close {
        position: absolute;
        right: 15px;
        top: 10px;
        font-size: 20px;
        color: #888;
        cursor: pointer;
    }
    #add-map, #view-map {
        height: 300px;
        width: 100%;
        margin-bottom: 10px;
    }
    .btn-action {
        padding: 4px 8px;
        margin: 2px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
    }
    .btn-delete {
        background-color: #dc3545;
    }
  </style>
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

<div class="main-content">
    <div class="page-title">
        <h1>Manage Locations</h1>
        <button onclick="openAddLocationModal()">➕ Add Location via Map</button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <p style="color: green;">✅ Location added successfully.</p>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($locations as $location): ?>
            <tr>
                <td><?= $location['location_id'] ?></td>
                <td><?= htmlspecialchars($location['user_name'] ?? 'Unassigned') ?></td>
                <td><?= $location['latitude'] ?></td>
                <td><?= $location['longitude'] ?></td>
                <td><?= htmlspecialchars($location['address']) ?></td>
                <td>
                    <a href="delete_location.php?id=<?= $location['location_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Delete this location?')">Delete</a>
                    <button class="btn-action" onclick="openMapModal(<?= $location['latitude'] ?>, <?= $location['longitude'] ?>)">Map</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Location Modal -->
<div id="addLocationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddLocationModal()">&times;</span>
        <h3>Select Location on Map</h3>
        <div id="add-map"></div>
        <form method="POST" action="add_location.php">
            <input type="hidden" name="latitude" id="add_latitude" required>
            <input type="hidden" name="longitude" id="add_longitude" required>

            <label>User:</label>
            <select name="user_id" required>
                <option value="">-- Select --</option>
                <?php while ($u = $user_options->fetch_assoc()): ?>
                    <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Address</label>
            <input type="text" name="address" id="add_address" required>

            <button type="submit">Save Location</button>
        </form>
    </div>
</div>

<!-- View Location Modal (square box like Add modal) -->
<div id="viewMapModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeViewMapModal()">&times;</span>
        <h3>Location Map</h3>
        <div id="view-map"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script>
let addMap, addMarker, viewMap;

function openAddLocationModal() {
    document.getElementById("addLocationModal").style.display = "block";

    if (!addMap) {
        addMap = L.map('add-map').setView([23.8103, 90.4125], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(addMap);

        L.Control.geocoder({ defaultMarkGeocode: false })
        .on('markgeocode', function(e) {
            const latlng = e.geocode.center;
            addMap.setView(latlng, 15);
            if (addMarker) addMap.removeLayer(addMarker);
            addMarker = L.marker(latlng).addTo(addMap).bindPopup(e.geocode.name).openPopup();
            document.getElementById('add_latitude').value = latlng.lat;
            document.getElementById('add_longitude').value = latlng.lng;
            document.getElementById('add_address').value = e.geocode.name;
        }).addTo(addMap);

        addMap.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            if (addMarker) addMap.removeLayer(addMarker);
            addMarker = L.marker([lat, lng]).addTo(addMap)
                         .bindPopup("Lat: " + lat + "<br>Lng: " + lng).openPopup();
            document.getElementById('add_latitude').value = lat;
            document.getElementById('add_longitude').value = lng;
            document.getElementById('add_address').value = '';
        });
    }
}

function closeAddLocationModal() {
    document.getElementById("addLocationModal").style.display = "none";
}

function openMapModal(lat, lng) {
    document.getElementById("viewMapModal").style.display = "block";

    if (viewMap) viewMap.remove();
    viewMap = L.map('view-map').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(viewMap);
    L.marker([lat, lng]).addTo(viewMap).bindPopup(`Lat: ${lat}<br>Lng: ${lng}`).openPopup();
}

function closeViewMapModal() {
    document.getElementById("viewMapModal").style.display = "none";
    if (viewMap) viewMap.remove();
}
</script>
</body>
</html>
