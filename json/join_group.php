<?php
// دریافت مقادیر user و group از درخواست
$user = $_GET['user'] ?? $_POST['user'] ?? null; // اصلاح شده: از $_GET یا $_POST
$group = $_GET['group'] ?? $_POST['group'] ?? null; // اصلاح شده: از $_GET یا $_POST

// بررسی وجود مقادیر ضروری
if (empty($user) || empty($group)) {
    http_response_code(400);
    die("پارامترهای user و group الزامی هستند");
}

// اطمینان از وجود پوشه joined
$joinedDirectory = 'joined';
if (!file_exists($joinedDirectory)) {
    if (!mkdir($joinedDirectory, 0755, true)) {
        http_response_code(500);
        die("خطا در ایجاد پوشه joined");
    }
}

// 1. ذخیره گروه در پروفایل کاربر (همان کد قبلی)
$userFilePath = $joinedDirectory . '/' . $user . '.json';
$userGroups = [];

if (file_exists($userFilePath)) {
    $fileContent = file_get_contents($userFilePath);
    $userGroups = json_decode($fileContent, true);
    if (!is_array($userGroups)) {
        $userGroups = [];
    }
}

// اضافه کردن گروه اگر وجود نداشته باشد
$groupAdded = false;
if (!in_array($group, $userGroups)) {
    $userGroups[] = $group;
    if (file_put_contents($userFilePath, json_encode($userGroups, JSON_PRETTY_PRINT)) === false) {
        http_response_code(500);
        die("خطا در ذخیره فایل کاربر");
    }
    $groupAdded = true;
}

// 2. ذخیره کاربر در لیست اعضای گروه
$followedDirectory = 'followed';
if (!file_exists($followedDirectory)) {
    if (!mkdir($followedDirectory, 0755, true)) {
        http_response_code(500);
        die("خطا در ایجاد پوشه followed");
    }
}

$groupFilePath = $followedDirectory . '/' . $group . '.json';
$groupMembers = [];

if (file_exists($groupFilePath)) {
    $fileContent = file_get_contents($groupFilePath);
    $groupMembers = json_decode($fileContent, true);
    if (!is_array($groupMembers)) {
        $groupMembers = [];
    }
}

// اضافه کردن کاربر اگر وجود نداشته باشد
if (!in_array($user, $groupMembers)) {
    $groupMembers[] = $user;
    if (file_put_contents($groupFilePath, json_encode($groupMembers, JSON_PRETTY_PRINT)) === false) {
        http_response_code(500);
        die("خطا در ذخیره فایل گروه");
    }
}

// پاسخ نهایی
if ($groupAdded) {
    echo "ok";
} else {
    echo "این گروه قبلاً وجود دارد";
}
?>