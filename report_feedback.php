<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mentoringdb";

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Change to 0 to prevent display of errors to the client
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/Capstone/logs/php-error.log'); // Adjust path as needed

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'create_report_feedback') {
        // Sanitize and validate inputs
        $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
        $topic = filter_input(INPUT_POST, 'topic', FILTER_SANITIZE_STRING);
        $student_id = filter_input(INPUT_POST, 'name', FILTER_VALIDATE_INT);
        $summary = filter_input(INPUT_POST, 'summary', FILTER_SANITIZE_STRING);
        $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_FLOAT);
        $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);

        // Additional validation for feedback
        if (preg_match('/^\d+$/', $feedback)) {
            echo json_encode(["status" => "error", "message" => "Feedback must include some text and not be purely numeric."]);
            exit();
        }

        $rating = null;
        if ($user_role !== 'mentor') {
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        }
        $feedback_giver_name = $_SESSION['user_name'];
        $feedback_giver_role = $_SESSION['role'];
        $feedback_date = date('Y-m-d');

        // Fetch student name based on ID
        $stmt = $conn->prepare("SELECT firstName, lastName FROM users WHERE id = ?");
        if ($stmt === false) {
            $error_message = "Prepare failed: " . $conn->error;
            log_error($error_message);
            echo json_encode(["status" => "error", "message" => $error_message]);
            exit();
        }
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name = $row['firstName'] . ' ' . $row['lastName'];
        } else {
            echo json_encode(["status" => "error", "message" => "Student not found"]);
            exit();
        }
        $stmt->close();

        // Check for existing report
        $stmt = $conn->prepare("SELECT id FROM reports WHERE userId = ? AND date = ? AND topic = ? AND name = ?");
        if ($stmt === false) {
            $error_message = "Prepare failed: " . $conn->error;
            log_error($error_message);
            echo json_encode(["status" => "error", "message" => $error_message]);
            exit();
        }
        $stmt->bind_param("isss", $user_id, $date, $topic, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Report already exists for the specified date, topic, and student."]);
            exit();
        }
        $stmt->close();

        // Handle file upload
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image = file_get_contents($_FILES['image']['tmp_name']);
        }

        // Prepare SQL statement
        $sql = "INSERT INTO reports (userId, date, topic, name, summary, duration, image, feedback, rating, feedback_giver_name, feedback_giver_role, feedback_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error_message = "Prepare failed: " . $conn->error;
            log_error($error_message);
            echo json_encode(["status" => "error", "message" => $error_message]);
            exit();
        }

        // Bind parameters
        $null = NULL;
        $stmt->bind_param("isssssbsssss", $user_id, $date, $topic, $name, $summary, $duration, $null, $feedback, $rating, $feedback_giver_name, $feedback_giver_role, $feedback_date);

        if ($image !== null) {
            $stmt->send_long_data(6, $image);
        }

        // Execute the statement
        try {
            if ($stmt->execute()) {
                $imagePath = $image !== null ? "data:image/png;base64," . base64_encode($image) : null;
                echo json_encode(["status" => "success", "image" => $imagePath]);
            } else {
                $error_message = "Failed to create report: " . $stmt->error;
                log_error($error_message);
                echo json_encode(["status" => "error", "message" => $error_message]);
            }
        } catch (mysqli_sql_exception $e) {
            $error_message = "Got a packet bigger than 'max_allowed_packet' bytes: " . $e->getMessage();
            log_error($error_message);
            echo json_encode(["status" => "error", "message" => $error_message]);
        }

        $stmt->close();
    } else {
        $error_message = "Invalid action";
        log_error($error_message);
        echo json_encode(["status" => "error", "message" => $error_message]);
    }
} else {
    $error_message = "Invalid request method";
    log_error($error_message);
    echo json_encode(["status" => "error", "message" => $error_message]);
}

$conn->close();
?>
