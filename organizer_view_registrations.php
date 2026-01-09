<?php
// organizer_view_registrations.php
session_start();
require_once 'db.php'; 

// Check if user is logged in and is an Organizer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Organizer') {
    header("Location: index.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$organizer_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); 

// Get event_id from URL
if (!isset($_GET['event_id'])) {
    die("Event ID not specified.");
}
$event_id = intval($_GET['event_id']);

// Fetch event details (to show title)
try {
    $event_stmt = $pdo->prepare("SELECT title FROM events WHERE id = ? AND organizer_id = ?");
    $event_stmt->execute([$event_id, $organizer_id]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found or you do not have permission to view it.");
    }
    $event_title = htmlspecialchars($event['title']);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Fetch registrations
try {
    $registrations_stmt = $pdo->prepare("
        SELECT 
            student_full_name, 
            student_personal_email, 
            student_phone_no, 
            token_number, 
            student_department, 
            student_semester, 
            registered_at
        FROM registrations
        WHERE event_id = ?
        ORDER BY registered_at ASC
    ");
    $registrations_stmt->execute([$event_id]);
    $registrations = $registrations_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database Fetch Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registrations for "<?php echo $event_title; ?>"</title>
    <style>
        body { font-family: sans-serif; background-color: #1a1a2e; color: #e9e9e9; padding: 20px; }
        h1 { color: #FFC107; }
        a.btn-back { background-color: #00BCD4; color: #1a1a2e; padding: 8px 12px; text-decoration: none; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #555; text-align: left; }
        th { background-color: #00BCD4; color: #1a1a2e; }
        tr:nth-child(even) { background-color: #2a2a4e; }
    </style>
</head>
<body>
    <h1>Registrations for "<?php echo $event_title; ?>"</h1>
    <a href="organizer_view.php" class="btn-back">⬅️ Back to Events</a>

    <?php if (empty($registrations)): ?>
        <p>No students have registered for this event yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Token Number</th>
                    <th>Department</th>
                    <th>Semester</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $reg): ?>
                <tr>
                    <td><?php echo htmlspecialchars($reg['student_full_name']); ?></td>
                    <td><?php echo htmlspecialchars($reg['student_personal_email']); ?></td>
                    <td><?php echo htmlspecialchars($reg['student_phone_no']); ?></td>
                    <td><?php echo htmlspecialchars($reg['token_number']); ?></td>
                    <td><?php echo htmlspecialchars($reg['student_department']); ?></td>
                    <td><?php echo htmlspecialchars($reg['student_semester']); ?></td>
                    <td><?php echo date('F j, Y, h:i A', strtotime($reg['registered_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
