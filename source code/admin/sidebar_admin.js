document.addEventListener("DOMContentLoaded", function() {
    let menuItems = document.querySelectorAll(".sidebar ul li a");

    // Kiểm tra nếu có mục nào được chọn trước đó
    let currentUrl = window.location.href;
    menuItems.forEach(item => {
        if (item.href === currentUrl) {
            item.classList.add("active");
        }
        
        item.addEventListener("click", function() {
            // Xóa lớp active khỏi tất cả các mục
            menuItems.forEach(i => i.classList.remove("active"));
            // Thêm lớp active vào mục được chọn
            this.classList.add("active");
        });
    });
});
