<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "logged_in",
        "role" => $_SESSION['user_role']
    ]);
} else {
    echo json_encode([
        "status" => "not_logged_in"
    ]);
}
?>
