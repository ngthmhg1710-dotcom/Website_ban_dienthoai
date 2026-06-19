<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Nhân viên</title>
    <link rel="stylesheet" href="sidebar_employee.css">
    <style>
        /* Định dạng font chữ */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        * {
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Thiết kế phần nội dung chính */
        .content {
            flex: 1;
            background: #f5f7fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 30px;
        }

        .dashboard-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(31, 120, 209, 0.1);
            max-width: 800px;
            width: 100%;
            border: 2px solid #1f78d1;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .dashboard-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(31, 120, 209, 0.15);
        }

        .content h1 {
            color: #1f78d1;
            font-size: 36px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .content p {
            font-size: 18px;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .icon {
            font-size: 60px;
            color: #1f78d1;
            margin-bottom: 25px;
        }

        /* Nút hỗ trợ */
        .btn-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            background-color: #1f78d1;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
            justify-content: center;
        }

        .btn:hover {
            background-color: #1660b0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 120, 209, 0.2);
        }

        .btn i {
            font-size: 20px;
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }

            .dashboard-box {
                padding: 30px;
            }

            .content h1 {
                font-size: 28px;
            }

            .content p {
                font-size: 16px;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_employee.php'; ?>

    <div class="content">
        <div class="dashboard-box">
            <div class="icon">📊</div>
            <h1>Chào mừng đến với trang quản lý</h1>
            <p>Bạn có thể quản lý đơn hàng, xem doanh thu và thực hiện các công việc khác từ thanh điều hướng bên trái.</p>
            <div class="btn-container">
                <button class="btn" onclick="location.href='view_revenue.php'">
                    <i class="fas fa-chart-line"></i>
                    Xem doanh thu
                </button>
                <button class="btn" onclick="location.href='manage_orders.php'">
                    <i class="fas fa-shopping-cart"></i>
                    Quản lý đơn hàng
                </button>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
