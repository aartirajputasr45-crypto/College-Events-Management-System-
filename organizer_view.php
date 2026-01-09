<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Organizer') {
    header("Location: index.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$organizer_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
$error_message = "";
$success_message = "";

try {
    $events_stmt = $pdo->prepare("
        SELECT 
            e.*,
            COUNT(r.registration_id) AS num_registrations
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.organizer_id = ?
        GROUP BY 
            e.id, e.title, e.description, e.event_date,
            e.event_time, e.event_venue, e.image_path, e.status, e.organizer_id
        ORDER BY e.event_date DESC
    ");
    $events_stmt->execute([$organizer_id]);
    $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Events Management Panel | <?php echo $organizer_name; ?></title>

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
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.55);
        z-index: 0;
    }

    .container {
        position: relative;
        z-index: 2;
        max-width: 1300px;
        margin: 40px auto;
        padding: 30px;
        background: rgba(0, 0, 0, 0.55);
        border-radius: 12px;
        backdrop-filter: blur(4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    }

    .header-dash {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 15px;
        margin-bottom: 25px;
        border-bottom: 3px solid #FFC107;
    }

    h1 {
        color: #FFC107;
        margin: 0;
    }

    .btn-logout {
        background: blue;
        padding: 10px 18px;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
    }

    .btn-logout:hover {
        background: #c82333;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95em;
    }

    .data-table th {
        background: #FFC107;
        color: #000;
        padding: 12px;
        text-align: left;
    }

    .data-table td {
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    .status-published {
        background: #28a745;
        padding: 5px 8px;
        border-radius: 5px;
        color: white;
    }

    .status-draft {
        background: #FF9800;
        padding: 5px 8px;
        border-radius: 5px;
        color: black;
    }

    .error-msg {
        background: #8b1e1e;
        padding: 10px;
        color: #ffdddd;
        border-left: 5px solid red;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
        color: white;
        margin-right: 5px;
        font-size: 0.85em;
    }

    .btn-edit { background-color: blue; }
    .btn-edit:hover { background-color: #e0a800; }

    .btn-delete { background-color: blue; }
    .btn-delete:hover { background-color: #c82333; }

</style>
</head>
<body>

<div class="container">

    <div class="header-dash">
        <h1>Organizer Dashboard (<?php echo $organizer_name; ?>)</h1>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <?php if ($error_message): ?>
        <div class="error-msg"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <h2 style="color:#FFD75F;">📅 Your Created Events</h2>

    <?php if (empty($events)): ?>
        <p>You haven't created any events yet.</p>
    <?php else: ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Date & Time</th>
                    <th>Venue</th> <!-- Added Venue Column -->
                    <th>Registrations</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo $event['id']; ?></td>

                    <td>
                        <?php if ($event['image_path']): ?>
                            <img src="<?php echo $event['image_path']; ?>" 
                                 style="width:55px;height:55px;border-radius:6px;object-fit:cover;">
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>

                    <td><?php echo htmlspecialchars($event['title']); ?></td>

                    <td><?php echo date('M d, Y - h:i A', strtotime($event['event_date'].' '.$event['event_time'])); ?></td>

                    <td><?php echo htmlspecialchars($event['event_venue']); ?></td> <!-- Show Venue -->

                    <td>
                        <a style="color:#00E5FF;font-weight:bold;" 
                           href="organizer_view_registrations.php?event_id=<?php echo $event['id']; ?>">
                            <?php echo $event['num_registrations']; ?> Students
                        </a>
                    </td>

                    <td>
                        <span class="<?php echo ($event['status'] === 'published') ? 'status-published' : 'status-draft'; ?>">
                            <?php echo ucfirst($event['status']); ?>
                        </span>
                    </td>

                    <td>
                        <a href="organizer_edit_event.php?id=<?php echo $event['id']; ?>" class="action-btn btn-edit">✏️ Edit</a>
                        <a href="organizer_delete_event.php?id=<?php echo $event['id']; ?>" 
                           class="action-btn btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this event?');">
                           🗑️ Delete
                        </a>
                    </td>

                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

</body>
</html>
