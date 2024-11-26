<?php
session_start();
header('Content-Type: application/json');

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit();
}

$user_email = $_SESSION['user_email'];

// SQL query to get pending requests and related user data
$sql = "SELECT pr.id, pr.sender_email, pr.request_data, pr.status,
               u.firstName, u.lastName, u.profilePic, u.specialization, u.topic, u.phone, 
               u.availabilityStart, u.availabilityEnd, u.courseSection, u.role, u.email 
        FROM pending_requests pr
        JOIN users u ON pr.sender_email = u.email
        WHERE pr.recipient_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);

// Execute the query and fetch the results
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON data
        $row['request_data'] = json_decode($row['request_data'], true);
        $requests[] = $row;
    }
    echo json_encode(["status" => "success", "requests" => $requests]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to fetch pending requests: " . $stmt->error]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
