# HHG POS Website

Hệ thống quản lý bán hàng hỗ trợ quản lý sản phẩm, khách hàng, nhân viên, hóa đơn và tồn kho.

## Thành viên

* Nguyễn Thị Hương - 52300201
* Đặng Hân Hân - 52300196
* Phạm Thị Cẩm Giang - 52300194

## Công nghệ sử dụng

* PHP 8.2+
* MySQL
* HTML/CSS/JavaScript
* Bootstrap
* XAMPP

## Cài đặt

1. Clone source code.
2. Khởi động Apache và MySQL bằng XAMPP.
3. Import file `database/data.sql`.
4. Cài đặt thư viện:

```bash
composer require picqer/php-barcode-generator
```

5. Truy cập:

```text
http://localhost/d-an-cu-i-BaiNop/
```

## Tài khoản mặc định

### Admin

* Username: `admin`
* Password: `admin123`

### Nhân viên

* Username: phần trước dấu `@` của email đăng ký.
* Mật khẩu mặc định: `52300201`

## Demo

Video demo:

https://drive.google.com/file/d/1KDD2-Yvb54SUmEHPzGy605Xp4yOa-_uu/view

## Ghi chú

* Kiểm tra thư mục Spam/Junk nếu không nhận được email.
* Đổi mật khẩu sau lần đăng nhập đầu tiên.
* Không đăng nhập tài khoản Admin và Nhân viên trên cùng trình duyệt.
