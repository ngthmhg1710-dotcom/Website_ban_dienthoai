<?php
session_start();

// Kiểm tra quyền truy cập (chỉ admin mới có thể quản lý nhân viên)
if (!isset($_SESSION["logged_in"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/config.php";

// Thêm thư viện gửi email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$message = "";
if (!$conn) {
    die("Kết nối database thất bại: " . mysqli_connect_error());
}

// Xử lý thêm nhân viên
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_staff"])) {
    $email = $_POST["email"];
    $username = explode("@", $email)[0]; // Lấy phần đầu email làm username
    $fullname = $_POST["fullname"];
    $password = "52300201";  // Mật khẩu mặc định
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);  // Mã hóa mật khẩu

    // Kiểm tra xem tên đăng nhập đã tồn tại hay chưa
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $message = "Tên đăng nhập đã tồn tại. Vui lòng chọn email khác.";
    } else {
        // Thêm nhân viên vào CSDL (loại bỏ cột raw_password)
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, email, fullname) VALUES (?, ?, 'employee', ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $fullname);
        

        if ($stmt->execute()) {
            // Lấy ID vừa thêm
            $user_id = $stmt->insert_id;

            // Tạo token đăng nhập một lần (hết hạn sau 1 phút)
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 minute"));
            $token_stmt = $conn->prepare("INSERT INTO login_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $token_stmt->bind_param("iss", $user_id, $token, $expiry);
            $token_stmt->execute();

            // Gửi email cho nhân viên
            $login_link = "http://localhost/d-an-cu-i-BaiNop/admin/first_login.php?token=$token";
            $mail = new PHPMailer(true);
            try {
                // Cấu hình SMTP của Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'phamthicamgiang1265@gmail.com';  // Thay bằng email của bạn
                $mail->Password = 'bjeo hzkp ddle wryw';    // Thay bằng mật khẩu ứng dụng
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8'; // Để hỗ trợ tiếng Việt
           
                // Người gửi và người nhận
                $mail->setFrom('phamthicamgiang1265@gmail.com', 'Giang Phomaique');
                $mail->addAddress($email);   // Thay bằng email người nhận
                $mail->addReplyTo('phamthicamgiang1265@gmail.com', 'Giang Phomaique'); // Tránh bị vào thư rác
           
                // Nội dung email
                $mail->isHTML(true);
                $mail->Subject = 'Chào bạn 🌸';
                $mail->Body = "<p>Chào bạn,</p>
                <p><a href='$login_link'>Nhấp vào đây để đăng nhập lần đầu</a></p>
                <p>Mật khẩu mặc định của bạn là: <strong>$password</strong>. Vui lòng thay đổi sau khi đăng nhập.</p>";
                $mail->AltBody = "Truy cập liên kết này để đăng nhập: $login_link";
 
               
                $mail->send();
                $message = "Nhân viên đã được thêm thành công! Email đã được gửi.";                
            } catch (Exception $e) {
                $message = "Nhân viên đã được thêm nhưng lỗi khi gửi email!";
            }
        } else {
            $message = "Lỗi khi thêm nhân viên!";
        }
    }
}


