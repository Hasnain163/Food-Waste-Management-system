<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_type'])) {
    header("Location: Login.php");
    exit();
}

if ($_SESSION['user_type'] !== 'Admin') {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied. Admin privileges required.");
}

include 'db_connect.php';

$admin_id = $_SESSION['user_id'];
$admin_name = 'Admin';
$error_msg = '';
$success_msg = '';

// Get admin name
$stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $admin_name = htmlspecialchars($result->fetch_assoc()['name']);
}
$stmt->close();

// Handle tab switching
$active_tab = 'give-feedback';
$valid_tabs = ['give-feedback', 'view-feedback', 'my-feedback', 'manage-feedback'];
if (isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs)) {
    $active_tab = $_GET['tab'];
}

// Handle feedback editing
$editing_id = null;
$edit_feedback_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editing_id = intval($_GET['edit']);
    $active_tab = 'my-feedback';
    
    $stmt = $conn->prepare("SELECT * FROM feedback WHERE feedback_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $editing_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_feedback_data = $result->fetch_assoc();
    $stmt->close();
}

// Handle feedback deletion
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    
    // Verify admin is deleting their own feedback or has admin privileges
    if ($active_tab === 'my-feedback') {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $del_id, $admin_id);
    } else {
        // Admin can delete any feedback in manage tab
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
        $stmt->bind_param("i", $del_id);
    }
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM feedback_ratings WHERE feedback_id = $del_id");
        $stmt->execute();
        $conn->commit();
        $success_msg = "Feedback deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error deleting feedback: " . $e->getMessage();
    }
    $stmt->close();
    
    header("Location: feedback.php?tab=$active_tab");
    exit();
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $comments = trim($_POST['comments']);
    
    // Validate input
    if (empty($comments)) {
        $error_msg = "Comment cannot be empty.";
    } elseif (strlen($comments) < 3) {
        $error_msg = "Comment must be at least 3 characters.";
    } elseif (preg_match('/\b(fuck|shit|asshole|bitch)\b/i', $comments)) {
        $error_msg = "Inappropriate language detected.";
    } else {
        $comments = $conn->real_escape_string($comments);
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, date, comments) VALUES (?, CURDATE(), ?)");
        $stmt->bind_param("is", $admin_id, $comments);
        
        if ($stmt->execute()) {
            $success_msg = "Feedback submitted successfully!";
            $active_tab = 'view-feedback';
        } else {
            $error_msg = "Error submitting feedback.";
        }
        $stmt->close();
    }
}

// Handle feedback update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_feedback'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $comments = trim($_POST['comments']);
    
    if (empty($comments)) {
        $error_msg = "Comment cannot be empty.";
    } elseif (strlen($comments) < 3) {
        $error_msg = "Comment must be at least 3 characters.";
    } else {
        $comments = $conn->real_escape_string($comments);
        $stmt = $conn->prepare("UPDATE feedback SET comments = ? WHERE feedback_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $comments, $feedback_id, $admin_id);
        
        if ($stmt->execute()) {
            $success_msg = "Feedback updated successfully!";
        } else {
            $error_msg = "Error updating feedback.";
        }
        $stmt->close();
        
        header("Location: feedback.php?tab=my-feedback");
        exit();
    }
}

// Handle feedback voting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_feedback'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $vote = ($_POST['vote_feedback'] === 'helpful') ? 1 : 0;
    
    // Check if user already voted
    $stmt = $conn->prepare("SELECT * FROM feedback_ratings WHERE feedback_id = ? AND rater_id = ?");
    $stmt->bind_param("ii", $feedback_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO feedback_ratings (feedback_id, rater_id, helpful) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $feedback_id, $admin_id, $vote);
        $stmt->execute();
    }
    $stmt->close();
    
    header("Location: feedback.php?tab=view-feedback");
    exit();
}

// Sorting for Manage Feedback tab
$valid_sort_options = [
    'date_desc' => ['column' => 'f.date', 'order' => 'DESC'],
    'date_asc' => ['column' => 'f.date', 'order' => 'ASC'],
    'helpful_desc' => ['column' => 'helpful_count', 'order' => 'DESC'],
    'unhelpful_desc' => ['column' => 'unhelpful_count', 'order' => 'DESC'],
];

$sort_key = isset($_GET['sort']) && isset($valid_sort_options[$_GET['sort']]) ? $_GET['sort'] : 'date_desc';
$sort_by = $valid_sort_options[$sort_key]['column'];
$order = $valid_sort_options[$sort_key]['order'];

