FROM php:8.2-apache

# Cài đặt các thư viện cần thiết cho SQLite
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy toàn bộ mã nguồn web vào thư mục root của Apache
COPY app/ /var/www/html/

# Copy thư mục database ra ngoài đúng theo cấu trúc tương đối
COPY database/ /var/www/database/

# Phân quyền cho user www-data (Apache) có quyền đọc ghi dữ liệu SQLite
RUN chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/www/database \
    && chmod -R 775 /var/www/database

EXPOSE 80
