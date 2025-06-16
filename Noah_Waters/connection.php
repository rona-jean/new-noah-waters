<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "noah_waters";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if($conn->connect_error) {
        die("Connected failed: " . $conn->connect_error);
    }
?>