<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($password)) {
        header('Location: login.html?error=empty');
        exit;
    }

    //prepare query and execute gamit mysqli
    $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE fullname = ?");
    $stmt->bind_param("s", $fullname);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        //session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        //redirect base sa role
        if ($user['role'] === 'admin') {
            header('Location: admin_orders.php');
        } else {
            echo "<script>alert('Successfully Logged In.'); window.location.href='logged_in_index.php';</script>";
        }
        exit;
    } else {
        //invalid login
        header('Location: login.html?error=invalid');
        exit;
    }
} else {
    header('Location: login.html');
    exit;
}
