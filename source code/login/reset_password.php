<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Tải autoload của Composer
require '../vendor/autoload.php';

// Kết nối đến cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "company_db"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Kiểm tra token từ URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Kiểm tra token trong bảng password_resets
    $sql = "SELECT * FROM password_resets WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Nếu token hợp lệ, lấy email của người dùng
    if ($result->num_rows > 0) {
        $reset_request = $result->fetch_assoc();
        $email = $reset_request['email']; // Lấy email từ bảng password_resets

        // Kiểm tra nếu người dùng gửi form POST để thay đổi mật khẩu
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Lấy mật khẩu mới từ form
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Kiểm tra mật khẩu có khớp không
            if ($new_password === $confirm_password) {
                // Băm mật khẩu mới
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Cập nhật mật khẩu mới vào bảng users
                $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $hashed_password, $email);
                if ($update_stmt->execute()) {
                    // Xóa token khỏi bảng password_resets sau khi thay đổi mật khẩu
                    $delete_sql = "DELETE FROM password_resets WHERE token = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("s", $token);
                    $delete_stmt->execute();

                    // Hiển thị thông báo thành công bằng JavaScript
                    echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const notification = document.getElementById('notification');
                                notification.innerHTML = 'Mật khẩu của bạn đã được cập nhật thành công. Bạn có thể đăng nhập với mật khẩu mới.';
                                notification.style.display = 'block';
                                notification.style.opacity = '1';
                                // Ẩn thông báo sau 5 giây
                                setTimeout(function() {
                                    notification.style.opacity = '0';
                                    setTimeout(function() {
                                        notification.style.display = 'none';
                                    }, 500);
                                }, 5000);
                            });
                          </script>";
                } else {
                    // Hiển thị thông báo lỗi bằng JavaScript
                    echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const notification = document.getElementById('notification');
                                notification.innerHTML = 'Đã có lỗi xảy ra, vui lòng thử lại.';
                                notification.style.background = 'linear-gradient(45deg, #dc3545, #ff6666)';
                                notification.style.display = 'block';
                                notification.style.opacity = '1';
                                setTimeout(function() {
                                    notification.style.opacity = '0';
                                    setTimeout(function() {
                                        notification.style.display = 'none';
                                    }, 500);
                                }, 5000);
                            });
                          </script>";
                }
            } else {
                // Hiển thị thông báo lỗi khi mật khẩu không khớp
                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const notification = document.getElementById('notification');
                            notification.innerHTML = 'Mật khẩu xác nhận không khớp.';
                            notification.style.background = 'linear-gradient(45deg, #dc3545, #ff6666)';
                            notification.style.display = 'block';
                            notification.style.opacity = '1';
                            setTimeout(function() {
                                notification.style.opacity = '0';
                                setTimeout(function() {
                                    notification.style.display = 'none';
                                }, 500);
                            }, 5000);
                        });
                      </script>";
            }
        }
    } else {
        // Nếu token không hợp lệ hoặc đã hết hạn
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const notification = document.getElementById('notification');
                    notification.innerHTML = 'Token không hợp lệ hoặc đã hết hạn.';
                    notification.style.background = 'linear-gradient(45deg, #dc3545, #ff6666)';
                    notification.style.display = 'block';
                    notification.style.opacity = '1';
                    setTimeout(function() {
                        notification.style.opacity = '0';
                        setTimeout(function() {
                            notification.style.display = 'none';
                        }, 500);
                    }, 5000);
                });
              </script>";
    }
} else {
    // Nếu không có token, hiển thị thông báo lỗi
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const notification = document.getElementById('notification');
                notification.innerHTML = 'Không có token để xác nhận.';
                notification.style.background = 'linear-gradient(45deg, #dc3545, #ff6666)';
                notification.style.display = 'block';
                notification.style.opacity = '1';
                setTimeout(function() {
                    notification.style.opacity = '0';
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 500);
                }, 5000);
            });
          </script>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu</title>
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
            justify-content: flex-start; /* Căn trái */
            align-items: center;
            height: 100vh; /* Dịch sang trái */
            padding-left: 50px;
            background: url('../bg4.png') no-repeat center center/cover;
        }

        label {
            display: block;
            margin: 12px 0 0;
            font-size: 16px;
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
            box-shadow: 0px 0px 4px 2px rgba(0, 114, 255, 0.6); /* Hiệu ứng sáng hơn khi focus */
            background: rgba(255, 255, 255, 0.3);
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .btn {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: linear-gradient(45deg, #2575fc, #6a11cb);
        }

        .back {
            font-size: 20px;
        }

        .back:hover {
            color: rgb(107, 228, 255);
        }

        /* CSS cho thông báo */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: linear-gradient(45deg, #28a745, #34c759);
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: none; /* Ẩn mặc định */
            z-index: 1000;
            font-size: 16px;
            font-weight: 400;
            opacity: 0; /* Mặc định ẩn */
            transition: opacity 0.5s ease-in-out; /* Hiệu ứng chuyển đổi độ mờ */
        }

        .login {
            font-size: 20px;
        }

        .login:hover {
            color: rgb(107, 228, 255);
        }
    </style>
</head>
<body>
    <?php
    // Chỉ hiển thị form nếu token hợp lệ
    if (isset($token) && $result->num_rows > 0) {
    ?>
    <form method="POST" action="">
        <label for="new_password">Mật khẩu mới:</label><br>
        <input type="password" name="new_password" required><br>

        <label for="confirm_password">Xác nhận mật khẩu:</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <div>
        <input type="submit" value="Cập nhật mật khẩu" class="btn">
        <a class="login" href="../index.php">Đăng nhập</a>
        </div>
        
    </form>
    <?php
    }
    ?>
    <div class="alert" id="notification"></div>
</body>
</html>