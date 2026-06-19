<?php
session_start();

// Update user status to offline if they were logged in
if (isset($_SESSION["user"])) {
    require_once __DIR__ . "/../config/config.php";
    $user_id = $_SESSION["user"]["id"];
    
    $update_status = $conn->prepare("UPDATE users SET status = 'offline' WHERE id = ?");
    $update_status->bind_param("i", $user_id);
    $update_status->execute();
}

$_SESSION = []; // Xóa toàn bộ session

// Hủy session và cookie
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/'); // Xóa session cookie

// Chuyển hướng về trang đăng nhập
header("Location: ../index.php");
exit;
?>
