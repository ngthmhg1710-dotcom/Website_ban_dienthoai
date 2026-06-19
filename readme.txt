============================================
HỆ THỐNG QUẢN LÝ BÁN HÀNG - HHG POS WEBSITE
============================================
📥 CÀI ĐẶT VÀ KHỞI CHẠY:

1. Tải hoặc clone source code về máy

2. Mở XAMPP → Start Apache và MySQL

3. Truy cập `localhost/phpmyadmin`
   → Import database `database/data.sql` 

4. Mở file `php.ini` trong thư mục XAMPP

   → Tìm dòng:
      ;extension=gd

   → Xoá dấu `;` phía trước để sử dụng thư viện 

5. Truy cập CMD tại thư mục chứa project, chạy lệnh Composer để cài đặt thư viện: composer require picqer/php-barcode-generator

6. Truy cập trình duyệt:

  http://localhost/d-an-cu-i-BaiNop/

---
⚙️ YÊU CẦU HỆ THỐNG:

- PHP / XAMPP version 8.2 hoặc mới hơn
- Trình duyệt hỗ trợ JavaScript (Chrome, Edge,...)
- MySQL Server

---
🔐 TÀI KHOẢN ĐĂNG NHẬP:

1. Quản trị viên (admin):
- Tên đăng nhập: admin
- Mật khẩu: admin123

2. Nhân viên:
- Tên đăng nhập: phần trước dấu "@" trong email đăng ký
  (VD: email `nguyen@abc.com` → username là `nguyen`)
- Mật khẩu mặc định: 52300201

📬 LƯU Ý:

- Nếu không nhận được email: kiểm tra trong thư rác (Spam/Junk Mail)

- Đổi mật khẩu sau lần đăng nhập đầu tiên để đảm bảo bảo mật.

- Không đăng nhập tài khoản quản trị viên và nhân viên trên cùng một trình duyệt web (do trình duyệt chia sẻ phiên đăng nhập, sẽ gây xung đột)


🎥 LINK VIDEO DEMO (Google Drive):
👉 https://drive.google.com/file/d/1KDD2-Yvb54SUmEHPzGy605Xp4yOa-_uu/view?usp=sharing

---

📞 THÔNG TIN NHÓM HHG POS WEBSITE:

Người thực hiện: Nguyễn Thị Hương, Đặng Hân Hân, Phạm Thị Cẩm Giang

MSSV: 52300201, 52300196, 52300194

Lớp: 23050401

Ngành: Mạng máy tính và truyền thông dữ liệu

---

✅ Ghi chú:
- Nhớ thay đổi mật khẩu sau khi đăng nhập lần đầu.

- Sử dụng trình duyệt cập nhật để đảm bảo tương thích giao diện.

