<?php

session_start();
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . "/../config/config.php";

// Xử lý thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "checkout") {
    $customer_name = $conn->real_escape_string($_POST["customer_name"]);
    $customer_phone = $conn->real_escape_string($_POST["customer_phone"]);
    $customer_address = $conn->real_escape_string($_POST["customer_address"]);
    $customer_email = isset($_POST["customer_email"]) ? $conn->real_escape_string($_POST["customer_email"]) : "";
    $cart_data = json_decode($_POST["cart_data"], true);
    $discount = floatval($_POST["discount"]);
    $cash_received = floatval($_POST["cash_received"]);
    $total = floatval($_POST["total"]);
    $final_price = floatval($_POST["final_price"]);
    $change_amount = $cash_received - $final_price;

    // Bắt đầu transaction
    $conn->begin_transaction();

    try {
        // 1. Lưu hoặc cập nhật thông tin khách hàng
        $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 0");
        $stmt->bind_param("s", $customer_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $customer_id = $customer['id'];
            // Cập nhật thông tin khách hàng
            $stmt = $conn->prepare("UPDATE customers SET name = ?, address = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sssi", $customer_name, $customer_address, $customer_email, $customer_id);
            $stmt->execute();
        } else {
            // Thêm khách hàng mới
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, address, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $customer_name, $customer_phone, $customer_address, $customer_email);
            $stmt->execute();
            $customer_id = $conn->insert_id;
        }

        // 2. Tạo đơn hàng mới
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, total, discount, cash_received, change_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, 'Tiền mặt', 'Hoàn thành')");
        $stmt->bind_param("idddd", $customer_id, $total, $discount, $cash_received, $change_amount);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // 3. Thêm chi tiết đơn hàng
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
        foreach ($cart_data as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $stmt->bind_param("iiidi", $order_id, $item['id'], $item['quantity'], $item['price'], $item_total);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        // Lưu thông báo thành công vào session
        $_SESSION['checkout_success'] = [
            'message' => 'Thanh toán thành công!',
            'order_id' => $order_id,
            'total' => $final_price,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_id' => $customer_id
        ];

        // Lưu thông tin cập nhật khách hàng
        $_SESSION['customer_update'] = [
            'type' => $result->num_rows > 0 ? 'update' : 'add',
            'message' => $result->num_rows > 0 ? 
                "✅ Đã cập nhật thông tin khách hàng vào danh sách" : 
                "✅ Đã thêm khách hàng mới vào danh sách",
            'customer' => [
                'id' => $customer_id,
                'name' => $customer_name,
                'phone' => $customer_phone,
                'email' => $customer_email,
                'address' => $customer_address
            ]
        ];

        // Trả về response thành công
        echo json_encode([
            "status" => "success",
            "order_id" => $order_id,
            "message" => "Thanh toán thành công!"
        ]);
        exit;

    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Lỗi khi xử lý đơn hàng: " . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "add_customer") {
    header('Content-Type: application/json'); // Trả về JSON

    $phone = $conn->real_escape_string($_POST["phone"]);
    $name = $conn->real_escape_string($_POST["name"]);
    $address = $conn->real_escape_string($_POST["address"]);

    // Kiểm tra xem khách hàng đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Số điện thoại đã tồn tại."]);
    } else {
        // Chèn khách hàng mới vào database
        $insertStmt = $conn->prepare("INSERT INTO customers (phone, name, address) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $phone, $name, $address);
        
        if ($insertStmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi khi thêm khách hàng."]);
        }
        $insertStmt->close();
    }
    $stmt->close();
    $conn->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["phone"])) {
    header('Content-Type: application/json');
    $phone = $conn->real_escape_string($_POST["phone"]);

    // Kiểm tra khách hàng có tồn tại và chưa bị xóa
    $stmt = $conn->prepare("SELECT id, phone, name, address, email FROM customers WHERE phone = ? AND is_deleted = 0");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        // Lưu thông tin khách hàng vào session
        $_SESSION['current_customer'] = $customer;
        echo json_encode([
            "status" => "found",
            "customer" => $customer
        ]);
    } else {
        // Xóa thông tin khách hàng khỏi session nếu không tìm thấy
        unset($_SESSION['current_customer']);
        echo json_encode(["status" => "not_found"]);
    }
    $stmt->close();
    exit;
}

// Thêm chức năng tìm kiếm khách hàng theo tên
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["name_search"])) {
    header('Content-Type: application/json');
    $name = $conn->real_escape_string($_POST["name_search"]);

    // Tìm kiếm khách hàng theo tên (chưa bị xóa)
    $stmt = $conn->prepare("SELECT id, phone, name, address, email FROM customers WHERE name LIKE ? AND is_deleted = 0");
    $searchTerm = "%$name%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($customer = $result->fetch_assoc()) {
        $customers[] = $customer;
    }
    
    echo json_encode([
        "status" => "found",
        "customers" => $customers
    ]);
    $stmt->close();
    exit;
}

// Thêm chức năng tìm kiếm khách hàng theo số điện thoại
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["phone_search"])) {
    $phone = $conn->real_escape_string($_POST["phone_search"]);

    // Kiểm tra khách hàng có tồn tại và chưa bị xóa
    $stmt = $conn->prepare("SELECT phone, name, address, email FROM customers WHERE phone LIKE ? AND is_deleted = 0");
    $searchTerm = "%$phone%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($customer = $result->fetch_assoc()) {
        $customers[] = $customer;
    }
    
    echo json_encode(["status" => "found", "customers" => $customers]);
    $stmt->close();
    exit;
}

