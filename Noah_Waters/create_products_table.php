<?php
require 'config.php';

// SQL to create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('container', 'bottle') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    is_borrowable TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Execute the query
if ($conn->query($sql)) {
    echo "Products table created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 