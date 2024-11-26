<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_registration";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT recipient_email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $recipientEmail = $user['recipient_email'];

        $sql = "SELECT * FROM pending_requests WHERE recipient_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $recipientEmail);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $requests = [];
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
            echo json_encode(["status" => "success", "requests" => $requests]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to fetch requests: " . $stmt->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Failed to fetch user: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
