<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    header("Location: login.php");
    exit;
}

//check kung nakaconnect ba sa database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    
    // Debug yung POST data na nreceive
    error_log("POST data received: " . print_r($_POST, true));
    
    if (isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        error_log("Processing order ID: " . $order_id);
        
        // status update to
        if (isset($_POST['status'])) {
            //debug lang sa login
            error_log("Attempting to update order #$order_id");
            error_log("Raw POST status: " . print_r($_POST['status'], true));
            
            $status = trim($_POST['status']);
            error_log("Trimmed status: " . $status);
            
            // check yung status bago maupdate
            $check_sql = "SELECT status FROM orders WHERE id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $order_id);
            $check_stmt->execute();
            $current = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();
            error_log("Current status in database: " . $current['status']);
            
            // dito na iniupdate
            $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                error_log("Prepare failed: " . $conn->error);
            }
            $update_stmt->bind_param("si", $status, $order_id);
            $result = $update_stmt->execute();
            error_log("Update result: " . ($result ? "success" : "failed"));
            if (!$result) {
                error_log("Update error: " . $update_stmt->error);
            }
            $update_stmt->close();
            
            // dito verify yung update
            $verify_sql = "SELECT status FROM orders WHERE id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $order_id);
            $verify_stmt->execute();
            $updated = $verify_stmt->get_result()->fetch_assoc();
            $verify_stmt->close();
            error_log("Status after update: " . $updated['status']);

            if ($result) {
                $_SESSION['update_message'] = "Order #$order_id status updated to " . ucfirst($status);
            } else {
                $_SESSION['update_error'] = "Failed to update order #$order_id status";
            }
        }
        
        // yung paid button
        if (isset($_POST['toggle_payment'])) {
            $stmt = $conn->prepare("SELECT payment_status FROM orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();

            $newPaymentStatus = ($order['payment_status'] === 'paid') ? 'unpaid' : 'paid';

            $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $newPaymentStatus, $order_id);
            $stmt->execute();
            $stmt->close();
        }
        
        header("Location: admin_orders.php");
        exit;
    }
}

