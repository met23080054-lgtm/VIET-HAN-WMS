@echo off
title VIET-HAN WMS - Warehouse Management System

cd /d "%~dp0"

echo ==========================================
echo       VIET-HAN WMS DANG KHOI DONG
echo ==========================================
echo.
echo Dang mo website...
echo.

start "" http://localhost:8000/login.php

php\php.exe -S localhost:8000 -t app

pause