// Xóa nhân viên (chuyển qua bảng deleted_users)
if (isset($_GET["delete"])) {
    // Kiểm tra quyền truy cập trước khi xóa
    if (!isset($_SESSION["logged_in"]) || $_SESSION["role"] !== "admin") {
        header("Location: ../index.php");
        exit;
    }

    $id = $_GET["delete"];


    // Lấy thông tin nhân viên trước khi xóa
    $stmt = $conn->prepare("SELECT id, username, password, plain_password, role, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();


    if ($row = $result->fetch_assoc()) {
        // Kiểm tra xem email đã tồn tại trong bảng deleted_users chưa
        $check_email = $conn->prepare("SELECT id FROM deleted_users WHERE email = ?");
        $check_email->bind_param("s", $row["email"]);
        $check_email->execute();
        $check_email->store_result(); // Cần có dòng này để num_rows hoạt động
    
        if ($check_email->num_rows == 0) { // Kiểm tra email có tồn tại chưa
            // Chỉ thêm vào deleted_users nếu email chưa tồn tại
            $stmt_delete = $conn->prepare("INSERT INTO deleted_users (id, username, password, plain_password, role, email, deleted_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt_delete->bind_param("isssss", $row["id"], $row["username"], $row["password"], $row["plain_password"], $row["role"], $row["email"]);
            $stmt_delete->execute();
        }
    
        // Xóa nhân viên trong bảng users
        $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete_user->bind_param("i", $id);
        $stmt_delete_user->execute();
    
        // Chuyển hướng sau khi xóa thành công
        header("Location: manage_staff.php");
        exit;
    } else {
        echo "Không tìm thấy nhân viên!";
    }
    
}


// Khôi phục nhân viên
if (isset($_GET["restore"])) {
    $id = $_GET["restore"];


    // Lấy thông tin nhân viên đã xóa
    $result = $conn->query("SELECT * FROM deleted_users WHERE id = $id");
    $row = $result->fetch_assoc();


    // Chèn lại dữ liệu vào bảng users
    $stmt = $conn->prepare("INSERT INTO users (id, username, password, plain_password, role, email) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $row['id'], $row['username'], $row['password'], $row['plain_password'], $row['role'], $row['email']);
    $stmt->execute();


    // Xóa bản ghi trong bảng deleted_users
    $conn->query("DELETE FROM deleted_users WHERE id = $id");


    header("Location: manage_staff.php");
    exit;
}


// Lấy danh sách nhân viên chưa xóa
$result_active = $conn->query("SELECT id, username, email, fullname, status, status_account, profile_image FROM users WHERE role = 'employee' AND deleted_at IS NULL ORDER BY id ASC");
if (!$result_active) {
    die("Lỗi truy vấn danh sách nhân viên: " . $conn->error);
}


// Lấy danh sách nhân viên đã xóa
$result_deleted = $conn->query("SELECT * FROM deleted_users ORDER BY deleted_at ASC");
if (!$result_deleted) {
    die("Lỗi truy vấn nhân viên đã xóa: " . $conn->error);
}
// Xóa vĩnh viễn nhân viên
if (isset($_GET["permanent_delete"])) {
    $id = $_GET["permanent_delete"];


    // Xóa nhân viên khỏi bảng deleted_users
    $stmt = $conn->prepare("DELETE FROM deleted_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Nhân viên đã bị xóa vĩnh viễn!'); window.location.href='manage_staff.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi xóa nhân viên!');</script>";
    }
}
// Kiểm tra nếu có yêu cầu khóa tài khoản
if (isset($_GET['lock'])) {
    $id = $_GET['lock'];

    // Kiểm tra ID hợp lệ
    if (is_numeric($id) && !empty($id)) {
        // Cập nhật trạng thái tài khoản thành "Tài khoản đã bị khóa" (is_locked = 1)
        $sql = "UPDATE users SET is_locked = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "Tài khoản đã bị khóa thành công.";
            header("Location: manage_staff.php"); // Quay lại trang quản lý nhân viên
            exit();
        } else {
            echo "Lỗi khi khóa tài khoản: " . $conn->error;
        }

        $stmt->close();
    } else {
        echo "ID không hợp lệ.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $user_id = $_SESSION["user"]["id"];
    $new_password = trim($_POST["new_password"]);
    $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Cập nhật mật khẩu đã đổi trong cơ sở dữ liệu
    $stmt = $conn->prepare("UPDATE users SET password = ?, changed_password = ? WHERE id = ?");
    $stmt->bind_param("ssi", $hashed_new_password, $new_password, $user_id);

    if ($stmt->execute()) {
        echo "Mật khẩu đã được cập nhật thành công!";
    } else {
        echo "Lỗi khi cập nhật mật khẩu: " . $conn->error;
    }

    $stmt->close();
}

// Xử lý cập nhật trạng thái tài khoản
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE users SET status_account = CASE WHEN status_account = 'active' THEN 'locked' ELSE 'active' END WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_staff.php");
    exit;
}

?>






<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sidebar_admin.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhân viên</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
      /* Thêm CSS cho phần quản lý nhân viên */
/* Thiết lập chung */

body {
    font-family: 'Arial', sans-serif;
    background: #EEF2F6; /* Nền xám nhạt phủ toàn trang */
    margin: 0;
    padding: 0;
}

/* Container chính */
.container {
    width: 80%;
    padding: 20px;
    background: transparent; /* Đổi từ #EEF2F6 thành trong suốt */
    min-height: 100vh; /* Đảm bảo nó chiếm đủ không gian */
    overflow-y: auto; /* Cho phép cuộn dọc nếu nội dung dài hơn */
    text-align: center;
}


.container:hover {
    transform: scale(1.02);
}

/* Tiêu đề */
h2 {
    color: #1f78d1; /* Xanh dương đậm */
    font-size: 28px;
    font-weight: bold;
}

/* Hộp nhập liệu */
.form-box {
    background:rgb(255, 255, 255); /* Xanh dương pastel trung bình */
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0px 5px 10px rgba(40, 40, 40, 0.2);
    text-align: center;
}

/* Input và Button */
input, button {
    width: 90%;
    max-width: 350px;
    margin: 12px auto;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #6ea8dc;
    display: block;
    font-size: 16px;
    transition: 0.3s;
}

/* Button */
button {
    background: #1f78d1; /* Xanh dương sáng */
    color: white;
    border: none;
    cursor: pointer;
    font-weight: bold;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.2);
}

button:hover {
    background: #155a99; /* Xanh dương đậm hơn */
    transform: scale(1.05);
}

/* Bảng dữ liệu */
.table-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    overflow-x: auto;
    overflow-y: auto;
    max-width: 1300px;

}

