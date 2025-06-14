<?php
session_start();
if (isset($_SESSION['user_id'])) {
    echo "User logged in with ID: " . $_SESSION['user_id'];
} else {
    echo "User not logged in";
}
?>
