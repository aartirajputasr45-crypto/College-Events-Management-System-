<?php
session_start();
require_once 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // path to PHPMailer autoload.php

// Check login & role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Organizer') {
    header("Location: index.php");
    exit();
}

// Get registration ID
if (!isset($_GET['id'])) {
    die("Registration ID not specified.");
}
$registration_id = intval($_GET['id']);

// Fetch registration + event details
$stmt = $pdo->prepare("
    SELECT r.student_full_name, r.student_personal_email, e.title, e.event_date, e.venue, e.description
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    WHERE r.registration_id = ?
");
$stmt->execute([$registration_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Registration not found.");
}

// Prepare email
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com'; // your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@example.com';
    $mail->Password = 'your_email_password';
    $mail->SMTPSecure = 'tls'; // or 'ssl'
    $mail->Port = 587; // or 465 for SSL

    // Recipients
    $mail->setFrom('your_email@example.com', 'Event Organizer');
    $mail->addAddress($data['student_personal_email'], $data['student_full_name']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Confirmation: {$data['title']}";
    $mail->Body = "
        <h2>Event Registration Confirmed!</h2>
        <p>Dear {$data['student_full_name']},</p>
        <p>Your registration for the event <strong>{$data['title']}</strong> has been confirmed.</p>
        <p><strong>Event Details:</strong></p>
        <ul>
            <li>Date: {$data['event_date']}</li>
            <li>Venue: {$data['venue']}</li>
            <li>Description: {$data['description']}</li>
        </ul>
        <p>We look forward to your participation!</p>
    ";

    $mail->send();

    // Update database
    $update = $pdo->prepare("UPDATE registrations SET confirmation_sent = 'Sent' WHERE registration_id = ?");
    $update->execute([$registration_id]);

    echo "<script>alert('Confirmation email sent successfully.'); window.location='organizer_view_registrations.php?event_id={$data['id']}';</script>";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
