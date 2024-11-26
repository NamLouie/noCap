<?php
$servername = "localhost";
$username = "root"; // Adjust if you have a different user
$password = ""; // Adjust if you have a password
$dbname = "mentoringdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
