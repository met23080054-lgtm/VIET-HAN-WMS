HSB2006 - MET5 - VIỆT HÀN WMS
📦 Hệ thống quản lý kho VIỆT HÀN
VIỆT HÀN WMS là hệ thống quản lý kho được xây dựng bằng PHP, sử dụng SQLite làm cơ sở dữ liệu. Hệ thống hỗ trợ quản lý danh mục vật tư, sản phẩm, nhà cung cấp, nhập kho, xuất kho, tồn kho, tài khoản người dùng, thông báo và lịch sử hoạt động.

📌 1. Tổng quan dự án
Hệ thống được xây dựng để hỗ trợ các nghiệp vụ quản lý kho trong môi trường local, tập trung dữ liệu và xử lý các giao dịch nhập, xuất kho trong một hệ thống thống nhất.
Đối tượng sử dụng
Admin: quản lý dữ liệu và các chức năng quản trị.
Nhân viên (Staff): thực hiện nghiệp vụ kho và xem các thông tin tồn kho.

✨ 2. Chức năng chính
Quản lý kho
Quản lý danh mục vật tư
Quản lý sản phẩm
Quản lý nhà cung cấp
Nhập kho
Xuất kho
Báo cáo tồn kho
Quản lý tài khoản
Đăng nhập và đăng ký
Xác thực bằng session
Phân quyền theo vai trò
Quản lý tài khoản
Đổi mật khẩu
Đặt lại mật khẩu bằng OTP qua email
Chức năng hỗ trợ
Dashboard thống kê
Biểu đồ nhập/xuất
Import dữ liệu từ Excel
Thông báo trong ứng dụng
Ghi nhật ký hoạt động
Quản lý thông tin cá nhân và avatar

🛠️ 3. Công nghệ sử dụng
Thành phần
Công nghệ
Backend
PHP
Frontend
HTML, CSS, JavaScript
Kiến trúc
PHP procedural / Server-Side Rendering
Cơ sở dữ liệu
SQLite
Kết nối cơ sở dữ liệu
PDO
Web Server
PHP Built-in Development Server
Quản lý dependency
Composer
Import Excel
PhpSpreadsheet 5.9.0
Email / OTP
PHPMailer 7.1.1 + Gmail SMTP
Biểu đồ
Chart.js
Xác thực
PHP Session
Mật khẩu
password_hash() / password_verify()
Phân quyền
Admin / Nhân viên

⚠️ Phiên bản PHP cụ thể chưa được cung cấp trong tài liệu project.

🏗️ 4. Kiến trúc hệ thống
Hệ thống sử dụng kiến trúc PHP procedural kết hợp server-side rendering.
Người dùng
    │
    ▼
Trình duyệt Web
    │
    ▼
PHP Built-in Development Server
    │
    ▼
Ứng dụng PHP
    │
    ├── Xác thực / Session
    ├── Xử lý nghiệp vụ
    ├── SQLite Database
    ├── Composer Libraries
    │     ├── PHPMailer
    │     └── PhpSpreadsheet
    └── JavaScript
          ├── notifications.js
          └── Chart.js
Database chính của hệ thống:
app/database/quan_ly_kho.sqlite

📂 5. Cấu trúc project
Mã nguồn chính nằm trong thư mục app/.
VIET-HAN-WMS/
├── app/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── account.php
│   ├── users.php
│   ├── categories.php
│   ├── products.php
│   ├── suppliers.php
│   ├── stock_in.php
│   ├── stock_out.php
│   ├── inventory_report.php
│   ├── notifications.php
│   ├── notifications_api.php
│   ├── chart_data.php
│   ├── forgot-password.php
│   ├── import_products.php
│   ├── import_products_commit.php
│   ├── import_categories.php
│   ├── import_categories_commit.php
│   ├── import_suppliers.php
│   ├── import_suppliers_commit.php
│   │
│   ├── config/
│   │   ├── app.php
│   │   ├── auth.php
│   │   ├── database.php
│   │   ├── logger.php
│   │   ├── mail.php
│   │   └── notifications.php
│   │
│   ├── database/
│   │   └── quan_ly_kho.sqlite
│   │
│   └── vendor/
│
├── php/
├── CHAY_WEB.bat
└── README.md
Project còn chứa PHP Runtime, các thư viện Composer và một số file phục vụ quá trình phát triển.

🚀 6. Hướng dẫn cài đặt và chạy
6.1. Chuẩn bị môi trường
Project được thiết kế để chạy trên Windows trong môi trường local.
Project đã tích hợp sẵn PHP Runtime và các thành phần SQLite cần thiết, do đó không yêu cầu cài đặt PHP riêng cho cách chạy được tài liệu hóa.
Các dependency chính:
PHPMailer 7.1.1
PhpSpreadsheet 5.9.0
⚠️ Phiên bản PHP cụ thể chưa được xác định.

