<?php
session_start();
require 'config.php';
require 'send_email.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    //check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));


        //save token and expiry of link
    $stmt->close();
    if (!$conn) {
            die("Database connection failed: " . mysqli_connect_error());
        }

        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        //create reset link
        $resetLink = "http://localhost/Noah_Waters/reset_password.php?token=" . urlencode($token);

        if (sendResetEmail($email, $resetLink)) {
            $message = "A password reset link has been sent to your email.";
        } else {
            $message = "Failed to send email. Please try again later.";
        }
    } else {
        $message = "No account found with that email.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Noah Waters</title>
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

        .forgot-container {
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

        h3 {
            color: #112752;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .alert-info {
            background-color: #e3f2fd;
            color: #0d47a1;
            border: 1px solid #bbdefb;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
            
        }

        .form-label {
            display: block;
            color: #112752;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #112752;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #112752;
            border-radius: 25px;
            font-size: 1rem;
            font-family: "Boogaloo", sans-serif;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #79c7ff;
            box-shadow: 0 0 10px rgba(121, 199, 255, 0.3);
        }

        .btn-primary {
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
            margin-top: 1rem;
        }

        .btn-primary:hover {
            background-color: #1a3a6e;
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #112752;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #79c7ff;
        }

        @media (max-width: 480px) {
            .forgot-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            h3 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="logo-container">
            <img src="logo.jpg" alt="Noah Waters Logo" class="login-logo">
        </div>
        <h3>Forgot Password</h3>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Enter your email address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn-primary">Send Reset Link</button>
        </form>

        <a href="login.php" class="back-link">Back to login</a>
    </div>
</body>
</html>
