<?php 
session_start();

// Kiểm tra nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}

// Kết nối CSDL
require_once __DIR__ . "/../config/config.php";

$filter = $_GET['filter'] ?? 'today';
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

if (empty($start_date) || empty($end_date)) {
    if ($filter == "this_month") {
        $start_date = date("Y-m-01 00:00:00");
        $end_date = date("Y-m-t 23:59:59");
    } else {
        $start_date = date("Y-m-d 00:00:00");
        $end_date = date("Y-m-d 23:59:59");
    }
}



// Xử lý bộ lọc ngày
$condition = "1";
if ($filter == "today") {
    $condition = "DATE(created_at) = CURDATE()";
} elseif ($filter == "yesterday") {
    $condition = "DATE(created_at) = CURDATE() - INTERVAL 1 DAY";
} elseif ($filter == "7days") {
    $condition = "DATE(created_at) >= CURDATE() - INTERVAL 7 DAY";
} elseif ($filter == "this_month") {
    $condition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} elseif ($filter == "custom" && $start_date && $end_date) {
    $condition = "DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
}

// Truy vấn dữ liệu
$sql = "SELECT COUNT(id) AS total_orders, SUM(total) AS total_revenue FROM orders WHERE $condition";
$result = $conn->query($sql);
$summary = $result->fetch_assoc();
$total_orders = $summary['total_orders'] ?? 0;
$total_revenue = $summary['total_revenue'] ?? 0;

// Dữ liệu doanh thu theo tháng
$sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total) AS revenue 
        FROM orders GROUP BY month ORDER BY month ASC";
$revenue_data = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$revenue_json = json_encode($revenue_data);

// Dữ liệu sản phẩm bán theo danh mục
// Lấy danh sách đơn hàng (dùng prepare để tránh lỗi)
$sql_orders = "SELECT o.id, c.name AS customer_name, p.name AS product_name, 
                      oi.price AS unit_price, oi.quantity, o.created_at
               FROM orders o
               JOIN order_items oi ON o.id = oi.order_id
               JOIN products p ON oi.product_id = p.id
               JOIN customers c ON o.customer_id = c.id
               WHERE o.created_at BETWEEN ? AND ?
               ORDER BY o.created_at DESC";



$stmt = $conn->prepare($sql_orders);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result_orders = $stmt->get_result();
$orders = $result_orders->fetch_all(MYSQLI_ASSOC);





// Lấy dữ liệu doanh số theo danh mục
$sql = "SELECT categories.name AS category_name, SUM(order_items.quantity) AS total_sold 
        FROM order_items 
        JOIN products ON order_items.product_id = products.id 
        JOIN categories ON products.category_id = categories.id
        GROUP BY categories.name";
$result = $conn->query($sql);
$brand_data = $result->fetch_all(MYSQLI_ASSOC);
$brand_json = json_encode($brand_data);

