<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (isset($_SESSION["user"])) {
    $user_id = $_SESSION["user"]["id"];
    
    // Cập nhật trạng thái online
    $update_status = $conn->prepare("UPDATE users SET status = 'online', last_activity = NOW() WHERE id = ?");
    $update_status->bind_param("i", $user_id);
    $update_status->execute();
    
    // Cập nhật trạng thái offline cho những người dùng không hoạt động trong 5 phút
    $timeout = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $update_offline = $conn->prepare("UPDATE users SET status = 'offline' WHERE last_activity < ? AND status = 'online'");
    $update_offline->bind_param("s", $timeout);
    $update_offline->execute();
}
?> 