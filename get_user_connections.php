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

$currentUserId = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role == 'mentor') {
    $sql = "SELECT id, firstName, lastName FROM users WHERE role = 'mentee' AND id IN (SELECT mentee_id FROM mentor_mentee WHERE mentor_id = ?)";
} else if ($user_role == 'mentee') {
    $sql = "SELECT id, firstName, lastName FROM users WHERE role = 'mentor' AND id IN (SELECT mentor_id FROM mentor_mentee WHERE mentee_id = ?)";
} else {
    echo json_encode(["status" => "error", "message" => "Invalid user role"]);
    exit();
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $currentUserId);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $profiles = [];

    while ($row = $result->fetch_assoc()) {
        $profiles[] = $row;
    }

    echo json_encode(["status" => "success", "profiles" => $profiles]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to fetch profiles: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
