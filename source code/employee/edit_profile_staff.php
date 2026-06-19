<?php 
session_start();

// Kiểm tra nếu chưa đăng nhập hoặc không phải nhân viên, chuyển hướng về trang đăng nhập
if (!isset($_SESSION["logged_in"]) || $_SESSION["role"] !== "employee") {
    header("Location: ../index.php");
    exit;
}

// Kết nối CSDL
require_once __DIR__ . "/../config/config.php";

// Lấy thông tin nhân viên từ session
$staff = $_SESSION["user"];
$staff["profile_image"] = $staff["profile_image"] ?? "uploads/default-avatar.png";
$message = "";

// Xử lý cập nhật thông tin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $profile_image = $staff["profile_image"];
    $staff_id = $staff["id"]; // Lấy ID nhân viên từ session

    // Xử lý tải ảnh đại diện mới
    if (!empty($_FILES["profile_image"]["name"])) {
        $target_dir = __DIR__ . "/uploads/";

        // Kiểm tra thư mục uploads, nếu chưa có thì tạo
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);

        // Kiểm tra và di chuyển file
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = "uploads/" . basename($_FILES["profile_image"]["name"]);
        } else {
            $message = "Lỗi khi tải ảnh lên!";
        }
    }

    // Nếu có đổi mật khẩu, kiểm tra hợp lệ và cập nhật
    if (!empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $message = "⚠ Mật khẩu xác nhận không khớp!";
        } else {
            // Mã hóa mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Cập nhật cả mật khẩu đã mã hóa và mật khẩu gốc
            $stmt = $conn->prepare("UPDATE users SET password = ?, changed_password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $hashed_password, $new_password, $staff_id);
            
            if ($stmt->execute()) {
                $_SESSION["user"]["changed_password"] = $new_password; // Cập nhật session
                $message = "✅ Đổi mật khẩu thành công!";
            } else {
                $message = "❌ Lỗi khi cập nhật mật khẩu!";
            }
        }
    }

    // Cập nhật thông tin vào database (bao gồm username, email, ảnh đại diện)
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, profile_image=? WHERE id=?");
    $stmt->bind_param("sssi", $username, $email, $profile_image, $staff_id);

    if ($stmt->execute()) {
        // Cập nhật session
        $_SESSION["user"]["username"] = $username;
        $_SESSION["user"]["email"] = $email;
        $_SESSION["user"]["profile_image"] = $profile_image;
        $message = "✅ Cập nhật thành công!";
    } else {
        $message = "❌ Lỗi khi cập nhật!";
    }
}
?>   

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa thông tin Nhân viên</title>
    <link rel="stylesheet" href="sidebar_employee.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background:  #EEF2F6
        }

        .container {
            flex: 1;
            max-width: 600px;
            margin: 40px auto;
            padding: 35px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(31, 120, 209, 0.1);
            width: 80%;

        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #1f78d1;
            font-size: 28px;
            font-weight: 600;
        }

        .profile-img {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }

        .profile-img img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 4px solid #1f78d1;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(31, 120, 209, 0.2);
            transition: transform 0.3s ease;
        }

        .profile-img img:hover {
            transform: scale(1.05);
        }

        .message {
            text-align: center;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message:not(:empty) {
            background: #e3f2fd;
            color: #1f78d1;
            border: 1px solid #1f78d1;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            color: #444;
            font-weight: 500;
            margin-bottom: -5px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input:focus {
            border-color: #1f78d1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(31, 120, 209, 0.1);
        }

        input[type="file"] {
            padding: 10px;
            border: 2px dashed #1f78d1;
            background: #f8faff;
            cursor: pointer;
        }

        button {
            background: #1f78d1;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        button:hover {
            background: #1860aa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 120, 209, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 25px;
            }

            h2 {
                font-size: 24px;
            }

            .profile-img img {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_employee.php'; ?>

    <div class="container">
        <h2>Chỉnh sửa thông tin cá nhân</h2>

        <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

        <div class="profile-img">
            <img src="<?= htmlspecialchars($staff["profile_image"]) ?>" alt="Avatar">
        </div>

        <form method="POST" enctype="multipart/form-data">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" value="<?= htmlspecialchars($staff["username"]) ?>" required>
            
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($staff["email"]) ?>" required>
            
            <label>Mật khẩu mới</label>
            <input type="password" name="new_password" placeholder="Nhập mật khẩu mới nếu muốn thay đổi">

            <label>Xác nhận mật khẩu</label>
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới">
            
            <label>Ảnh đại diện</label>
            <input type="file" name="profile_image" accept="image/*">
            
            <button type="submit">Cập nhật thông tin</button>
        </form>
    </div>
</body>
</html>
