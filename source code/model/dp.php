<?php
$servername = "localhost";
$username = "root"; // Tên người dùng MySQL của bạn
$password = ""; // Mật khẩu MySQL của bạn
$dbname = "company_db"; // Tên cơ sở dữ liệu của bạn

// Kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
