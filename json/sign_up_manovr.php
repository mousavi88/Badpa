<?php
header('Content-Type: text/plain; charset=utf-8');

define('KARBARI_FILE', 'history_add/name_karbari.json');
define('MOBILE_FILE', 'history_add/mobiles.json');

function validateMobileNumber($mobile) {
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        return ['status' => 'error', 'message' => 'فرمت شماره موبایل نامعتبر است'];
    }
    return ['status' => 'ok'];
}

function checkUsernameExists($username) {
    $username = strtoupper($username);
    
    if (!file_exists(KARBARI_FILE)) {
        return ['status' => 'ok'];
    }
    
    $json_data = file_get_contents(KARBARI_FILE);
    $data = json_decode($json_data, true);
    
    if ($data === null) {
        return ['status' => 'error', 'message' => 'خطا: فایل اطلاعات کاربران خراب است'];
    }
    
    if (in_array($username, $data)) {
        return ['status' => 'exists', 'message' => 'این نام کاربری از قبل وجود دارد'];
    }
    
    return ['status' => 'ok'];
}

function checkMobileExists($mobile) {
    $validation = validateMobileNumber($mobile);
    if ($validation['status'] === 'error') {
        return $validation;
    }
    
    if (!file_exists(MOBILE_FILE)) {
        return ['status' => 'ok'];
    }
    
    $json_data = file_get_contents(MOBILE_FILE);
    $data = json_decode($json_data, true);
    
    if ($data === null) {
        return ['status' => 'error', 'message' => 'خطا: فایل شماره موبایل‌ها خراب است'];
    }
    
    if (in_array($mobile, $data)) {
        return ['status' => 'exists', 'message' => 'این شماره موبایل از قبل ثبت شده است'];
    }
    
    return ['status' => 'ok'];
}

function sendVerificationCode($mobile) {
    $validation = validateMobileNumber($mobile);
    if ($validation['status'] === 'error') {
        return $validation;
    }
    
    $url = 'https://zotos.ir/json/check_number/send.php';
    $data = ['mobile' => $mobile];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['status' => 'error', 'message' => 'خطا در ارتباط با سرور پیامک: ' . $error];
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['status' => 'error', 'message' => 'سرور پیامک پاسخ نامعتبر داد: کد وضعیت ' . $httpCode];
    }
    
    return ['status' => 'ok', 'response' => $response];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('درخواست نامعتبر است');
    }

    $user = $_POST['user'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    
    // اعتبارسنجی ورودی‌های ضروری
    if (empty($user)) {
        throw new Exception('نام کاربری (user) نباید خالی باشد');
    }
    
    if (empty($mobile)) {
        throw new Exception('شماره موبایل نباید خالی باشد');
    }

    // اعتبارسنجی شماره موبایل
    $mobileValidation = validateMobileNumber($mobile);
    if ($mobileValidation['status'] === 'error') {
        throw new Exception($mobileValidation['message']);
    }

    // بررسی نام کاربری
    $checkUserResult = checkUsernameExists($user);
    if ($checkUserResult['status'] === 'exists') {
        throw new Exception($checkUserResult['message']);
    }
    if ($checkUserResult['status'] === 'error') {
        throw new Exception($checkUserResult['message']);
    }

    // بررسی شماره موبایل
    $checkMobileResult = checkMobileExists($mobile);
    if ($checkMobileResult['status'] === 'exists') {
        throw new Exception($checkMobileResult['message']);
    }
    if ($checkMobileResult['status'] === 'error') {
        throw new Exception($checkMobileResult['message']);
    }

    // ارسال کد تأیید
    $sendResult = sendVerificationCode($mobile);
    if ($sendResult['status'] === 'error') {
        throw new Exception($sendResult['message']);
    }

    echo 'ok';

} catch (Exception $e) {
    echo $e->getMessage();
}
?>