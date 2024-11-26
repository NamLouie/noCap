<?php
session_start();
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $userId = $_SESSION['user_id'];
    $firstName = $data['firstName'];
    $lastName = $data['lastName'];
    $profilePic = $data['profilePic'];
    $specialization = $data['specialization'];
    $topic = $data['topic'];
    $phone = $data['phone'];
    $availabilityStart = $data['availabilityStart'];
    $availabilityEnd = $data['availabilityEnd'];
    $courseSection = $data['courseSection'];

    $query = "UPDATE users SET first_name=?, last_name=?, profile_pic=?, specialization=?, topic=?, phone=?, availability_start=?, availability_end=?, course_section=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssssi", $firstName, $lastName, $profilePic, $specialization, $topic, $phone, $availabilityStart, $availabilityEnd, $courseSection, $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