// ito yung nagdidisplay ng update message 
if (isset($_SESSION['update_message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['update_message']) . '</div>';
    unset($_SESSION['update_message']);
}
if (isset($_SESSION['update_error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['update_error']) . '</div>';
    unset($_SESSION['update_error']);
}

$limit = 8;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$usertypeFilter = isset($_GET['usertype']) && $_GET['usertype'] !== '' ? $_GET['usertype'] : null;
$paymentStatusFilter = isset($_GET['payment_status']) && $_GET['payment_status'] !== '' ? $_GET['payment_status'] : null;

if ($paymentStatusFilter) {
    $conditions[] = "o.payment_status = ?";
    $params[] = $paymentStatusFilter;
    $paramTypes .= 's';
}

$conditions = [];
$params = [];
$paramTypes = '';

if ($statusFilter) {
    $conditions[] = "o.status = ?";
    $params[] = $statusFilter;
    $paramTypes .= 's';
}
if ($usertypeFilter) {
    $conditions[] = "LOWER(o.usertype) = ?";
    $params[] = strtolower($usertypeFilter);
    $paramTypes .= 's';
}

$whereSQL = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*) as total FROM orders o $whereSQL";
$countStmt = $conn->prepare($countSql);
if ($params) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalOrders = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalOrders / $limit);

$sql = "SELECT o.*, u.fullname AS username, o.pickup_time 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id
        $whereSQL
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($paramTypes . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders - Noah Waters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet">
    <script>
        // ito yung naghahandle ng form submission
        function handleStatusUpdate(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // kumukuha ng data
                const formData = new FormData(form);
                
                // ito yung nagseseend ng update request
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        // reload page pag ka update ng status
                        window.location.reload();
                    } else {
                        alert('Failed to update status. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        }

        // Add event listeners to all status update forms when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            const statusForms = document.querySelectorAll('form[action="admin_orders.php"]');
            statusForms.forEach(form => handleStatusUpdate(form));
        });
    </script>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .container-box {
            background: rgba(3, 0, 0, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.52);
            color: white;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .order-box {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-family: "Boogaloo", sans-serif;
        }
        .order-box h5 {
            color: #0f65b4;
            font-size: 1.4em;
            margin-bottom: 15px;
            font-family: "Boogaloo", sans-serif;
        }
        .order-box p {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
            font-family: "Boogaloo", sans-serif;
        }
        .order-box strong {
            color: #0f65b4;
            font-family: "Boogaloo", sans-serif;
        }
        .items-list {
            padding-left: 1.5rem;
            color: #444;
            font-size: 1.1em;
            margin: 15px 0;
            font-family: "Boogaloo", sans-serif;
        }
        .form-select, .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 1.1em;
            font-family: "Boogaloo", sans-serif;
        }
        .btn-primary, .btn-success, .btn-secondary {
            font-family: "Boogaloo", sans-serif;
            font-size: 1.1em;
        }
        .btn-primary {
            background-color: #0f65b4;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0d4d8c;
        }
        .btn-success {
            background-color: #28a745;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
        }
        .badge {
            font-size: 0.9em;
            padding: 6px 12px;
            border-radius: 6px;
            font-family: "Boogaloo", sans-serif;
        }
        .badge.bg-success {
            background-color: #0f65b4 !important;
            color: white;
        }
        .pagination {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        .page-item {
            list-style: none;
        }
        .page-link {
            color: #0f65b4;
            font-size: 1.1em;
            padding: 10px 18px;
            font-family: "Boogaloo", sans-serif;
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid #0f65b4;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .page-link:hover {
            background-color: #0f65b4;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .page-item.active .page-link {
            background-color: #0f65b4;
            border-color: #0f65b4;
            color: white;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .page-item.disabled .page-link {
            background-color: rgba(255, 255, 255, 0.5);
            border-color: #ccc;
            color: #999;
            cursor: not-allowed;
        }
        .page-item.disabled .page-link:hover {
            background-color: rgba(255, 255, 255, 0.5);
            color: #999;
            transform: none;
            box-shadow: none;
        }
        .form-label {
            color: white;
            font-size: 1.1em;
            margin-bottom: 8px;
            font-family: "Boogaloo", sans-serif;
        }
        h2 {
            color: white;
            font-size: 2em;
            margin-bottom: 30px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            font-family: "Boogaloo", sans-serif;
        }
        .row {
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .form-select {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 1.1em;
            font-family: "Boogaloo", sans-serif;
            width: 100%;
            min-width: 200px;
        }
        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            max-width: 900px;
            margin: 0 auto 30px auto;
            padding: 0 15px;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
        }
        .filter-button {
            min-width: 150px;
            height: 42px;
        }
        @media (max-width: 768px) {
            .container-box {
                margin: 20px 15px;
                padding: 15px;
            }
            .order-box {
                padding: 15px;
            }
            .order-box h5 {
                font-size: 1.2em;
            }
            .order-box p, .items-list {
                font-size: 1em;
            }
            .btn-primary, .form-select, .form-control {
                font-size: 1em;
            }
            .page-link {
                padding: 8px 14px;
                font-size: 1em;
            }
            .pagination {
                flex-wrap: wrap;
                gap: 3px;
            }
            .filter-form {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            .filter-group {
                width: 100%;
            }
            .filter-button {
                width: 100%;
            }
        }
        .order-box form .input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .order-box form .form-select {
            width: auto;
            min-width: 150px;
            max-width: 700px;
            flex: 1;
        }
        .order-box form .btn-primary {
            white-space: nowrap;
            min-width: 100px;
        }
    </style>
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<div class="container container-box">

    <h2 class="text-center mb-4">Manage Orders</h2>

    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All Orders</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Orders</option>
                <option value="preparing" <?= $statusFilter === 'preparing' ? 'selected' : '' ?>>Preparing Orders</option>
                <option value="out for delivery" <?= $statusFilter === 'out for delivery' ? 'selected' : '' ?>>Out for Delivery Orders</option>
                <option value="picked up by customer" <?= $statusFilter === 'picked up by customer' ? 'selected' : '' ?>>Picked Up by Customer Orders</option>
                <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered Orders</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled Orders</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="form-label">User Type</label>
            <select name="usertype" class="form-select">
                <option value="">All Users</option>
                <option value="user" <?= $usertypeFilter === 'user' ? 'selected' : '' ?>>Registered Users</option>
                <option value="guest" <?= $usertypeFilter === 'guest' ? 'selected' : '' ?>>Guest Users</option>
            </select>
        </div>
        <div class="filter-group">
            <button type="submit" class="btn btn-primary filter-button">Apply Filters</button>
        </div>
    </form>

    <?php if ($orders->num_rows === 0): ?>
        <p>No orders found.</p>
    <?php else: ?>
        <?php while ($order = $orders->fetch_assoc()): ?>
            <div class="order-box">
                <h5>Order #<?= $order['id'] ?></h5>
                <?php if ($order['user_id']): ?>
                    <p><strong>User:</strong> <?= htmlspecialchars($order['username']) ?> | <strong>Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                <?php else: ?>
                    <p><strong>Guest Name:</strong> <?= htmlspecialchars($order['fullname']) ?> | <strong>Guest Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                <?php endif; ?>
                <p>
                    <strong>Shipping Method:</strong> 
                    <?php if (strtolower($order['shipping_method']) === 'pickup'): ?>
                        Pick up | <strong>Pickup Time:</strong> <?= htmlspecialchars($order['pickup_time']) ?>
                    <?php else: ?>
                        Delivery
                    <?php endif; ?>
                </p>
                <p><strong>Status:</strong> <span id="status-<?= $order['id'] ?>"><?= htmlspecialchars($order['status']) ?></span></p>
                <p><strong>Payment Status:</strong> <span class="me-2"><?= ucfirst($order['payment_status']) ?></span>
                    <?php if ($order['status'] !== 'cancelled'): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="toggle_payment" value="1">
                            <button type="submit" class="btn btn-sm <?= $order['payment_status'] === 'paid' ? 'btn-secondary' : 'btn-success' ?>">Mark as <?= $order['payment_status'] === 'paid' ? 'Unpaid' : 'Paid' ?></button>
                    </form>
                    <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled>Payment Status Locked</button>
                    <?php endif; ?>
                </p>

                <p><strong>Created:</strong> <?= $order['created_at'] ?></p>

                <?php if ($order['is_new_user_order']): ?>
                    <span class="badge bg-success" style="background-color: #0f65b4 !important;">New User</span>
                <?php endif; ?>

                <?php
                $stmt2 = $conn->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmt2->bind_param("i", $order['id']);
                $stmt2->execute();
                $items = $stmt2->get_result();
                ?>
                <ul class="items-list">
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <li><?= htmlspecialchars($item['name']) ?> - ₱<?= $item['price'] ?> × <?= $item['quantity'] ?></li>

                    <?php endwhile; ?>
                </ul>
                <?php $stmt2->close(); ?>

                <?php if ($order['status'] !== 'cancelled'): ?>
                    <form method="POST" action="admin_orders.php" class="mt-2">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="input-group">
                        <select name="status" class="form-select">
                                <?php 
                                $statusOptions = ['pending', 'preparing', 'out for delivery', 'picked up by customer', 'delivered', 'cancelled'];
                                $currentStatus = trim($order['status']);
                                foreach ($statusOptions as $s): 
                                    $selected = ($currentStatus === $s) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= $selected ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mt-2">
                        <div class="input-group">
                            <select class="form-select" disabled>
                                <option selected>Order Cancelled</option>
                            </select>
                            <button class="btn btn-secondary" disabled>Update</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>

        <!--page numbers sa baba-->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
