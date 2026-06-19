<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . "/../config/config.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["email"])) {
    $email = $conn->real_escape_string($_POST["email"]);
    $phone = isset($_POST["phone"]) ? $conn->real_escape_string($_POST["phone"]) : "";
    
    // Kiểm tra email trùng lặp
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND phone != ? AND is_deleted = 0");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(["status" => "exists", "message" => "Email này đã tồn tại trong hệ thống"]);
    } else {
        echo json_encode(["status" => "available", "message" => "Email có thể sử dụng"]);
    }
    
    $stmt->close();
    exit;
}

echo json_encode(["status" => "error", "message" => "Yêu cầu không hợp lệ"]);
exit; 