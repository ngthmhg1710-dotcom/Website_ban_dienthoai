<?php
// Quản lý khách hàng
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . "/../config/config.php";

$message = "";

// Xử lý xóa khách hàng
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("UPDATE customers SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Khách hàng đã bị xóa thành công!";
    } else {
        $message = "Lỗi khi xóa khách hàng!";
    }
}

// Xử lý khôi phục khách hàng
if (isset($_GET['restore'])) {
    $id = $_GET['restore'];
    $stmt = $conn->prepare("UPDATE customers SET is_deleted = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Khách hàng đã được khôi phục!";
    } else {
        $message = "Lỗi khi khôi phục khách hàng!";
    }
}

// Xử lý xóa vĩnh viễn khách hàng
if (isset($_GET['permanent_delete'])) {
    $id = $_GET['permanent_delete'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Khách hàng đã bị xóa vĩnh viễn!";
    } else {
        $message = "Lỗi khi xóa vĩnh viễn khách hàng!";
    }
}

// Xử lý xem lịch sử mua hàng
if (isset($_POST['view_history'])) {
    $customer_id = $_POST['customer_id'];
    
    // Lấy thông tin khách hàng
    $stmt = $conn->prepare("SELECT name, phone FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    $customer = $customer_result->fetch_assoc();
    
    // Lấy lịch sử đơn hàng
    $stmt = $conn->prepare("
        SELECT o.id, o.created_at, o.total, o.discount, o.cash_received, o.change_amount,
               o.payment_method, o.status, o.notes
        FROM orders o 
        WHERE o.customer_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $orders_result = $stmt->get_result();
    
    $orders = [];
    while ($row = $orders_result->fetch_assoc()) {
        // Lấy chi tiết sản phẩm trong đơn hàng
        $stmt_items = $conn->prepare("
            SELECT p.name, oi.quantity, oi.price, oi.total as item_total
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt_items->bind_param("i", $row['id']);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        $stmt_items->close();
        
        $row['items'] = $items;
        $orders[] = $row;
    }
    
    $_SESSION['customer_history'] = [
        'customer' => $customer,
        'orders' => $orders
    ];
}

// Lấy danh sách khách hàng (chỉ lấy khách hàng chưa bị xóa)
$result = $conn->query("SELECT * FROM customers WHERE is_deleted = 0 ORDER BY id DESC");

// Lấy danh sách khách hàng đã xóa
$deleted_result = $conn->query("SELECT * FROM customers WHERE is_deleted = 1 ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sidebar_employee.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Quản lý Khách Hàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #EEF2F6;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 25px;
            background: #EEF2F6;
            border-radius: 12px;
        }

        h2 {
            color: #1f78d1;
            font-size: 28px;
            margin-bottom: 25px;
            text-align: center;
        }

        h3 {
            color: #1f78d1;
            font-size: 22px;
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #1f78d1;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #1f78d1;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .message {
            color: #28a745;
            text-align: center;
            padding: 10px;
            margin: 10px 0;
            background: #d4edda;
            border-radius: 4px;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1f78d1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #1864b4;
        }

        .btn-danger, .btn-success {
            padding: 8px 16px;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
            margin: 0 5px;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .view-history-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            margin-right: 5px;
        }

        .view-history-btn:hover {
            background: #138496;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: flex-start;
            align-items: center;
        }

        .scrollable-table {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .scrollable-table::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-table::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .scrollable-table::-webkit-scrollbar-thumb {
            background: #1f78d1;
            border-radius: 4px;
        }

        .scrollable-table::-webkit-scrollbar-thumb:hover {
            background: #1660b0;
        }
        
        /* Thêm CSS cho lịch sử mua hàng */
        .history-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .history-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 1000px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .close-history {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-history:hover {
            color: black;
        }
        
        .history-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .history-table th {
            background: #1f78d1;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .history-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .history-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .order-details {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .order-items {
            margin-top: 10px;
        }
        
        .order-items table {
            width: 100%;
            margin-top: 10px;
        }
        
        .success-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }
        
        .notification-content {
            margin: 0;
            font-size: 16px;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body>
<?php include 'sidebar_employee.php'; ?>

    <div class="container">
        <h2>Quản lý Khách Hàng</h2>

        <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

        <?php if (isset($_SESSION['customer_update'])): ?>
        <div class="success-notification" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        ">
            <p style="margin: 0; font-size: 16px;">
                <strong><?php echo $_SESSION['customer_update']['message']; ?></strong>
            </p>
        </div>
        <script>
            // Tự động ẩn thông báo sau 5 giây
            setTimeout(function() {
                document.querySelector('.success-notification').style.animation = 'slideOut 0.5s ease-in';
                setTimeout(function() {
                    document.querySelector('.success-notification').remove();
                }, 500);
            }, 5000);
        </script>
        <style>
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        </style>
        <?php 
            // Xóa thông báo sau khi hiển thị
            unset($_SESSION['customer_update']);
        endif; 
        ?>

        <?php if (isset($_SESSION['current_customer'])): ?>
        <?php 
            // Xóa thông tin khách hàng hiện tại khỏi session
            unset($_SESSION['current_customer']);
        endif; 
        ?>

        <!-- Danh sách khách hàng -->
        <div class="table-container">
            <h3>Danh Sách Khách Hàng</h3>
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th>Địa chỉ</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $result = $conn->query("SELECT * FROM customers WHERE is_deleted = 0 ORDER BY id DESC");
                        while ($row = $result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?= $row["id"] ?></td>
                            <td><?= htmlspecialchars($row["name"]) ?></td>
                            <td><?= htmlspecialchars($row["phone"]) ?></td>
                            <td><?= htmlspecialchars($row["email"]) ?></td>
                            <td><?= htmlspecialchars($row["address"]) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="view-history-btn" onclick="viewHistory(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')">Xem lịch sử</button>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa khách hàng này?')">Xóa</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Danh sách khách hàng đã xóa -->
        <div class="table-container">
            <h3>Danh Sách Khách Hàng Đã Xóa</h3>
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th>Địa chỉ</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $deleted_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row["id"] ?></td>
                            <td><?= htmlspecialchars($row["name"]) ?></td>
                            <td><?= htmlspecialchars($row["phone"]) ?></td>
                            <td><?= htmlspecialchars($row["email"]) ?></td>
                            <td><?= htmlspecialchars($row["address"]) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?restore=<?= $row['id'] ?>" class="btn-success">Khôi phục</a>
                                    <a href="?permanent_delete=<?= $row['id'] ?>" class="btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn khách hàng này?')">Xóa vĩnh viễn</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <a href="dashboard.php" class="back-btn">Quay lại</a>
    </div>
    
    <!-- Modal hiển thị lịch sử mua hàng -->
    <div id="historyModal" class="history-modal">
        <div class="history-content">
            <span class="close-history">&times;</span>
            <div class="history-header">
                <h2>Lịch sử mua hàng</h2>
                <h3 id="customerName"></h3>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Giảm giá</th>
                                <th>Phương thức thanh toán</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Hàm xem lịch sử mua hàng
        function viewHistory(customerId, customerName) {
            $.ajax({
                url: 'get_customer_history.php',
                type: 'POST',
                data: { customer_id: customerId },
                dataType: 'json',
                success: function(response) {
                    let html = '';
                    if (response.length > 0) {
                        response.forEach(function(order) {
                            html += `
                                <tr>
                                    <td>#${order.id}</td>
                                    <td>${order.order_date}</td>
                                    <td>${order.total_amount}</td>
                                    <td>${parseFloat(order.discount).toLocaleString('vi-VN')} VND</td>
                                    <td>${order.payment_method}</td>
                                    <td>${order.status}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html = '<tr><td colspan="6" class="text-center">Không có đơn hàng nào</td></tr>';
                    }
                    $('#historyTableBody').html(html);
                    $('#customerName').text(customerName);
                    document.getElementById('historyModal').style.display = 'block';
                },
                error: function() {
                    alert('Có lỗi xảy ra khi tải lịch sử mua hàng');
                }
            });
        }
        
        // Đóng modal khi click nút close
        document.querySelector('.close-history').onclick = function() {
            document.getElementById('historyModal').style.display = 'none';
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            let modal = document.getElementById('historyModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
