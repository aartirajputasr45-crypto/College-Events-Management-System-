<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Student') {
    header("Location: student_login.php");
    exit();
}
$student_id = $_SESSION['user_id'];
$message = "";
// -------------------- REGISTRATION PROCESS --------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_event'])) {
    $event_id = $_POST['event_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dept = trim($_POST['department']);
    $sem = trim($_POST['semester']);
    $token = trim($_POST['token_number']);
    if (empty($full_name) || empty($email) || empty($token)) {
        $message = "<p class='error'>❌ Full name, email & token number are required.</p>";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO registrations 
            (event_id, student_id, student_full_name, student_personal_email, student_phone_no, student_department, student_semester, token_number)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$event_id, $student_id, $full_name, $email, $phone, $dept, $sem, $token]);
        $message = "<p class='success'>✅ Registered Successfully!</p>";
    }
}
// -------------------- FETCH PUBLISHED EVENTS --------------------
// Explicitly select event_venue
$events = $pdo->query("
    SELECT id, title, description, event_date, event_time, event_venue, image_path 
    FROM events 
    WHERE status='published' 
    ORDER BY event_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Dashboard</title>
    <style>
        body { 
            margin: 0; 
            font-family: Arial, sans-serif; 
            background: url('sixth.jpg') no-repeat center center/cover;
            color: #000;
        }
        .navbar {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            background: #0b1e78; 
            padding: 12px 20px; 
            color: white;
        }
        .navbar h1 { margin: 0; font-size: 22px; }
        .navbar a { 
            color: white; 
            text-decoration: none; 
            background: #dc3545; 
            padding: 6px 12px; 
            border-radius: 4px; 
        }
        .main { 
            display: flex; 
            justify-content: center;
            min-height: 90vh; 
        }
        .content { 
            width: 80%; 
            padding: 20px; 
        }
        .event-box {
            background: #ffffff;
            width: 45%;
            margin: 25px auto;
            padding: 15px;
            border-left: 5px solid #0b1e78;
            border-radius: 8px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.25);
        }
        img.event-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .event-title { 
            font-size: 18px; 
            font-weight: bold; 
            color: #0b1e78; 
            margin-top: 10px; 
        }
        .btn-register { 
            background: #0b1e78; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer; 
            margin-top: 8px;
        }
        .register-form {
            display: none; 
            margin-top: 12px;
            background: #eef0ff; 
            padding: 12px; 
            border-radius: 6px; 
        }
        input, select { 
            width: 100%; 
            padding: 8px; 
            margin-top: 6px; 
            margin-bottom: 10px;
            border: 1px solid #888; 
            border-radius: 4px; 
        }
        .success { 
            background: #d4edda; 
            padding: 10px; 
            border-left: 4px solid #28a745; 
        }
        .error { 
            background: #f8d7da; 
            padding: 10px; 
            border-left: 4px solid #dc3545; 
        }
    </style>
    <script>
        function openForm(eventId) {
            document.querySelectorAll(".register-form").forEach(f => f.style.display = "none");
            document.getElementById("form-" + eventId).style.display = "block";
        }
        function cancelForm(eventId) {
            document.getElementById("form-" + eventId).style.display = "none";
        }
    </script>
</head>
<body>
<div class="navbar">
    <h1>Student Dashboard</h1>
    <a href="logout.php">Logout</a>
</div>
<div class="main">
    <div class="content">
        <?php echo $message; ?>
        <h2 style="color:white">📌 Available Events</h2>
        <?php if (empty($events)) : ?>
            <p>No events available right now.</p>
        <?php else: foreach ($events as $e): ?>
            <div class="event-box">
                <?php if (!empty($e['image_path'])): ?>
                    <img src="<?= htmlspecialchars($e['image_path']) ?>" class="event-image">
                <?php endif; ?>
                <div class="event-title"><?= htmlspecialchars($e['title']); ?></div>
                <p><?= htmlspecialchars($e['description']); ?></p>
                <p><strong>Date:</strong> <?= $e['event_date']; ?></p>
                <p><strong>Time:</strong> <?= $e['event_time']; ?></p>
                <p><strong>Venue:</strong> <?= htmlspecialchars($e['event_venue']); ?></p> <!-- Display Venue -->
                <button class="btn-register" onclick="openForm(<?= $e['id']; ?>)">Register</button>
                <div class="register-form" id="form-<?= $e['id']; ?>">
                    <form method="POST">
                        <input type="hidden" name="register_event" value="1">
                        <input type="hidden" name="event_id" value="<?= $e['id']; ?>">
                        <input type="text" name="full_name" placeholder="Your Full Name" required>
                        <input type="email" name="email" placeholder="Your Personal Email" required>
                        <input type="text" name="phone" placeholder="Phone Number">
                        <input type="text" name="department" placeholder="Department">
                        <input type="text" name="semester" placeholder="Semester">
                        <input type="text" name="token_number" placeholder="Token Number (College ID)" required>
                        <div style="display:flex; gap:4%;">
                            <button type="submit" class="btn-register" style="flex:1; background:#28a745;">
                                Confirm Registration
                            </button>
                            <button type="button" class="btn-register" 
                                    style="flex:1; background:#dc3545;" 
                                    onclick="cancelForm(<?= $e['id']; ?>)">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
</body>
</html>
