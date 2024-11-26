<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_registration";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

// Validate the input
if (!isset($data['mentor_id'], $data['mentee_id'], $data['user_role']) || empty($data['mentor_id']) || empty($data['mentee_id']) || empty($data['user_role'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit();
}

$mentor_id = $data['mentor_id'];
$mentee_id = $data['mentee_id'];
$user_role = $data['user_role'];

$sql = '';
if ($user_role === 'mentor') {
    $sql = "UPDATE mentor_mentee SET mentor_status='Complete' WHERE mentor_id=? AND mentee_id=?";
} elseif ($user_role === 'mentee') {
    $sql = "UPDATE mentor_mentee SET mentee_status='Complete' WHERE mentor_id=? AND mentee_id=?";
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid user role"]);
    exit();
}

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $mentor_id, $mentee_id);

$response = array();

if ($stmt->execute()) {
    // Check if both mentor and mentee have completed
    $checkSql = "SELECT mentor_status, mentee_status FROM mentor_mentee WHERE mentor_id=? AND mentee_id=?";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param("ii", $mentor_id, $mentee_id);
        $checkStmt->execute();
        $checkStmt->bind_result($mentor_status, $mentee_status);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($mentor_status === 'Complete' && $mentee_status === 'Complete') {
            $updateFinalStatusSql = "UPDATE mentor_mentee SET status='Complete' WHERE mentor_id=? AND mentee_id=?";
            $updateFinalStatusStmt = $conn->prepare($updateFinalStatusSql);
            if ($updateFinalStatusStmt) {
                $updateFinalStatusStmt->bind_param("ii", $mentor_id, $mentee_id);
                $updateFinalStatusStmt->execute();
                $updateFinalStatusStmt->close();
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
                exit();
            }
        }

        $response['status'] = 'success';
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
        exit();
    }
} else {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = $stmt->error;
}

// Close the connection
$stmt->close();
$conn->close();

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
