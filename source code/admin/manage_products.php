<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Picqer\Barcode\BarcodeGeneratorPNG;

// Kiểm tra quyền truy cập (chỉ admin mới có thể quản lý sản phẩm)
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập trang này.");
}

$message = "";

// Lúc này, biến $conn đã có từ config.php rồi, không cần tạo lại.

// Thêm biến cho đường dẫn mặc định
$default_avatar = '../image/default-avatar.png';

// Xử lý thêm sản phẩm
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $brand = 'Apple';
    $import_price = (float)$_POST['import_price'];
    $retail_price = (float)$_POST['retail_price'];
    $stock = (int)$_POST['stock'];
    $category_id = $_POST['category'];

    // Kiểm tra ràng buộc
    $errors = [];
    
    // Kiểm tra tên sản phẩm trùng
    $check_sql = "SELECT COUNT(*) as count FROM products WHERE name = ? AND deleted_at IS NULL";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $message = "Tên sản phẩm đã tồn tại trong danh sách sản phẩm";
        echo "<script>alert('$message');</script>";
        exit();
    }
    
    if ($import_price <= 0) {
        $errors[] = "Giá nhập khẩu phải lớn hơn 0";
    }
    
    if ($retail_price <= 0) {
        $errors[] = "Giá bán lẻ phải lớn hơn 0";
    }
    
    if ($retail_price <= $import_price) {
        $errors[] = "Giá bán lẻ phải lớn hơn giá nhập khẩu";
    }
    
    if ($stock < 0) {
        $errors[] = "Số lượng không được âm";
    }

    if (empty($errors)) {
        // Tạo mã vạch tự động
        $barcode = substr(uniqid(), 0, 8);
        $generator = new BarcodeGeneratorPNG();
        $barcodeImage = $generator->getBarcode($barcode, $generator::TYPE_CODE_128);

        // Lưu ảnh mã vạch
        $barcode_filename = 'barcodes/' . $barcode . '.png';
        if (!is_dir('uploads/barcodes')) {
            mkdir('uploads/barcodes', 0777, true);
        }
        file_put_contents('uploads/' . $barcode_filename, $barcodeImage);

        // Xử lý upload ảnh sản phẩm
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $image_path = '../image/' . $image_name;
            if (!is_dir('../image')) {
                mkdir('../image', 0777, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
            $image = $image_name;
        }

        // Lưu sản phẩm vào database
        $stmt = $conn->prepare("INSERT INTO products (name, brand, import_price, retail_price, stock, category_id, image, barcode, barcode_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdiisss", $name, $brand, $import_price, $retail_price, $stock, $category_id, $image, $barcode, $barcode_filename);
        
        if ($stmt->execute()) {
            header("Location: manage_products.php");
            exit();
        } else {
            $message = "❌ Lỗi khi thêm sản phẩm!";
        }
    } else {
        $message = "⚠ " . implode("<br>⚠ ", $errors);
    }
}

// Xử lý xóa sản phẩm (ẩn thay vì xóa hẳn)
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Kiểm tra xem sản phẩm có trong đơn hàng nào không
    $check_order_sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
    $check_order_stmt = $conn->prepare($check_order_sql);
    $check_order_stmt->bind_param("i", $product_id);
    $check_order_stmt->execute();
    $order_result = $check_order_stmt->get_result();
    $order_count = $order_result->fetch_assoc()['count'];
    
    if ($order_count > 0) {
        $message = "Không thể xóa sản phẩm vì sản phẩm đã được mua trong đơn hàng";
        echo "<script>
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = '$message';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('hide');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        </script>";
    } else {
        $stmt = $conn->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
    }
    header("Location: manage_products.php");
    exit();
}

