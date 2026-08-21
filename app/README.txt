VIỆT HÀN WMS
HỆ THỐNG QUẢN LÝ KHO

HƯỚNG DẪN CHẠY HỆ THỐNG

1. Giải nén thư mục VIET-HAN-WMS.

2. Mở Command Prompt tại thư mục VIET-HAN-WMS.

3. Chạy lệnh:

php -S localhost:8000

4. Mở trình duyệt:

http://localhost:8000/login.php

5. Cơ sở dữ liệu:

database/quan_ly_kho.sqlite

6. Thư viện PHPMailer đã được đóng gói trong:

vendor/

CÁC CHỨC NĂNG

- Đăng ký và đăng nhập
- Phân quyền Admin và Nhân viên
- Quản lý danh mục vật tư
- Quản lý sản phẩm
- Quản lý nhà cung cấp
- Nhập kho
- Xuất kho
- Báo cáo tồn kho
- Dashboard thống kê
- Biểu đồ nhập - xuất kho
- Lịch sử thao tác
- Quên mật khẩu bằng mã OTP qua Email
