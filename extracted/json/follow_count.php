<?php
// دریافت مقدار group از پارامتر GET
$group = isset($_GET['group']) ? $_GET['group'] : null;

if ($group === null) {
    die("پارامتر group در URL مشخص نشده است.");
}

// اطمینان از ایمنی نام فایل
$safeGroup = preg_replace('/[^a-zA-Z0-9_-]/', '', $group);
$filePath = "followed/{$safeGroup}.json";

// بررسی وجود فایل
if (!file_exists($filePath)) {
    die("فایل مورد نظر یافت نشد: {$filePath}");
}

// خواندن محتوای فایل JSON
$jsonContent = file_get_contents($filePath);
if ($jsonContent === false) {
    die("خطا در خواندن فایل JSON.");
}

// تبدیل JSON به آرایه PHP
$data = json_decode($jsonContent, true);
if ($data === null) {
    die("خطا در تجزیه فایل JSON.");
}

// بررسی اینکه آیا داده یک آرایه است
if (!is_array($data)) {
    die("داده‌های فایل JSON به شکل صحیح نیستند.");
}

// شمارش آیتم‌ها و نمایش نتیجه
$count = count($data);
echo $count;
?>