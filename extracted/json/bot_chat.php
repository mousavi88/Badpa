<?php
header('Content-Type: application/json');

// تنظیمات پایه
define('BOTS_DIR', 'badpa_chat_bot/data_bots/');
define('GROUPS_FILE', 'badpa_chat_bot/fallo_bots.json');
define('CHATS_DIR', 'chat/');

// دریافت و اعتبارسنجی داده ورودی
$input = json_decode(file_get_contents('php://input'), true);

if (!validateRequest($input)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// پردازش پیام
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['bot_username'])) {
    processBotMessage($input);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad Request']);
}

// ================= توابع اصلی =================

/**
 * اعتبارسنجی درخواست
 */
function validateRequest($input) {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    
    if (empty($auth) || !str_starts_with($auth, 'Bearer ')) {
        return false;
    }
    
    $api_key = substr($auth, 7);
    $bot_username = $input['bot_username'] ?? '';
    
    // بررسی وجود فایل پیکربندی بات
    $bot_file = BOTS_DIR . $bot_username . '.json';
    if (!file_exists($bot_file)) {
        return false;
    }
    
    // بررسی تطابق کلید API
    $bot_config = json_decode(file_get_contents($bot_file), true);
    return hash_equals($bot_config['api_key'], $api_key);
}

/**
 * پردازش پیام بات
 */
function processBotMessage($data) {
    $group_id = $data['group'] ?? '';
    $bot_username = $data['bot_username'] ?? '';
    $message = $data['message'] ?? '';
    $image = $data['image'] ?? '';
    
    // بررسی عضویت بات در گروه
    if (!isBotInGroup($bot_username, $group_id)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Bot not in group']);
        return;
    }
    
    // ذخیره پیام در تاریخچه چت
    $result = saveMessageToHistory($group_id, [
        'id' => generateMessageId($group_id),
        'user' => $bot_username,
        'chat' => $message,
        'image' => $image ?: 'false',
        'video' => 'false',
        'file' => 'false',
        'time' => date('Y-m-d H:i:s'),
        'is_bot' => true
    ]);
    
    if ($result) {
        echo json_encode(['ok' => true, 'message_id' => $result]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to save message']);
    }
}

/**
 * بررسی عضویت بات در گروه
 */
function isBotInGroup($bot_username, $group_id) {
    if (!file_exists(GROUPS_FILE)) return false;
    
    $groups = json_decode(file_get_contents(GROUPS_FILE), true);
    return isset($groups[$group_id]) && in_array($bot_username, $groups[$group_id]);
}

/**
 * ذخیره پیام در تاریخچه
 */
function saveMessageToHistory($group_id, $message_data) {
    $file_path = CHATS_DIR . $group_id . '.json';
    
    // ایجاد فایل جدید یا بارگیری تاریخچه موجود
    $history = file_exists($file_path) ? 
        json_decode(file_get_contents($file_path), true) : 
        ['chat' => [], 'time' => date('Y-m-d H:i:s')];
    
    // اضافه کردن پیام جدید
    $history['chat'][] = $message_data;
    $history['time'] = date('Y-m-d H:i:s');
    
    // ذخیره فایل
    return file_put_contents($file_path, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ? 
        $message_data['id'] : false;
}

/**
 * تولید شناسه پیام
 */
function generateMessageId($group_id) {
    $last_id_file = 'last_id/' . $group_id . '.json';
    $last_id = file_exists($last_id_file) ? (int)file_get_contents($last_id_file) : 0;
    $new_id = $last_id + 1;
    
    file_put_contents($last_id_file, $new_id);
    return $new_id;
}
?>