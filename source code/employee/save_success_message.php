<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST["order_id"];
    $total = $_POST["total"];
    $customer_name = $_POST["customer_name"];
    $customer_phone = $_POST["customer_phone"];
    $customer_id = $_POST["customer_id"];

    $_SESSION["checkout_success"] = [
        "message" => "Thanh toán thành công!",
        "order_id" => $order_id,
        "total" => $total,
        "customer_name" => $customer_name,
        "customer_phone" => $customer_phone,
        "customer_id" => $customer_id
    ];

    // Lưu thông báo cập nhật khách hàng
    if (isset($_POST['customer_update'])) {
        $_SESSION['customer_update'] = $_POST['customer_update'];
    }

    echo json_encode(["status" => "success"]);
}
?> 