<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}


// Lấy dữ liệu từ URL
$customerName = isset($_GET['name']) ? $_GET['name'] : '';
$customerPhone = isset($_GET['phone']) ? $_GET['phone'] : '';
$customerAddress = isset($_GET['address']) ? $_GET['address'] : '';
$cartData = isset($_GET['cart']) ? json_decode(urldecode($_GET['cart']), true) : [];
$discount = isset($_GET['discount']) ? $_GET['discount'] : '0';
$cashReceived = isset($_GET['cash_received']) ? floatval($_GET['cash_received']) : 0;
$total = isset($_GET['total']) ? floatval($_GET['total']) : 0;
$finalPrice = isset($_GET['final_price']) ? floatval($_GET['final_price']) : 0;
$changeAmount = isset($_GET['change_amount']) ? floatval($_GET['change_amount']) : 0;


// Nếu không có dữ liệu, quay về trang trước
if (empty($customerName) || empty($customerPhone) || empty($customerAddress) || empty($cartData)) {
    header("Location: manage_orders.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .customer-info {
            margin-bottom: 20px;
        }
        .customer-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-section p {
            margin: 5px 0;
            font-size: 16px;
        }
        .total-section strong {
            min-width: 150px;
            display: inline-block;
        }
        .buttons {
            margin-top: 30px;
            text-align: center;
        }
        .buttons button, .buttons a {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .print-btn {
            background-color: #4CAF50;
            color: white;
        }
        .back-btn {
            background-color: #f44336;
            color: white;
        }
        @media print {
            .buttons {
                display: none;
            }
            .container {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>HÓA ĐƠN THANH TOÁN</h1>
            <p>Ngày: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
       
        <div class="customer-info">
            <h2>Thông tin khách hàng</h2>
            <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($customerName); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($customerPhone); ?></p>
            <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($customerAddress); ?></p>
        </div>


        <h2>Chi tiết đơn hàng</h2>
        <table>
            <tr>
                <th>Tên sản phẩm</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
            <?php
            foreach ($cartData as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                echo "<tr>
                        <td>" . htmlspecialchars($item['name']) . "</td>
                        <td>" . $item['quantity'] . "</td>
                        <td>" . number_format($item['price'], 0, ',', '.') . " VND</td>
                        <td>" . number_format($subtotal, 0, ',', '.') . " VND</td>
                      </tr>";
            }
            ?>
        </table>


        <div class="total-section">
            <p><strong>Tổng tiền:</strong> <?php echo number_format($total, 0, ',', '.') . " VND"; ?></p>
            <p><strong>Giảm giá:</strong> <?php echo number_format($total - $finalPrice, 0, ',', '.') . " VND"; ?></p>
            <p><strong>Khách cần trả:</strong> <?php echo number_format($finalPrice, 0, ',', '.') . " VND"; ?></p>
            <p><strong>Tiền khách đưa:</strong> <?php echo number_format($cashReceived, 0, ',', '.') . " VND"; ?></p>
            <p><strong>Tiền thối:</strong> <?php echo number_format($changeAmount, 0, ',', '.') . " VND"; ?></p>
        </div>


        <div class="buttons">
            <button class="print-btn" onclick="window.print()">In hóa đơn</button>
            <a href="manage_orders.php" class="back-btn">Quay lại</a>
        </div>
    </div>
</body>
</html>



