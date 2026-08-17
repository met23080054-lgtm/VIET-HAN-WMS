# VIET-HAN WMS - Hệ Thống Quản Lý Kho Hàng

Hệ thống quản lý kho hàng (Warehouse Management System - WMS) được xây dựng bằng ngôn ngữ **PHP** và cơ sở dữ liệu **SQLite**. Ứng dụng hỗ trợ quản lý danh mục sản phẩm, nhập kho, xuất kho, báo cáo tồn kho, quản lý tài khoản người dùng và phân quyền.

---

## ⚡ Dùng Thử Trực Tuyến Với 1-Click

Bạn có thể chạy thử trực tiếp ứng dụng này ngay trên trình duyệt web của mình thông qua **GitHub Codespaces** mà không cần cài đặt bất kỳ công cụ nào dưới máy cá nhân.

Nhấn vào nút bên dưới để mở ứng dụng:

[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://github.com/codespaces/new?hide_repo_select=true&ref=main&repo=met23080054-lgtm/VIET-HAN-WMS)

> *Sau khi Codespace khởi chạy xong (khoảng 1-2 phút), hệ thống sẽ tự động mở trang web ứng dụng ở một tab mới trên trình duyệt của bạn.*

---

## 💻 Cách Chạy Ứng Dụng Dưới Máy Cục Bộ (Local)

Dự án đã tích hợp sẵn môi trường chạy nhanh trên Windows (bao gồm PHP portable và SQLite):

1. Tải dự án này về máy tính của bạn và giải nén.
2. Click đúp chuột vào file **`CHAY_WEB.bat`** ở thư mục gốc của dự án.
3. Trình duyệt web sẽ tự động mở trang đăng nhập tại địa chỉ: `http://localhost:8000`.

---

## 🚀 Triển Khai Lên Máy Chủ Cloud (Render / Railway)

Dự án đã cấu hình sẵn file `Dockerfile` giúp triển khai dễ dàng lên các dịch vụ hosting Docker như Render, Railway, Fly.io:

### Các bước triển khai lên Render:
1. Đăng nhập vào [Render.com](https://render.com) bằng tài khoản GitHub của bạn.
2. Nhấn vào nút **New** > **Web Service**.
3. Kết nối với tài khoản GitHub của bạn và chọn repository `VIET-HAN-WMS`.
4. Render sẽ tự động phát hiện `Dockerfile` và thực hiện biên dịch, triển khai tự động.
5. Sau khi hoàn thành, Render sẽ cấp cho bạn một tên miền miễn phí để truy cập ứng dụng.

---

## 📁 Cấu Trúc Thư Mục Dự Án

* `app/`: Thư mục chứa mã nguồn chính của trang web (các file giao diện PHP, logic xử lý PHP, CSS, JS).
* `database/`: Chứa file cơ sở dữ liệu SQLite `quan_ly_kho.sqlite`.
* `php/`: Bộ cài PHP portable chạy cục bộ trên Windows.
* `CHAY_WEB.bat`: File kịch bản khởi chạy nhanh máy chủ PHP nội bộ trên Windows.
* `Dockerfile`: File cấu hình tạo container Docker triển khai lên đám mây.
* `.devcontainer/`: Cấu hình môi trường máy ảo chạy trên GitHub Codespaces.
