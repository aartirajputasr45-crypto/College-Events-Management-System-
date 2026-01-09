<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Organizer') {
    header("Location: organizer_view.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$organizer_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
$error_message = "";
$success_message = "";

// ---------------- FETCH EVENT TO EDIT ----------------
if (!isset($_GET['id'])) {
    header("Location: organizer_view.php");
    exit();
}

$event_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $organizer_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $error_message = "Event not found or you don't have permission to edit it.";
    }

} catch (Exception $e) {
    $error_message = "Database Error: " . $e->getMessage();
}

// ---------------- UPDATE EVENT ----------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_event'])) {

    $title = trim($_POST['event_title']);
    $description = trim($_POST['event_description']);
    $event_date = trim($_POST['event_date']);
    $event_time = trim($_POST['event_time']);
    $status = trim($_POST['status']);
    $image_path = $event['image_path']; // Keep existing image if not updated

    if (!empty($_FILES['event_image']['name'])) {
        if (!is_dir("uploads")) mkdir("uploads", 0755, true);
        $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
        $filename = "uploads/event_" . time() . "." . $ext;
        move_uploaded_file($_FILES['event_image']['tmp_name'], $filename);
        $image_path = $filename;
    }

    try {
        $update = $pdo->prepare("
            UPDATE events 
            SET title=?, description=?, event_date=?, event_time=?, status=?, image_path=?
            WHERE id=? AND organizer_id=?
        ");
        $update->execute([$title, $description, $event_date, $event_time, $status, $image_path, $event_id, $organizer_id]);

        $success_message = "✅ Event updated successfully!";
        // Refresh event data
        $stmt->execute([$event_id, $organizer_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error_message = "Database Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Event | <?php echo $organizer_name; ?></title>
<style>
    body {
        font-family: 'Segoe UI';
        margin: 0;
        padding: 0;
        color: white;
        background: url('organizer.jpg') no-repeat center center/cover;
        height: 100vh;
        width: 100%;
        position: relative;
    }
    body::before {
        content: "";
        position: absolute;
        top:0; left:0;
        width:100%; height:100%;
        background: rgba(0,0,0,0.55);
        z-index:0;
    }
    .container {
        position: relative;
        z-index:2;
        max-width:800px;
        margin:40px auto;
        padding:30px;
        background: rgba(0,0,0,0.55);
        border-radius:12px;
        backdrop-filter: blur(4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    }
    h1 { color:#FFC107; margin-bottom:20px; }
    .btn-back { float:right; background:#00BCD4; padding:10px 18px; color:white; text-decoration:none; border-radius:6px; font-weight:bold; }
    .btn-back:hover { background:#0097a7; }
    input, textarea, select { width:100%; padding:10px; margin-top:8px; border-radius:6px; border:1px solid #bbb; background: rgba(255,255,255,0.1); color:white; }
    button { padding:12px 20px; margin-top:15px; border:none; border-radius:6px; background:#FFC107; color:black; font-weight:bold; cursor:pointer; }
    .success-msg { background:#155724; padding:10px; border-left:5px solid #28a745; margin-bottom:15px; border-radius:5px; }
    .error-msg { background:#8b1e1e; padding:10px; border-left:5px solid red; margin-bottom:15px; border-radius:5px; }
    img.event-img { width:100px; height:100px; object-fit:cover; border-radius:6px; margin-top:10px; }
</style>
</head>
<body>
<div class="container">
    <a href="organizer_view.php" class="btn-back">⬅️ Back to Dashboard</a>
    <h1>Edit Event</h1>

    <?php if($error_message): ?>
        <div class="error-msg"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <?php if($success_message): ?>
        <div class="success-msg"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if($event): ?>
    <form method="POST" enctype="multipart/form-data">
        <label>Title</label>
        <input type="text" name="event_title" value="<?php echo htmlspecialchars($event['title']); ?>" required>

        <label>Description</label>
        <textarea name="event_description" required><?php echo htmlspecialchars($event['description']); ?></textarea>

        <label>Date</label>
        <input type="date" name="event_date" value="<?php echo $event['event_date']; ?>" required>

        <label>Time</label>
        <input type="time" name="event_time" value="<?php echo $event['event_time']; ?>" required>

        <label>Status</label>
        <select name="status" required>
            <option value="draft" <?php echo ($event['status']=='draft')?'selected':''; ?>>Draft</option>
            <option value="published" <?php echo ($event['status']=='published')?'selected':''; ?>>Published</option>
        </select>

        <label>Event Image</label>
        <?php if($event['image_path']): ?>
            <img src="<?php echo $event['image_path']; ?>" class="event-img"><br>
        <?php endif; ?>
        <input type="file" name="event_image">

        <button type="submit" name="update_event">Update Event</button>
    </form>
    <?php endif; ?>

</div>
</body>
</html>
