<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function log_error($message) {
    $log_directory = 'C:/xampp/htdocs/Capstone/logs';
    if (!is_dir($log_directory)) {
        mkdir($log_directory, 0777, true);
    }
    $error_log_path = $log_directory . '/error_log.txt';
    error_log($message, 3, $error_log_path); // Ensure this path is writable
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $error_message = "Connection failed: " . $conn->connect_error;
    log_error($error_message);
    echo json_encode(["status" => "error", "message" => $error_message]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'mentee'; // Default to 'mentee' if role is not set

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch reports
    $sql = "SELECT * FROM reports WHERE userId = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error_message = "Prepare failed: " . $conn->error;
        log_error($error_message);
        echo json_encode(["status" => "error", "message" => $error_message]);
        exit();
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = [];

    while ($row = $result->fetch_assoc()) {
        $duration = $row['duration'];
        $duration_hours = is_null($duration) ? 0 : floor($duration);
        $duration_minutes = is_null($duration) ? 0 : ($duration - $duration_hours) * 60;

        $report = [
            "id" => $row['id'],
            "userId" => $row['userId'],
            "date" => $row['date'],
            "topic" => $row['topic'],
            "name" => $row['name'],
            "summary" => $row['summary'],
            "duration" => $row['duration'],
            "image" => $row['image'] ? base64_encode($row['image']) : null,
            "feedback_giver_name" => $row['feedback_giver_name'],
            "feedback" => $row['feedback'],
            "feedback_date" => $row['feedback_date'],
            "feedback_giver_role" => $row['feedback_giver_role'],
            "duration_hours" => $duration_hours,
            "duration_minutes" => round($duration_minutes)
        ];

        // Remove ratings for mentors
        if ($user_role !== 'mentor') {
            $report['rating'] = $row['rating'];
        }

        $reports[] = $report;
    }

    $stmt->close();

    echo json_encode(["status" => "success", "reports" => $reports]);
} else {
    $error_message = "Invalid request method";
    log_error($error_message);
    echo json_encode(["status" => "error", "message" => $error_message]);
}

$conn->close();
?>
