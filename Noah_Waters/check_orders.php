<?php
include 'config.php';

// Debug query to check all orders for ronna adona
$debug_sql = "SELECT o.id, o.status, o.created_at, o.payment_status, 
                     u.fullname, u.id as user_id,
                     COUNT(oi.id) as item_count,
                     GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              LEFT JOIN order_items oi ON o.id = oi.order_id
              LEFT JOIN products p ON oi.product_id = p.id
              WHERE u.fullname = 'ronna adona'
              GROUP BY o.id
              ORDER BY o.created_at DESC";

$result = $conn->query($debug_sql);

echo "<pre>";
echo "Orders for ronna adona:\n\n";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Order ID: " . $row['id'] . "\n";
        echo "Status: " . $row['status'] . "\n";
        echo "Payment Status: " . $row['payment_status'] . "\n";
        echo "Created At: " . $row['created_at'] . "\n";
        echo "User ID: " . $row['user_id'] . "\n";
        echo "Number of Items: " . $row['item_count'] . "\n";
        echo "Items: " . $row['items'] . "\n";
        echo "----------------------------------------\n";
    }
} else {
    echo "No orders found for ronna adona\n";
}

// Also check the users table to verify the user exists
$user_sql = "SELECT * FROM users WHERE fullname = 'ronna adona'";
$user_result = $conn->query($user_sql);

echo "\nUser Information:\n";
if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    print_r($user);
} else {
    echo "User not found in database\n";
}

echo "</pre>";
?> 