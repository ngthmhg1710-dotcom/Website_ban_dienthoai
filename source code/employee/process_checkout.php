<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST["name"]);
    $phone = $conn->real_escape_string($_POST["phone"]);
    $address = $conn->real_escape_string($_POST["address"]);
    $cart = json_decode($_POST["cart"], true);
    $discount = floatval($_POST["discount"]);

    if (empty($cart)) {
        echo json_encode(["status" => "error", "message" => "Giỏ hàng trống"]);
        exit;
    }

    // ✅ Tìm hoặc tạo khách hàng
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 0");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $customer_id = $row["id"];
        // Cập nhật thông tin khách hàng
        $stmt = $conn->prepare("UPDATE customers SET name = ?, address = ? WHERE id = ? AND is_deleted = 0");
        $stmt->bind_param("ssi", $name, $address, $customer_id);
        $stmt->execute();
        $_SESSION['customer_update'] = [
            'type' => 'update',
            'message' => "✅ Đã cập nhật thông tin khách hàng vào danh sách"
        ];
    } else {
        // Thêm khách hàng mới nếu chưa có
        $stmt = $conn->prepare("INSERT INTO customers (name, phone, address, is_deleted) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $name, $phone, $address);
        $stmt->execute();
        $customer_id = $stmt->insert_id;
        $_SESSION['customer_update'] = [
            'type' => 'add',
            'message' => "✅ Đã thêm khách hàng mới vào danh sách"
        ];
    }
    $stmt->close();

    // ✅ Tính tổng tiền
    $total_price = 0;
    foreach ($cart as $item) {
        $total_price += $item["price"] * $item["quantity"];
    }

    // ✅ Áp dụng giảm giá (nếu có)
    $final_price = $total_price - $discount;
    if ($final_price < 0) $final_price = 0;

    // ✅ Lưu đơn hàng
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, total, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("id", $customer_id, $final_price);
    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "Lỗi khi tạo đơn hàng"]);
        exit;
    }
    $order_id = $stmt->insert_id;
    $stmt->close();

    // ✅ Lưu chi tiết đơn hàng
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $stmt->bind_param("iiid", $order_id, $item["id"], $item["quantity"], $item["price"]);
        $stmt->execute();
    }
    $stmt->close();
    $conn->close();

    // Thêm thông báo thành công vào session
    $_SESSION['checkout_success'] = [
        'message' => 'Thanh toán thành công!',
        'order_id' => $order_id,
        'total' => $final_price,
        'customer_name' => $name
    ];

    // Chuyển hướng sang trang checkout với order_id
    header("Location: checkout.php?name=" . urlencode($name) . 
           "&phone=" . urlencode($phone) . 
           "&address=" . urlencode($address) . 
           "&cart=" . urlencode(json_encode($cart)) . 
           "&discount=" . urlencode($discount) . 
           "&order_id=" . $order_id);
    exit;
}
?>
