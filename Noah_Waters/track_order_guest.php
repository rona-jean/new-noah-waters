<?php
session_start();
include 'config.php';

$orders = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //order cancellation
    if (isset($_POST['cancel_order_id'])) {
        $cancelId = intval($_POST['cancel_order_id']);
        $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND usertype = 'guest'");
        $stmt->bind_param("i", $cancelId);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF'] . "?fullname=" . urlencode($_POST['fullname']) . "&phone=" . urlencode($_POST['phone']));
        exit;
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fullname) && empty($phone)) {
        $error = "Please enter your full name or phone number.";
    } else {
        //orders for the guests
        $orders_sql = "SELECT o.*, u.fullname AS user_fullname 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       WHERE (o.fullname = ? OR o.phone = ?) AND o.usertype = 'guest' 
                       ORDER BY o.created_at DESC";

        error_log("Orders SQL: " . $orders_sql);

        $orders_stmt = $conn->prepare($orders_sql);
        if ($orders_stmt) {
            $orders_stmt->bind_param("ss", $fullname, $phone);
            $orders_stmt->execute();
            $orders_result = $orders_stmt->get_result();
            
            error_log("Found " . $orders_result->num_rows . " orders");
            
            if ($orders_result && $orders_result->num_rows > 0) {
                while ($order = $orders_result->fetch_assoc()) {
             
                    $items_sql = "SELECT oi.*, p.name, p.price 
                                 FROM order_items oi 
                                 LEFT JOIN products p ON oi.product_id = p.id 
                                 WHERE oi.order_id = ?";
                    
                    error_log("Items SQL for order " . $order['id'] . ": " . $items_sql);
                    
                    $items_stmt = $conn->prepare($items_sql);
                    if ($items_stmt) {
                        $items_stmt->bind_param("i", $order['id']);
                        $items_stmt->execute();
                        $items_result = $items_stmt->get_result();
                        
                        error_log("Order #" . $order['id'] . " has " . $items_result->num_rows . " items");
                        
                        $items = [];
                        $total = 0;
                        
                        while ($item = $items_result->fetch_assoc()) {
                            $items[] = $item;
                            $total += $item['quantity'] * $item['price'];
                            error_log("  Item: " . $item['name'] . " (Qty: " . $item['quantity'] . ", Price: " . $item['price'] . ")");
                        }
                        
                        $order['items'] = $items;
                        $order['total'] = $total;
                        $orders[] = $order;
                        
                        $items_stmt->close();
                    } else {
                        error_log("Prepare failed for items: " . $conn->error);
                    }
                }
            } else {
                error_log("No orders found for guest: " . $fullname . " or phone: " . $phone);
                $error = "No orders found.";
            }
            $orders_stmt->close();
        } else {
            error_log("Prepare failed for orders: " . $conn->error);
            $error = "Error fetching orders: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Noah Waters</title>
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
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        h1 {
            color: #112752;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
        }

        .track-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
            width: 95%;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: "Boogaloo", sans-serif;
        }

        .form-group input:focus {
            border-color: #0f65b4;
            outline: none;
        }

        .submit-btn {
            background-color: #0f65b4;
            color: white;
            border: none;
            padding: 12px 24px;
            width: 100%;
            border-radius: 8px;
            font-size: 1.2rem;
            font-family: "Boogaloo", sans-serif;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #094a85;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .order-info {
            flex: 1;
        }

        .order-id {
            font-size: 1.2rem;
            color: #0f65b4;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .guest-name {
            color: #0f65b4;
            font-size: 1.1rem;
            margin: 0.5rem 0;
            font-weight: bold;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-items {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .order-total {
            font-size: 1.2rem;
            color: #2a9d8f;
            font-weight: bold;
            text-align: right;
            margin-top: 1rem;
        }

        .cancel-btn {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: "Boogaloo", sans-serif;
            font-size: 1rem;
            text-decoration: none;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }
        .cancel-btn:hover {
            background: #c82333;
        }

        .tracking-bar {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            position: relative;
            padding: 0 1rem;
        }

        .tracking-bar::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 10px;
            right: 10px;
            height: 4px;
            background: #e0e0e0;
            z-index: 0;
        }

        .tracking-step {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
        }

        .tracking-step::before {
            content: "●";
            display: block;
            font-size: 24px;
            margin-bottom: 8px;
            color: #e0e0e0;
        }

        .tracking-step.active::before {
            color: #0f65b4;
        }

        .tracking-step.active {
            color: #0f65b4;
        }

        .tracking-step.completed::before {
            color: #28a745;
        }

        .tracking-step.completed {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
            }
            .order-status {
                margin-top: 1rem;
            }
            .tracking-bar {
                flex-wrap: wrap;
            }
            .tracking-step {
                flex: 0 0 50%;
                margin-bottom: 1rem;
            }
        }

        .status-container {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .payment-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .payment-paid {
            background:  #721c24;
            color: white;
        }
        .payment-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .status-preparing {
            background: #cce5ff;
            color: #004085;
        }

        .status-delivery {
            background: #fff3cd;
            color: #856404;
        }

        .tracking-bar {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            position: relative;
            padding: 0 1rem;
        }

        .tracking-bar::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 10px;
            right: 10px;
            height: 4px;
            background: #e0e0e0;
            z-index: 0;
        }

        .tracking-step {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
            font-size: 0.9rem;
        }

        .tracking-step::before {
            content: "●";
            display: block;
            font-size: 24px;
            margin-bottom: 8px;
            color: #e0e0e0;
        }

        .tracking-step.active::before {
            color: #0f65b4;
        }

        .tracking-step.active {
            color: #0f65b4;
        }

        .tracking-step.completed::before {
            color: #28a745;
        }

        .tracking-step.completed {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .tracking-step {
                font-size: 0.8rem;
            }
            .status-container {
                align-items: flex-start;
                margin-top: 1rem;
            }
        }

        
    </style>
</head>
<body>
    <?php include 'navbar_guest.php'; ?>

    <div class="container">
        <h1>Track Your Order</h1>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <form method="POST" class="track-form">
                <div class="form-group">
                    <input type="text" name="fullname" placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Enter your phone number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <button type="submit" class="submit-btn">Track Order</button>
            </form>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
    <div class="order-card">
        <div class="order-header">
            <div class="order-info">
                <div class="order-id">Order #<?= htmlspecialchars($order['id']) ?></div>
                <div class="guest-name">Guest: <?= htmlspecialchars($order['fullname']) ?></div>
                <div class="order-date"><?= date('F j, Y, g:i A', strtotime($order['created_at'])) ?></div>
            </div>
            <div class="status-container">
                <div class="order-status status-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
    <?= htmlspecialchars($order['status']) ?>
</div>

                <div class="payment-status <?= $order['payment_status'] === 'Paid' ? 'payment-paid' : 'payment-unpaid' ?>">
                    <?= htmlspecialchars($order['payment_status']) ?>
                </div>
                <?php if (in_array(strtolower($order['status']), ['pending', 'preparing'])): ?>

                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="fullname" value="<?= htmlspecialchars($_POST['fullname']) ?>">
                        <input type="hidden" name="phone" value="<?= htmlspecialchars($_POST['phone']) ?>">
                        <button type="submit" class="cancel-btn">Cancel</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-items">
            <?php foreach ($order['items'] as $item): ?>
                <div>
                    <?= htmlspecialchars($item['name']) ?> - 
                    <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?> = 
                    ₱<?= number_format($item['quantity'] * $item['price'], 2) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="order-total">Total: ₱<?= number_format($order['total'], 2) ?></div>

        <div class="tracking-bar">
            <?php
                $status = strtolower($order['status']);
            ?>
            <div class="tracking-step <?= ($status == 'pending' || in_array($status, ['preparing','out for delivery','delivered'])) ? 'active' : '' ?>">Pending</div>
            <div class="tracking-step <?= in_array($status, ['preparing','out for delivery','delivered']) ? 'active' : '' ?>">Preparing</div>
            <div class="tracking-step <?= in_array($status, ['out for delivery','delivered']) ? 'active' : '' ?>">Out for Delivery</div>
            <div class="tracking-step <?= $status == 'delivered' ? 'active' : '' ?>">Delivered</div>
        </div>
    </div>
            </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
