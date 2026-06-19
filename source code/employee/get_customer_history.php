<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . "/../config/config.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["customer_id"])) {
    $customer_id = $conn->real_escape_string($_POST["customer_id"]);
    
    // Lấy thông tin đơn hàng của khách hàng
    $query = "SELECT o.id, o.created_at as order_date, o.total as total_amount, 
                     o.discount, o.payment_method, o.status
              FROM orders o 
              WHERE o.customer_id = ? 
              ORDER BY o.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = array();
    while ($row = $result->fetch_assoc()) {
        // Format số tiền
        $row['total_amount'] = number_format($row['total_amount'], 0, ',', '.') . ' VNĐ';
        
        // Format ngày tháng
        $row['order_date'] = date('d/m/Y H:i', strtotime($row['order_date']));
        
        // Format discount
        $row['discount'] = $row['discount'] ? $row['discount'] . '%' : '0%';
        
        $orders[] = $row;
    }
    
    echo json_encode($orders);
    exit;
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}

echo json_encode(["status" => "error", "message" => "Yêu cầu không hợp lệ"]);
exit; 