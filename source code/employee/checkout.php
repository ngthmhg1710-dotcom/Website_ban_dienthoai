<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}

// Lấy order_id từ URL
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

if (empty($order_id)) {
    header("Location: manage_orders.php");
    exit;
}

// Lấy thông tin đơn hàng từ database
$stmt = $conn->prepare("
    SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_orders.php");
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

// Lấy chi tiết sản phẩm trong đơn hàng
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xuất hóa đơn</title>
    <link rel="stylesheet" href="../assets/public_user/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background-color: #f8f8f8;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn-container {
            margin-top: 20px;
            text-align: center;
        }
        button, a {
            padding: 10px 15px;
            margin: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        button {
            background-color:rgb(115, 84, 236);
            color: white;
            border-radius: 5px;
        }
        a {
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
        }
        @media print {
            button, a {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center;">Hóa đơn thanh toán</h1>
        
        <h2>Thông tin khách hàng</h2>
        <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>

        <h2>Chi tiết thanh toán</h2>
        <table border="1" cellspacing="0" cellpadding="8">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($items as $item) {
                    echo "<tr>
                            <td>" . htmlspecialchars($item['product_name']) . "</td>
                            <td>" . $item['quantity'] . "</td>
                            <td>" . number_format($item['price'], 0, ',', '.') . " VND</td>
                            <td>" . number_format($item['total'], 0, ',', '.') . " VND</td>
                          </tr>";
                }
                ?>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Tổng tiền:</td>
                    <td><?php echo number_format($order['total'], 0, ',', '.'); ?> VND</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Giảm giá:</td>
                    <td><?php echo number_format($order['discount'], 0, ',', '.'); ?> VND</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Khách cần trả:</td>
                    <td><?php echo number_format($order['total'] - $order['discount'], 0, ',', '.'); ?> VND</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Tiền nhận:</td>
                    <td><?php echo number_format($order['cash_received'], 0, ',', '.'); ?> VND</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Tiền thối:</td>
                    <td><?php echo number_format($order['change_amount'], 0, ',', '.'); ?> VND</td>
                </tr>
            </tbody>
        </table>

        <div class="btn-container">
            <button onclick="printAndRedirect()">In hóa đơn</button>
            <a href="manage_orders.php">Quay lại</a>
        </div>
    </div>

    <script>
        function printAndRedirect() {
            window.print();
            
            // Lưu thông báo thành công vào session
            fetch('save_success_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=<?php echo $order_id; ?>&total=<?php echo $order['total']; ?>&customer_name=<?php echo urlencode($order['customer_name']); ?>&customer_phone=<?php echo urlencode($order['customer_phone']); ?>&customer_id=<?php echo $order['customer_id']; ?>'
            }).then(() => {
                // Chuyển hướng về trang quản lý đơn hàng sau khi in
                window.location.href = 'manage_orders.php?success=1';
            });
        }
    </script>
</body>
</html>
