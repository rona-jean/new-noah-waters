<?php
require 'config.php';

// Check if is_borrowable column exists
$result = $conn->query("SHOW COLUMNS FROM products LIKE 'is_borrowable'");
if ($result->num_rows === 0) {
    // Column doesn't exist, add it
    $conn->query("ALTER TABLE products ADD COLUMN is_borrowable TINYINT(1) NOT NULL DEFAULT 0");
    echo "Added is_borrowable column\n";
} else {
    // Column exists, show its definition
    $row = $result->fetch_assoc();
    echo "is_borrowable column exists:\n";
    print_r($row);
}

// Show all columns
$result = $conn->query("SHOW COLUMNS FROM products");
echo "\nAll columns in products table:\n";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$conn->close();
?> 