<?php
session_start();
require_once "db.php";

// ---------------------------------------------
// 1. CHECK LOGIN
// ---------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Student') {
    header("Location: student_login.php");
    exit();
}

$student_name       = $_SESSION['full_name'];
$student_email      = $_SESSION['email'];
$student_phone      = $_SESSION['phone'];
$student_department = $_SESSION['department'];
$student_semester   = $_SESSION['semester'];

// ---------------------------------------------
// 2. EVENT ID CHECK
// ---------------------------------------------
if (!isset($_POST['event_id'])) {
    die("Event ID missing.");
}

$event_id = intval($_POST['event_id']);

// ---------------------------------------------
// 3. GET EVENT DETAILS
// ---------------------------------------------
try {
    $event_stmt = $pdo->prepare("SELECT title FROM events WHERE id = ?");
    $event_stmt->execute([$event_id]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found.");
    }

    $event_title = $event["title"];
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}

// ---------------------------------------------
// 4. GENERATE TOKEN NUMBER
// ---------------------------------------------
$token_number = "TN-" . rand(1000, 9999);

// ---------------------------------------------
// 5. INSERT REGISTRATION
// ---------------------------------------------
try {
    $insert = $pdo->prepare("
        INSERT INTO registrations
        (event_id, student_full_name, student_personal_email, student_phone_no,
         token_number, student_department, student_semester, registered_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $insert->execute([
        $event_id,
        $student_name,
        $student_email,
        $student_phone,
        $token_number,
        $student_department,
        $student_semester
    ]);

} catch (Exception $e) {
    die("Registration Error: " . $e->getMessage());
}

// =====================================================
// 6. SEND CONFIRMATION EMAIL WITH PHPMailER
// =====================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {

    // SMTP settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;

    // CHANGE THESE TWO:
    $mail->Username   = 'YOUR_GMAIL@gmail.com';       // Your Gmail
    $mail->Password   = 'YOUR_APP_PASSWORD';          // Gmail App Password

    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Sender
    $mail->setFrom('YOUR_GMAIL@gmail.com', 'College Event Management System');
    $mail->addReplyTo('YOUR_GMAIL@gmail.com');

    // Receiver
    $mail->addAddress($student_email, $student_name);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = "Registration Confirmed: $event_title";

    $mail->Body = "
        <div style='font-family: Arial; padding:15px; background:#f3f3f3; border-radius:10px;'>
            <h2 style='color:green;'>Registration Successful</h2>

            <p>Hello <strong>$student_name</strong>,</p>
            <p>You have successfully registered for the event:</p>

            <h3>$event_title</h3>
            <hr>

            <p><strong>Token Number:</strong> $token_number</p>
            <p><strong>Department:</strong> $student_department</p>
            <p><strong>Semester:</strong> $student_semester</p>

            <hr>
            <p>You will receive all event/project updates soon. Please keep your token number safe.</p>

            <br>
            Regards,<br>
            <strong>College Event Management System</strong>
        </div>
    ";

    $mail->AltBody = "
Registration Successful!

Hello $student_name,

Event: $event_title
Token: $token_number
Department: $student_department
Semester: $student_semester

-- College Event Management System
";

    $mail->send();

} catch (Exception $e) {
    error_log("Email Error: " . $mail->ErrorInfo);
}

// ---------------------------------------------
// 7. REDIRECT TO SUCCESS PAGE
// ---------------------------------------------
header("Location: student_success.php?registered=1");
exit();

?>
