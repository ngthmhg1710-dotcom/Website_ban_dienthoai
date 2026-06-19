<?php 
session_start();

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin, chuyển hướng về trang đăng nhập
if (!isset($_SESSION["logged_in"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/config.php";

$admin = $_SESSION["user"];
$admin["profile_image"] = $admin["profile_image"] ?? "default-avatar.png";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $new_password = trim($_POST["new_password"]);
    $profile_image = $admin["profile_image"];

    if (!empty($_FILES["profile_image"]["name"])) {
        $target_dir = __DIR__ . "/uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = "uploads/" . basename($_FILES["profile_image"]["name"]);
        } else {
            $message = "Lỗi khi tải ảnh lên!";
        }
    }

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, profile_image=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $hashed_password, $profile_image, $admin["id"]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, profile_image=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $email, $profile_image, $admin["id"]);
    }

    if ($stmt->execute()) {
        $_SESSION["user"]["username"] = $username;
        $_SESSION["user"]["email"] = $email;
        $_SESSION["user"]["profile_image"] = $profile_image;
        $message = "Cập nhật thành công!";
    } else {
        $message = "Lỗi khi cập nhật!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa hồ sơ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background:  #EEF2F6;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;

        }
            .profile-container {
        max-width: 600px;
        background: white;
        padding: 40px; /* Giảm padding để không quá rộng */
        margin: 40px auto;
        text-align: center;
        
        /* Bo góc */
        border-radius: 12px; 
        
        /* Bóng đổ mềm mại */
        box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15); 
    }


        h2 {
            color:rgb(39, 114, 255);
            font-weight: 600;
        }

        .profile-header {
            position: relative;
            margin-bottom: 20px;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #4c82ff;
            object-fit: cover;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .profile-img-label:hover .profile-img {
            opacity: 0.7;
        }

        .profile-form label {
            display: flex;
            align-items: center;
            margin: 8px 0 5px;
            font-weight: bold;
        }

        .profile-form label i {
            margin-right: 8px;
            color: #4c82ff;
        }

        .profile-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #4c82ff, #8438ff);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
<?php include 'sidebar_admin.php'; ?>

<div class="main-content">
    <div class="profile-container">
        <h2>Chỉnh sửa hồ sơ</h2>

        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="profile-header">
                <label for="profile_image_input" class="profile-img-label">
                    <img src="<?= htmlspecialchars($admin["profile_image"]) ?>" alt="Avatar" class="profile-img" id="profilePreview">
                </label>
                <input type="file" name="profile_image" id="profile_image_input" accept="image/*" style="display: none;">
            </div>

            <label><i class="fas fa-user"></i>Tên đăng nhập:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($admin["username"]) ?>" required>

            <label><i class="fas fa-envelope"></i>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($admin["email"]) ?>" required>

            <label><i class="fas fa-lock"></i>Mật khẩu mới (để trống nếu không đổi):</label>
            <input type="password" name="new_password">

            <button type="submit">Cập nhật</button>
        </form>
    </div>
</div>
</body>
</html>
