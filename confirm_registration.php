<?php
// confirm_registration.php - Handles confirmation update and email sending
session_start();
require_once 'db.php'; // Your PDO connection file

// --- 1. PHPMailer Includes (UPDATE THESE PATHS!) ---
// --- 1. PHPMailer Includes (CORRECTED PATHS) ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php'; // Corrected path
require 'PHPMailer/src/PHPMailer.php'; // Corrected path
require 'PHPMailer/src/SMTP.php'; // Corrected path


// --- 2. SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: index.php"); 
    exit(); 
}

// --- 3. INPUT VALIDATION ---
if (!isset($_GET['reg_id']) || !is_numeric($_GET['reg_id'])) {
    header("Location: admin_dashboard.php?error=" . urlencode("Invalid registration ID provided."));
    exit;
}

$registration_id = (int)$_GET['reg_id'];
$event_id_to_redirect = 0; 

try {
    // Start Transaction
    $pdo->beginTransaction(); 

    // --- 4. DATABASE UPDATE (Set confirmation_sent = 'Yes') ---
    // Only update if it is currently 'No' (or equivalent) to prevent accidental resends
    $update_stmt = $pdo->prepare("UPDATE registrations SET confirmation_sent = 'Yes' WHERE registration_id = ? AND confirmation_sent != 'Yes'");
    $update_stmt->execute([$registration_id]);

    // Check if a row was updated (if not, it was already confirmed or not found)
    if ($update_stmt->rowCount() === 0) {
        // Fetch event_id for redirect before throwing exception
        $check_stmt = $pdo->prepare("SELECT event_id FROM registrations WHERE registration_id = ?");
        $check_stmt->execute([$registration_id]);
        $check_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $event_id_to_redirect = $check_data ? $check_data['event_id'] : 0;
        
        // If no row was updated, we assume it was already confirmed. We still commit the transaction (or skip rollback)
        // and redirect with a warning.
        $pdo->commit(); 
        $warning_msg = urlencode("Registration was already confirmed. No email sent.");
        header("Location: admin_view_registrations.php?event_id={$event_id_to_redirect}&message={$warning_msg}");
        exit;
    }
    
    // --- 5. FETCH DETAILS FOR EMAIL ---
    $fetch_stmt = $pdo->prepare("
        SELECT 
            r.student_full_name, r.student_personal_email, r.token_number, r.event_id, e.title 
        FROM registrations r 
        JOIN events e ON r.event_id = e.id
        WHERE r.registration_id = ? 
    ");
    $fetch_stmt->execute([$registration_id]);
    $reg_data = $fetch_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reg_data) {
        $pdo->rollBack();
        throw new Exception("Student details for email not found.");
    }
    
    $event_id_to_redirect = $reg_data['event_id'];


    // --- 6. SEND EMAIL (PHPMailer Configuration - UPDATE THESE SETTINGS!) ---
    $mail = new PHPMailer(true);
    // Server settings 
    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com'; // E.g., 'smtp.sendgrid.net' or 'smtp.gmail.com'
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your_smtp_username'; // E.g., 'apikey' or your email
    $mail->Password   = 'your_smtp_password'; // E.g., your actual SMTP/App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SMTPS (port 465) or STARTTLS (port 587)
    $mail->Port       = 465;

    // Recipients
    $mail->setFrom('admin@yourapp.com', 'Event Organizing Team');
    $mail->addAddress($reg_data['student_personal_email'], $reg_data['student_full_name']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = '✅ Confirmation: Your Registration for ' . htmlspecialchars($reg_data['title']);
    $mail->Body    = "
        <h2>Hello " . htmlspecialchars($reg_data['student_full_name']) . ",</h2>
        <p>This is a confirmation that your registration for the event <b>\"" . htmlspecialchars($reg_data['title']) . "\"</b> has been officially approved and confirmed.</p>
        
        <p><b>Your Confirmation Details:</b></p>
        <ul>
            <li>Registration ID: <b>{$registration_id}</b></li>
            <li>Token Number: <b>" . htmlspecialchars($reg_data['token_number']) . "</b></li>
        </ul>

        <p>We look forward to seeing you there!</p>
        <p>Best Regards,<br>The Organizing Team</p>
    ";

    $mail->send();
    
    $pdo->commit(); // Commit only if update and email succeeded

    // --- 7. SUCCESS REDIRECT ---
    $success_msg = urlencode("Confirmation email sent successfully to " . htmlspecialchars($reg_data['student_full_name']) . "!");
    header("Location: admin_view_registrations.php?event_id={$event_id_to_redirect}&message={$success_msg}");
    exit;

} catch (Exception $e) {
    // --- 8. ERROR HANDLING AND REDIRECT ---
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $error_msg = urlencode("Confirmation failed: " . $e->getMessage());
    $redirect_event_id = $event_id_to_redirect ?: (isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0);
    
    header("Location: admin_view_registrations.php?event_id={$redirect_event_id}&error_message={$error_msg}");
    exit;
}
?>