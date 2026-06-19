<body>
<link rel="stylesheet" href="sidebar_employee.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<div class="sidebar">
    <h2>Chào mừng, Nhân viên!</h2>
    <a href="edit_profile_staff.php">
    <img src="<?= !empty($_SESSION["user"]["profile_image"]) ? $_SESSION["user"]["profile_image"] : 'uploads/avatars/default-avatar.png' ?>" alt="Avatar">
</a>
    <ul>
        <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Trang chủ Bán Hàng</a></li>
        <li><a href="manage_customers.php"><i class="fas fa-users"></i> Quản lý Khách Hàng</a></li>
        <li><a href="view_revenue.php"><i class="fas fa-chart-line"></i> Xem doanh thu</a></li>
        <li><a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>
<script src="sidebar_employee.js"></script>

</body>

