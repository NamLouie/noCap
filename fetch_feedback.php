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

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $error_message = "Connection failed: " . $conn->connect_error;
    error_log($error_message, 3, 'C:/xampp/htdocs/Capstone/error_log.txt'); // Ensure this path is writable
    echo json_encode(["status" => "error", "message" => $error_message]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $name = isset($_GET['name']) ? $_GET['name'] : '';

    if (empty($name)) {
        echo json_encode(["status" => "error", "message" => "Name parameter is required"]);
        exit();
    }

    // Prepare SQL statement
    $sql = "SELECT * FROM reports WHERE name = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error_message = "Prepare failed: " . $conn->error;
        error_log($error_message, 3, 'C:/xampp/htdocs/Capstone/error_log.txt');
        echo json_encode(["status" => "error", "message" => $error_message]);
        exit();
    }

    // Bind parameters
    $stmt->bind_param("s", $name);

    // Execute the statement
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $feedbacks = [];
        
        while ($row = $result->fetch_assoc()) {
            $feedbacks[] = [
                'feedback_giver_name' => $row['feedback_giver_name'],
                'feedback' => $row['feedback'],
                'rating' => $row['rating'],
                'feedback_date' => $row['feedback_date']
            ];
        }

        echo json_encode(["status" => "success", "feedbacks" => $feedbacks]);
    } else {
        $error_message = "Failed to retrieve feedback: " . $stmt->error;
        error_log($error_message, 3, 'C:/xampp/htdocs/Capstone/error_log.txt');
        echo json_encode(["status" => "error", "message" => $error_message]);
    }

    $stmt->close();
} else {
    $error_message = "Invalid request method";
    error_log($error_message, 3, 'C:/xampp/htdocs/Capstone/error_log.txt');
    echo json_encode(["status" => "error", "message" => $error_message]);
}

$conn->close();
?>
