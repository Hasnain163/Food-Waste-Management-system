<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'NGO') {
    header("Location: Login.php");
    exit();
}

include 'db_connect.php';

$ngo_id = $_SESSION['user_id'];
$ngo_query = $conn->query("SELECT name FROM users WHERE user_id = '$ngo_id' AND user_type = 'NGO'");
$ngo_data = $ngo_query->fetch_assoc();
$ngo_name = $ngo_data ? $ngo_data['name'] : 'NGO User';

$editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
$active_tab = isset($_GET['edit']) ? 'my-feedback' : 'give-feedback';

$edit_feedback_data = null;
if ($editing_id !== null) {
    $edit_query = $conn->query("SELECT * FROM feedback WHERE feedback_id = '$editing_id' AND user_id = '$ngo_id'");
    $edit_feedback_data = $edit_query->fetch_assoc();
}

// Delete feedback with ratings cleanup first
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM feedback_ratings WHERE feedback_id = '$del_id'"); // First delete ratings
    $conn->query("DELETE FROM feedback WHERE feedback_id = '$del_id' AND user_id = '$ngo_id'");
    header("Location: feedback_ngo.php");
    exit();
}

// Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_feedback'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $comments = trim($conn->real_escape_string($_POST['comments']));
    if (strlen($comments) < 3) {
        $error_msg = "Comment too short.";
    } else {
        $conn->query("UPDATE feedback SET comments = '$comments' WHERE feedback_id = '$feedback_id' AND user_id = '$ngo_id'");
        header("Location: feedback_ngo.php");
        exit();
    }
}

// Submit feedback
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $comments = trim($conn->real_escape_string($_POST['comments']));
    if (stripos($comments, 'fuck') !== false || strlen($comments) < 3) {
        $error_msg = "Inappropriate or too short comment.";
    } else {
        $conn->query("INSERT INTO feedback (user_id, date, comments) VALUES ('$ngo_id', CURDATE(), '$comments')");
        $success_msg = "Feedback submitted successfully!";
    }
}

// Handle voting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_feedback'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $vote = $_POST['vote_feedback'] === 'helpful' ? 1 : 0;

    $check = $conn->prepare("SELECT * FROM feedback_ratings WHERE feedback_id = ? AND rater_id = ?");
    $check->bind_param("ii", $feedback_id, $ngo_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO feedback_ratings (feedback_id, rater_id, helpful) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $feedback_id, $ngo_id, $vote);
        $stmt->execute();
    }
    header("Location: feedback_ngo.php");
    exit();
}

