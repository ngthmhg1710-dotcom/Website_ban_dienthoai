<?php
// admin/dashboard.php - Giao diện Admin
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] != "admin") {
    header("Location: login.php");
    exit;
}

// Lấy thông tin admin từ session
$admin = $_SESSION["user"];

// Giả lập số liệu thống kê (có thể lấy từ database)
$total_staff = 10;
$total_products = 50;
$total_revenue = 50000000;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Nhân viên</title>
    <link rel="stylesheet" href="../sidebar_admin.css">
    <style>
        /* Định dạng font chữ */
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;600&display=swap');

        * {
    font-family: 'Poppins', sans-serif;
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    
}

/* Thiết kế phần nội dung chính */
.content {
    background: #f5f5f5; /* Light gray background */
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    overflow: auto;
    z-index: 0; /* Đảm bảo nội dung không đè lên sidebar */
    position: relative; /* Giữ vị trí trong layout */
}

.dashboard-box {
    background:rgb(255, 255, 255); /* Nền xám nhạt */
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    max-height: 50%; /* Đảm bảo không bị lấn chiếm */
    border: 3px solid #3A7BD5; /* Viền xanh dương */
    overflow: auto; /* Đảm bảo nội dung không tràn */
}


.dashboard-box:hover {
    transform: translateY(-5px);
    box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.15);
}

.content h1 {
    color: #3A7BD5; /* Xanh dương */
    font-size: 32px;
    margin-bottom: 15px;
    font-weight: 600;
}

.content p {
    font-size: 18px;
    color: #333; /* Chữ đen để nổi bật trên nền trắng */
    line-height: 1.6;
}

.icon {
    font-size: 50px;
    color: #3A7BD5;
    margin-bottom: 15px;
}

/* Nút hỗ trợ */
.btn-container {
    margin-top: 20px;
}

.btn {
    background-color: #3A7BD5; /* Màu xanh dương */
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s, transform 0.2s;
}

.btn:hover {
    background-color: #2A5C9A; /* Đậm hơn khi hover */
    transform: scale(1.05);
}

    </style>
</head>
<body>

    <?php include 'sidebar_admin.php'; ?>

    <div class="content">
        <div class="dashboard-box">
            <div class="icon">📊</div>
            <h1>Chào mừng đến với trang ADMIN</h1>
            <p>Bạn có thể quản lý nhân viên, xem doanh thu và thực hiện các công việc khác từ thanh điều hướng bên trái.</p>
            <div class="btn-container">
                <button class="btn" onclick="location.href='edit_profile.php'">Chỉnh sửa thông tin</button>
                <button class="btn" onclick="location.href='manage_revenue.php'">Quản lý doanh thu</button>
                
            </div>
        </div>
    </div>

</body>
</html>