// Xử lý khôi phục sản phẩm
if (isset($_GET['restore'])) {
    $product_id = $_GET['restore'];
    $stmt = $conn->prepare("UPDATE products SET deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    header("Location: manage_products.php");
    exit();
}

// Xử lý xóa vĩnh viễn
if (isset($_GET['permanent_delete'])) {
    $product_id = $_GET['permanent_delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    header("Location: manage_products.php");
    exit();
}

// Thêm phần xử lý kiểm tra tên sản phẩm qua AJAX
if (isset($_GET['check_name'])) {
    header('Content-Type: application/json');
    $name = $_GET['check_name'];
    
    $check_sql = "SELECT COUNT(*) as count FROM products WHERE name = ? AND deleted_at IS NULL";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode(['exists' => $row['count'] > 0]);
    exit;
}

// Thêm phần xử lý kiểm tra sản phẩm trong đơn hàng
if (isset($_GET['check_orders'])) {
    header('Content-Type: application/json');
    $product_id = $_GET['check_orders'];
    
    // Kiểm tra xem sản phẩm có trong đơn hàng nào không
    $check_order_sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
    $check_order_stmt = $conn->prepare($check_order_sql);
    $check_order_stmt->bind_param("i", $product_id);
    $check_order_stmt->execute();
    $order_result = $check_order_stmt->get_result();
    $order_count = $order_result->fetch_assoc()['count'];
    
    echo json_encode(['in_orders' => $order_count > 0]);
    exit();
}

// Phân trang cho danh sách sản phẩm
$items_per_page = 4; // Số sản phẩm mỗi trang
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Lấy tổng số sản phẩm chưa bị xóa
$count_sql = "SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL";
$count_result = $conn->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách sản phẩm chưa bị xóa với phân trang
$sql_active = "SELECT p.id, p.name, p.import_price, p.retail_price, p.stock, c.name AS category_name, p.image, p.barcode, p.barcode_image, p.created_at 
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.deleted_at IS NULL
                ORDER BY p.created_at DESC
                LIMIT $items_per_page OFFSET $offset";

$result_active = $conn->query($sql_active);

// Phân trang cho danh sách sản phẩm đã xóa
$deleted_page = isset($_GET['deleted_page']) ? (int)$_GET['deleted_page'] : 1;
$deleted_offset = ($deleted_page - 1) * $items_per_page;

// Lấy tổng số sản phẩm đã xóa
$count_deleted_sql = "SELECT COUNT(*) as total FROM products WHERE deleted_at IS NOT NULL";
$count_deleted_result = $conn->query($count_deleted_sql);
$total_deleted_items = $count_deleted_result->fetch_assoc()['total'];
$total_deleted_pages = ceil($total_deleted_items / $items_per_page);

// Lấy danh sách sản phẩm đã bị xóa với phân trang
$sql_deleted = "SELECT p.id, p.name, p.import_price, p.retail_price, p.stock, c.name AS category_name, p.image, p.created_at, p.deleted_at 
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.deleted_at IS NOT NULL
                ORDER BY p.deleted_at DESC
                LIMIT $items_per_page OFFSET $deleted_offset";

$result_deleted = $conn->query($sql_deleted);

// Lấy danh mục sản phẩm
$categories = $conn->query("SELECT * FROM categories");

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sidebar_admin.css">

    <title>Quản lý sản phẩm</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            background: #EEF2F6;
            margin: 0;
            padding: 20px;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: calc(100% - 20px);
            min-height: 100vh;
            margin-left: auto;
            margin-right: auto;
        }

        .product-container {
            width: 95%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 90vh;
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            width: 100%;
            max-width: 1400px;
            margin: auto;
            padding: 25px;
        }

        .table-container {
            margin-bottom: 20px;
        }

        .table-wrapper {
            max-height: 500px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper thead tr:first-child {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #1f78d1;
        }

        .table-wrapper thead th {
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 16px;
            border: 2px solid #1f78d1;
        }

        .table-wrapper tbody td {
            padding: 12px;
            border: 2px solid #1f78d1;
            text-align: center;
        }

        .table-wrapper::-webkit-scrollbar {
            width: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            border-radius: 8px;
            overflow: hidden;
            font-size: 15px;
            min-width: 1200px;
        }

        table th, table td {
            border: 2px solid #1f78d1;
            padding: 12px;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        input, select, button {
            width: 100%;
            height: 40px;
            padding: 8px;
            box-sizing: border-box;
            font-size: 15px;
        }

        th {
            background: #1f78d1;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 16px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        tr:hover {
            background: #e6f0fa;
        }

        .delete-btn, .edit-btn, .restore-btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .delete-btn {
            background: #1f78d1;
        }

        .delete-btn:hover {
            background: #1660b0;
        }

        .edit-btn {
            background: #1f78d1;
        }

        .edit-btn:hover {
            background: #1660b0;
        }

        .restore-btn {
            background: #1f78d1;
        }

        .restore-btn:hover {
            background: #1660b0;
        }

        .add-product {
            background: #e6f0fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .barcode-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .barcode-image {
            max-width: 150px;
            height: auto;
        }

        .barcode-number {
            font-size: 12px;
            word-break: break-all;
            text-align: center;
        }

        button[type="submit"] {
            background: #1f78d1;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }

        button[type="submit"]:hover {
            background: #1660b0;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .error-message {
            color: #ff0000;
            background-color: #ffeeee;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            min-width: 300px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .input-error {
            border: 2px solid #ff0000 !important;
        }

        .error-text {
            color: #ff0000;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #1f78d1;
            border-radius: 4px;
            color: #1f78d1;
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #1f78d1;
            color: white;
        }

        .pagination .active {
            background: #1f78d1;
            color: white;
        }

        .pagination .disabled {
            color: #ccc;
            border-color: #ccc;
            pointer-events: none;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: #ff4444;
            color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.hide {
            animation: slideOut 0.5s ease-in;
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>

</head>
<body>
<?php include 'sidebar_admin.php'; ?>

<?php if (!empty($message)): ?>
    <div class="error-message"><?= $message ?></div>
<?php endif; ?>

<div class="container">
<div class="main-content">
    <div class="product-container">
        <!-- Quản lý sản phẩm (Form thêm sản phẩm) -->
        <div class="table-container" id="product-management">
            <h2>Quản lý sản phẩm</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tên sản phẩm</th>
                        <th>Giá nhập khẩu</th>
                        <th>Giá bán lẻ</th>
                        <th>Danh mục</th>
                        <th>Số lượng</th>
                        <th>Hình ảnh</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <form action="manage_products.php" method="POST" enctype="multipart/form-data" id="productForm">
                            <td>
                                <input type="text" name="name" required id="productName" placeholder="Nhập tên sản phẩm">
                                <div class="error-text" id="productNameError"></div>
                            </td>
                            <td>
                                <input type="number" name="import_price" min="0" step="0.01" required id="importPrice">
                                <div class="error-text" id="importPriceError"></div>
                            </td>
                            <td>
                                <input type="number" name="retail_price" min="0" step="0.01" required id="retailPrice">
                                <div class="error-text" id="retailPriceError"></div>
                            </td>
                            <td>
                                <select name="category" required>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="stock" min="0" step="1" required id="stock">
                                <div class="error-text" id="stockError"></div>
                            </td>
                            <td><input type="file" name="image" accept="image/*" required></td>
                            <td><button type="submit" name="add_product">Thêm</button></td>
                        </form>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="table-container" id="product-list">
            <h2>Danh Sách Sản Phẩm</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th width="25px">ID</th>
                            <th>Mã vạch</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá nhập khẩu</th>
                            <th>Giá bán lẻ</th>
                            <th>Danh mục</th>
                            <th>Số lượng</th>
                            <th>Ngày tạo</th>
                            <th>Hình ảnh</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_active->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <div class="barcode-container">
                                        <img src='uploads/<?= $row['barcode_image'] ?>' class="barcode-image">
                                        <div class="barcode-number"><?= $row['barcode'] ?></div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= number_format($row['import_price']) ?> VNĐ</td>
                                <td><?= number_format($row['retail_price']) ?> VNĐ</td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td><?= $row['stock'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <?php if (!empty($row['image']) && file_exists('../image/' . $row['image'])): ?>
                                        <img src='../image/<?= $row['image'] ?>' alt="<?= htmlspecialchars($row['name']) ?>" style="width: 60px; height: 60px; object-fit: contain;">
                                    <?php else: ?>
                                        <img src='<?= $default_avatar ?>' alt="No image" style="width: 60px; height: 60px; object-fit: contain;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href='?delete=<?= $row['id'] ?>' class='delete-btn'>Xóa</a>
                                    <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn edit-btn">Sửa</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang cho danh sách sản phẩm -->
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>">&laquo;</a>
                <?php else: ?>
                    <span class="disabled">&laquo;</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>">&raquo;</a>
                <?php else: ?>
                    <span class="disabled">&raquo;</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sản phẩm đã xóa -->
        <div class="table-container" id="deleted-products">
            <h2>Sản phẩm đã xóa</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Mã sản phẩm</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá bán</th>
                            <th>Danh mục</th>
                            <th>Ngày xóa</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_deleted->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row["id"] ?></td>
                                <td><?= htmlspecialchars($row["name"]) ?></td>
                                <td><?= number_format($row["retail_price"], 0, ',', '.') ?> VNĐ</td>
                                <td><?= htmlspecialchars($row["category_name"]) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row["deleted_at"])) ?></td>
                                <td>
                                    <a href="?restore=<?= $row['id'] ?>" class="restore-btn">Khôi phục</a>
                                    <a href="?permanent_delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn sản phẩm này?')">Xóa vĩnh viễn</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang cho danh sách sản phẩm đã xóa -->
            <div class="pagination">
                <?php if ($deleted_page > 1): ?>
                    <a href="?deleted_page=<?= $deleted_page - 1 ?>">&laquo;</a>
                <?php else: ?>
                    <span class="disabled">&laquo;</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_deleted_pages; $i++): ?>
                    <?php if ($i == $deleted_page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?deleted_page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($deleted_page < $total_deleted_pages): ?>
                    <a href="?deleted_page=<?= $deleted_page + 1 ?>">&raquo;</a>
                <?php else: ?>
                    <span class="disabled">&raquo;</span>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</div>

<script>
// Tự động ẩn thông báo lỗi sau 5 giây
setTimeout(function() {
    var errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.style.display = 'none';
    }
}, 5000);

document.addEventListener('DOMContentLoaded', function() {
    const productName = document.getElementById('productName');
    const importPrice = document.getElementById('importPrice');
    const retailPrice = document.getElementById('retailPrice');
    const form = document.getElementById('productForm');

    // Lấy danh sách tên sản phẩm hiện có
    const existingProducts = <?php 
        $products = [];
        $sql = "SELECT name FROM products WHERE deleted_at IS NULL";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $products[] = $row['name'];
        }
        echo json_encode($products);
    ?>;

    // Thêm validation cho tên sản phẩm
    function validateProductName() {
        const name = productName.value.trim();
        
        // Kiểm tra trùng tên sản phẩm với danh sách hiện có
        if (existingProducts.includes(name)) {
            showNotification('Tên sản phẩm đã tồn tại trong danh sách sản phẩm');
            return false;
        }
        return true;
    }

    // Thêm validation cho giá nhập khẩu
    function validateImportPrice() {
        const price = parseFloat(importPrice.value);
        if (isNaN(price) || price <= 0) {
            showNotification('Giá nhập khẩu phải lớn hơn 0');
            return false;
        }
        return true;
    }

    // Thêm validation cho giá bán lẻ
    function validateRetailPrice() {
        const retail = parseFloat(retailPrice.value);
        const importPrice = parseFloat(importPrice.value);
        
        if (isNaN(retail) || retail <= 0) {
            showNotification('Giá bán lẻ phải lớn hơn 0');
            return false;
        }
        
        if (retail <= importPrice) {
            showNotification('Giá bán lẻ phải lớn hơn giá nhập khẩu');
            return false;
        }
        return true;
    }

    // Hiển thị thông báo
    function showNotification(message) {
        // Xóa thông báo cũ nếu có
        const oldNotification = document.querySelector('.notification');
        if (oldNotification) {
            oldNotification.remove();
        }

        // Tạo thông báo mới
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        document.body.appendChild(notification);

        // Tự động ẩn sau 3 giây
        setTimeout(() => {
            notification.classList.add('hide');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }

    // Validate ngay khi nhập
    productName.addEventListener('input', function() {
        validateProductName();
    });

    importPrice.addEventListener('input', function() {
        validateImportPrice();
        if (retailPrice.value) {
            validateRetailPrice();
        }
    });

    retailPrice.addEventListener('input', function() {
        validateRetailPrice();
    });

    // Validate before form submission
    form.addEventListener('submit', function(e) {
        if (!validateProductName() || !validateImportPrice() || !validateRetailPrice()) {
            e.preventDefault();
        }
    });

    // Xử lý nút xóa sản phẩm
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('href').split('=')[1];
            
            // Kiểm tra xem sản phẩm có trong đơn hàng không
            fetch('manage_products.php?check_orders=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.in_orders) {
                        showNotification('Không thể xóa sản phẩm vì sản phẩm đã được mua trong đơn hàng');
                    } else {
                        if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                            window.location.href = `manage_products.php?delete=${productId}`;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Có lỗi xảy ra khi kiểm tra sản phẩm');
                });
        });
    });
});
</script>
</body>
</html>

<?php
// Đóng kết nối
$conn->close();
?>
