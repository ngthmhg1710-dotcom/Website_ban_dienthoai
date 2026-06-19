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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Kiểm tra xem email có tồn tại trong cơ sở dữ liệu không
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt_a = $conn->prepare($sql);
    $stmt_a->bind_param("s", $email);
    $stmt_a->execute();
    $result = $stmt_a->get_result();

    if ($result->num_rows > 0) {
        // Tạo mã token ngẫu nhiên
        $token = bin2hex(random_bytes(50)); 

        // Cập nhật token vào bảng password_resets
        $stmt = $conn->prepare("REPLACE INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        

        if ($stmt->execute()) {
            // Tiến hành gửi email với PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Cấu hình máy chủ gửi mail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;
                $mail->Username = 'tonydangtg63@gmail.com'; // Email gửi
                $mail->Password = 'btmo raoa wghx gcak'; // Mật khẩu ứng dụng
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                // Cấu hình người gửi và nhận
                $mail->setFrom('tonydangtg63@gmail.com', 'Web Supporter');
                $mail->addAddress($email);
                $mail->addReplyTo('tonydangtg63@gmail.com', 'Web Supporter');

                // Nội dung email
                $mail->isHTML(true);
                $mail->Subject = 'Đặt lại mật khẩu';
                $reset_link = "localhost/d-an-cu-i-BaiNop/login/reset_password.php?token=$token"; 
                $mail->Body = "<p>Xin chào,</p>
                                <p>Bạn đã yêu cầu đặt lại mật khẩu. Nhấn vào liên kết sau để reset:</p>
                                <p><a href='{$reset_link}'>Đặt lại mật khẩu</a></p>
                                <p>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
                                <p>Trân trọng,<br>Đội ngũ HHGPOS</p>";

                // Gửi email
                $mail->send();
                // Hiển thị thông báo thành công bằng JavaScript
                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const notification = document.getElementById('notification');
                            notification.innerHTML = 'Một email đặt lại mật khẩu đã được gửi đến $email.';
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
            } catch (Exception $e) {
                // Hiển thị thông báo lỗi bằng JavaScript
                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const notification = document.getElementById('notification');
                            notification.innerHTML = 'Không thể gửi email. Lỗi: {$mail->ErrorInfo}';
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
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const notification = document.getElementById('notification');
                        notification.innerHTML = 'Đã có lỗi khi tạo yêu cầu đặt lại mật khẩu.';
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
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const notification = document.getElementById('notification');
                    notification.innerHTML = 'Email không tồn tại trong hệ thống.';
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu</title>
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
    </style>
</head>
<body>
    <form method="POST" action="">
        <label for="email">Nhập email đã đăng ký:</label><br>
        <input type="email" name="email" required><br>
        <input type="submit" value="Gửi yêu cầu đặt lại mật khẩu" class="btn">
        <a class="back" href="../index.php">⬅ Quay lại</a>
    </form>
    <!-- Div thông báo -->
    <div class="alert" id="notification"></div>
</body>
</html>