// Others' Feedback
$feedback_result = $conn->query("
SELECT f.*, 
       COUNT(CASE WHEN fr.helpful = 1 THEN 1 END) AS helpful_count,
       COUNT(CASE WHEN fr.helpful = 0 THEN 1 END) AS unhelpful_count
FROM feedback f
LEFT JOIN feedback_ratings fr ON f.feedback_id = fr.feedback_id
WHERE f.user_id != '$ngo_id'
GROUP BY f.feedback_id
ORDER BY f.date DESC
");

// My Feedback
$my_feedback_result = $conn->query("
SELECT f.*, 
       COUNT(CASE WHEN fr.helpful = 1 THEN 1 END) AS helpful_count,
       COUNT(CASE WHEN fr.helpful = 0 THEN 1 END) AS unhelpful_count
FROM feedback f
LEFT JOIN feedback_ratings fr ON f.feedback_id = fr.feedback_id
WHERE f.user_id = '$ngo_id'
GROUP BY f.feedback_id
ORDER BY f.date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback - FoodResQ</title>
    <link rel="stylesheet" href="features.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
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

<div class="sidebar">
    <ul>
        <li><a href="dashboard_ngo.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="collection_schedule_ngo.php"><i class="fas fa-calendar"></i> Collection Schedule</a></li>
        <li><a href="locations_ngo.php"><i class="fas fa-map-marker-alt"></i> Locations</a></li>
        <li><a href="food_inventory_ngo.php"><i class="fas fa-utensils"></i> Food Inventory</a></li>
        <li><a href="donations_ngo.php"><i class="fas fa-gift"></i> Donations</a></li>
        <li><a href="alerts_ngo.php"><i class="fas fa-bell"></i> Alerts</a></li>
        <li class="active"><a href="feedback_ngo.php"><i class="fas fa-comment"></i> Feedback</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="dashboard-title">
        <h1>Feedback Management</h1>
        <p>Welcome, <?= htmlspecialchars($ngo_name) ?></p>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="message success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="message error"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="tab-container">
        <button class="tab <?= $active_tab === 'give-feedback' ? 'active' : '' ?>" onclick="openTab('give-feedback', event)">Give Feedback</button>
        <button class="tab <?= $active_tab === 'view-feedback' ? 'active' : '' ?>" onclick="openTab('view-feedback', event)">View Feedback</button>
        <button class="tab <?= $active_tab === 'my-feedback' ? 'active' : '' ?>" onclick="openTab('my-feedback', event)">My Feedback</button>
    </div>

    <!-- Give Feedback -->
    <div id="give-feedback" class="tab-content <?= $active_tab === 'give-feedback' ? 'active' : '' ?>">
        <form method="POST" class="feedback-form">
            <h2>Submit Feedback</h2>
            <div class="form-group">
                <label>Comments:</label>
                <textarea name="comments" required></textarea>
            </div>
            <button type="submit" name="submit_feedback" class="submit-btn">Submit Feedback</button>
        </form>
    </div>

    <!-- View Feedback -->
    <div id="view-feedback" class="tab-content <?= $active_tab === 'view-feedback' ? 'active' : '' ?>">
        <h2>All Feedback</h2>
        <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
            <div class="feedback-container">
                <div class="feedback-comments"><?= htmlspecialchars($feedback['comments']) ?></div>
                <div class="feedback-meta">
                    <span>Posted on: <?= $feedback['date'] ?></span>
                </div>
                <div class="feedback-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="feedback_id" value="<?= $feedback['feedback_id'] ?>">
                        <button type="submit" name="vote_feedback" value="helpful" class="helpful-btn">👍 Helpful</button>
                        <button type="submit" name="vote_feedback" value="unhelpful" class="unhelpful-btn">👎 Unhelpful</button>
                    </form>
                    <div style="margin-top:8px;">
                        Helpful: <?= $feedback['helpful_count'] ?> | Unhelpful: <?= $feedback['unhelpful_count'] ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- My Feedback -->
    <div id="my-feedback" class="tab-content <?= $active_tab === 'my-feedback' ? 'active' : '' ?>">
        <h2>My Feedback</h2>
        <?php if ($edit_feedback_data): ?>
            <form method="POST" class="feedback-form">
                <input type="hidden" name="feedback_id" value="<?= $edit_feedback_data['feedback_id'] ?>">
                <div class="form-group">
                    <label>Comments:</label>
                    <textarea name="comments" required><?= htmlspecialchars($edit_feedback_data['comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <button type="submit" name="update_feedback" class="submit-btn">Update</button>
                <a href="feedback_ngo.php" class="btn-submit" style="margin-left: 10px;">Cancel</a>
            </form>
        <?php endif; ?>

        <?php while ($feedback = $my_feedback_result->fetch_assoc()): ?>
            <?php if ($editing_id === intval($feedback['feedback_id'])) continue; ?>
            <div class="feedback-container">
                <div class="feedback-comments"><?= htmlspecialchars($feedback['comments']) ?></div>
                <div class="feedback-meta">
                    <span>Posted on: <?= $feedback['date'] ?></span>
                    <span>Helpful: <?= $feedback['helpful_count'] ?> | Unhelpful: <?= $feedback['unhelpful_count'] ?></span>
                </div>
                <div class="feedback-actions">
                    <a href="feedback_ngo.php?edit=<?= $feedback['feedback_id'] ?>" class="btn-submit btn-edit">Edit</a>
                    <a href="feedback_ngo.php?delete=<?= $feedback['feedback_id'] ?>" class="btn-submit btn-delete" onclick="return confirm('Delete this feedback?');">Delete</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
function openTab(tabName, event) {
    const tabs = document.querySelectorAll('.tab-content');
    const buttons = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    buttons.forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}
document.getElementById('bar-icon').addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.main-content').classList.toggle('active');
    document.getElementById('menu').classList.toggle('hidden');
});
</script>
</body>
</html>
