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
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "not_logged_in", "message" => "User not logged in"]);
    exit();
}

// Get and validate the incoming request data
$data = json_decode(file_get_contents('php://input'), true);
$requestId = intval($data['requestId'] ?? 0);
$status = trim($data['status'] ?? '');

if ($requestId <= 0 || empty($status)) {
    echo json_encode(["status" => "error", "message" => "Invalid request data"]);
    exit();
}

// Check if the request has already been accepted or declined
$check_sql = "SELECT status FROM pending_requests WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
if ($check_stmt === false) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare check statement: " . $conn->error]);
    exit();
}

$check_stmt->bind_param("i", $requestId);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if ($check_result->num_rows > 0) {
    $current_status = $check_result->fetch_assoc()['status'];
    if ($current_status === 'Accepted' || $current_status === 'Declined') {
        echo json_encode(["status" => "error", "message" => "Request has already been processed"]);
        exit();
    }
} else {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit();
}

// Update the request status
$sql = "UPDATE pending_requests SET request_data = JSON_SET(request_data, '$.status', ?), status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare statement: " . $conn->error]);
    exit();
}

$stmt->bind_param("ssi", $status, $status, $requestId);

if ($stmt->execute()) {
    // Fetch the updated request data to return in the response
    $fetch_sql = "SELECT id, request_data FROM pending_requests WHERE id = ?";
    $fetch_stmt = $conn->prepare($fetch_sql);
    if ($fetch_stmt === false) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare fetch statement: " . $conn->error]);
        exit();
    }

    $fetch_stmt->bind_param("i", $requestId);
    if ($fetch_stmt->execute()) {
        $result = $fetch_stmt->get_result();
        if ($result->num_rows > 0) {
            $updatedRequest = $result->fetch_assoc();
            $updatedRequest['request_data'] = json_decode($updatedRequest['request_data'], true);
            echo json_encode(["status" => "success", "updatedRequest" => $updatedRequest]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to retrieve updated request"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to execute fetch query: " . $fetch_stmt->error]);
    }
    $fetch_stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update request status: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
