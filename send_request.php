<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit();
}

$sender_email = $_SESSION['user_email'];
$data = json_decode(file_get_contents('php://input'), true);

$recipient_email = $data['recipientEmail'];

// Check if a request already exists
$sql = "SELECT COUNT(*) as count FROM pending_requests WHERE sender_email = ? AND recipient_email = ? AND JSON_EXTRACT(request_data, '$.status') = 'Pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $sender_email, $recipient_email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(["status" => "error", "message" => "A pending request already exists."]);
    exit();
}

// Fetch sender details
$sql = "SELECT firstName, lastName, role FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $sender_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Sender not found"]);
    exit();
}

$sender = $result->fetch_assoc();

$request_data = json_encode([
    "name" => "{$sender['firstName']} {$sender['lastName']}",
    "role" => $sender['role'],
    "topics" => $data['topics'],
    "specialization" => $data['specialization'],
    "email" => $sender_email,
    "availabilityStart" => $data['availabilityStart'],
    "availabilityEnd" => $data['availabilityEnd'],
    "status" => "Pending"
]);

$sql = "INSERT INTO pending_requests (sender_email, recipient_email, request_data) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $sender_email, $recipient_email, $request_data);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send request: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