// Thêm chức năng lưu khách hàng mới
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_customer") {
    $phone = $conn->real_escape_string($_POST["phone"]);
    $name = $conn->real_escape_string($_POST["name"]);
    $address = $conn->real_escape_string($_POST["address"]);
    $email = isset($_POST["email"]) ? $conn->real_escape_string($_POST["email"]) : "";

    // Kiểm tra xem khách hàng đã tồn tại và chưa bị xóa chưa
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 0");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Cập nhật thông tin khách hàng hiện có
        $customer = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE customers SET name = ?, address = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $address, $email, $customer["id"]);
        if ($stmt->execute()) {
            echo json_encode(["status" => "updated", "message" => "Cập nhật thông tin khách hàng thành công"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi khi cập nhật thông tin khách hàng"]);
        }
    } else {
        // Kiểm tra xem có khách hàng đã xóa với số điện thoại này không
        $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Khôi phục và cập nhật thông tin khách hàng đã xóa
            $customer = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE customers SET name = ?, address = ?, email = ?, is_deleted = 0 WHERE id = ?");
            $stmt->bind_param("sssi", $name, $address, $email, $customer["id"]);
            if ($stmt->execute()) {
                echo json_encode(["status" => "restored", "message" => "Khôi phục và cập nhật thông tin khách hàng thành công"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Lỗi khi khôi phục khách hàng"]);
            }
        } else {
            // Thêm khách hàng mới
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $phone, $email, $address);
            if ($stmt->execute()) {
                echo json_encode(["status" => "added", "message" => "Thêm khách hàng mới thành công"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Lỗi khi thêm khách hàng mới"]);
            }
        }
    }
    $stmt->close();
    exit;
}

// Xử lý lưu thông tin khách hàng sau khi thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_customer_after_checkout") {
    $phone = $conn->real_escape_string($_POST["phone"]);
    $name = $conn->real_escape_string($_POST["name"]);
    $address = $conn->real_escape_string($_POST["address"]);
    $email = isset($_POST["email"]) ? $conn->real_escape_string($_POST["email"]) : "";

    // Kiểm tra xem khách hàng đã tồn tại và chưa bị xóa chưa
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 0");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Cập nhật thông tin khách hàng hiện có
        $customer = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE customers SET name = ?, address = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $address, $email, $customer["id"]);
        if ($stmt->execute()) {
            $_SESSION['customer_update'] = [
                'type' => 'update',
                'message' => "✅ Đã cập nhật thông tin khách hàng vào danh sách",
                'customer' => [
                    'id' => $customer["id"],
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address
                ]
            ];
            echo json_encode(["status" => "updated", "message" => "Cập nhật thông tin khách hàng thành công"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi khi cập nhật thông tin khách hàng"]);
        }
    } else {
        // Kiểm tra xem có khách hàng đã xóa với số điện thoại này không
        $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Khôi phục và cập nhật thông tin khách hàng đã xóa
            $customer = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE customers SET name = ?, address = ?, email = ?, is_deleted = 0 WHERE id = ?");
            $stmt->bind_param("sssi", $name, $address, $email, $customer["id"]);
            if ($stmt->execute()) {
                $_SESSION['customer_update'] = [
                    'type' => 'restore',
                    'message' => "✅ Đã khôi phục và cập nhật thông tin khách hàng vào danh sách",
                    'customer' => [
                        'id' => $customer["id"],
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'address' => $address
                    ]
                ];
                echo json_encode(["status" => "restored", "message" => "Khôi phục và cập nhật thông tin khách hàng thành công"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Lỗi khi khôi phục khách hàng"]);
            }
        } else {
            // Thêm khách hàng mới
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $phone, $email, $address);
            if ($stmt->execute()) {
                $new_customer_id = $conn->insert_id;
                $_SESSION['customer_update'] = [
                    'type' => 'add',
                    'message' => "✅ Đã thêm khách hàng mới vào danh sách",
                    'customer' => [
                        'id' => $new_customer_id,
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'address' => $address
                    ]
                ];
                echo json_encode(["status" => "added", "message" => "Thêm khách hàng mới thành công"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Lỗi khi thêm khách hàng mới"]);
            }
        }
    }
    $stmt->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["phone"], $_POST["name"], $_POST["address"])) {
    $phone = $conn->real_escape_string($_POST["phone"]);
    $name = $conn->real_escape_string($_POST["name"]);
    $address = $conn->real_escape_string($_POST["address"]);

    // Kiểm tra khách hàng trước khi thêm mới
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $insertStmt = $conn->prepare("INSERT INTO customers (phone, name, address) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $phone, $name, $address);
        $insertStmt->execute();
        $insertStmt->close();
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["history_phone"])) {
    header('Content-Type: application/json');
    $phone = $conn->real_escape_string($_POST["history_phone"]);

    // Tìm ID khách hàng theo số điện thoại (chỉ khách hàng chưa bị xóa)
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 0");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "no_data", "message" => "Không tìm thấy khách hàng với số điện thoại này"]);
        exit;
    }

    $customer = $result->fetch_assoc();
    $customer_id = $customer['id'];
    $stmt->close();

    // Lấy danh sách đơn hàng của khách hàng này
    $stmtOrders = $conn->prepare("
        SELECT o.id, o.created_at, o.total, o.discount, o.cash_received, o.change_amount,
               o.payment_method, o.status, o.notes
        FROM orders o 
        WHERE o.customer_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmtOrders->bind_param("i", $customer_id);
    $stmtOrders->execute();
    $resultOrders = $stmtOrders->get_result();

    $orders = [];
    while ($row = $resultOrders->fetch_assoc()) {
        // Lấy chi tiết sản phẩm trong đơn hàng
        $stmtItems = $conn->prepare("
            SELECT p.name, oi.quantity, oi.price, oi.total as item_total
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmtItems->bind_param("i", $row['id']);
        $stmtItems->execute();
        $resultItems = $stmtItems->get_result();

        $items = [];
        while ($item = $resultItems->fetch_assoc()) {
            $items[] = $item;
        }
        $stmtItems->close();

        $row['items'] = $items;
        $orders[] = $row;
    }

    $stmtOrders->close();
    echo json_encode($orders);
    exit;
}

// Xử lý upload ảnh
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["product_image"])) {
    $target_dir = "../../uploads/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
        $image_path = "products/" . $new_filename;
        // Cập nhật ảnh sản phẩm trong database
        $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
        $stmt->bind_param("si", $image_path, $_POST["product_id"]);
        $stmt->execute();
        header("Location: manage_orders.php");
        exit();
    }
}

