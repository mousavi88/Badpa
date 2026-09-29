<?php
header('Content-Type: application/json');

$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
$user = isset($_GET['user']) ? $_GET['user'] : '';

if (!$mode || !$user) {
    echo json_encode([
        'status' => 'error',
        'message' => 'پارامترهای ضروری (mode یا user) ارسال نشده!'
    ]);
    exit;
}

$user = strtoupper($user);

$file = '';
if ($mode == 'group') {
    $file = 'group.json';
} elseif ($mode == 'karbari') {
    $file = 'name_karbari.json';
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'حالت نامعتبر! (فقط group یا karbari مجاز است)'
    ]);
    exit;
}

if (!file_exists($file)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'فایل داده پیدا نشد!'
    ]);
    exit;
}

$json_data = file_get_contents($file);
$data = json_decode($json_data, true);

$response = [
    'status' => in_array($user, $data) ? 'ok' : 'not',
    'user' => $user
];

echo json_encode($response);
?>