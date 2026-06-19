<?php
session_start();
require_once __DIR__ . "/../config/config.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Kiểm tra dữ liệu đầu vào
        if (!isset($_POST["customer_id"]) || !isset($_POST["total"]) || !isset($_POST["items"])) {
            throw new Exception("Thiếu thông tin cần thiết");
        }

        $customer_id = $_POST["customer_id"];
        $total = $_POST["total"];
        $discount = $_POST["discount"] ?? 0;
        $cash_received = $_POST["cash_received"];
        $change_amount = $_POST["change_amount"];
        $payment_method = $_POST["payment_method"] ?? "Tiền mặt";
        $notes = $_POST["notes"] ?? "";
        $items = json_decode($_POST["items"], true);

        if (!$items || !is_array($items)) {
            throw new Exception("Dữ liệu sản phẩm không hợp lệ");
        }

        // Bắt đầu transaction
        $conn->begin_transaction();

        try {
            // Lưu đơn hàng
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, total, discount, cash_received, change_amount, payment_method, status, notes) VALUES (?, ?, ?, ?, ?, ?, 'Đã thanh toán', ?)");
            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
            }

            $stmt->bind_param("iddddss", $customer_id, $total, $discount, $cash_received, $change_amount, $payment_method, $notes);
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi lưu đơn hàng: " . $stmt->error);
            }

            $order_id = $conn->insert_id;

            // Lưu chi tiết đơn hàng
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị câu lệnh SQL chi tiết: " . $conn->error);
            }
            
            foreach ($items as $item) {
                if (!isset($item["id"]) || !isset($item["quantity"]) || !isset($item["price"])) {
                    throw new Exception("Dữ liệu sản phẩm không đầy đủ");
                }

                $product_id = $item["id"];
                $quantity = $item["quantity"];
                $price = $item["price"];
                $item_total = $price * $quantity;

                $stmt->bind_param("iiidd", $order_id, $product_id, $quantity, $price, $item_total);
                
                if (!$stmt->execute()) {
                    throw new Exception("Lỗi khi lưu chi tiết đơn hàng: " . $stmt->error);
                }

                // Cập nhật số lượng tồn kho
                $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                if (!$update_stock) {
                    throw new Exception("Lỗi chuẩn bị câu lệnh SQL cập nhật tồn kho: " . $conn->error);
                }

                $update_stock->bind_param("ii", $quantity, $product_id);
                if (!$update_stock->execute()) {
                    throw new Exception("Lỗi khi cập nhật tồn kho: " . $update_stock->error);
                }
            }

            // Cập nhật thông tin khách hàng
            $update_customer = $conn->prepare("UPDATE customers SET last_purchase_date = NOW() WHERE id = ?");
            if (!$update_customer) {
                throw new Exception("Lỗi chuẩn bị câu lệnh SQL cập nhật khách hàng: " . $conn->error);
            }

            $update_customer->bind_param("i", $customer_id);
            if (!$update_customer->execute()) {
                throw new Exception("Lỗi khi cập nhật thông tin khách hàng: " . $update_customer->error);
            }

            // Commit transaction
            $conn->commit();

            // Lưu thông báo thành công
            $_SESSION["checkout_success"] = [
                "message" => "Thanh toán thành công!",
                "order_id" => $order_id,
                "total" => $total,
                "customer_name" => $_POST["customer_name"],
                "customer_phone" => $_POST["customer_phone"]
            ];

            echo json_encode([
                "status" => "success",
                "message" => "Lưu đơn hàng thành công",
                "order_id" => $order_id
            ]);

        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Lỗi khi lưu đơn hàng: " . $e->getMessage());
        echo json_encode([
            "status" => "error",
            "message" => "Lỗi khi lưu đơn hàng: " . $e->getMessage()
        ]);
    }

    if (isset($stmt)) $stmt->close();
    if (isset($update_stock)) $update_stock->close();
    $conn->close();
    exit;
}
?> 