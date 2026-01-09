<?php
session_start();
require_once 'db.php';

// Only Admin Allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];

    // Delete event registrations
    $stmt = $pdo->prepare("DELETE FROM registrations WHERE event_id = ?");
    $stmt->execute([$event_id]);

    // Delete the event
    $stmt2 = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt2->execute([$event_id]);

    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: admin_dashboard.php");
    exit();
}
