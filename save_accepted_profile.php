<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$requestEmail = $data['email'];
$currentUserId = $_SESSION['user_id'];

// Fetch recipient details
$recipientDetailsSql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($recipientDetailsSql);
$stmt->bind_param("s", $requestEmail);
$stmt->execute();
$recipientDetailsResult = $stmt->get_result();
$recipient = $recipientDetailsResult->fetch_assoc();
$stmt->close();

if (!$recipient) {
    echo json_encode(["status" => "error", "message" => "Recipient not found."]);
    exit();
}

// Insert into mentor_mentee table
$sql = "INSERT INTO mentor_mentee (mentor_id, mentee_id, status) VALUES (?, ?, 'ongoing')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $currentUserId, $recipient['id']);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save accepted profile: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
