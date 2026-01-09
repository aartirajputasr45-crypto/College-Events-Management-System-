<?php
session_start();
require_once 'db.php';

// Security: Only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Get organizer ID from query
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Optional: Prevent Admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        die("❌ You cannot delete yourself!");
    }

    // Delete organizer
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND user_role='Organizer'");
    $stmt->execute([$user_id]);

    // Optional: Also delete events created by this organizer
    $stmt2 = $pdo->prepare("DELETE FROM events WHERE organizer_id = ?");
    $stmt2->execute([$user_id]);

    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: admin_dashboard.php");
    exit();
}
