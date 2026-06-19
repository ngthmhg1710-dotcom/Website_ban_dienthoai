<?php
if (!isset($_GET['name']) || !isset($_GET['phone']) || !isset($_GET['address']) || !isset($_GET['cart'])) {
    die("Dữ liệu không hợp lệ.");
}

$customerName = htmlspecialchars($_GET['name']);
$customerPhone = htmlspecialchars($_GET['phone']);
$customerAddress = htmlspecialchars($_GET['address']);
$cartData = json_decode($_GET['cart'], true);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn</title>
    <link rel="stylesheet" href="../assets/public_user/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Hóa đơn thanh toán</h1>
        <p><strong>Khách hàng:</strong> <?php echo $customerName; ?></p>
        <p><strong>Số điện thoại:</strong> <?php echo $customerPhone; ?></p>
        <p><strong>Địa chỉ:</strong> <?php echo $customerAddress; ?></p>
        
        <h2>Chi tiết đơn hàng</h2>
        <table>
            <tr>
                <th>Tên sản phẩm</th>
                <th>Số lượng</th>
                <th>Giá</th>
            </tr>
            <?php 
            $total = 0;
            foreach ($cartData as $item) { 
                $total += $item['price'] * $item['quantity'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VND</td>
                </tr>
            <?php } ?>
        </table>
        <p><strong>Tổng tiền:</strong> <?php echo number_format($total, 0, ',', '.'); ?> VND</p>
        <button onclick="window.print()">In hóa đơn</button>
    </div>
</body>
</html>
