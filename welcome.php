<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';

    if ($role === 'Admin') {
        header("Location: admin_dashboard.php");
        exit;
    }
    elseif ($role === 'Organizer') {
        header("Location: organizer_view.php");
        exit;
    }
    elseif ($role === 'Student') {
        header("Location: student_dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to EMS</title>
    <style>
         body {
            font-family: sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;

            /* Background image */
            background: url('welcome.jpg') no-repeat center center/cover;
            position: relative;
            color: white;
        }

        /* Header with logo */
        header {
            position: absolute;
            top: 0;
            left: 0;
            padding: 20px 20px;
        }

        header img {
            height: 50px;
            object-fit: contain;
        }

        .content {
            position: relative;
            padding: 50px;
            border-radius: 15px;
            z-index: 1;
        }

        h1 { 
            font-size: 2.1em; 
            color: #ffcc00; 
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px #000; /* for readability */
        }

        p {
            font-size: 18px;
            text-shadow: 2px 2px 4px #000;
            font-weight: bold;
        }

        .btn-enter {
            padding: 15px 30px;
            background-color: #38b2ac;
            color: white; 
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
        }

    </style>
</head>
<body>
    <!-- Header with logo -->
    <header>
        <img src="NTTF-logo.png" alt="EMS Logo">
    </header>

    <div class="content">
        <h1>Welcome to College Events Management System</h1>
        <p >Centralized system for managing all events.</p><br>
        <a href="index.php" class="btn-enter">Proceed to Login</a>
    </div>
</body>
</html>
