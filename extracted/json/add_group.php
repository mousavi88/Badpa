<?php
include('jdf.php'); 

$group = isset($_POST['group']) ? $_POST['group'] : '';
$pass = isset($_POST['pass']) ? $_POST['pass'] : '';
$modir = isset($_POST['modir']) ? $_POST['modir'] : '';
$name_group = isset($_POST['name_group']) ? $_POST['name_group'] : '';
$link_prof_group = isset($_POST['link_prof_group']) ? $_POST['link_prof_group'] : '';

if (empty($group) || empty($modir)) {
    echo "لطفاً مقادیر گروه و مدیر را وارد کنید.";
    exit;
}
$group = basename($group);
$group = strtoupper($group);

$currentTime = time();
$shamsiDate = jdate('Y-m-d H:i', $currentTime);

$microTime = microtime(true);
$milliseconds = floor(($microTime - floor($microTime)) * 1000);
$nanoseconds = floor(($microTime - floor($microTime)) * 1000000000);

$gregorianTime = date('Y-m-d H:i:s', floor($microTime)) . '.' . sprintf("%03d", $milliseconds) . '.' . sprintf("%09d", $nanoseconds);

$chatFile = 'chat/' . $group . '.json';
$groupSettingFile = 'group_setting/' . $group . '.json';
$lastIdFile = 'last_id/' . $group . '.json';

$chatData = [
    [
        "id" => 0,
        "image" => "false",
        "video" => "false",
        "file" => "false",
        "chat" => "گروه ایجاد شد",
        "user" => $modir,
        "time" => $shamsiDate
    ]
];

$groupSettingData = [
    "modir" => $modir,
    "mode" => "group",
    "name_group" => $name_group,
    "link_prof_group" => $link_prof_group,
    "pass" => $pass,
    "edit_time" => $gregorianTime,
    "start_time" => $gregorianTime
];

$finalChatData = [
    "chat" => $chatData,
    "time" => $gregorianTime
];

if (!file_exists('chat')) {
    mkdir('chat', 0777, true);
}
if (!file_exists('group_setting')) {
    mkdir('group_setting', 0777, true);
}
if (!file_exists('last_id')) {
    mkdir('last_id', 0777, true);
}

file_put_contents($chatFile, json_encode($finalChatData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents($groupSettingFile, json_encode($groupSettingData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents($lastIdFile, json_encode(0, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "ok";
?>
