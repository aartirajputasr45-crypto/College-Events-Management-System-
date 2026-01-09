<?php
session_start();
require_once 'db.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submitted_id = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($submitted_id) || empty($password)) {
        $error_message = "❌ Both fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT user_id, full_name, username, password, user_role 
                FROM users 
                WHERE username = ?
            ");
            $stmt->execute([$submitted_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];

                if ($user['user_role'] == 'Admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['user_role'] == 'Organizer') {
                    header("Location: organizer_view.php");
                } elseif ($user['user_role'] == 'Student') {
                    header("Location: student_dashboard.php");
                }
                exit();
            } else {
                $error_message = "❌ Invalid ID or Password.";
            }

        } catch (PDOException $e) {
            $error_message = "❌ Database Error: " . $e->getMessage();
        }
    }
}

$_SESSION['login_error'] = $error_message;
header("Location: index.php");
exit();
?>
