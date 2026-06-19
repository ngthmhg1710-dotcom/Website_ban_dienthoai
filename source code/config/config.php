<?php
$host = "localhost";  // hoặc 127.0.0.1
$username = "root";   // Tài khoản MySQL mặc định
$password = "";       // Nếu có mật khẩu, hãy điền vào đây
$database = "company_db";  // Đảm bảo đúng tên database

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}
?>
