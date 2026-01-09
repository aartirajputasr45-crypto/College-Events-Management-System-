<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'Admin') {
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($role === 'Organizer') {
        header("Location: organizer_view.php");
        exit;
    } elseif ($role === 'Student') {
        header("Location: student_dashboard.php");
        exit;
    }
}

$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EMS - Login</title>

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
            background: url('2event.jpg') no-repeat center center/cover;
            position: relative;
            color: white;
        }

        /* Logo at top-left */
        header {
            position: absolute;
            top: 15px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 2;
        }

        header img {
            height: 50px; /* logo size */
        }

        header .logo-text {
            font-size: 1.5em;
            font-weight: bold;
            color: #ffcc00;
        }

        /* Login container */
        .container {
            position: relative;
            z-index: 1;
            width: 400px;
            padding: 35px;
            background: #2a2a4e; /* fully solid color */
            border-radius: 10px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: #3e3e6e;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .btn-login {
            background: #38b2ac;
            color: white;
            padding: 13px;
            border: none;
            width: 100%;
            border-radius: 5px;
            margin-top: 15px;
            cursor: pointer;
        }

        a {
            color: #bbb;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <!-- Header with logo + text -->
    <header>
        <img src="NTTF-logo.png" alt="EMS Logo">
    </header>

    <div class="container">
        <h2>🔑 System Login</h2>

        <?php if ($login_error): ?>
            <div style="background:#722d3b; padding:10px; color:#ffb3b3; margin-bottom:10px;">
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <form action="process_login.php" method="POST">
            <label>Email / Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button class="btn-login">Login</button>
        </form>

        <p style="text-align:center;margin-top:10px;">
            <a href="welcome.php">← Back</a>
        </p>
    </div>
</body> 
</html>
