<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Thank You - Noah Waters</title>
  <link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Boogaloo', cursive;
      background: #e0f7ff;
      margin: 0;
      padding: 0;
      text-align: center;
      background-image: url('back.webp');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
      color: #002e6d;
    }

    .container {
      max-width: 700px;
      margin: 100px auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    h1 {
      font-size: 3rem;
      margin-bottom: 20px;
      color: #004aad;
    }

    p {
      font-size: 1.3rem;
      margin-bottom: 30px;
    }

    a {
      display: inline-block;
      padding: 12px 25px;
      background-color: #004aad;
      color: white;
      border-radius: 8px;
      text-decoration: none;
      font-size: 1.2rem;
      transition: background-color 0.3s ease;
    }

    a:hover {
      background-color: #0065cc;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🎉 Thank You!</h1>
    <p>Your order has been placed successfully. We’ll get started on it right away.</p>

    <?php if ($isLoggedIn): ?>
      <a href="track_order_user.php">📦 Track Your Orders</a>
    <?php else: ?>
      <a href="track_order_guest.php">📦 Track as Guest</a>
    <?php endif; ?>

    <br><br>
    <a href="menu.php">🛍️ Continue Shopping</a>
  </div>
</body>
</html>
