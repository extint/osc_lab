<?php
// Database connection
$servername = "localhost";
$db_username = "vedant"; // MySQL username
$db_password = "vedant"; // MySQL password
$dbname = "exp9"; // Your database name

// Create a new connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "shutup divyanhu"; // This will confirm if the connection is successful
}
?>
