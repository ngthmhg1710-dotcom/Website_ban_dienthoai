<?php
require_once __DIR__ . "/../config/config.php";

// Kiểm tra ID sản phẩm hợp lệ
$id = $_GET["id"] ?? 0;
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo "Sản phẩm không tồn tại!";
    exit;
}

// Lấy danh sách danh mục
$categories = [];
$result = $conn->query("SELECT * FROM categories");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Xử lý cập nhật sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $import_price = $_POST["import_price"];
    $retail_price = $_POST["retail_price"];
    $category = $_POST["name"];
    $stock = $_POST["stock"];

    $stmt = $conn->prepare("UPDATE products SET name=?, import_price=?, retail_price=?, name=?, stock=? WHERE id=?");
    $stmt->bind_param("sddsdi", $name, $import_price, $retail_price, $name, $stock, $id);
    $stmt->execute();
    
    header("Location: manage_products.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Thông Tin Sản Phẩm</title>
    <link rel="stylesheet" href="css/edit_products.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
    color: black;
}

body {
    display: flex;
    justify-content: center; /* Căn trái */
    align-items: center;
    height: 100vh; /* Dịch sang trái */
    padding-left: 50px;
    background:rgb(219, 230, 241);
}

label {
    display: block;
    margin: 12px 0 6px;
    font-size: 24px;
    font-weight: 400;
    color: black;
}

input {
    width: 100%;
    padding: 10px;
    border: 2px solid transparent; /* Xóa viền cũ */
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.2);
    color: black;
    font-size: 14px;
    transition: 0.3s ease-in-out;
    outline: none;
    box-shadow: 0px 0px 2px 2px rgba(0, 114, 255, 0.4); /* Viền gradient */
}

input:focus {
    border: 2px solid transparent;
    box-shadow: 0px 0px 4px 2px rgba(0, 114, 255, 0.6); 
    background: rgba(255, 255, 255, 0.3);
}

input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}
     .back, .update {
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            background: #1f78d1;
        }

        .back:hover, .update:hover {
            background:rgb(24, 98, 173);
        }

    </style>

</head>
<body>
    <div class="container">
        <div class="headerr"><h2>Cập Nhật Thông Tin Sản Phẩm</h2></div>

        <div class="form-container">
            <form method="POST">
                <label>Tên sản phẩm:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product["name"]); ?>" required>

                <label>Giá nhập:</label>
                <input type="number" name="import_price" value="<?= htmlspecialchars($product['import_price']); ?>" required>

                <label>Giá bán lẻ:</label>
                <input type="number" name="retail_price" value="<?= htmlspecialchars($product['retail_price']); ?>" required>

                <label>Danh mục:</label>
                <select name="category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['id']) ?>" 
                            <?= (isset($product['category_id']) && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Số lượng:</label>
                <input type="number" name="stock" value="<?php echo htmlspecialchars($product["stock"]); ?>" required>

                <label>Hình ảnh:</label>
                <input type="text" name="image" value="<?= htmlspecialchars($product["image"]); ?>" required>

                <div class="button-container">
                    <a class="back" href="manage_products.php">⬅ Quay lại</a>
                    <button type="submit" class="update">Cập nhật</button>
                </div>
            </form>
        </div>
        

    </div>

</body>
</html>