$search = isset($_POST['search']) ? $_POST['search'] : '';
$query = "SELECT p.id, p.name, p.retail_price, p.image, p.barcode_image, p.barcode 
          FROM products p 
          WHERE p.deleted_at IS NULL 
          AND (p.name LIKE ? OR p.barcode LIKE ?)";
$stmt = $conn->prepare($query);
$searchTerm = "%$search%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sidebar_employee.css">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" type="text/css" href="../employee/manage_orders.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #EEF2F6;
        margin: 0;
        padding: 0;
    }

    .main-container {
        display: flex;
        width: 100%;
        min-height: 100vh;
    }

    .content-container {
        flex: 1;
        padding: 30px;
        width: 100%;
    }

    .header-section::before {
        display: none;
    }

    .search-box {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        align-items: center;
    }

    .search-box input {
        flex: 1;
        padding: 15px 20px;
        border: 1px solid #ddd;
        border-radius: 12px;
        font-size: 16px;
        transition: 0.3s;
    }

    .search-box input:focus {
        border-color: #1f78d1;
        box-shadow: 0 0 0 2px rgba(31, 120, 209, 0.1);
        outline: none;
    }

    .search-box button {
        padding: 15px 35px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
    }

    .search-box button:hover {
        background: #1864b0;
        transform: translateY(-2px);
    }

    .search-box button[style*="background: #6c757d"] {
        background: #6c757d;
    }

    .search-box button[style*="background: #6c757d"]:hover {
        background: #5a6268;
    }

    .main-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
    }

    .product-section, .customer-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .section-title {
        font-size: 20px;
        font-weight: bold;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #1f78d1;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
        margin-top: 25px;
    }

    .product-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: all 0.3s ease;
    }

    .product-info {
        padding: 20px;
        position: relative;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .product-name {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #2c3e50;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 44px;
    }

    .product-price {
        font-size: 18px;
        color: #1f78d1;
        font-weight: 700;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .barcode-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        margin: 12px 0;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .barcode-image {
        max-width: 120px;
        height: auto;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .barcode-number {
        font-size: 14px;
        color: #333;
        font-family: monospace;
        background: white;
        padding: 6px 12px;
        border-radius: 4px;
        letter-spacing: 1px;
        border: 1px solid #e9ecef;
    }

    .no-barcode {
        color: #666;
        font-style: italic;
        padding: 8px 12px;
        background: white;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }

    .product-quantity {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 12px 0;
    }

    .product-quantity input {
        width: 80px;
        padding: 8px;
        border: 2px solid #e9ecef;
        border-radius: 6px;
        text-align: center;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .add-to-cart-btn {
        width: 100%;
        padding: 12px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: auto;
    }

    .add-to-cart-btn:hover {
        background: #1660b0;
        transform: translateY(-2px);
    }

    .view-toggle {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
    }

    .view-toggle button {
        padding: 12px 30px;
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #666;
    }

    .view-toggle button:hover {
        border-color: #1f78d1;
        color: #1f78d1;
    }

    .view-toggle button.active {
        background: #1f78d1;
        color: white;
        border-color: #1f78d1;
    }

    .product-list {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 15px;
        margin-top: 30px;
    }

    .product-list th, .product-list td {
        padding: 20px;
        text-align: center;
        border: none;
    }

    .product-list th {
        background: #1f78d1;
        color: white;
        font-weight: 600;
        font-size: 16px;
        padding: 15px 20px;
    }

    .product-list tr {
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .product-list tr:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .product-list img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .product-list input[type="number"] {
        width: 100px;
        padding: 12px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        text-align: center;
        font-size: 16px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .product-list input[type="number"]:focus {
        border-color: #1f78d1;
        box-shadow: 0 0 0 3px rgba(31, 120, 209, 0.1);
        outline: none;
    }

    .product-list .add-to-cart {
        padding: 12px 25px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .product-list .add-to-cart:hover {
        background: #1660b0;
        transform: translateY(-2px);
    }

    .image-container {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .customer-info {
        margin-bottom: 25px;
    }

    .customer-info input {
        width: 100%;
        padding: 15px;
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 10px;
        font-size: 15px;
        transition: 0.3s;
    }

    .customer-info input:focus {
        border-color: #1f78d1;
        box-shadow: 0 0 0 2px rgba(31, 120, 209, 0.1);
        outline: none;
    }

    .customer-actions {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
    }

    .customer-actions button {
        flex: 1;
        padding: 15px 25px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .customer-actions button:hover {
        background: #1864b0;
        transform: translateY(-2px);
    }

    .order-summary {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .order-summary table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        margin: 20px 0;
    }

    .order-summary th:first-child {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }

    .order-summary th:last-child {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .order-summary tr {
        background: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .order-summary td:first-child {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }

    .order-summary td:last-child {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .payment-info {
        margin-top: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        font-size: 14px;
    }

    .payment-info p {
        margin: 5px 0;
        font-size: 14px;
        display: flex;
        align-items: center;
    }

    .payment-info strong {
        color: #333;
        min-width: 120px;
        display: inline-block;
    }

    .payment-info input {
        padding: 6px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 120px;
        margin: 0 8px;
        font-size: 14px;
    }

    .payment-info input:focus {
        border-color: #1f78d1;
        box-shadow: 0 0 0 2px rgba(31, 120, 209, 0.1);
        outline: none;
    }

    .checkout-section {
        margin-top: 25px;
        padding: 25px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .checkout-section input {
        width: 100%;
        padding: 15px;
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 10px;
        font-size: 15px;
        transition: 0.3s;
    }

    .checkout-section input:focus {
        border-color: #1f78d1;
        box-shadow: 0 0 0 2px rgba(31, 120, 209, 0.1);
        outline: none;
    }

    .checkout-section button {
        width: 100%;
        padding: 16px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 15px;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 16px;
    }

    .checkout-section button:hover {
        background: #1864b0;
        transform: translateY(-2px);
    }

    .add-to-cart {
        background: #1f78d1;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .add-to-cart:hover {
        background: #1864b0;
        transform: translateY(-2px);
    }

    .product-quantity input {
        width: 80px;
        padding: 8px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        text-align: center;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .add-to-cart-btn {
        width: 100%;
        padding: 10px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: auto;
    }

    .view-toggle {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .view-toggle button {
        padding: 8px 20px;
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
        color: #666;
    }

    .product-list {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        margin-top: 15px;
    }

    .product-list th, .product-list td {
        padding: 12px;
        text-align: center;
        border: none;
    }

    .product-list img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .customer-info {
        margin-bottom: 20px;
    }

    .customer-info input {
        width: 100%;
        padding: 10px;
        margin: 8px 0;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: 0.3s;
    }

    .customer-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .customer-actions button {
        flex: 1;
        padding: 10px 15px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 14px;
    }

    .order-summary {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .payment-info {
        margin-top: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 14px;
    }

    .payment-info p {
        margin: 8px 0;
        font-size: 14px;
        display: flex;
        align-items: center;
    }

    .payment-info strong {
        color: #333;
        min-width: 100px;
        display: inline-block;
    }

    .payment-info input {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        width: 100px;
        margin: 0 6px;
        font-size: 14px;
    }

    .checkout-section {
        margin-top: 20px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .checkout-section button {
        width: 100%;
        padding: 12px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 10px;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 14px;
    }

    .barcode-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        margin: 12px 0;
    }

    .barcode-image {
        max-width: 120px;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .barcode-number {
        font-size: 14px;
        color: #666;
        font-family: monospace;
        background: #f8f9fa;
        padding: 6px 10px;
        border-radius: 6px;
        letter-spacing: 1px;
    }

    .no-barcode {
        color: #666;
        font-style: italic;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .product-image-container {
        position: relative;
        overflow: hidden;
    }

    .upload-form {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.7);
        padding: 10px;
        display: flex;
        gap: 10px;
        transform: translateY(100%);
        transition: transform 0.3s ease;
    }

    .product-image-container:hover .upload-form {
        transform: translateY(0);
    }

    .upload-input {
        flex: 1;
        padding: 8px;
        background: white;
        border-radius: 5px;
        font-size: 14px;
    }

    .upload-btn {
        padding: 8px 15px;
        background: #1f78d1;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .upload-btn:hover {
        background: #1660b0;
    }

    .product-id {
        font-size: 16px;
        color: #666;
        margin-bottom: 10px;
    }

    .product-name {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .product-price {
        font-size: 20px;
        color: #1f78d1;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .barcode-container {
        margin: 15px 0;
    }

    .barcode-image {
        max-width: 200px;
        height: auto;
        margin-bottom: 5px;
    }

    .barcode-number {
        font-family: monospace;
        font-size: 14px;
        color: #333;
        background: #f5f5f5;
        padding: 5px 10px;
        border-radius: 4px;
    }

    .no-barcode {
        color: #666;
        font-style: italic;
    }

    .image-container {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-image {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: 5px;
    }

    .cash-input {
        margin: 15px 0;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .cash-input label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }

    .cash-input input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        margin-bottom: 10px;
    }

    .cash-input input:focus {
        border-color: #1f78d1;
        box-shadow: 0 0 0 2px rgba(31, 120, 209, 0.1);
        outline: none;
    }

    .payment-info {
        margin-top: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .payment-info p {
        margin: 8px 0;
        font-size: 16px;
        display: flex;
        align-items: center;
    }

    .payment-info strong {
        color: #333;
        min-width: 150px;
        display: inline-block;
    }

    .payment-info input {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
        width: 150px;
        margin: 0 10px;
    }

    .payment-info input:focus {
        border-color: #1f78d1;
        box-shadow: 0 0 0 2px rgba(31, 120, 209, 0.1);
        outline: none;
    }

    .auto-label {
        color: #28a745;
        font-size: 14px;
        font-style: italic;
    }

    .input-label {
        color: #dc3545;
        font-size: 14px;
        font-style: italic;
    }

    @media (min-width: 992px) {
        .main-content {
            grid-template-columns: 1.5fr 1fr;
        }
    }

    .search-customer {
        position: relative;
        margin-bottom: 15px;
    }

    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 5px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }

    .search-result-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .search-result-item:hover {
        background: #f5f5f5;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    /* Thêm CSS cho phần lịch sử */
    .history-section {
        margin-top: 20px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
    }

    .history-section h3 {
        color: #1f78d1;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #1f78d1;
    }

    .history-section .table-responsive {
        width: 100%;
        overflow-x: auto;
        margin: 0;
        padding: 0;
        scrollbar-width: thin;
        scrollbar-color: #1f78d1 #f0f0f0;
    }

    /* Tùy chỉnh thanh cuộn cho Chrome, Edge, và Safari */
    .history-section .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .history-section .table-responsive::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 4px;
    }

    .history-section .table-responsive::-webkit-scrollbar-thumb {
        background-color: #1f78d1;
        border-radius: 4px;
        border: 2px solid #f0f0f0;
    }

    .history-section .table-responsive::-webkit-scrollbar-thumb:hover {
        background-color: #1660b0;
    }

    .history-section .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        table-layout: fixed;
    }

    .history-section .table th {
        background: #1f78d1;
        color: white;
        padding: 12px;
        text-align: left;
        position: sticky;
        top: 0;
    }

    .history-section .table td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        word-wrap: break-word;
    }

    .history-section .table tr:hover {
        background-color: #f5f5f5;
    }

    .history-section .list-unstyled {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .history-section .list-unstyled li {
        margin-bottom: 5px;
        font-size: 14px;
    }

  
    /* Căn phải cho các cột số */
    .text-end {
        text-align: right;
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
    }

    .view-history-btn:hover {
        background: #138496;
    }

    /* Custom styles for order history */
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
        border-bottom: 2px solid #1f78d1;
    }

    .history-header h2 {
        color: #1f78d1;
        margin: 0;
        font-size: 24px;
    }

    .history-header h3 {
        color: #1f78d1;
        margin: 10px 0 0 0;
        font-size: 18px;
        font-weight: bold;
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

    .view-history-btn {
        background: #1f78d1;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s;
    }

    .view-history-btn:hover {
        background: #1660b0;
    }
</style>
<body>


<!-- Thêm thông báo thành công -->
<?php if (isset($_SESSION['checkout_success'])): ?>
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
        <strong>✅ <?php echo $_SESSION['checkout_success']['message']; ?></strong><br>
        Mã đơn hàng: #<?php echo $_SESSION['checkout_success']['order_id']; ?><br>
        Khách hàng: <?php echo htmlspecialchars($_SESSION['checkout_success']['customer_name']); ?><br>
        Tổng tiền: <?php echo number_format($_SESSION['checkout_success']['total'], 0, ',', '.'); ?> VNĐ
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
    unset($_SESSION['checkout_success']);
endif; 
?>
<?php if (isset($_SESSION['customer_message'])): ?>
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
        <strong><?php echo $_SESSION['customer_message']; ?></strong>
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
    unset($_SESSION['customer_message']);
endif; 
?>
<?php include 'sidebar_employee.php'; ?>

<div class="main-container">
    <div class="content-container">
        <div class="header-section">
            <div class="search-box">
                <form method="POST" action="">
                    <div style="display: flex; gap: 10px; width: 100%;">
                        <input type="text" id="search" name="search" placeholder="Tìm theo tên sản phẩm hoặc mã vạch..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                        <div style="display: flex; gap: 10px;">
                            <button type="submit">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                            <button type="button" onclick="window.location.href='manage_orders.php'" style="background: #6c757d;">
                                <i class="fas fa-undo"></i> Quay về
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="main-content">
            <div class="product-section">
                <div class="section-title">
                    <i class="fas fa-box"></i>
                    Danh sách sản phẩm
                </div>
                
                <div class="view-toggle">
                    <button class="active" onclick="toggleView('grid')">
                        <i class="fas fa-th-large"></i> Grid
                    </button>
                    <button onclick="toggleView('table')">
                        <i class="fas fa-table"></i> Table
                    </button>
                </div>

                <div class="product-grid" id="grid-view">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <img src="../image/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" class="product-image">
                            </div>
                            <div class="product-info">
                                <div class="product-id">ID: <?php echo $row['id']; ?></div>
                                <div class="product-name"><?php echo $row['name']; ?></div>
                                <div class="product-price"><?php echo number_format($row['retail_price'], 0, ',', '.'); ?> VND</div>
                                <div class="barcode-container">
                                    <?php if (!empty($row['barcode_image'])): ?>
                                        <img src="../admin/uploads/<?php echo $row['barcode_image']; ?>" class="barcode-image" alt="Mã vạch" style="max-width: 150px; height: auto;">
                                        <div class="barcode-number" style="font-size: 14px; margin-top: 5px;"><?php echo $row['barcode']; ?></div>
                                    <?php else: ?>
                                        <div class="no-barcode">Không có mã vạch</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-quantity">
                                    <input type="number" min="1" value="1" class="quantity-input">
                                </div>
                                <button class="add-to-cart-btn" data-id="<?php echo $row['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                </button>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <table class="product-list" id="table-view" style="display: none;">
                    <tr>
                        <th>ID</th>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá bán lẻ</th>
                        <th>Mã vạch</th>
                        <th>Số lượng</th>
                        <th>Thêm</th>
                    </tr>
                    <?php 
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <div class="image-container">
                                    <img src="../image/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" class="product-image">
                                </div>
                            </td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo number_format($row['retail_price'], 0, ',', '.'); ?> VND</td>
                            <td>
                                <div class="barcode-container">
                                    <?php if (!empty($row['barcode_image'])): ?>
                                        <img src="../admin/uploads/<?php echo $row['barcode_image']; ?>" class="barcode-image" alt="Mã vạch" style="max-width: 150px; height: auto;">
                                        <div class="barcode-number" style="font-size: 14px; margin-top: 5px;"><?php echo $row['barcode']; ?></div>
                                    <?php else: ?>
                                        <div class="no-barcode">Không có mã vạch</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><input type="number" min="1" value="1"></td>
                            <td><button class="add-to-cart" data-id="<?php echo $row['id']; ?>">Thêm</button></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>

            <div class="customer-section">
                <div class="customer-info">
                    <h2>Thông tin khách hàng</h2>
                    <input type="text" id="customer_phone" name="phone" placeholder="Nhập số điện thoại..." required>
                    <input type="text" id="customer_name" name="name" placeholder="Họ tên" required readonly>
                    <input type="text" id="customer_address" name="address" placeholder="Địa chỉ" required readonly>
                    <input type="email" id="customer_email" name="email" placeholder="Email (không bắt buộc)" readonly>
                </div>

                <div class="customer-actions">
                    <button type="button" id="view_history_btn" class="view-history-btn">Xem lịch sử</button>
                </div>

                <div class="order-summary">
                    <h2>Chi tiết hóa đơn</h2>
                    <table id="cart">
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Giá</th>
                            <th>Xóa</th>
                        </tr>
                    </table>
                    <div class="payment-info">
                        <p><strong>Tổng tiền:</strong> <span id="total-price">0</span></p>
                        <p><strong>Giảm giá:</strong> <input type="text" id="discount" placeholder="Nhập mã giảm giá"></p>
                        <p><strong>Khách cần trả:</strong> <span id="final-price">0</span></p>
                        <p><strong>Tiền khách đưa:</strong> <input type="number" id="cash-received" placeholder="Nhập số tiền khách đưa"></p>
                        <p><strong>Tiền thối:</strong> <span id="change">0</span></p>
                    </div>
                </div>

                <div class="checkout-section">
                    <button id="checkout">Thanh toán</button>
                </div>
            </div>
        </div>
    </div>
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

<style>
/* Custom styles for order history */
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
    border-bottom: 2px solid #1f78d1;
}

.history-header h2 {
    color: #1f78d1;
    margin: 0;
    font-size: 24px;
}

.history-header h3 {
    color: #1f78d1;
    margin: 10px 0 0 0;
    font-size: 18px;
    font-weight: bold;
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

.view-history-btn {
    background: #1f78d1;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.view-history-btn:hover {
    background: #1660b0;
}
</style>

<script>
    let cartData = []; // Giữ giỏ hàng toàn cục

    // Thêm hàm chuyển đổi view
    function toggleView(view) {
        if (view === 'grid') {
            $('#grid-view').show();
            $('#table-view').hide();
            $('.view-toggle button').removeClass('active');
            $('.view-toggle button:first-child').addClass('active');
        } else {
            $('#grid-view').hide();
            $('#table-view').show();
            $('.view-toggle button').removeClass('active');
            $('.view-toggle button:last-child').addClass('active');
        }
    }

    $(document).ready(function() {
        // Thêm sự kiện click cho các nút chuyển đổi view
        $('.view-toggle button').on('click', function() {
            const view = $(this).text().toLowerCase().includes('grid') ? 'grid' : 'table';
            toggleView(view);
        });

        // Đưa hàm viewHistory ra ngoài document.ready
        window.viewHistory = function() {
            const phone = $('#customer_phone').val().trim();
            if (!phone) {
                alert('⚠️ Vui lòng nhập số điện thoại khách hàng');
                return;
            }

            $('#history_result').html('<div class="loading">Đang tải dữ liệu...</div>').show();
            
            $.ajax({
                url: 'manage_orders.php',
                method: 'POST',
                data: { history_phone: phone },
                dataType: 'json',
                success: function(response) {
                    let historyDiv = $('#history_result');
                    historyDiv.empty();

                    if (!Array.isArray(response) || response.length === 0) {
                        historyDiv.html('<p>Khách hàng chưa có đơn hàng nào.</p>');
                        return;
                    }

                    // Tạo bảng hiển thị lịch sử
                    let historyHTML = `
                        <h3>Lịch sử mua hàng</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày mua</th>
                                        <th>Sản phẩm</th>
                                        <th>Tổng tiền</th>
                                        <th>Giảm giá</th>
                                        <th>Tiền nhận</th>
                                        <th>Tiền thối</th>
                                        <th>Phương thức</th>
                                        <th>Trạng thái</th>
                                        <th>Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    response.forEach(order => {
                        let itemsHTML = '<ul class="list-unstyled">';
                        order.items.forEach(item => {
                            itemsHTML += `<li>${item.name} - SL: ${item.quantity}, Giá: ${parseFloat(item.price).toLocaleString('vi-VN')} VND</li>`;
                        });
                        itemsHTML += '</ul>';

                        historyHTML += `
                            <tr>
                                <td>#${order.id}</td>
                                <td>${new Date(order.created_at).toLocaleString('vi-VN')}</td>
                                <td>${itemsHTML}</td>
                                <td class="text-end">${parseFloat(order.total).toLocaleString('vi-VN')} VND</td>
                                <td class="text-end">${parseFloat(order.discount || 0).toLocaleString('vi-VN')} VND</td>
                                <td class="text-end">${parseFloat(order.cash_received).toLocaleString('vi-VN')} VND</td>
                                <td class="text-end">${parseFloat(order.change_amount).toLocaleString('vi-VN')} VND</td>
                                <td>${order.payment_method || 'Tiền mặt'}</td>
                                <td>${order.status || 'Hoàn thành'}</td>
                                <td>${order.notes || ''}</td>
                            </tr>`;
                    });

                    historyHTML += '</tbody></table></div>';
                    historyDiv.html(historyHTML);
                },
                error: function(xhr, status, error) {
                    console.error("Lỗi AJAX:", status, error);
                    $('#history_result').html('<p>Lỗi khi tải dữ liệu lịch sử: ' + xhr.responseText + '</p>');
                }
            });
        };

        // Thêm sự kiện click cho nút xem lịch sử
        $('#view_history_btn').on('click', function() {
            const phone = $('#customer_phone').val().trim();
            if (!phone) {
                alert('⚠️ Vui lòng nhập số điện thoại khách hàng');
                return;
            }

            // Hiển thị loading
            $('#historyTableBody').html('<tr><td colspan="6" class="text-center">Đang tải dữ liệu...</td></tr>');
            document.getElementById('historyModal').style.display = 'block';

            // Gọi AJAX
            $.ajax({
                url: 'manage_orders.php',
                type: 'POST',
                data: { history_phone: phone },
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response); // Debug

                    if (!Array.isArray(response) || response.length === 0) {
                        $('#historyTableBody').html('<tr><td colspan="6" class="text-center">Khách hàng chưa có đơn hàng nào.</td></tr>');
                        return;
                    }

                    // Cập nhật tên khách hàng
                    $('#customerName').text($('#customer_name').val());
                    
                    // Hiển thị danh sách đơn hàng
                    let html = '';
                    response.forEach(order => {
                        const orderDate = new Date(order.created_at).toLocaleString('vi-VN');
                        html += `
                            <tr>
                                <td>#${order.id}</td>
                                <td>${orderDate}</td>
                                <td>${parseFloat(order.total).toLocaleString('vi-VN')} VNĐ</td>
                                <td>${parseFloat(order.discount || 0).toLocaleString('vi-VN')} VNĐ</td>
                                <td>${order.payment_method || 'Tiền mặt'}</td>
                                <td>${order.status || 'Hoàn thành'}</td>
                            </tr>
                        `;
                    });
                    $('#historyTableBody').html(html);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error); // Debug
                    $('#historyTableBody').html('<tr><td colspan="6" class="text-center">Có lỗi xảy ra khi tải dữ liệu.</td></tr>');
                }
            });
        });

        // Đóng modal khi click nút close
        $('.close-history').on('click', function() {
            $('#historyModal').hide();
        });

        // Đóng modal khi click bên ngoài
        $(window).on('click', function(event) {
            if ($(event.target).is('#historyModal')) {
                $('#historyModal').hide();
            }
        });

        // 🛒 Thêm sản phẩm vào giỏ hàng
        $(document).on("click", ".add-to-cart-btn, .add-to-cart", function () {
            let productId = $(this).data("id");
            let name, price, quantity;

            // Xử lý cho cả grid view và table view
            if ($(this).hasClass('add-to-cart-btn')) {
                // Grid view
                let card = $(this).closest('.product-card');
                name = card.find('.product-name').text();
                price = parseInt(card.find('.product-price').text().replace(/\D/g, ''));
                quantity = parseInt(card.find('.quantity-input').val());
            } else {
                // Table view
                let row = $(this).closest('tr');
                name = row.find('td:nth-child(3)').text();
                price = parseInt(row.find('td:nth-child(4)').text().replace(/\D/g, ''));
                quantity = parseInt(row.find('td:nth-child(6) input').val());
            }

            if (quantity <= 0 || isNaN(quantity)) {
                alert("Vui lòng nhập số lượng hợp lệ!");
                return;
            }

            let existing = cartData.find(item => item.id === productId);
            if (existing) {
                existing.quantity += quantity;
            } else {
                cartData.push({ id: productId, name, price, quantity });
            }

            updateCart();
        });

        // 🔄 Cập nhật giỏ hàng
        function updateCart() {
            let cartHTML = `<tr><th>Tên sản phẩm</th><th>Số lượng</th><th>Giá</th><th>Xóa</th></tr>`;
            let total = 0;

            cartData.forEach((item) => {
                let itemTotal = item.price * item.quantity;
                total += itemTotal;
                cartHTML += `<tr>
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>${itemTotal.toLocaleString()} VND</td>
                    <td><button class="remove-item" data-id="${item.id}">X</button></td>
                </tr>`;
            });

            $('#cart').html(cartHTML);
            $('#total-price').text(total.toLocaleString() + ' VND');
            $('#final-price').text(total.toLocaleString() + ' VND');
            $('#discount').val(''); // Reset discount when cart changes
            $('#cash-received').val(''); // Reset cash received when cart changes
            $('#change').text('0 VND');
        }

        // ❌ Xóa sản phẩm khỏi giỏ hàng
        $(document).on("click", ".remove-item", function () {
            let productId = $(this).data("id");
            cartData = cartData.filter(item => item.id !== productId);
            updateCart();
        });

        // 💰 Tính tiền thối
        $('#cash-received').on('input', function() {
            let cashReceived = parseFloat($(this).val()) || 0;
            let finalPrice = parseFloat($('#final-price').text().replace(/[^\d]/g, '')) || 0;
            let change = cashReceived - finalPrice;
            
            if (change < 0) {
                $('#change').text("Không đủ tiền");
                $('#checkout').prop('disabled', true);
            } else {
                $('#change').text(change.toLocaleString() + " VND");
                $('#checkout').prop('disabled', false);
            }
        });

        // 🎁 Áp dụng giảm giá
        $('#discount').on("input", function () {
            let discountVal = $(this).val().trim();
            let originalTotal = parseFloat($("#total-price").text().replace(/[^\d]/g, '')) || 0;
            let total = originalTotal;
            let discountAmount = 0;

            if (discountVal === "") {
                $('#final-price').text(total.toLocaleString() + " VND");
                // Cập nhật lại tiền thối nếu có
                let cashReceived = parseFloat($("#cash-received").val()) || 0;
                let change = cashReceived - total;
                if (change < 0) {
                    $('#change').text("Không đủ tiền");
                    $('#checkout').prop('disabled', true);
                } else {
                    $('#change').text(change.toLocaleString() + " VND");
                    $('#checkout').prop('disabled', false);
                }
                return;
            }

            if (discountVal.includes("%")) {
                let percent = parseFloat(discountVal.replace("%", ""));
                if (isNaN(percent) || percent < 0 || percent > 100) {
                    alert("⚠️ Phần trăm giảm giá phải từ 0% đến 100%");
                    $(this).val("");
                    $('#final-price').text(total.toLocaleString() + " VND");
                    return;
                }
                discountAmount = Math.round(total * (percent / 100));
                total = total - discountAmount;
            } else {
                let discountValue = parseFloat(discountVal.replace(/[^\d]/g, ''));
                if (isNaN(discountValue) || discountValue < 0) {
                    alert("⚠️ Số tiền giảm giá phải là số dương");
                    $(this).val("");
                    $('#final-price').text(total.toLocaleString() + " VND");
                    return;
                }
                if (discountValue > total) {
                    alert("⚠️ Số tiền giảm giá không được lớn hơn tổng tiền");
                    $(this).val("");
                    $('#final-price').text(total.toLocaleString() + " VND");
                    return;
                }
                discountAmount = discountValue;
                total = total - discountAmount;
            }

            $('#final-price').text(total.toLocaleString() + " VND");
            
            // Cập nhật lại tiền thối nếu có
            let cashReceived = parseFloat($("#cash-received").val()) || 0;
            let change = cashReceived - total;
            if (change < 0) {
                $('#change').text("Không đủ tiền");
                $('#checkout').prop('disabled', true);
            } else {
                $('#change').text(change.toLocaleString() + " VND");
                $('#checkout').prop('disabled', false);
            }
        });

        // 🎁 Xử lý thanh toán
        $('#checkout').on('click', function() {
            let customerName = $("#customer_name").val().trim();
            let customerPhone = $("#customer_phone").val().trim();
            let customerAddress = $("#customer_address").val().trim();
            let customerEmail = $("#customer_email").val().trim();
            let finalPrice = parseFloat($("#final-price").text().replace(/[^\d]/g, '')) || 0;
            let cashReceived = parseFloat($("#cash-received").val()) || 0;
            let total = parseFloat($("#total-price").text().replace(/[^\d]/g, '')) || 0;
            let discount = $("#discount").val().trim();
            let change = cashReceived - finalPrice;

            if (!customerName || !customerPhone || !customerAddress) {
                alert("⚠️ Vui lòng nhập đầy đủ thông tin khách hàng.");
                return;
            }

            if (cartData.length === 0) {
                alert("⚠️ Giỏ hàng trống, không thể thanh toán.");
                return;
            }

            if (finalPrice < 0) {
                alert("⚠️ Tổng tiền không hợp lệ. Vui lòng kiểm tra lại mã giảm giá.");
                return;
            }

            if (cashReceived < finalPrice) {
                alert("⚠️ Số tiền khách đưa không đủ.");
                return;
            }

            // Gửi request thanh toán
            $.ajax({
                url: "manage_orders.php",
                type: "POST",
                data: {
                    action: "checkout",
                    customer_name: customerName,
                    customer_phone: customerPhone,
                    customer_address: customerAddress,
                    customer_email: customerEmail,
                    cart_data: JSON.stringify(cartData),
                    discount: discount,
                    cash_received: cashReceived,
                    total: total,
                    final_price: finalPrice,
                    change_amount: change
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        // Chuyển hướng đến trang in hóa đơn
                        let invoiceUrl = "export_invoice_pdf.php?" + 
                            "name=" + encodeURIComponent(customerName) +
                            "&phone=" + encodeURIComponent(customerPhone) +
                            "&address=" + encodeURIComponent(customerAddress) +
                            "&cart=" + encodeURIComponent(JSON.stringify(cartData)) +
                            "&discount=" + encodeURIComponent(discount) +
                            "&cash_received=" + encodeURIComponent(cashReceived) +
                            "&total=" + encodeURIComponent(total) +
                            "&final_price=" + encodeURIComponent(finalPrice) +
                            "&change_amount=" + encodeURIComponent(change);
                        
                        window.location.href = invoiceUrl;
                    } else {
                        alert("Lỗi: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Lỗi AJAX:", error);
                    alert("Lỗi kết nối đến server. Vui lòng thử lại.");
                }
            });
        });

        // 📞 Kiểm tra thông tin khách hàng
        $('#customer_phone').on('change', function() {
            const phone = $(this).val().trim();
            if (phone) {
                $.ajax({
                    url: 'manage_orders.php',
                    method: 'POST',
                    data: { phone: phone },
                    success: function(response) {
                        if (response.status === 'found') {
                            // Nếu tìm thấy khách hàng, tự động điền thông tin
                            const customer = response.customer;
                            $('#customer_name').val(customer.name);
                            $('#customer_address').val(customer.address);
                            $('#customer_email').val(customer.email || '');
                            
                            // Khóa các trường thông tin
                            $('#customer_name').prop('readonly', true);
                            $('#customer_address').prop('readonly', true);
                            $('#customer_email').prop('readonly', true);
                        } else {
                            // Nếu không tìm thấy, cho phép nhập thông tin mới
                            $('#customer_name').val('').prop('readonly', false);
                            $('#customer_address').val('').prop('readonly', false);
                            $('#customer_email').val('').prop('readonly', false);
                        }
                    }
                });
            }
        });

        // Xử lý chọn khách hàng từ kết quả tìm kiếm
        $(document).on('click', '.search-result-item', function() {
            const customer = {
                phone: $(this).data('phone'),
                name: $(this).data('name'),
                address: $(this).data('address'),
                email: $(this).data('email')
            };

            $('#customer_phone').val(customer.phone);
            $('#customer_name').val(customer.name);
            $('#customer_address').val(customer.address);
            $('#customer_email').val(customer.email);
            $('#search_results').hide();
            $('#search_customer').val('');
        });
    });

    // Đóng modal khi click bên ngoài
    window.onclick = function(event) {
        let modal = document.getElementById('historyModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function viewHistory(phone) {
        $.ajax({
            url: 'manage_orders.php',
            type: 'POST',
            data: { history_phone: phone },
            dataType: 'json',
            success: function(response) {
                if (!Array.isArray(response) || response.length === 0) {
                    alert('Khách hàng chưa có đơn hàng nào.');
                    return;
                }

                // Cập nhật tên khách hàng
                $('#customerName').text($('#customer_name').val());
                
                // Hiển thị danh sách đơn hàng
                let html = '';
                response.forEach(order => {
                    const orderDate = new Date(order.created_at).toLocaleString('vi-VN');
                    html += `
                        <tr>
                            <td>#${order.id}</td>
                            <td>${orderDate}</td>
                            <td>${parseFloat(order.total).toLocaleString('vi-VN')} VNĐ</td>
                            <td>${parseFloat(order.discount || 0).toLocaleString('vi-VN')} VNĐ</td>
                            <td>${order.payment_method || 'Tiền mặt'}</td>
                            <td>${order.status || 'Hoàn thành'}</td>
                        </tr>
                    `;
                });
                $('#historyTableBody').html(html);
                
                // Hiển thị modal
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


