<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

// Create connection
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

$sql = "SELECT firstName, lastName, role, profilePic, specialization, topic, email, phone, availabilityStart, availabilityEnd, courseSection FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user['status'] = 'logged_in';
        $_SESSION['user_name'] = $user['firstName'] . ' ' . $user['lastName'];
        $_SESSION['user_role'] = $user['role'];
        echo json_encode($user);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Failed to fetch user details: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
