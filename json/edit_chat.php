<?php
include('jdf.php');

$currentTime = time();
$shamsiDate = jdate('Y-m-d H:i', $currentTime);
$timeWithEditLabel = "ویرایش شده " . $shamsiDate;

$microTime = microtime(true);
$time_group = date("Y-m-d H:i:s.", $microTime) . sprintf("%03d", ($microTime - floor($microTime)) * 1000);

$group = isset($_POST['group']) ? $_POST['group'] : '';
$chat = isset($_POST['chat']) ? $_POST['chat'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$filePath = "chat/{$group}.json";

if (file_exists($filePath)) {
    $jsonData = file_get_contents($filePath);
    $data = json_decode($jsonData, true);

    if ($data !== null) {
        if (isset($data['chat']) && is_array($data['chat'])) {
            foreach ($data['chat'] as &$chatItem) {
                if ($chatItem['id'] == $id) {
                    $chatItem['chat'] = $chat;
                    $chatItem['time'] = $timeWithEditLabel;
                    $data['time'] = $time_group;
                    break;
                }
            }

            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "ok";
        } else {
            echo "آرایه chat در داده‌ها وجود ندارد.";
        }
    } else {
        echo "خطا در بارگذاری داده‌ها.";
    }
} else {
    echo "فایل JSON پیدا نشد.";
}
?>
