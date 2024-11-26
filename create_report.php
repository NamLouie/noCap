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
    error_log($message, 3, 'error_log.txt');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
    $topic = filter_var($_POST['topic'], FILTER_SANITIZE_STRING);
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $summary = filter_var($_POST['summary'], FILTER_SANITIZE_STRING);
    $duration = filter_var($_POST['duration'], FILTER_SANITIZE_NUMBER_INT);

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $upload_file = $upload_dir . basename($_FILES['image']['name']);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
            $image = $upload_file;
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to upload image"]);
            exit();
        }
    } else {
        $image = null;
    }

    $sql = "INSERT INTO reports (userId, date, topic, name, summary, duration, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error_message = "Prepare failed: " . $conn->error;
        log_error($error_message);
        echo json_encode(["status" => "error", "message" => $error_message]);
        exit();
    }
    $stmt->bind_param("issssis", $user_id, $date, $topic, $name, $summary, $duration, $image);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "image" => basename($image)]);
    } else {
        $error_message = "Failed to create report: " . $stmt->error;
        log_error($error_message);
        echo json_encode(["status" => "error", "message" => $error_message]);
    }

    $stmt->close();
} else {
    $error_message = "Invalid request method";
    log_error($error_message);
    echo json_encode(["status" => "error", "message" => $error_message]);
}

$conn->close();
?>
