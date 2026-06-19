<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"];
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $_SESSION["user_id"]);

    if ($stmt->execute()) {
        unset($_SESSION["user_id"]);
        header("Location: ../index.php");
        
        exit;
    } else {
        echo "Lỗi khi đổi mật khẩu!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu</title>
    <style>
                body {
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg,rgb(73, 47, 106), #1a46d9); /* Gradient tím-xanh đậm hơn */
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
}


        .change-password-container {
            background-color: #ffffff;
            padding: 40px 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
        }
        .change-password-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
        }

        input[type="password"]:focus {
            border-color: #2575fc;
            box-shadow: 0 0 5px rgba(37, 117, 252, 0.4);
        }
        button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #6a11cb, #2575fc); /* Gradient tím-xanh dương */
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}


            button:hover {
    filter: brightness(1.1);
}        
    </style>
</head>
<body>

<div class="change-password-container">
    <h2>Đổi mật khẩu</h2>
    <form method="POST">
        <label for="new_password">Nhập mật khẩu mới:</label>
        <input type="password" id="new_password" name="new_password" required>
        <button type="submit">Đổi mật khẩu</button>
    </form>
</div>

</body>
</html>
