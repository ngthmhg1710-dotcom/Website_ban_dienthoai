<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="shortcut icon" href="img/favicon.ico" />

<!-- Load font awesome icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
 crossorigin="anonymous">

<!-- owl carousel libraries -->
<link rel="stylesheet" href="js/owlcarousel/owl.carousel.min.css">
<link rel="stylesheet" href="js/owlcarousel/owl.theme.default.min.css">
<script src="js/Jquery/Jquery.min.js"></script>
<script src="js/owlcarousel/owl.carousel.min.js"></script>

<!-- tidio - live chat -->
<!-- <script src="//code.tidio.co/bfiiplaaohclhqwes5xivoizqkq56guu.js"></script> -->

<!-- our files -->
<!-- css -->
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/topnav.css">
<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="css/banner.css">
<link rel="stylesheet" href="css/taikhoan.css">
<link rel="stylesheet" href="css/trangchu.css">
<link rel="stylesheet" href="css/home_products.css">
<link rel="stylesheet" href="css/pagination_phantrang.css">
<link rel="stylesheet" href="css/footer.css">
<!-- js -->
<script src="data/products.js"></script>
<script src="js/classes.js"></script>
<script src="js/dungchung.js"></script>
<script src="js/trangchu.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            color:black;
        }

        body {
            display: flex;
            justify-content: flex-start; /* Căn trái */
            align-items: center;
            height: 100vh;
            padding-left: 120px; /* Dịch sang trái */
            background: url('bg4.png') no-repeat center center/cover;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1); /* Nền trong suốt */
            backdrop-filter: blur(12px); /* Hiệu ứng mờ */
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            width: 340px;
            color: white;
            transition: 0.3s ease-in-out;
        }

        .login-button:hover {
        background: linear-gradient(90deg, #005bb5, #0099ff);
        box-shadow: 0px 4px 10px rgba(0, 114, 255, 0.4);
        }

        h2 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin: 12px 0 6px;
            font-size: 14px;
            font-weight: 400;
            color:black;
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

        .links {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
        }

        .links a {
            color:rgb(30, 72, 148);
            text-decoration: none;
            transition: 0.3s;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Đăng nhập</h2>
        <form action="/d-an-cu-i-BaiNop/login/login.php" method="POST">
            <label for="username">Tên đăng nhập:</label>
            <input type="text" id="username" name="username" required placeholder="Nhập tên đăng nhập">

            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">

            <button class="btn" type="submit">Đăng nhập</button>
        </form>
        <div class="links">
            <a href="login/forgot_password.php">Quên mật khẩu?</a>
        </div>
    </div>
</body>
</html>
