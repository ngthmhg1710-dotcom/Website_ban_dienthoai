<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar</title>
    <link rel="stylesheet" href="sidebar_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>
<!-- Import JavaScript ở cuối -->

<body>

    <div class="sidebar">
        <h2>Chào mừng, Admin!</h2>

        <!-- Ảnh đại diện -->
        <a href="edit_profile.php">
    <img src="<?= !empty($_SESSION["user"]["profile_image"]) ? $_SESSION["user"]["profile_image"] : 'uploads/avatars/default-avatar.png' ?>" alt="Avatar">
</a>

        <!-- Danh sách menu -->
        <ul>
            
            <li><a href="manage_staff.php"><i class="fas fa-users"></i> Quản lý nhân viên</a></li>
            <li><a href="manage_products.php"><i class="fas fa-box"></i> Quản lý sản phẩm</a></li>
            <li><a href="manage_revenue.php"><i class="fas fa-chart-line"></i> Xem doanh thu</a></li>
            <li><a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>
    <script src="sidebar_admin.js"></script>
</body>
</html>
</html>
