<?php
include '../config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // Chuyển ID thành số nguyên để bảo mật

    // Cập nhật trạng thái sản phẩm về 0 (đã xóa)
    $sql = "UPDATE products SET status = 0 WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Sản phẩm đã được đưa vào thùng rác!'); window.location.href='manage_products.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi xóa sản phẩm!'); window.location.href='manage_products.php';</script>";
    }
} else {
    echo "<script>alert('ID sản phẩm không hợp lệ!'); window.location.href='manage_products.php';</script>";
}
?>
