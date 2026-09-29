<?php
// دریافت مقادیر user و group از درخواست
$user = $_GET['user'] ?? $_POST['user'] ?? null;
$group = $_GET['group'] ?? $_POST['group'] ?? null;

// بررسی وجود مقادیر ضروری
if (empty($user) || empty($group)) {
    http_response_code(400);
    die("پارامترهای user و group الزامی هستند");
}

// 1. حذف گروه از لیست کاربر --------------------------------------
$joinedDirectory = 'joined';
$userFilePath = $joinedDirectory . '/' . $user . '.json';

// بررسی وجود پوشه و فایل کاربر
if (!file_exists($userFilePath)) {
    http_response_code(404);
    die("فایل کاربر یافت نشد");
}

// خواندن و پردازش فایل کاربر
$userGroups = json_decode(file_get_contents($userFilePath), true);
if (!is_array($userGroups)) {
    http_response_code(500);
    die("فرمت فایل کاربر نامعتبر است");
}

// حذف گروه از لیست کاربر (اگر وجود داشته باشد)
$userKey = array_search($group, $userGroups);
$userUpdated = false;
if ($userKey !== false) {
    unset($userGroups[$userKey]);
    $userGroups = array_values($userGroups); // بازسازی ایندکس‌ها
    
    if (file_put_contents($userFilePath, json_encode($userGroups, JSON_PRETTY_PRINT)) === false) {
        http_response_code(500);
        die("خطا در ذخیره فایل کاربر");
    }
    $userUpdated = true;
}

// 2. حذف کاربر از لیست گروه --------------------------------------
$followedDirectory = 'followed';
$groupFilePath = $followedDirectory . '/' . $group . '.json';

// فقط اگر فایل گروه وجود داشت پردازش شود
if (file_exists($groupFilePath)) {
    $groupMembers = json_decode(file_get_contents($groupFilePath), true);
    
    if (is_array($groupMembers)) {
        $memberKey = array_search($user, $groupMembers);
        if ($memberKey !== false) {
            unset($groupMembers[$memberKey]);
            $groupMembers = array_values($groupMembers); // بازسازی ایندکس‌ها
            
            if (file_put_contents($groupFilePath, json_encode($groupMembers, JSON_PRETTY_PRINT)) === false) {
                // اگر حذف کاربر از گروه ناموفق بود، خطا نمی‌دهیم تا تغییرات کاربر حفظ شود
                error_log("خطا در ذخیره فایل گروه: " . $groupFilePath);
            }
        }
    }
}

// پاسخ نهایی
if ($userUpdated) {
    echo "ok";
} else {
    echo "این گروه در پروفایل کاربر وجود ندارد";
}
?>