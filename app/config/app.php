<?php
// Thiet lap encoding UTF-8 toan cuc
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

define(
    'ADMIN_REGISTRATION_CODE',
    'VIETHAN-ADMIN-2026'
);