// Lấy danh sách các sản phẩm đã bán kèm thông tin khách hàng
// Fetch sold products with time filter
$sql = "SELECT IFNULL(c.name, 'Khách vãng lai') AS customer_name, c.email, 
       p.name AS product_name, 
       IFNULL(cat.name, 'Chưa phân loại') AS category_name, 
       oi.price AS retail_price, oi.quantity, o.created_at 
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
LEFT JOIN customers c ON o.customer_id = c.id
JOIN products p ON oi.product_id = p.id
LEFT JOIN categories cat ON p.category_id = cat.id
WHERE o.created_at BETWEEN ? AND ? 
ORDER BY o.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$sold_products = $result->fetch_all(MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sidebar_employee.css">
    <title>Thống kê doanh thu</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
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
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-box form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-box label {
            font-weight: bold;
            color: #1f78d1;
            font-size: 16px;
        }

        .filter-box select,
        .filter-box input[type="date"],
        .filter-box button {
            padding: 12px 20px;
            border: 1px solid #1f78d1;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .filter-box select:focus,
        .filter-box input[type="date"]:focus {
            border-color: #1660b0;
            box-shadow: 0 0 0 2px rgba(31, 120, 209, 0.1);
            outline: none;
        }

        .filter-box button {
            background: #1f78d1;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            min-width: 120px;
        }

        .filter-box button:hover {
            background: #1660b0;
            transform: translateY(-2px);
        }

        .table-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
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

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            font-size: 14px;
        }

        thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #1f78d1;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
        }

        th {
            color: white;
            font-weight: bold;
            font-size: 15px;
            background: #1f78d1;
        }

        td {
            font-size: 14px;
            color: #333;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .total-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            font-size: 18px;
            color: #1f78d1;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .total-box:hover {
            transform: translateY(-5px);
        }

        .total-box p {
            margin: 10px 0;
            font-size: 20px;
        }

        .total-box strong {
            color: #1660b0;
            font-size: 24px;
        }

        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }

        .chart-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .chart-box:hover {
            transform: translateY(-5px);
        }

        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 15px;
            }

            .filter-box form {
                flex-direction: column;
                align-items: stretch;
            }

            .chart-box {
                min-width: 100%;
            }

            .table-container {
                padding: 15px;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar_employee.php'; ?>

<form method="GET" action=""></form>
<div class="container">
    <h2>Thống kê doanh thu</h2>

    <div class="filter-box">
        <form method="GET">
            <label for="filter">Chọn khoảng thời gian:</label>
            <select name="filter" id="filter" onchange="this.form.submit()">
                <option value="today" <?= ($filter == 'today') ? 'selected' : '' ?>>Hôm nay</option>
                <option value="yesterday" <?= ($filter == 'yesterday') ? 'selected' : '' ?>>Hôm qua</option>
                <option value="7days" <?= ($filter == '7days') ? 'selected' : '' ?>>7 ngày trước</option>
                <option value="this_month" <?= ($filter == 'this_month') ? 'selected' : '' ?>>Tháng này</option>
                <option value="custom" <?= ($filter == 'custom') ? 'selected' : '' ?>>Tùy chọn</option>
            </select>

            <input type="date" name="start_date" value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">
            <input type="date" name="end_date" value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">
            <button type="submit">Lọc</button>
        </form>
        <h3>Khoảng thời gian đã chọn:</h3>
        <?php
        if ($filter == 'today') {
            echo "<p>Hôm nay (" . date("d/m/Y") . ")</p>";
        } elseif ($filter == 'yesterday') {
            echo "<p>Hôm qua (" . date("d/m/Y", strtotime("-1 day")) . ")</p>";
        } elseif ($filter == '7days') {
            echo "<p>7 ngày trước (" . date("d/m/Y", strtotime("-7 days")) . " - " . date("d/m/Y") . ")</p>";
        } elseif ($filter == 'this_month') {
            echo "<p>Tháng này (" . date("d/m/Y", strtotime($start_date)) . " - " . date("d/m/Y", strtotime($end_date)) . ")</p>";
        } elseif ($filter == 'custom' && !empty($start_date) && !empty($end_date)) {
            echo "<p>Từ " . date("d/m/Y", strtotime($start_date)) . " đến " . date("d/m/Y", strtotime($end_date)) . "</p>";
        } else {
            echo "<p>Chưa chọn khoảng thời gian</p>";
        }
        ?>
    </div>

    <div class="total-box">
        <p>Tổng số đơn hàng: <strong><?= number_format($total_orders) ?></strong></p>
        <p>Tổng doanh thu: <strong><?= number_format($total_revenue, 0, ',', '.') ?> VNĐ</strong></p>
    </div>

    <div class="chart-container">
        <div class="chart-box">
            <canvas id="revenueChart"></canvas>
        </div>
        <div class="chart-box">
            <canvas id="brandChart"></canvas>
        </div>
    </div>

    <div class="table-container">
        <h3>Danh sách sản phẩm đã bán</h3>
        <div class="scrollable-table">
            <table>
                <tr>
                    <th>Khách hàng</th>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Thời gian</th>
                </tr>
                <?php foreach ($sold_products as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                    <td><?php echo number_format($item['retail_price'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td><?php echo date("d/m/Y H:i", strtotime($item['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Biểu đồ doanh thu theo tháng
    var revenueData = <?php echo $revenue_json; ?>;
    if (Array.isArray(revenueData) && revenueData.length > 0) {
        var months = revenueData.map(data => data.month);
        var revenues = revenueData.map(data => data.revenue);
        var ctx1 = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Doanh thu theo tháng (VNĐ)',
                    data: revenues,
                    backgroundColor: 'rgba(50, 183, 23, 0.5)',
                    borderColor: 'rgb(106, 211, 41)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Biểu đồ tròn số lượng sản phẩm bán theo danh mục
    var brandData = <?php echo $brand_json; ?>;
    if (Array.isArray(brandData) && brandData.length > 0) {
        var categories = brandData.map(data => data.category_name);
        var sales = brandData.map(data => data.total_sold);
        var ctx2 = document.getElementById('brandChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: sales,
                    backgroundColor: ['#FFE0B2', '#A7C7E7', '#FFF3E0', '#B9FBC0', '#DAB6FC', '#81C784']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>


</body>
</html>
