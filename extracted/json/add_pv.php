<?php
include('jdf.php');

$userA = isset($_POST['userA']) ? $_POST['userA'] : '';
$userB = isset($_POST['userB']) ? $_POST['userB'] : '';

if (empty($userA) || empty($userB)) {
    echo "خطا: نام کاربران مشخص نیست";
    exit;
}

// تولید شناسه یکتا برای پیوی (مرتب شده بر اساس الفبا)
$users = [strtoupper($userA), strtoupper($userB)];
sort($users);
$pvGroupId = "pv_" . $users[0] . "_" . $users[1];

$currentTime = time();
$shamsiDate = jdate('Y-m-d H:i', $currentTime);

$microTime = microtime(true);
$gregorianTime = date('Y-m-d H:i:s.', $microTime) . sprintf("%03d", ($microTime - floor($microTime)) * 1000);

$chatFile = 'chat/' . $pvGroupId . '.json';
$lastIdFile = 'last_id/' . $pvGroupId . '.json';

// ایجاد پوشه ها در صورت عدم وجود
if (!file_exists('chat')) mkdir('chat', 0777, true);
if (!file_exists('last_id')) mkdir('last_id', 0777, true);
if (!file_exists('joined')) mkdir('joined', 0777, true);

// ایجاد فایل چت اگر وجود ندارد
if (!file_exists($chatFile)) {
    $initialData = [
        "chat" => [],
        "time" => $gregorianTime
    ];
    file_put_contents($chatFile, json_encode($initialData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($lastIdFile, json_encode(0));
}

// اضافه کردن به لیستjoined هر دو کاربر
foreach ([$userA, $userB] as $u) {
    $joinedPath = 'joined/' . strtoupper($u) . '.json';
    $joinedData = [];
    if (file_exists($joinedPath)) {
        $joinedData = json_decode(file_get_contents($joinedPath), true);
        if (!is_array($joinedData)) $joinedData = [];
    }

    if (!in_array($pvGroupId, $joinedData)) {
        $joinedData[] = $pvGroupId;
        file_put_contents($joinedPath, json_encode($joinedData, JSON_PRETTY_PRINT));
    }
}

echo "ok";
?>
