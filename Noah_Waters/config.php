<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "noah_waters";  // ← change this to your actual DB name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
