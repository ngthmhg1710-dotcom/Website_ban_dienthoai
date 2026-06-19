<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền truy cập (chỉ admin mới có thể thêm sản phẩm)
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền thêm sản phẩm.");
}

// Kết nối database
$conn = new mysqli("localhost", "root", "", "company_db");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $brand = 'Apple'; // Cố định thương hiệu Apple
    $import_price = $_POST['import_price'];
    $retail_price = $_POST['retail_price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category'];

    // Xử lý upload ảnh
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $image_path = 'uploads/' . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        $image = $image_name;
    }

    // Chèn sản phẩm vào database
    $stmt = $conn->prepare("INSERT INTO products (name, brand, import_price, retail_price, stock, category_id, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiis", $name, $brand, $import_price, $retail_price, $stock, $category_id, $image);
    if ($stmt->execute()) {
        echo "Sản phẩm đã được thêm thành công.";
    } else {
        echo "Lỗi khi thêm sản phẩm: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();

    // Quay về trang quản lý sản phẩm
    header("Location: manage_products.php");
    exit();
} else {
    echo "Phương thức không hợp lệ.";
}