.table-container {
    display: flex;
    flex-direction: column; /* Sắp xếp dọc */
    align-items: center;
    margin-top: 20px;
    overflow-x: auto;
    max-width: 1300px;
}

table {
    width: 100%;
    max-width: 1300px;
    min-width: 1000px; /* Cố định cho đồng đều */
    margin-bottom: 20px;
    background-color: white;
    border-radius: 12px; /* Bo tròn góc bảng */
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1); /* Thêm hiệu ứng bóng đổ */
    overflow: hidden; /* Giới hạn phần hiển thị khi có tràn */
}




th, td {
    border: 2px solid #6ea8dc;
    padding: 14px;
    text-align: center;
    font-size: 16px;
}

/* Tiêu đề bảng */
th {
    background: #1f78d1; /* Xanh dương đậm */
    color: white;
}

/* Hiệu ứng hover cho hàng */
tr:hover {
    background: #c6e0ff;
}

/* Nút Xóa & Khôi phục */
td a {
    text-decoration: none;
    font-weight: bold;
    padding: 10px 18px;
    border-radius: 8px;
    display: inline-block;
    transition: 0.3s;
}

td a[style="color: red;"] {
    background: #1f78d1;
    color: white !important;
}

td a[style="color: red;"]:hover {
    background: #155a99;
}

td a[style="color: green;"] {
    background: #1f78d1;
    color: white !important;
}

td a[style="color: green;"]:hover {
    background: #155a99;
}

/* Thêm CSS cho trạng thái */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.status-online {
    background-color: #4CAF50;
    color: white;
}

.status-offline {
    background-color: #9E9E9E;
    color: white;
}

.status-active {
    background-color: #2196F3;
    color: white;
}

.status-locked {
    background-color: #F44336;
    color: white;
}

