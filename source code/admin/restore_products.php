<?php
include '../config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // Chuyển ID thành số nguyên để bảo mật

    // Cập nhật trạng thái sản phẩm về 1 (đã khôi phục)
    $sql = "UPDATE products SET status = 1 WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Sản phẩm đã được khôi phục!'); window.location.href='manage_products.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi khôi phục sản phẩm!'); window.location.href='manage_products.php';</script>";
    }
} else {
    echo "<script>alert('ID sản phẩm không hợp lệ!'); window.location.href='manage_products.php';</script>";
}
?>
