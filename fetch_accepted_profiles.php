<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb"; // Updated database name

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

$sql = "SELECT u.id, u.firstName, u.lastName, u.profilePic, u.role, u.specialization, u.topic, u.email, u.phone, 
               u.availabilityStart, u.availabilityEnd, u.courseSection, mm.status
        FROM mentor_mentee mm
        JOIN users u ON (mm.mentor_id = u.id OR mm.mentee_id = u.id)
        WHERE (mm.mentor_id = ? OR mm.mentee_id = ?) AND u.id != ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
if ($stmt->execute() === false) {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
    exit();
}

$result = $stmt->get_result();
$profiles = [];

while ($row = $result->fetch_assoc()) {
    $profiles[] = $row;
}

echo json_encode(["status" => "success", "profiles" => $profiles]);

$stmt->close();
$conn->close();
?>
