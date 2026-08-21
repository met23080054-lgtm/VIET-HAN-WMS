@echo off
title VIET-HAN WMS - Warehouse Management System

cd /d "%~dp0"

echo ==========================================
echo       VIET-HAN WMS DANG KHOI DONG
echo ==========================================
echo.
echo Dang kiem tra PHP va SQLite...
echo.

php\php.exe -c php\php.ini -m | findstr /I "PDO pdo_sqlite sqlite3"

echo.
echo Dang mo website...
echo.

start "" http://localhost:8000/reset_session.php

php\php.exe -c php\php.ini -S localhost:8000 -t app

pause