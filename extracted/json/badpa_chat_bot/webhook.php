<?php
/*
 * بات ساده برای سیستم پیام‌رسان
 * نسخه 1.1 - بدون ویرایش پیام و بدون نیاز به Bot ID
 */

// ==============================================
// بخش 1: تنظیمات اصلی
// ==============================================

// اطلاعات احراز هویت
define('BOT_USERNAME', 'ZOTOSBOT'); // نام کاربری بات در سیستم
define('API_KEY', 'kgxirsigcjftsoyd'); // کلید امنیتی بات

// تنظیمات اتصال به سرور اصلی
define('MAIN_SERVER_URL', 'https://zotos.ir');
define('SEND_MESSAGE_ENDPOINT', MAIN_SERVER_URL . '/json/bot_chat.php');

// تنظیمات هوش مصنوعی
define('AI_API_KEY', 'aa-FEd4KOgIf8gscXaUdSjVUzTn2b3cQd3bG3MYJqeFyxLeHwX9');
define('AI_API_URL', 'https://api.avalai.ir/v1/chat/completions');
define('AI_MODEL', 'deepseek-chat');

// مدیر سیستم
define('ADMIN_USERNAME', 'HASANALI');

// ==============================================
// بخش 2: توابع اصلی
// ==============================================

/**
 * دریافت و پردازش درخواست ورودی
 */
function handleRequest() {
    // دریافت داده ورودی
    $input = json_decode(file_get_contents('php://input'), true);
    
    // بررسی اعتبار سنجی
    if (!validateRequest()) {
        sendResponse(401, ['error' => 'Unauthorized']);
        exit;
    }
    
    // پردازش پیام
    if (isset($input['message'])) {
        processMessage($input['message']);
    } else {
        sendResponse(400, ['error' => 'Bad Request']);
    }
}

/**
 * پردازش پیام جدید
 */
function processMessage($message) {
    $chat_info = $message['chat'];
    $from_info = $message['from'];
    
    $group_id = $chat_info['user_group'];
    $user_id = $from_info['user'];
    $text = $message['text'] ?? '';
    
    // پردازش دستورات مدیریتی
    if (processAdminCommands($group_id, $user_id, $text)) {
        return;
    }
    
    // پردازش پیام‌های معمولی
    if (shouldProcessMessage($text)) {
        // ارسال پیام در حال پردازش
        sendMessage($group_id, "در حال پردازش درخواست شما...");
        
        // دریافت پاسخ از هوش مصنوعی
        $ai_response = getAIResponse($group_id, $user_id, $text);
        
        // ارسال پاسخ نهایی
        if ($ai_response['success']) {
            sendMessage($group_id, $ai_response['text']);
        } else {
            sendMessage($group_id, "خطا در پردازش: " . $ai_response['error']);
        }
    }
}

// ==============================================
// بخش 3: توابع کمکی
// ==============================================

/**
 * بررسی اعتبار درخواست
 */
function validateRequest() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($auth_header)) {
        return false;
    }
    
    list($type, $token) = explode(' ', $auth_header, 2);
    return ($type === 'Bearer' && $token === API_KEY);
}

/**
 * پردازش دستورات مدیریتی
 */
function processAdminCommands($group_id, $user_id, $text) {
    if ($user_id !== ADMIN_USERNAME) {
        return false;
    }
    
    $commands = [
        '/start' => '🤖 بات فعال شد!',
        '/help' => 'راهنما: برای استفاده از بات پیام خود را با + شروع کنید',
        '/status' => 'وضعیت بات: فعال'
    ];
    
    if (array_key_exists($text, $commands)) {
        sendMessage($group_id, $commands[$text]);
        return true;
    }
    
    return false;
}

/**
 * بررسی آیا پیام باید پردازش شود
 */
function shouldProcessMessage($text) {
    return str_starts_with($text, '+ ');
}

// ==============================================
// بخش 4: ارتباط با سرور اصلی
// ==============================================

/**
 * ارسال پیام به گروه
 */
function sendMessage($group_id, $text) {
    $payload = [
        'bot_username' => BOT_USERNAME,
        'api_key' => API_KEY,
        'group_id' => $group_id,
        'message' => $text
    ];
    
    $ch = curl_init(SEND_MESSAGE_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . API_KEY
        ],
        CURLOPT_TIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// ==============================================
// بخش 5: هوش مصنوعی
// ==============================================

/**
 * دریافت پاسخ از هوش مصنوعی
 */
function getAIResponse($group_id, $user_id, $text) {
    try {
        // آماده کردن پیام‌ها
        $messages = [
            [
                'role' => 'system',
                'content' => 'شما یک دستیار هوشمند فارسی‌زبان هستید. پاسخ‌های مفید و مختصر بدهید.'
            ],
            [
                'role' => 'user',
                'content' => substr($text, 2) // حذف + از ابتدای پیام
            ]
        ];
        
        // ارسال درخواست به هوش مصنوعی
        $data = [
            'model' => AI_MODEL,
            'messages' => $messages,
            'temperature' => 0.7
        ];
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . AI_API_KEY,
                'method' => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];
        
        $response = file_get_contents(AI_API_URL, false, stream_context_create($options));
        $response_data = json_decode($response, true);
        
        if (isset($response_data['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'text' => $response_data['choices'][0]['message']['content']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'پاسخ نامعتبر از سرویس هوش مصنوعی'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// ==============================================
// بخش 6: اجرای برنامه
// ==============================================

// شروع پردازش درخواست
header('Content-Type: application/json');
try {
    handleRequest();
} catch (Exception $e) {
    sendResponse(500, ['error' => 'خطای سرور']);
}

/**
 * ارسال پاسخ استاندارد
 */
function sendResponse($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
?>