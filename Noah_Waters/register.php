<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$username = "root";
$password = ""; 
$dbname = "noah_waters"; 

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$fullname = $_POST['fullname'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$address = $_POST['address'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

//check if passwords match
if ($password !== $confirm_password) {
    echo "<script>
            alert('Passwords do not match.');
            window.location.href = 'register.html';
          </script>";
    exit;
}

//check for duplicate fullname
$check_sql = "SELECT id FROM users WHERE fullname = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $fullname);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    $conn->close();
    echo "<script>
            alert('Full name already registered. Please use a different name.');
            window.location.href = 'register.html';
          </script>";
    exit;
}

$check_stmt->close();

//hash the password for security
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

//insert into database
$sql = "INSERT INTO users (fullname, email, phone, address, password, is_new_user) 
        VALUES (?, ?, ?, ?, ?, 1)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $fullname, $email, $phone, $address, $hashed_password);

if ($stmt->execute()) {
    echo "<script>
            alert('Registration successful!');
            window.location.href = 'index.html';
          </script>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
