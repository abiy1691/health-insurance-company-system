<?php
// Database configuration
$servername = "localhost";  // Typically 'localhost' for XAMPP or similar
$username = "root";         // Default username for XAMPP
$password = "";             // Default password for XAMPP (empty)
$dbname = "health_insurance"; // The name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
