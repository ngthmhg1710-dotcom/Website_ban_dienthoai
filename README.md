\# HHG POS WEBSITE - HỆ THỐNG QUẢN LÝ BÁN HÀNG



\## Giới thiệu



HHG POS Website là hệ thống quản lý bán hàng được xây dựng nhằm hỗ trợ quản lý sản phẩm, nhân viên, khách hàng, hóa đơn và các hoạt động bán hàng trong doanh nghiệp.



\## Thành viên thực hiện



\* Nguyễn Thị Hương - MSSV: 52300201

\* Đặng Hân Hân - MSSV: 52300196

\* Phạm Thị Cẩm Giang - MSSV: 52300194



Lớp: 23050401



Ngành: Mạng máy tính và truyền thông dữ liệu



\---



\## Công nghệ sử dụng



\* PHP 8.2+

\* MySQL

\* HTML/CSS/JavaScript

\* Bootstrap

\* XAMPP

\* Composer



\---



\## Yêu cầu hệ thống



\* XAMPP 8.2 hoặc mới hơn

\* PHP 8.2+

\* MySQL Server

\* Trình duyệt hiện đại (Chrome, Edge, Firefox,...)



\---



\## Hướng dẫn cài đặt



\### 1. Clone dự án



```bash

git clone <repository-url>

```



\### 2. Khởi động môi trường



Mở XAMPP và Start:



\* Apache

\* MySQL



\### 3. Import cơ sở dữ liệu



\* Truy cập: http://localhost/phpmyadmin

\* Tạo database

\* Import file:



```text

database/data.sql

```



\### 4. Kích hoạt thư viện GD



Mở file:



```text

xampp/php/php.ini

```



Tìm:



```ini

;extension=gd

```



Bỏ dấu `;`:



```ini

extension=gd

```



Khởi động lại Apache.



\### 5. Cài đặt thư viện Barcode



```bash

composer require picqer/php-barcode-generator

```



\### 6. Chạy ứng dụng



```text

http://localhost/d-an-cu-i-BaiNop/

```



\---



\## Tài khoản đăng nhập



\### Quản trị viên



| Tên đăng nhập | Mật khẩu |

| ------------- | -------- |

| admin         | admin123 |



\### Nhân viên



\* Username: phần trước dấu @ của email đăng ký

\* Ví dụ:



```text

nguyen@abc.com

```



Username:



```text

nguyen

```



Mật khẩu mặc định:



```text

52300201

```



\---



\## Chức năng chính



\* Quản lý sản phẩm

\* Quản lý danh mục

\* Quản lý nhân viên

\* Quản lý khách hàng

\* Quản lý hóa đơn

\* Quản lý tồn kho

\* In mã vạch sản phẩm

\* Phân quyền người dùng

\* Gửi email hỗ trợ



\---



\## Video Demo



https://drive.google.com/file/d/1KDD2-Yvb54SUmEHPzGy605Xp4yOa-\_uu/view



\---



\## Lưu ý



\* Kiểm tra thư mục Spam/Junk nếu không nhận được email.

\* Đổi mật khẩu sau lần đăng nhập đầu tiên.

\* Không đăng nhập tài khoản Admin và Nhân viên trên cùng trình duyệt để tránh xung đột phiên đăng nhập.



\---



\## License



Dự án được thực hiện phục vụ mục đích học tập và nghiên cứu.