6.2. Tải project
Repository GitHub:
https://github.com/met23080054-lgtm/VIET-HAN-WMS
Có thể clone project bằng:
git clone https://github.com/met23080054-lgtm/VIET-HAN-WMS
cd VIET-HAN-WMS
Mã nguồn chính nằm trong:
app/

6.3. Cài đặt dependency
Project sử dụng Composer và đã có thư mục:
vendor/
Các dependency chính đã được xác định gồm:
PHPMailer 7.1.1
PhpSpreadsheet 5.9.0
Tài liệu project không cung cấp một lệnh cài dependency riêng được xác nhận, vì vậy README không tự thêm lệnh ngoài thông tin đã có.

6.4. Cấu hình
Các file cấu hình chính nằm trong:
app/config/
bao gồm:
app.php
auth.php
database.php
logger.php
mail.php
notifications.php
Thông tin xác thực SMTP hiện được lưu trong:
app/config/mail.php
⚠️ Không nên công khai credential thật trên GitHub. Trước khi public repository, cần revoke/rotate credential và chuyển sang biến môi trường hoặc một cơ chế cấu hình không được commit.

6.5. Cơ sở dữ liệu
Hệ thống sử dụng SQLite.
Database:
app/database/quan_ly_kho.sqlite
Kết nối database được thực hiện bằng PDO.
Database hiện tại đã có dữ liệu demo:
Thành phần
Số lượng
Categories
28
Suppliers
7
Products
7
Stock Receipts
11
Stock Issues
7
Receipt Items
13
Issue Items
7
Notifications
63
Activity Logs
98
Users
7
Roles
2

ℹ️ Không có file SQL dump hoặc quy trình import SQL riêng được cung cấp trong tài liệu.

6.6. Khởi động hệ thống
Project cung cấp file:
CHAY_WEB.bat
Lệnh chạy PHP Server được xác định như sau:
php\php.exe -c php\php.ini -S localhost:8000 -t app
Trong đó:
app
là document root.
CHAY_WEB.bat cũng mở reset_session.php trước khi chạy server để reset session cũ phục vụ môi trường demo/testing.

6.7. Truy cập ứng dụng
Sau khi server được khởi động, mở:
http://localhost:8000/index.php

🔐 7. Tài khoản kiểm thử
Hệ thống có hai role chính:
Role
Mã đăng ký
Quyền chính
Admin
ADMIN2026
Quản lý danh mục, sản phẩm, nhà cung cấp, import Excel và tài khoản
Nhân viên (Staff)
NV2026
Xem dữ liệu, nhập kho, xuất kho và báo cáo tồn kho

⚠️ Các mã trên là registration security code, không phải username hoặc password. Người dùng cần đăng ký tài khoản trước khi có thể đăng nhập.

🔗 8. Repository
GitHub Repository
https://github.com/met23080054-lgtm/VIET-HAN-WMS
Commit History
Chưa đủ dữ liệu để xác minh số lượng commit, branch, pull request hoặc lịch sử phát triển của repository.
Tài liệu project xác định khoảng 19 PowerShell scripts được sử dụng trong quá trình phát triển để xử lý encoding, UI/layout, Excel import, category ID, account UI, dashboard và các vấn đề khác. Đây là bằng chứng về quá trình phát triển nhưng không thay thế cho Git commit history.

🌐 9. Ứng dụng Local 
Ứng dụng Local
http://localhost:8000/index.php


🗄️ 10. Cơ sở dữ liệu
Công nghệ
SQLite
File database
app/database/quan_ly_kho.sqlite
Kết nối
PDO
Các bảng chính
users
roles
categories
suppliers
products
stock_receipts
stock_receipt_items
stock_issues
stock_issue_items
notifications
activity_logs
Database demo đã có sẵn dữ liệu để phục vụ kiểm thử và trình diễn.

🧪 11. Kiểm thử
Thông tin hiện có chưa bao gồm đầy đủ bộ test case và kết quả kiểm thử thực tế trong README.
Ứng dụng có thể được kiểm tra trong môi trường local bằng các role Admin và Nhân viên cùng database demo được cung cấp.

⚠️ 12. Vấn đề và hạn chế
Các hạn chế đã được xác định gồm:
Chưa xác định phiên bản PHP cụ thể.
Ứng dụng được tài liệu hóa cho môi trường local.
Chưa có Live Application URL được xác minh.
Không có SQL dump riêng.
Chưa xác minh được Git commit history từ tài liệu hiện có.
Kiến trúc sử dụng PHP procedural thay vì MVC framework.
SQLite phù hợp với môi trường local/demo nhưng có hạn chế khi triển khai production với số lượng người dùng đồng thời lớn.
CSRF protection chưa được áp dụng đồng nhất cho mọi thao tác thay đổi trạng thái.
Một số cấu hình và security value vẫn còn hard-coded.
Project còn một số file backup, patch và fix phục vụ quá trình phát triển.
