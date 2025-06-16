<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $shippingMethod = $_POST['shipping_method'] ?? 'Delivery';
    $pickupTime = ($shippingMethod === 'Pickup') ? ($_POST['pickup_time'] ?? '') : null;
    $deliveryAddress = $_POST['delivery_address'] ?? '';
    $notes = $_POST['notes'] ?? '';

    //kumukuha ng cart items
    $stmt = $conn->prepare("SELECT c.*, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($items)) {
        die("Cart is empty.");
    }

    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    //check if user is new
    $is_new_user = 0;
    $stmt = $conn->prepare("SELECT is_new_user FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($is_new_user);
    $stmt->fetch();
    $stmt->close();

    //fetch users fullname and phone
    $stmt = $conn->prepare("SELECT fullname, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($fullname, $phone);
    $stmt->fetch();
    $stmt->close();

    //nag insert ng order with is_new_user_order para dun sa banner usertype, fullname at phone
    $stmt = $conn->prepare("INSERT INTO orders (user_id, fullname, phone, total_amount, shipping_method, pickup_time, delivery_address, is_new_user_order, usertype, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user', ?)");
    $stmt->bind_param("issdsssss", $userId, $fullname, $phone, $total, $shippingMethod, $pickupTime, $deliveryAddress, $is_new_user, $notes);
    if (!$stmt->execute()) {
        die("Failed to create order: " . $stmt->error);
    }
    $orderId = $stmt->insert_id;
    $stmt->close();

    //insert each item
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    $stmt->close();

    //ito naghahandle ng borrowed containers
    $checkProductStmt = $conn->prepare("SELECT category, is_borrowable FROM products WHERE id = ?");
    $borrowStmt = $conn->prepare("INSERT INTO borrowed_containers (user_id, order_id, container_id, borrowed_at, returned) VALUES (?, ?, ?, NOW(), 0)");

    if (!$checkProductStmt || !$borrowStmt) {
        die("Prepare failed for borrowed containers: " . $conn->error);
    }

    $hasBorrowedInThisOrder = false;
    foreach ($items as $item) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];

        //nagcheck ng product category at borrowable status ng item
        $checkProductStmt->bind_param("i", $productId);
        $checkProductStmt->execute();
        $checkProductStmt->bind_result($category, $isBorrowable);
        $checkProductStmt->fetch();
        $checkProductStmt->reset();

        if (strtolower($category) === 'container' && $isBorrowable) {
            $hasBorrowedInThisOrder = true;
            for ($i = 0; $i < $quantity; $i++) {
                $borrowStmt->bind_param("iii", $userId, $orderId, $productId);
                if (!$borrowStmt->execute()) {
                    die("Failed to insert borrowed container: " . $borrowStmt->error);
                }
            }
        }
    }

    $checkProductStmt->close();
    $borrowStmt->close();

    // Only set is_new_user = 0 if a borrowable container was ordered
    if ($is_new_user && $hasBorrowedInThisOrder) {
        $stmt = $conn->prepare("UPDATE users SET is_new_user = 0 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Clear user's cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    header("Location: thank_you.php");
    exit;
}
?>