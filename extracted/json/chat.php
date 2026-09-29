<?php
include('jdf.php'); 

$currentTime = time();
$shamsiDate = jdate('Y-m-d H:i', $currentTime);

$microTime = microtime(true);
$time_group = date("Y-m-d H:i:s.", $microTime) . sprintf("%03d", ($microTime - floor($microTime)) * 1000);

$group = isset($_POST['group']) ? $_POST['group'] : '';
$chat = isset($_POST['chat']) ? $_POST['chat'] : '';
$user = isset($_POST['user']) ? $_POST['user'] : '';
$link_image = isset($_POST['link_image']) ? $_POST['link_image'] : '';
$link_video = isset($_POST['link_video']) ? $_POST['link_video'] : '';
$link_file = isset($_POST['link_file']) ? $_POST['link_file'] : '';
$file_name = isset($_POST['file_name']) ? $_POST['file_name'] : '';

$file_path = "chat/" . $group . ".json";
$last_id_path = "last_id/" . $group . ".json";

if (file_exists($last_id_path)) {
    $last_id = (int)file_get_contents($last_id_path);
} else {
    $last_id = 0;
}

$new_id = $last_id + 1;
file_put_contents($last_id_path, $new_id);

if (file_exists($file_path)) {
    $json_data = file_get_contents($file_path);
    $data = json_decode($json_data, true);
} else {
    $data = [
        "chat" => [],
        "time" => $time_group
    ];
}

$image_value = empty($link_image) ? 'false' : $link_image;
$video_value = empty($link_video) ? 'false' : $link_video;
$file_value = empty($link_file) ? 'false' : $link_file;

$new_entry = [
    "id" => $new_id,
    "image" => $image_value,
    "video" => $video_value,
    "file" => $file_value,
    "file_name" => $file_name,
    "chat" => $chat,
    "time" => $shamsiDate,
    "user" => $user
];

$data['chat'][] = $new_entry;
$data['time'] = $time_group;

// استفاده از LOCK_EX برای جلوگیری از تداخل با delete_chat.php یا سایر پیام‌های همزمان
file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

echo "ok";
?>