/* Thêm CSS cho nút */
.toggle-btn {
    padding: 5px 10px;
    border-radius: 15px;
    text-decoration: none;
    margin-left: 10px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.lock-btn {
    background-color: #FF9800;
    color: white;
}

.unlock-btn {
    background-color: #4CAF50;
    color: white;
}

.lock-btn:hover {
    background-color: #F57C00;
}

.unlock-btn:hover {
    background-color: #388E3C;
}

.delete-btn {
    background-color: #F44336;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.delete-btn:hover {
    background-color: #D32F2F;
}

.resend-btn {
    background-color: #2196F3;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    text-decoration: none;
    margin-left: 10px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.resend-btn:hover {
    background-color: #1976D2;
}

    </style>
</head>
<body>
<?php include 'sidebar_admin.php'; ?>


    <div class="container">
        <h2>Quản lý Nhân Viên</h2>


        <!-- Form thêm nhân viên -->
        <div class="form-box">
            <h3>Thêm Nhân Viên</h3>
            <form method="POST">
                <label>Họ tên:</label>
                <input type="text" name="fullname" required>


                <label>Email:</label>
                <input type="email" name="email" required>


                <button type="submit" name="add_staff">Thêm Nhân Viên</button>
            </form>
            <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
        </div>


        <?php
// Đoạn mã xử lý resend token (từ câu lệnh GET)
// Đoạn mã xử lý gửi lại token khi GET có tham số resend_token
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["resend_token"])) {
    $user_id = $_GET['resend_token'];

    // Lấy thông tin email của nhân viên từ cơ sở dữ liệu
    $get_email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $get_email_stmt->bind_param("i", $user_id);
    $get_email_stmt->execute();
    $email_result = $get_email_stmt->get_result();

    if ($email_row = $email_result->fetch_assoc()) {
        $email = $email_row['email'];

        // Xóa token cũ nếu có
        $delete_old_token_stmt = $conn->prepare("DELETE FROM login_tokens WHERE user_id = ?");
        $delete_old_token_stmt->bind_param("i", $user_id);
        $delete_old_token_stmt->execute();

        // Tạo token mới
        $token = bin2hex(random_bytes(32)); // Tạo token ngẫu nhiên
        $expiry = date("Y-m-d H:i:s", strtotime("+1 minute")); // Hết hạn trong 1 phút

        // Thêm token mới vào cơ sở dữ liệu
        $token_stmt = $conn->prepare("INSERT INTO login_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $token_stmt->bind_param("iss", $user_id, $token, $expiry);
        $token_stmt->execute();

        // Gửi email với liên kết đăng nhập mới
        $login_link = "http://localhost/d-an-cu-i-BaiNop/admin/first_login.php?token=$token";
        $mail = new PHPMailer(true);
        try {
            // Cấu hình gửi email qua SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'phamthicamgiang1265@gmail.com';  // Thay bằng email của bạn
            $mail->Password = 'bjeo hzkp ddle wryw';    // Thay bằng mật khẩu ứng dụng
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8'; // Để hỗ trợ tiếng Việt
        
            // Người gửi và người nhận
            $mail->setFrom('phamthicamgiang1265@gmail.com', 'Giang Phomaique');
            $mail->addAddress($email);   // Thay bằng email người nhận
            $mail->addReplyTo('phamthicamgiang1265@gmail.com', 'Giang Phomaique'); // Tránh bị vào thư rác
        
            // Nội dung email
            $mail->isHTML(true);
            $mail->Subject = 'HHG POS Website xin chào 🌸';
            $mail->Body = "<p>Chào bạn,</p>
            <p><a href='$login_link'>Nhấp vào đây để đăng nhập lần đầu</a></p>
            <p>Mật khẩu mặc định của bạn là: <strong>52300201</strong>. Vui lòng thay đổi sau khi đăng nhập.</p>";
            $mail->AltBody = "Truy cập liên kết này để đăng nhập: $login_link";
        
            // Gửi email
            $mail->send();
            $message = "Email đăng nhập đã được gửi lại thành công!";
        } catch (Exception $e) {
            $message = "Lỗi khi gửi email: {$mail->ErrorInfo}";
        }
    } else {
        $message = "Không tìm thấy nhân viên!";
    }

}
?>

<div class="form-box">
    <div class="content-table">
    <div style="overflow-x: auto;">

        <h3>Danh Sách Nhân Viên</h3>
        <table class="staff-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ảnh đại diện</th>
                    <th>Họ tên</th>

                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Trạng thái hoạt động</th>
                    <th>Trạng thái tài khoản</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_active->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td>
                            <img src="<?= htmlspecialchars($row['profile_image'] ?? 'uploads/avatars/default-avatar.png') ?>" 
                                 alt="Avatar" 
                                 style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                        </td>
                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>

                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $row['status'] === 'online' ? 'status-online' : 'status-offline'; ?>">
                                <?php echo $row['status'] === 'online' ? 'Online' : 'Offline'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $row['status_account'] === 'active' ? 'status-active' : 'status-locked'; ?>">
                                <?php echo $row['status_account'] === 'active' ? 'Hoạt động' : 'Đã khóa'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="?delete=<?php echo $row['id']; ?>" 
                               class="delete-btn" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?')">Xóa</a>
                            <a href="?toggle_status=<?php echo $row['id']; ?>" 
                               class="toggle-btn <?php echo $row['status_account'] === 'active' ? 'lock-btn' : 'unlock-btn'; ?>">
                                <?php echo $row['status_account'] === 'active' ? 'Khóa' : 'Mở khóa'; ?>
                            </a>
                            <a href="?resend_token=<?php echo $row['id']; ?>" 
                               class="resend-btn">Gửi lại email</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


        <!-- Danh sách nhân viên đã xóa -->
         <div class="form-box">
            <div class="content-table">
            <h3>Danh Sách Nhân Viên Đã Xóa</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Mật khẩu gốc</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_deleted->fetch_assoc()): ?>
                <tr>
                    <td><?= $row["id"] ?></td>
                    <td><?= htmlspecialchars($row["username"]) ?></td>
                    <td><?= htmlspecialchars($row["email"]) ?></td>
                    <td><?= htmlspecialchars($row["plain_password"]) ?></td>
                    <td>
    <a href="?permanent_delete=<?= $row['id'] ?>" style="color: red; margin-left: 10px;">Xóa vĩnh viễn</a>
