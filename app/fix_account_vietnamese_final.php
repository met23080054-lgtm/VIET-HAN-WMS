<?php

$file = __DIR__ . '/account.php';

$backup = __DIR__ . '/account_before_vietnamese_final.php';

copy($file, $backup);

$lines = file($file);

function u($base64) {
    return base64_decode($base64);
}

/*
 * Mỗi dòng bên dưới được thay thế bằng UTF-8 chuẩn.
 * Nội dung tiếng Việt được lưu dạng Base64 để không bị CMD/PowerShell
 * làm hỏng encoding.
 */

$replace = [

    108 => u('            \'' . base64_encode('Phiên làm việc không hợp lệ. Vui lòng thử lại.') . '\','),

    133 => u('                \'' . base64_encode('Họ và tên không được để trống.') . '\','),

    148 => u('                \'' . base64_encode('Email không hợp lệ.') . '\','),

    174 => u('            \'' . base64_encode('Đã cập nhật thông tin cá nhân.') . '\','),

    210 => u('                \'' . base64_encode('Vui lòng nhập đầy đủ mật khẩu.') . '\','),

    223 => u('                \'' . base64_encode('Mật khẩu hiện tại không đúng.') . '\','),

    231 => u('                \'' . base64_encode('Mật khẩu mới phải có ít nhất 8 ký tự.') . '\','),

    239 => u('                \'' . base64_encode('Xác nhận mật khẩu không khớp.') . '\','),

    252 => u('                \'' . base64_encode('Mật khẩu mới phải khác mật khẩu hiện tại.') . '\','),

    274 => u('            \'' . base64_encode('Đổi mật khẩu thành công.') . '\','),

    293 => u('                \'' . base64_encode('Vui lòng chọn một ảnh hợp lệ.') . '\','),

    315 => u('                \'' . base64_encode('Tệp tải lên không phải hình ảnh hợp lệ.') . '\','),

    350 => u('                \'' . base64_encode('Không thể tạo thư mục lưu ảnh.') . '\','),

    374 => u('                \'' . base64_encode('Không thể lưu ảnh lên máy chủ.') . '\','),

    415 => u('            \'' . base64_encode('Đã cập nhật ảnh đại diện.') . '\','),

    490 => u('    <title>' . base64_decode(base64_encode('Tài khoản - VIỆT HAN WMS')) . '</title>'),

    874 => u('                        ?? \'' . base64_encode('Nhân viên') . '\')'),

    947 => u('                    ' . base64_decode(base64_encode('Thông tin cá nhân'))),

    967 => u('                                ' . base64_decode(base64_encode('Họ và tên'))),

    984 => u('                                ' . base64_decode(base64_encode('Tên đăng nhập'))),

    1000 => u('                                ' . base64_decode(base64_encode('Quyền tài khoản'))),

    1007 => u('                                    ?? \'' . base64_encode('Nhân viên') . '\''),

    1032 => u('                            ' . base64_decode(base64_encode('Lưu thông tin'))),

    1088 => u('                            ' . base64_decode(base64_encode('Tối thiểu 8 ký tự.'))),

    1179 => u('                                ' . base64_decode(base64_encode('Tải ảnh lên'))),

];

/*
 * Một số dòng có thể đã thay đổi vị trí do các lần sửa trước.
 * Vì vậy ngoài thay theo dòng, xử lý thêm các chuỗi lỗi còn lại.
 */

foreach ($replace as $lineNumber => $newLine) {
    $index = $lineNumber - 1;

    if (isset($lines[$index])) {
        $lines[$index] = $newLine . PHP_EOL;
    }
}

/*
 * Sửa các đoạn HTML/entity còn sót.
 */

$content = implode('', $lines);

$content = str_replace(
    '?? \'Nh&#226;n vi&#234;n\'',
    '?? \'Nhân viên\'',
    $content
);

$content = str_replace(
    '\'Nh&#226;n vi&#234;n\'',
    '\'Nhân viên\'',
    $content
);

$content = str_replace(
    'T?i thi?u 8 ky t?.',
    'Tối thiểu 8 ký tự.',
    $content
);

$content = str_replace(
    'JPG, PNG ho?c WEBP.',
    'JPG, PNG hoặc WEBP.',
    $content
);

$content = str_replace(
    'Dung lu?ng t?i da 5 MB.',
    'Dung lượng tối đa 5 MB.',
    $content
);

$content = str_replace(
    'C?P NH?T THONG TIN CA NHAN',
    'CẬP NHẬT THÔNG TIN CÁ NHÂN',
    $content
);

$content = str_replace(
    'D?I M?T KH?U',
    'ĐỔI MẬT KHẨU',
    $content
);

$content = str_replace(
    'D?I ?NH D?I DI?N',
    'ĐỔI ẢNH ĐẠI DIỆN',
    $content
);

$content = str_replace(
    'D?C L?I THONG TIN SAU KHI C?P NH?T',
    'ĐỌC LẠI THÔNG TIN SAU KHI CẬP NHẬT',
    $content
);

$content = str_replace(
    'VI&#7878;T HAN WMS',
    'VIỆT HAN WMS',
    $content
);

file_put_contents(
    $file,
    $content
);

echo PHP_EOL;
echo "========================================" . PHP_EOL;
echo " ACCOUNT VIETNAMESE FIX COMPLETED" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo "Backup: " . basename($backup) . PHP_EOL;
echo PHP_EOL;