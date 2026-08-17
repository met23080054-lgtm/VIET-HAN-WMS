<?php

session_start();

echo '<pre>';

echo "USER_ID: ";
var_dump($_SESSION['user_id'] ?? null);

echo "FULL_NAME: ";
var_dump($_SESSION['full_name'] ?? null);

echo "ROLE: ";
var_dump($_SESSION['role'] ?? null);

echo "\nSESSION:\n";
print_r($_SESSION);

echo '</pre>';