</td>


                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
            </div>
         </div>
       
    </div>
</div>

<script>
// Cập nhật trạng thái mỗi phút
function updateStatus() {
    fetch('update_status.php')
        .then(response => response.text())
        .then(() => {
            fetch('get_staff_status.php')
                .then(response => response.json())
                .then(staffData => {
                    const tbody = document.querySelector('.staff-table tbody');
                    tbody.innerHTML = ''; // Xóa nội dung cũ
                    
                    staffData.forEach(staff => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${staff.id}</td>
                            <td>
                                <img src="${staff.profile_image || 'uploads/avatars/default-avatar.png'}" 
                                     alt="Avatar" 
                                     style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                            </td>
                            <td>${staff.username}</td>
                            <td>${staff.email}</td>
                            <td>${staff.fullname}</td>
                            <td>
                                <span class="status-badge ${staff.status === 'online' ? 'status-online' : 'status-offline'}">
                                    ${staff.status === 'online' ? 'Online' : 'Offline'}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge ${staff.status_account === 'active' ? 'status-active' : 'status-locked'}">
                                    ${staff.status_account === 'active' ? 'Hoạt động' : 'Đã khóa'}
                                </span>
                            </td>
                            <td>
                                <a href="?delete=${staff.id}" 
                                   class="delete-btn" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?')">Xóa</a>
                                <a href="?toggle_status=${staff.id}" 
                                   class="toggle-btn ${staff.status_account === 'active' ? 'lock-btn' : 'unlock-btn'}">
                                    ${staff.status_account === 'active' ? 'Khóa' : 'Mở khóa'}
                                </a>
                                <a href="?resend_token=${staff.id}" 
                                   class="resend-btn">Gửi lại email</a>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                });
        });
}

// Cập nhật trạng thái mỗi phút
setInterval(updateStatus, 60000);

// Cập nhật ngay khi trang load
updateStatus();
</script>
</body>
</html>
