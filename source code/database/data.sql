-- Tạo database nếu chưa có
CREATE DATABASE IF NOT EXISTS company_db;
USE company_db;

-- Bảng người dùng (Admin & Nhân viên)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    plain_password VARCHAR(255) NOT NULL,
    changed_password VARCHAR(255) NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    reset_token VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    status ENUM('online', 'offline') DEFAULT 'offline',
    status_account ENUM('active', 'locked') DEFAULT 'active',
    last_activity TIMESTAMP NULL DEFAULT NULL,
    profile_image VARCHAR(255) NULL,
    first_login_token VARCHAR(255) NULL,
    token_expiry DATETIME NULL,
    raw_password VARCHAR(255) NOT NULL
);

-- Bảng danh mục sản phẩm
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Bảng sản phẩm
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(50) NOT NULL DEFAULT 'Apple',
    description TEXT,
    specifications TEXT,
    import_price INT NOT NULL,
    retail_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    barcode VARCHAR(255) NULL,
    barcode_image VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    category_id INT NULL,
    warranty_period INT DEFAULT 12,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Bảng khách hàng
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    address TEXT,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    last_purchase_date TIMESTAMP NULL DEFAULT NULL,
    total_purchases INT DEFAULT 0,
    total_spent DECIMAL(15,2) DEFAULT 0
);

-- Bảng đơn hàng
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    total DECIMAL(10,2) NOT NULL,
    discount DECIMAL(5,2) DEFAULT 0,
    cash_received DECIMAL(10,2) DEFAULT 0,
    change_amount DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT 'Tiền mặt',
    status VARCHAR(50) DEFAULT 'Đã thanh toán',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Bảng chi tiết đơn hàng
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Bảng lưu trữ thông tin người dùng đã bị xóa
CREATE TABLE IF NOT EXISTS deleted_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    plain_password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NOT NULL
);
ALTER TABLE deleted_users ADD fullname VARCHAR(255) AFTER id;

-- Bảng lưu trữ token đăng nhập
CREATE TABLE IF NOT EXISTS login_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Thêm tài khoản admin mặc định
INSERT INTO users (username, password, plain_password, raw_password, role, email, fullname) VALUES 
('admin', MD5('admin123'), 'admin123', 'admin123', 'admin', 'admin@example.com', 'Administrator');

-- Thêm danh mục sản phẩm
INSERT INTO categories (name, description, created_by) VALUES
('iPhone', 'Danh mục các dòng iPhone', 1),
('MacBook', 'Danh mục các dòng MacBook', 1),
('iPad', 'Danh mục các dòng iPad', 1),
('Apple Watch', 'Danh mục đồng hồ Apple', 1),
('Accessories', 'Phụ kiện Apple', 1);

-- Thêm sản phẩm Apple với thông tin đầy đủ
INSERT INTO products (name, brand, description, specifications, import_price, retail_price, stock, image, category_id, barcode, barcode_image) VALUES
('iPhone 15 Pro', 'Apple', 'iPhone 15 Pro với chip A17 Pro mạnh mẽ', 'Màn hình 6.1 inch, RAM 8GB, Bộ nhớ 256GB', 25000000, 30000000, 10, 'ip15pro.png', (SELECT id FROM categories WHERE name = 'iPhone'), 'IP15P256', 'barcodes/6809b4fa.png'),
('MacBook Air M2', 'Apple', 'MacBook Air với chip M2 mới nhất', 'Màn hình 13.6 inch, RAM 8GB, SSD 256GB', 23000000, 27000000, 5, 'mac_air2.jpg', (SELECT id FROM categories WHERE name = 'MacBook'), 'MBAIRM2', 'barcodes/6809b4af.png'),
('iPad Pro 11"', 'Apple', 'iPad Pro với chip M2', 'Màn hình 11 inch, RAM 8GB, SSD 128GB', 20000000, 22000000, 8, 'ipad11.webp', (SELECT id FROM categories WHERE name = 'iPad'), 'IPAD11', 'barcodes/6809b4a3.png'),
('Apple Watch Series 9', 'Apple', 'Apple Watch Series 9 với chip S9', 'Màn hình 45mm, GPS + Cellular', 9000000, 10000000, 12, 'apple_9.webp', (SELECT id FROM categories WHERE name = 'Apple Watch'), 'AWS9', 'barcodes/6809b3ee.png');

-- Thêm sản phẩm Samsung
INSERT INTO products (
    name, 
    brand, 
    description, 
    specifications, 
    import_price, 
    retail_price, 
    stock, 
    image, 
    category_id, 
    barcode, 
    barcode_image
) VALUES
('Samsung Galaxy S24 Ultra', 'Samsung', 'Samsung Galaxy S24 Ultra với chip Snapdragon 8 Gen 3', 'Màn hình 6.8 inch, RAM 12GB, Bộ nhớ 256GB', 25000000, 30000000, 10, 'samsung-galaxy-s24-ultra-5g-600x600.jpg', (SELECT id FROM categories WHERE name = 'iPhone'), 'S24U', 'barcodes/6809b3bf.png'),
('Samsung Galaxy S25 Ultra', 'Samsung', 'Samsung Galaxy S25 Ultra với chip Snapdragon 8 Gen 4', 'Màn hình 6.9 inch, RAM 16GB, Bộ nhớ 512GB', 28000000, 32000000, 5, 'samsung-galaxy-s25-ultra-gia-re-2.jpg', (SELECT id FROM categories WHERE name = 'iPhone'), 'S25U', 'barcodes/6809b024.png'),
('Samsung Galaxy S24', 'Samsung', 'Samsung Galaxy S24 với chip Snapdragon 8 Gen 3', 'Màn hình 6.2 inch, RAM 8GB, Bộ nhớ 128GB', 20000000, 22000000, 8, 'samsung-galaxy-_main_204_1020.png.webp', (SELECT id FROM categories WHERE name = 'iPhone'), 'S24', 'barcodes/6809a00f.png');

-- Thêm khách hàng
INSERT INTO customers (name, phone, email, address) VALUES
('Nguyễn Văn A', '0909123456', 'nguyenvana@example.com', 'Hà Nội'),
('Trần Thị B', '0918123456', 'tranthib@example.com', 'TP.HCM');

-- Thêm tài khoản nhân viên mặc định
INSERT INTO users (username, password, plain_password, raw_password, role, email, fullname, profile_image) VALUES 
('employee1', MD5('employee123'), 'employee123', 'employee123', 'employee', 'employee1@example.com', 'Nhân viên 1', 'uploads/1.jpg'),
('employee2', MD5('employee123'), 'employee123', 'employee123', 'employee', 'employee2@example.com', 'Nhân viên 2', 'uploads/2.jpg');


-- Thêm dữ liệu mẫu cho đơn hàng
INSERT INTO orders (customer_id, total, discount, payment_method, status, notes, created_at) VALUES
(1, 50000000, 0, 'Tiền mặt', 'Đã thanh toán', 'Giao hàng trước 17h', '2024-03-01 10:00:00'),
(1, 27000000, 5, 'Chuyển khoản', 'Đã thanh toán', 'Giao hàng buổi sáng', '2024-03-02 14:30:00'),
(2, 30000000, 0, 'Tiền mặt', 'Đã thanh toán', NULL, '2024-03-03 09:15:00');

-- Thêm dữ liệu mẫu cho chi tiết đơn hàng
INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES
(1, 1, 1, 30000000, 30000000),
(1, 4, 2, 10000000, 20000000),
(2, 2, 1, 27000000, 27000000),
(3, 1, 1, 30000000, 30000000);

CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_email FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
);

