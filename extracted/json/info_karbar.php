<?php
// دریافت نام کاربر از پارامتر GET
$user = isset($_GET['user']) ? $_GET['user'] : '';

// بررسی اینکه نام کاربر وارد شده است
if (empty($user)) {
    http_response_code(400);
    echo json_encode(['error' => 'نام کاربر را وارد کنید']);
    exit;
}

// مسیر فایل JSON کاربر
$filePath = 'data_karbars/' . $user . '.json';

// بررسی وجود فایل
if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'فایل کاربر یافت نشد']);
    exit;
}

// خواندن محتوای فایل
$fileContent = file_get_contents($filePath);
if ($fileContent === false) {
    http_response_code(500);
    echo json_encode(['error' => 'خطا در خواندن فایل']);
    exit;
}

// تبدیل JSON به آرایه
$userData = json_decode($fileContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'خطا در پردازش فایل JSON']);
    exit;
}

// بررسی وجود فیلدهای مورد نیاز
if (!isset($userData['profile']) || !isset($userData['name'])) {
    http_response_code(404);
    echo json_encode(['error' => 'اطلاعات مورد نیاز در فایل وجود ندارد']);
    exit;
}

// ایجاد پاسخ JSON
$response = [
    'name' => $userData['name'],
    'profile' => $userData['profile']
];

// تنظیم هدر و ارسال پاسخ
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>