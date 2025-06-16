<?php
require 'config.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $newPassword, $token);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "<script>alert('Password updated successfully.'); window.location.href = 'login.php';</script>";
    } else {
        echo "<script>alert('Invalid or expired reset token. Please request a new password reset link.'); window.location.href = 'forgot_password.php';</script>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Noah Waters</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #79c7ff;
            margin: 0;
            padding: 0;
            font-family: "Boogaloo", sans-serif;
            background-image: url('back.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .reset-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 1.5rem;
        }

        .login-logo {
            width: 120px;
            height: auto;
            border-radius: 50%;
        }

        h2 {
            color: #112752;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .input-group {
            position: relative;
            width: 100%;
            max-width: 300px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #112752;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #112752;
            border-radius: 25px;
            font-size: 1rem;
            font-family: "Boogaloo", sans-serif;
            outline: none;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
            border-color: #79c7ff;
            box-shadow: 0 0 10px rgba(121, 199, 255, 0.3);
        }

        button {
            background-color: #112752;
            color: #79c7ff;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1.2rem;
            font-family: "Boogaloo", sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 300px;
        }

        button:hover {
            background-color: #1a3a6e;
            transform: translateY(-2px);
        }

        p {
            color: #112752;
            font-size: 1.1rem;
            margin: 1rem 0;
        }

        a {
            color: #112752;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #79c7ff;
        }

        @media (max-width: 480px) {
            .reset-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="logo-container">
            <img src="logo.jpg" alt="Noah Waters Logo" class="login-logo">
        </div>

        <?php if ($token): ?>
            <h2>Reset Password</h2>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="new_password" required placeholder="Enter new password">
                </div>
                <button type="submit">Reset Password</button>
            </form>
        <?php else: ?>
            <h2>Invalid Reset Link</h2>
            <p>The password reset link is invalid or has expired.</p>
            <p><a href="forgot_password.php">Request a new reset link</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
