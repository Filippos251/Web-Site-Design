<?php
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "P1ts1r1k0s"; // Default XAMPP password
$dbname = "diplomatiki_ergasia";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
