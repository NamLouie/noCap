<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Query to fetch user details
$sql = "SELECT firstName, lastName, profilePic, specialization, topic, availabilityStart, availabilityEnd, role, email FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $users]);
} else {
    echo json_encode(["status" => "error", "message" => "No users found"]);
}

$conn->close();
?>
