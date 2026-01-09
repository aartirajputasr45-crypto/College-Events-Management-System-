<?php
// admin_view_registrations.php - Admin: View Students Registered for Events
session_start();
require_once 'db.php'; 

// --- 1. Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: index.php"); 
    exit(); 
}

$admin_id = $_SESSION['user_id'];
$message = '';
$error_message = '';

// --- 2. Event ID Validation ---
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$event_id = $_GET['event_id'];

// --- 3. Fetch Event Details ---
try {
    $event_stmt = $pdo->prepare("SELECT e.title, u.full_name as organizer_name FROM events e LEFT JOIN users u ON e.organizer_id = u.user_id WHERE e.id = ?");
    $event_stmt->execute([$event_id]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $error_message = "❌ Event not found.";
        $registrations = [];
        $event_title = "Unknown Event";
    } else {
        $event_title = htmlspecialchars($event['title']);
        $organizer_name = htmlspecialchars($event['organizer_name'] ?? 'N/A');

        // --- 4. Fetch Registered Students ---
        $reg_stmt = $pdo->prepare("
            SELECT 
                r.registration_id,
                r.student_full_name,
                r.student_personal_email,
                r.student_phone_no,
                r.student_department,
                r.student_semester,
                r.registered_at,
                r.token_number,
                r.confirmation_sent
            FROM registrations r
            WHERE r.event_id = ?
            ORDER BY r.registered_at DESC
        ");
        $reg_stmt->execute([$event_id]);
        $registrations = $reg_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error_message = "❌ Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registered Students - <?php echo $event_title; ?></title>
<style>
body { font-family:sans-serif; background-color:#1a1a2e; color:#e9e9e9; }
.container { max-width:1400px; margin:30px auto; padding:30px; background-color:#2a2a4e; border-radius:10px; box-shadow:0 8px 20px rgba(0,0,0,0.5); }
.header { display:flex; justify-content:space-between; align-items:center; border-bottom:3px solid #FFC107; padding-bottom:15px; margin-bottom:25px; }
h1 { color:#FFC107; font-size:2.2em; margin:0; }
.btn-back { background-color:#00BCD4; color:white; padding:10px 15px; text-decoration:none; border-radius:5px; }
.data-table { width:100%; border-collapse:collapse; margin-top:20px; font-size:0.9em; }
.data-table th, .data-table td { padding:10px; text-align:left; border-bottom:1px solid #555; }
.data-table th { background-color:#00BCD4; color:#1a1a2e; font-weight:bold; }
.data-table tr:nth-child(even) { background-color:#2a2a4e; }
.success-msg { color:#d4edda; background-color:#1a3e1a; border:1px solid #4CAF50; padding:10px; border-radius:4px; text-align:center; margin-bottom:15px; }
.error-msg { color:#f8d7da; background-color:#4e2e2e; border:1px solid #dc3545; padding:10px; border-radius:4px; text-align:center; margin-bottom:15px; }
.status-sent { color:#4CAF50; font-weight:bold; }
.status-pending { color:#FFA500; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>Students Registered for "<?php echo $event_title; ?>"</h1>
<a href="admin_dashboard.php" class="btn-back">⬅️ Back to Dashboard</a>
</div>

<?php if($message) echo '<div class="success-msg">'.$message.'</div>'; ?>
<?php if($error_message) echo '<div class="error-msg">'.$error_message.'</div>'; ?>

<p>Organizer: <strong><?php echo $organizer_name; ?></strong></p>
<p>Total Registered Students: <strong><?php echo count($registrations); ?></strong></p>

<?php if(empty($registrations)): ?>
<p>No students have registered for this event yet.</p>
<?php else: ?>
<table class="data-table">
<thead>
<tr>
<th>#</th>
<th>Student Name</th>
<th>Email (Personal)</th>
<th>Phone No.</th>
<th>Dept / Sem</th>
<th>Token No.</th>
<th>Registered On</th>
<th>Confirmation</th>
</tr>
</thead>
<tbody>
<?php $count=1; foreach($registrations as $reg): ?>
<tr>
<td><?php echo $count++; ?></td>
<td><?php echo htmlspecialchars($reg['student_full_name']); ?></td>
<td><?php echo htmlspecialchars($reg['student_personal_email']); ?></td>
<td><?php echo htmlspecialchars($reg['student_phone_no']); ?></td>
<td><?php echo htmlspecialchars($reg['student_department']).' / '.htmlspecialchars($reg['student_semester']); ?></td>
<td><?php echo htmlspecialchars($reg['token_number']); ?></td>
<td><?php echo date('M j, Y H:i A', strtotime($reg['registered_at'])); ?></td>
<td>
<?php if($reg['confirmation_sent']=='Yes'): ?>
<span class="status-sent">✅ Sent</span>
<?php else: ?>
<span class="status-pending">⏳ Pending</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</body>
</html>
