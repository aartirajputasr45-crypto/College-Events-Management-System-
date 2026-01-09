<?php
session_start();
require_once 'db.php';
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}
$admin_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
$success_message = "";
$error_message = "";
/* ---------------------------------------------------------
   CREATE ORGANIZER
--------------------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_organizer'])) {
    $full_name = trim($_POST['full_name']);
    $username_input = trim($_POST['username_or_email']);
    $password = trim($_POST['password']);
    if (empty($full_name) || empty($username_input) || empty($password)) {
        $error_message = "❌ All fields are required.";
    } else {
        try {
            $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $check->execute([$username_input]);
            if ($check->rowCount() > 0) {
                $error_message = "❌ Username already exists.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, username, password, user_role) 
                    VALUES (?, ?, ?, 'Organizer')
                ");
                $stmt->execute([$full_name, $username_input, $password]);
                $success_message = "✅ Organizer '$full_name' created successfully!";
            }
        } catch (PDOException $e) { 
            $error_message = "❌ Database Error while creating organizer.";
        }
    }
}
/* ---------------------------------------------------------
   CREATE EVENT
--------------------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_event'])) {
    $title = trim($_POST['event_title']);
    $description = trim($_POST['event_description']);
    $event_date = trim($_POST['event_date']);
    $event_time = trim($_POST['event_time']);
    $venue = trim($_POST['event_venue']); // Venue after event_time
    $status = trim($_POST['status']);
    $organizer_id = $_POST['organizer_id'];
    $image_path = null;
    if (empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($venue) || empty($status) || empty($organizer_id)) {
        $error_message = "❌ All fields required.";
    } else {
        try {
            if (!empty($_FILES['event_image']['name'])) {
                if (!is_dir("uploads")) mkdir("uploads", 0755, true);
                $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
                $filename = "uploads/event_" . time() . "." . $ext;
                move_uploaded_file($_FILES['event_image']['tmp_name'], $filename);
                $image_path = $filename;
            }
            $stmt = $pdo->prepare("
                INSERT INTO events (title, description, event_date, event_time, event_venue, status, organizer_id, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $event_date, $event_time, $venue, $status, $organizer_id, $image_path]);
            $success_message = "✅ Event '$title' created successfully!";
        } catch (PDOException $e) {
            $error_message = "❌ Database Error while creating event.";
        }
    }
}
/* ---------------------------------------------------------
   FETCH ORGANIZERS & EVENTS
--------------------------------------------------------- */
try {
    $organizers_stmt = $pdo->prepare("SELECT * FROM users WHERE user_role='Organizer' ORDER BY joined_at DESC");
    $organizers_stmt->execute();
    $organizers = $organizers_stmt->fetchAll(PDO::FETCH_ASSOC);
    $events_stmt = $pdo->prepare("
        SELECT e.*, u.full_name AS organizer_name 
        FROM events e 
        LEFT JOIN users u ON e.organizer_id = u.user_id
        ORDER BY e.event_date DESC
    ");
    $events_stmt->execute();
    $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "❌ Unable to load organizers or events.";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
body {
    background: url('admin.jpg') no-repeat center center/cover;
    font-family: 'Segoe UI';
    margin: 0;
    color: white;
}
.overlay {
    background: rgba(0,0,0,0.75);
    padding: 40px;
    min-height: 100vh;
}
.container {
    width: 90%;
    margin: auto;
}
h1 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 32px;
    color: #00eaff;
}
.logout {
    position: fixed;
    top: 20px;
    right: 25px;
    background: red;
    padding: 10px 15px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    font-weight: bold;
    z-index: 9999;
}
.logout:hover {
    background: #b30000;
}
.glass-box {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    padding: 25px;
    border-radius: 15px;
    margin-top: 25px;
    box-shadow: 0 0 20px rgba(0,0,0,0.4);
}
input, select, textarea {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border-radius: 8px;
    background: rgba(255,255,255,0.2);
    color: black;
    border: 1px solid #bbb;
}
.btn {
    padding: 12px 20px;
    border: none;
    background: #00eaff;
    color: black;
    font-weight: bold;
    border-radius: 8px;
    margin-top: 10px;
    cursor: pointer;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th {
    background: #00eaff;
    color: black;
    padding: 10px;
}
td {
    padding: 10px;
    background: rgba(255,255,255,0.15);
}
.status-published {
    background: #28a745;
    padding: 5px 8px;
    border-radius: 5px;
}
.status-draft {
    background: #ff9800;
    padding: 5px 8px;
    border-radius: 5px;
}
.success-msg {
    background:#155724;
    padding:12px;
    border-left:5px solid #28a745;
}
.error-msg {
    background:#5a1a1a;
    padding:12px;
    border-left:5px solid #dc3545;
}
/* ACTION BUTTONS */
.action-btn {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    font-size: 0.85em;
    margin-right: 5px;
    color: white;
    cursor: pointer;
}
.btn-view {
    background-color: #00eaff;
}
.btn-view:hover {
    background-color: #00b3cc;
}
.btn-delete {
    background-color: #ff4d4d;
}
.btn-delete:hover {
    background-color: #cc0000;
}
</style>
</head>
<body>
<a href="logout.php" class="logout">Logout</a>
<div class="overlay">
<div class="container">
<h1>Admin Dashboard</h1>
<?php if ($success_message): ?>
<div class="success-msg"><?= $success_message ?></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="error-msg"><?= $error_message ?></div>
<?php endif; ?>
<!-- CREATE ORGANIZER -->
<div class="glass-box">
<h2>Create Organizer</h2>
<form method="POST">
    <input type="hidden" name="create_organizer" value="1">
    <label>Full Name</label>
    <input type="text" name="full_name">
    <label>Username</label>
    <input type="text" name="username_or_email">
    <label>Password</label>
    <input type="password" name="password">
    <button class="btn">Create Organizer</button>
</form>
</div>
<!-- CREATE EVENT -->
<div class="glass-box">
<h2>Create Event</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="create_event" value="1">
    <label>Title</label>
    <input type="text" name="event_title">
    <label>Description</label>
    <textarea name="event_description"></textarea>
    <label>Date</label>
    <input type="date" name="event_date">
    <label>Time</label>
    <input type="time" name="event_time">
    <label>Venue</label>
    <input type="text" name="event_venue" placeholder="Enter Event Venue">
    <label>Status</label>
    <select name="status">
        <option value="draft">Draft</option>
        <option value="published">Published</option>
    </select>
    <label>Assign Organizer</label>
    <select name="organizer_id">
        <option value="">-- Select Organizer --</option>
        <?php foreach ($organizers as $org): ?>
            <option value="<?= $org['user_id'] ?>"><?= $org['full_name'] ?></option>
        <?php endforeach; ?>
    </select>
    <label>Event Image</label>
    <input type="file" name="event_image">
    <button class="btn">Create Event</button>
</form>
</div>
<!-- ORGANIZERS TABLE -->
<div class="glass-box">
<h2>All Organizers</h2>
<table>
<tr>
    <th>ID</th><th>Name</th><th>Username</th><th>Joined</th><th>Action</th>
</tr>
<?php foreach ($organizers as $o): ?>
<tr>
    <td><?= $o['user_id'] ?></td>
    <td><?= $o['full_name'] ?></td>
    <td><?= $o['username'] ?></td>
    <td><?= $o['joined_at'] ?></td>
    <td><a class="action-btn btn-delete" href="admin_delete_user.php?id=<?= $o['user_id'] ?>">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- EVENTS TABLE -->
<div class="glass-box">
<h2>All Events</h2>
<table>
<tr>
    <th>ID</th><th>Image</th><th>Title</th><th>Date</th><th>Time</th><th>Venue</th><th>Organizer</th><th>Status</th><th>Action</th>
</tr>
<?php foreach ($events as $e): ?>
<tr>
    <td><?= $e['id'] ?></td>
    <td>
        <?php if ($e['image_path']): ?>
        <img src="<?= $e['image_path'] ?>" width="50" height="50" style="border-radius:5px;">
        <?php else: ?>N/A<?php endif; ?>
    </td>
    <td><?= $e['title'] ?></td>
    <td><?= $e['event_date'] ?></td>
    <td><?= $e['event_time'] ?></td>
    <td><?= htmlspecialchars($e['event_venue']) ?></td>
    <td><?= $e['organizer_name'] ?></td>
    <td>
        <span class="<?= $e['status']=='published' ? 'status-published' : 'status-draft' ?>">
            <?= ucfirst($e['status']) ?>
        </span>
    </td>
    <td>
        <a href="admin_view_registrations.php?event_id=<?= $e['id'] ?>" class="action-btn btn-view">View</a>
        <a href="delete_event.php?id=<?= $e['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

</div>
</div>
</body>
</html>
