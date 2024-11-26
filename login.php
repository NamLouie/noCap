<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root"; // Adjust if you have a different user
$password = ""; // Adjust if you have a password
$dbname = "mentoringdb"; // Ensure this is correct and matches the signup script

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Retrieve and sanitize input data
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email and password fields cannot be empty."]);
    exit();
}

// Prepare and execute the SQL statement
$stmt = $conn->prepare("SELECT id, email, role, firstName, lastName, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $email, $role, $firstName, $lastName, $hashed_password);
$stmt->fetch();

if ($stmt->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    $stmt->close();
    $conn->close();
    exit();
}

// Verify the password
if (password_verify($password, $hashed_password)) {
    $_SESSION['user_id'] = $id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    echo json_encode(["status" => "success", "redirectUrl" => 'index-page.html', "role" => $role, "firstName" => $firstName, "lastName" => $lastName]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
