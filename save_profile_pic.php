<?php
session_start();
header('Content-Type: application/json');

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log'); // Update this to your server's error log path
error_reporting(E_ALL);

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if ($data === null) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
    exit();
}

// Extract new profile data from request
$firstName = $data['firstName'] ?? '';
$lastName = $data['lastName'] ?? '';
$profilePic = $data['profilePic'] ?? '';
$specialization = $data['specialization'] ?? '';
$topic = $data['topic'] ?? '';
$phone = $data['phone'] ?? '';
$availabilityStart = $data['availabilityStart'] ?? '';
$availabilityEnd = $data['availabilityEnd'] ?? '';
$courseSection = $data['courseSection'] ?? '';

// Validate required fields
if (empty($firstName) || empty($lastName)) {
    echo json_encode(["status" => "error", "message" => "Required fields are missing"]);
    exit();
}

// Update the user profile
$sql = "UPDATE users SET firstName = ?, lastName = ?, profilePic = ?, specialization = ?, topic = ?, phone = ?, availabilityStart = ?, availabilityEnd = ?, courseSection = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("sssssssssi", $firstName, $lastName, $profilePic, $specialization, $topic, $phone, $availabilityStart, $availabilityEnd, $courseSection, $user_id);

if ($stmt->execute()) {
    // Fetch the updated profile information
    $stmt->close();
    $sql = "SELECT firstName, lastName, profilePic, specialization, topic, phone, availabilityStart, availabilityEnd, courseSection, role, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(["status" => "success", "user" => $user]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to fetch updated profile"]);
    }
} else {
    error_log("Failed to update profile: " . $stmt->error);
    echo json_encode(["status" => "error", "message" => "Failed to update profile: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