// Fetch all feedback for View and Manage tabs
$feedback_query = "
    SELECT f.*, u.name as user_name,
           COUNT(CASE WHEN fr.helpful = 1 THEN 1 END) AS helpful_count,
           COUNT(CASE WHEN fr.helpful = 0 THEN 1 END) AS unhelpful_count
    FROM feedback f
    LEFT JOIN feedback_ratings fr ON f.feedback_id = fr.feedback_id
    JOIN users u ON f.user_id = u.user_id
    GROUP BY f.feedback_id
    ORDER BY $sort_by $order
";
$feedback_result = $conn->query($feedback_query);

// Fetch admin's own feedback for My Feedback tab
$my_feedback_query = "
    SELECT f.*,
           COUNT(CASE WHEN fr.helpful = 1 THEN 1 END) AS helpful_count,
           COUNT(CASE WHEN fr.helpful = 0 THEN 1 END) AS unhelpful_count
    FROM feedback f
    LEFT JOIN feedback_ratings fr ON f.feedback_id = fr.feedback_id
    WHERE f.user_id = ?
    GROUP BY f.feedback_id
    ORDER BY f.date DESC
";
$stmt = $conn->prepare($my_feedback_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$my_feedback_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Feedback - FoodResQ</title>
    <link rel="stylesheet" href="features.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<header>
    <div class="logo">FoodResQ - Admin</div>
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
        <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
        <li class="active"><a href="feedback.php"><i class="fas fa-comment"></i> Feedback</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-title">
        <h1>Feedback Management</h1>
    </div>

    <?php if ($success_msg): ?>
        <div class="message success"><?= $success_msg ?></div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div class="message error"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="tab-container">
        <button class="tab <?= $active_tab === 'give-feedback' ? 'active' : '' ?>" 
                onclick="openTab('give-feedback', event)">Give Feedback</button>
        <button class="tab <?= $active_tab === 'view-feedback' ? 'active' : '' ?>" 
                onclick="openTab('view-feedback', event)">View Feedback</button>
        <button class="tab <?= $active_tab === 'my-feedback' ? 'active' : '' ?>" 
                onclick="openTab('my-feedback', event)">My Feedback</button>
        <button class="tab <?= $active_tab === 'manage-feedback' ? 'active' : '' ?>" 
                onclick="openTab('manage-feedback', event)">Manage Feedback</button>
    </div>

    <!-- Give Feedback Tab -->
    <div id="give-feedback" class="tab-content <?= $active_tab === 'give-feedback' ? 'active' : '' ?>">
        <form method="POST" class="feedback-form form-container">
            <h2>Submit Feedback</h2>
            <div class="form-group">
                <label for="comments">Comments:</label>
                <textarea id="comments" name="comments" required minlength="3"></textarea>
            </div>
            <button type="submit" name="submit_feedback" class="btn-submit">Submit Feedback</button>
        </form>
    </div>

    <!-- View Feedback Tab -->
    <div id="view-feedback" class="tab-content <?= $active_tab === 'view-feedback' ? 'active' : '' ?>">
        <h2>All Feedback</h2>
        <?php if ($feedback_result->num_rows > 0): ?>
            <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                <div class="feedback-container">
                    <div class="feedback-comments"><?= htmlspecialchars($feedback['comments']) ?></div>
                    <div class="feedback-meta">
                        <span><strong>User:</strong> <?= htmlspecialchars($feedback['user_name']) ?></span> | 
                        <span><strong>Date:</strong> <?= htmlspecialchars($feedback['date']) ?></span>
                    </div>
                    <div class="feedback-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="feedback_id" value="<?= $feedback['feedback_id'] ?>">
                            <button type="submit" name="vote_feedback" value="helpful" class="helpful-btn">
                                👍 Helpful (<?= $feedback['helpful_count'] ?>)
                            </button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="feedback_id" value="<?= $feedback['feedback_id'] ?>">
                            <button type="submit" name="vote_feedback" value="unhelpful" class="unhelpful-btn">
                                👎 Unhelpful (<?= $feedback['unhelpful_count'] ?>)
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No feedback found.</p>
        <?php endif; ?>
    </div>

    <!-- My Feedback Tab -->
    <div id="my-feedback" class="tab-content <?= $active_tab === 'my-feedback' ? 'active' : '' ?>">
        <h2>My Feedback</h2>
        
        <?php if ($edit_feedback_data): ?>
            <form method="POST" class="feedback-form form-container">
                <input type="hidden" name="feedback_id" value="<?= $edit_feedback_data['feedback_id'] ?>">
                <div class="form-group">
                    <label for="comments_edit">Comments:</label>
                    <textarea id="comments_edit" name="comments" required minlength="3"><?= 
                        htmlspecialchars($edit_feedback_data['comments'], ENT_QUOTES, 'UTF-8') 
                    ?></textarea>
                </div>
                <button type="submit" name="update_feedback" class="btn-submit">Update</button>
                <a href="feedback.php?tab=my-feedback" class="btn-submit cancel-btn">Cancel</a>
            </form>
        <?php endif; ?>

        <?php if ($my_feedback_result->num_rows > 0): ?>
            <?php while ($feedback = $my_feedback_result->fetch_assoc()): ?>
                <?php if ($editing_id !== intval($feedback['feedback_id'])): ?>
                    <div class="feedback-container">
                        <div class="feedback-comments"><?= htmlspecialchars($feedback['comments']) ?></div>
                        <div class="feedback-meta">
                            <span><strong>Date:</strong> <?= htmlspecialchars($feedback['date']) ?></span> |
                            <span>Helpful: <?= $feedback['helpful_count'] ?> | Unhelpful: <?= $feedback['unhelpful_count'] ?></span>
                        </div>
                        <div class="feedback-actions">
                            <a href="feedback.php?edit=<?= $feedback['feedback_id'] ?>&tab=my-feedback" 
                               class="btn-action btn-edit">Edit</a>
                            <a href="feedback.php?delete=<?= $feedback['feedback_id'] ?>&tab=my-feedback" 
                               class="btn-action btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this feedback?');">Delete</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't submitted any feedback yet.</p>
        <?php endif; ?>
    </div>

    <!-- Manage Feedback Tab -->
    <div id="manage-feedback" class="tab-content <?= $active_tab === 'manage-feedback' ? 'active' : '' ?>">
        <h2>Manage All Feedback</h2>
        
        <form method="GET" id="sortForm" class="sort-form">
            <input type="hidden" name="tab" value="manage-feedback">
            <label for="sort">Sort by: </label>
            <select name="sort" id="sort" onchange="this.form.submit()">
                <option value="date_desc" <?= $sort_key === 'date_desc' ? 'selected' : '' ?>>Newest</option>
                <option value="date_asc" <?= $sort_key === 'date_asc' ? 'selected' : '' ?>>Oldest</option>
                <option value="helpful_desc" <?= $sort_key === 'helpful_desc' ? 'selected' : '' ?>>Most Helpful</option>
                <option value="unhelpful_desc" <?= $sort_key === 'unhelpful_desc' ? 'selected' : '' ?>>Most Unhelpful</option>
            </select>
        </form>

        <?php if ($feedback_result->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Comments</th>
                        <th>Helpful</th>
                        <th>Unhelpful</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $feedback_result->data_seek(0); ?>
                    <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $feedback['feedback_id'] ?></td>
                            <td><?= htmlspecialchars($feedback['user_name']) ?></td>
                            <td><?= htmlspecialchars($feedback['date']) ?></td>
                            <td><?= htmlspecialchars($feedback['comments']) ?></td>
                            <td><?= $feedback['helpful_count'] ?></td>
                            <td><?= $feedback['unhelpful_count'] ?></td>
                            <td>
                                <a href="feedback.php?delete=<?= $feedback['feedback_id'] ?>&tab=manage-feedback" 
                                   class="btn-action btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this feedback?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No feedback found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function openTab(tabName, event) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Deactivate all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Activate selected tab
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
    
    // Update URL without reloading
    history.pushState(null, null, `?tab=${tabName}`);
}

// Handle back/forward navigation
window.addEventListener('popstate', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'give-feedback';
    const tabElement = document.getElementById(tab);
    if (tabElement) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tabElement.classList.add('active');
        document.querySelector(`.tab[onclick*="${tab}"]`).classList.add('active');
    }
});

// Initialize active tab from URL
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'give-feedback';
    const tabElement = document.getElementById(tab);
    if (tabElement) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tabElement.classList.add('active');
        document.querySelector(`.tab[onclick*="${tab}"]`).classList.add('active');
    }
    
    // Mobile menu toggle
    document.getElementById('bar-icon').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
        document.querySelector('.main-content').classList.toggle('active');
        document.getElementById('menu').classList.toggle('hidden');
    });
});
</script>
</body>
</html>