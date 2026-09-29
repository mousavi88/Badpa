<?php
function sendVerificationCode($token, $mobile, $appName, $code = null) {
    // ساخت URL درخواست
    $url = "https://sms.api-free.ir/?token=" . urlencode($token) . 
           "&mobile=" . urlencode($mobile) . 
           "&AppName=" . urlencode($appName);
    
    // اگر کد مشخص شده بود، به URL اضافه شود
    if ($code !== null) {
        $url .= "&code=" . urlencode($code);
    }
    
    // ارسال درخواست GET با استفاده از file_get_contents
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    // بررسی نتیجه درخواست
    if ($response === FALSE) {
        return ['success' => false, 'message' => 'خطا در ارسال درخواست به API'];
    }
    
    // پردازش پاسخ (فرض بر اینکه API پاسخ JSON برمی‌گرداند)
    $result = json_decode($response, true);
    
    return $result ?: ['success' => false, 'message' => 'پاسخ نامعتبر از API'];
}

// مثال استفاده از تابع:

// تنظیمات
$token = "c8fd1e73ef7eff6aa0887b05cb075884"; // توکن API شما
$mobile = "09196147418"; // شماره موبایل مقصد
$appName = "badpa chat"; // نام اپلیکیشن شما
$code = "5288"; // کد تایید (اختیاری)

// ارسال کد
$result = sendVerificationCode($token, $mobile, $appName, $code);

// نمایش نتیجه
if ($result['success'] ?? false) {
    echo "کد تایید با موفقیت ارسال شد.";
} else {
    echo json_encode($result);
}
?>