<?php
$host = "localhost";
$user = "root"; // Thay bằng username của MySQL nếu có
$password = ""; // Nếu có mật khẩu MySQL, hãy nhập vào đây
$database = "company_db";

$conn = new mysqli($host, $user, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8"); // Đảm bảo sử dụng UTF-8
?>
