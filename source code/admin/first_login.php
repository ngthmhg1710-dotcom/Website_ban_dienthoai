<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_GET["token"])) {
    die("Liên kết không hợp lệ!");
}

$token = $_GET["token"];
$stmt = $conn->prepare("SELECT user_id, expires_at FROM login_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (strtotime($row["expires_at"]) < time()) {
        die("Liên kết đã hết hạn! Vui lòng yêu cầu quản trị viên gửi lại.");
    }

    // Đăng nhập thành công -> Xóa token & tạo session
    $_SESSION["user_id"] = $row["user_id"];
    $conn->query("DELETE FROM login_tokens WHERE token = '$token'");

    // Chuyển hướng đến trang đổi mật khẩu
    header("Location: change_password.php");
    exit;
} else {
    die("Token không hợp lệ!");
}
?>
