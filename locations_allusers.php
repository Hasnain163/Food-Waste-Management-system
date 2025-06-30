<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: ../Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch this user's locations
$stmt = $conn->prepare("SELECT * FROM locations WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$locations = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>My Locations</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
  <style>
    .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
    .modal-content { background: #fff; margin: 5% auto; padding: 20px; width: 500px; height: 500px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.3); position: relative; }
    .close { position: absolute; right: 15px; top: 10px; font-size: 20px; cursor: pointer; color: #888; }
    #add-map, #view-map { height: 300px; width: 100%; margin-bottom: 10px; }
    .btn { padding: 5px 10px; background: #007bff; color: #fff; border: none; margin-right: 5px; cursor: pointer; border-radius: 4px; }
    .btn-danger { background: #dc3545; }
  </style>
</head>
<body>

<h2>My Locations</h2>
<button class="btn" onclick="openAddLocationModal()">➕ Add Location</button>

<table border="1" cellpadding="8" cellspacing="0">
  <thead>
    <tr><th>ID</th><th>Lat</th><th>Lng</th><th>Address</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($locations as $loc): ?>
    <tr>
      <td><?= $loc['location_id'] ?></td>
      <td><?= $loc['latitude'] ?></td>
      <td><?= $loc['longitude'] ?></td>
      <td><?= htmlspecialchars($loc['address']) ?></td>
      <td>
        <button class="btn" onclick="openMapModal(<?= $loc['latitude'] ?>, <?= $loc['longitude'] ?>)">Map</button>
        <a href="../delete_location.php?id=<?= $loc['location_id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this location?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Add Location Modal -->
<div id="addLocationModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAddLocationModal()">&times;</span>
    <h3>Select Location</h3>
    <div id="add-map"></div>
    <form method="POST" action="../add_location.php">
      <input type="hidden" name="latitude" id="add_latitude" required>
      <input type="hidden" name="longitude" id="add_longitude" required>
      <label>Address</label>
      <input type="text" name="address" id="add_address" required>
      <br><br>
      <button class="btn" type="submit">Save</button>
    </form>
  </div>
</div>

<!-- View Map Modal -->
<div id="viewMapModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeViewMapModal()">&times;</span>
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

    L.Control.geocoder({ defaultMarkGeocode: false }).on('markgeocode', function(e) {
      const latlng = e.geocode.center;
      addMap.setView(latlng, 16);
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
      addMarker = L.marker([lat, lng]).addTo(addMap).bindPopup(`Lat: ${lat}<br>Lng: ${lng}`).openPopup();
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
  viewMap = L.map('view-map').setView([lat, lng], 15);
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
