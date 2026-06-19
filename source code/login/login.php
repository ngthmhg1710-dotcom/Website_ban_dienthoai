<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Đặt tham số cookie session trước khi bắt đầu session
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối CSDL
require_once __DIR__ . "/../config/config.php";

if (!isset($conn)) {
    die("Lỗi kết nối CSDL: biến conn không tồn tại!");
}

// Kiểm tra phương thức POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("⚠ Form không gửi bằng phương thức POST!");
}

// Kiểm tra dữ liệu nhập vào
if (empty($_POST["username"]) || empty($_POST["password"])) {
    die("⚠ Thiếu thông tin đăng nhập!");
}

// Lấy dữ liệu từ form
$username = trim($_POST["username"]);
$password = trim($_POST["password"]);

// Kiểm tra trong database
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
if (!$stmt) {
    die("Lỗi truy vấn SQL: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && ( password_verify($password, $user["password"]) || (md5($password) === $user["password"]) )) {
    // Kiểm tra xem tài khoản có bị khóa không
    if ($user["status_account"] == 'locked') {
        echo "<script>alert('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin!'); window.location.href='../index.php';</script>";
        exit;
    }

    // Lưu thông tin người dùng vào session
    $_SESSION["user"] = $user;
    $_SESSION["logged_in"] = true;
    $_SESSION["role"] = $user["role"];
    
    // Update user status to online and last activity
    $update_status = $conn->prepare("UPDATE users SET status = 'online', last_activity = NOW() WHERE id = ?");
    $update_status->bind_param("i", $user["id"]);
    $update_status->execute();
    
    // Redirect dựa trên role
    $redirect_url = ($user["role"] == "admin") ? "../admin/dashboard.php" : "../employee/dashboard.php";
    header("Location: " . $redirect_url);
    exit;
} else {
    echo "<script>alert('Sai tài khoản hoặc mật khẩu!'); window.location.href='../index.php';</script>";
}
?>
