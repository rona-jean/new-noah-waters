<?php
session_start();

// Check if user is logged in and role is 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    // Redirect to login if not authorized
    header('Location: login.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Dashboard - Noah Waters</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <?php include 'navbar_loggedin_users.php'; ?>

    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h1>
        <p>This is your user dashboard.</p>

        <nav>
            <ul>
                <li><a href="menu.php">Browse Products</a></li>
                <li><a href="cart.php">View Cart</a></li>
                <!-- add more user links here -->
            </ul>
        </nav>

        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
