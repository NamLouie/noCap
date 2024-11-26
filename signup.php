<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root"; // Adjust if you have a different user
$password = ""; // Adjust if you have a password
$dbname = "mentoringdb"; // Ensure this is correct

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Collect input data
$role = $_POST['role'];
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit();
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already exists."]);
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();

// Insert new user into the users table
$stmt = $conn->prepare("INSERT INTO users (role, firstName, lastName, email, password) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit();
}
$stmt->bind_param("sssss", $role, $firstName, $lastName, $email, $password);

if ($stmt->execute()) {
    // Set session variables upon successful sign-up
    $_SESSION['user'] = [
        "id" => $stmt->insert_id,
        "role" => $role,
        "firstName" => $firstName,
        "lastName" => $lastName,
        "email" => $email
    ];
    echo json_encode(["status" => "success", "message" => "Sign up successful!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error during sign up: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
