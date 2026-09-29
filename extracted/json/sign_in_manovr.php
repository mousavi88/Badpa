<?php
header('Content-Type: application/json; charset=utf-8');

define('MOBILE_FILE', __DIR__ . '/history_add/mobiles.json');

function validateMobile($mobile) {
    return preg_match('/^09[0-9]{9}$/', $mobile);
}

function checkMobileExists($mobile) {
    if (!validateMobile($mobile)) {
        return ['status' => 'error', 'message' => 'فرمت شماره موبایل نامعتبر است'];
    }

    if (!file_exists(MOBILE_FILE)) {
        return ['status' => 'not_exists'];
    }

    $data = json_decode(file_get_contents(MOBILE_FILE), true);
    if (!is_array($data)) {
        return ['status' => 'error', 'message' => 'خطا در خواندن فایل ذخیره‌سازی'];
    }

    return in_array($mobile, $data)
        ? ['status' => 'exists']
        : ['status' => 'not_exists'];
}

function sendVerificationCode($mobile) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://zotos.ir/json/check_number/send.php',
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['mobile' => $mobile]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_errno($ch) ? curl_error($ch) : '';
    curl_close($ch);

    if ($curlErr) {
        return ['status' => 'error', 'message' => 'خطا در ارتباط با سرور پیامک: ' . $curlErr];
    }

    $result = json_decode($response, true);
    if (!is_array($result)) {
        return ['status' => 'error', 'message' => 'پاسخ نامعتبر از سرور پیامک (HTTP ' . $httpCode . ')'];
    }

    // send.php همیشه status: "ok" یا status: "error" برمی‌گرداند
    return $result['status'] === 'ok'
        ? ['status' => 'ok']
        : ['status' => 'error', 'message' => $result['message'] ?? 'خطا در ارسال پیامک'];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('درخواست نامعتبر است', 405);
    }

    $mobile = trim($_POST['mobile'] ?? '');
    if (empty($mobile)) {
        throw new Exception('شماره موبایل نباید خالی باشد', 400);
    }

    $checkResult = checkMobileExists($mobile);
    if ($checkResult['status'] === 'error') {
        throw new Exception($checkResult['message'], 400);
    }
    if ($checkResult['status'] === 'not_exists') {
        throw new Exception('این شماره موبایل ثبت‌نام نکرده است', 404);
    }

    $sendResult = sendVerificationCode($mobile);
    // فعلاً خطاهای ارسال پیامک نادیده گرفته می‌شود تا فلو متوقف نشود (مشابه ثبت‌نام)
    /*
    if ($sendResult['status'] !== 'ok') {
        // کد 400 نه 500: این خطای ورودی کاربر است (مثلاً کد فعال قبلی)، نه خطای سرور
        throw new Exception($sendResult['message'], 400);
    }
    */

    echo json_encode([
        'status'  => 'success',
        'message' => 'پیامک با موفقیت ارسال شد'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $code = $e->getCode();
    // جلوگیری از کد 0 که PHP آن را 500 می‌کند
    http_response_code(in_array($code, [400, 404, 405]) ? $code : 400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
