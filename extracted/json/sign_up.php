<?php
header('Content-Type: text/plain; charset=utf-8');

define('KARBARI_FILE', 'history_add/name_karbari.json');
define('MOBILE_FILE', 'history_add/mobiles.json');
define('DATA_DIR', 'data_karbars/');

function sendVerificationRequest($mobile, $code) {
    $checkUrl = 'https://zotos.ir/json/check_number/check.php';
    $postData = [
        'mobile' => $mobile,
        'code' => $code
    ];

    $ch = curl_init($checkUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return 'خطا در ارتباط با سرور تأیید کد';
    }

    return trim($response);
}

function validateMobileNumber($mobile) {
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        return ['status' => 'error', 'message' => 'فرمت شماره موبایل نامعتبر است'];
    }
    return ['status' => 'ok'];
}

function checkUsernameExists($username) {
    $username = strtoupper($username);
    
    if (!file_exists(KARBARI_FILE)) {
        return ['status' => 'error', 'message' => 'خطا: فایل اطلاعات کاربران یافت نشد'];
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
    $mobileValidation = validateMobileNumber($mobile);
    if ($mobileValidation['status'] === 'error') {
        return $mobileValidation;
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

function addUsername($username) {
    $username = strtoupper($username);
    
    if (!file_exists(KARBARI_FILE)) {
        file_put_contents(KARBARI_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    $data = json_decode(file_get_contents(KARBARI_FILE), true);
    if (!is_array($data)) {
        $data = [];
    }
    
    $data[] = $username;
    if (!file_put_contents(KARBARI_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        return ['status' => 'error', 'message' => 'خطا در ذخیره نام کاربری'];
    }
    
    return ['status' => 'ok'];
}

function addMobile($mobile) {
    $mobileValidation = validateMobileNumber($mobile);
    if ($mobileValidation['status'] === 'error') {
        return $mobileValidation;
    }
    
    if (!file_exists(MOBILE_FILE)) {
        file_put_contents(MOBILE_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    $data = json_decode(file_get_contents(MOBILE_FILE), true);
    if (!is_array($data)) {
        $data = [];
    }
    
    $data[] = $mobile;
    if (!file_put_contents(MOBILE_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        return ['status' => 'error', 'message' => 'خطا در ذخیره شماره موبایل'];
    }
    
    return ['status' => 'ok'];
}

function saveUserData($user, $name, $profile, $mobile) {
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0777, true)) {
        return ['status' => 'error', 'message' => 'خطا در ایجاد پوشه ذخیره اطلاعات'];
    }

    // ایجاد فایل joined/{user}.json
    $joinedDir = 'joined/';
    if (!is_dir($joinedDir) && !mkdir($joinedDir, 0777, true)) {
        return ['status' => 'error', 'message' => 'خطا در ایجاد پوشه joined'];
    }
    
    $joinedFilePath = $joinedDir . $user . '.json';
    if (!file_exists($joinedFilePath)) {
        if (file_put_contents($joinedFilePath, json_encode([], JSON_PRETTY_PRINT)) === false) {
            return ['status' => 'error', 'message' => 'خطا در ایجاد فایل joined'];
        }
    }

    $data = [
        'user' => $user,
        'name' => $name ?? '',
        'profile' => $profile ?? '',
        'mobile' => $mobile ?? ''
    ];
    
    $filePath = DATA_DIR . $user . '.json';
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (!file_put_contents($filePath, $jsonData)) {
        return ['status' => 'error', 'message' => 'خطا در ذخیره اطلاعات کاربر'];
    }
    
    return ['status' => 'ok'];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('درخواست نامعتبر است');
    }

    $user = $_POST['user'] ?? '';
    $name = $_POST['name'] ?? '';
    $profile = $_POST['profile'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $code = $_POST['code'] ?? '';
    
    // اعتبارسنجی ورودی‌های ضروری
    if (empty($user)) {
        throw new Exception('نام کاربری (user) نباید خالی باشد');
    }
    
    if (empty($mobile)) {
        throw new Exception('شماره موبایل نباید خالی باشد');
    }
    
    if (empty($code)) {
        throw new Exception('کد تأیید نباید خالی باشد');
    }

    // اعتبارسنجی شماره موبایل
    $mobileValidation = validateMobileNumber($mobile);
    if ($mobileValidation['status'] === 'error') {
        throw new Exception($mobileValidation['message']);
    }

    // تأیید کد پیامکی
    $verificationResponse = sendVerificationRequest($mobile, $code);
    if ($verificationResponse !== 'ok') {
        throw new Exception($verificationResponse);
    }

    // بررسی تکراری نبودن نام کاربری
    $checkUserResult = checkUsernameExists($user);
    if ($checkUserResult['status'] === 'exists') {
        throw new Exception($checkUserResult['message']);
    }
    if ($checkUserResult['status'] === 'error') {
        throw new Exception($checkUserResult['message']);
    }

    // بررسی تکراری نبودن شماره موبایل
    $checkMobileResult = checkMobileExists($mobile);
    if ($checkMobileResult['status'] === 'exists') {
        throw new Exception($checkMobileResult['message']);
    }
    if ($checkMobileResult['status'] === 'error') {
        throw new Exception($checkMobileResult['message']);
    }

    // ذخیره نام کاربری
    $addUserResult = addUsername($user);
    if ($addUserResult['status'] === 'error') {
        throw new Exception($addUserResult['message']);
    }

    // ذخیره شماره موبایل
    $addMobileResult = addMobile($mobile);
    if ($addMobileResult['status'] === 'error') {
        throw new Exception($addMobileResult['message']);
    }

    // ذخیره اطلاعات کاربر
    $saveResult = saveUserData($user, $name, $profile, $mobile);
    if ($saveResult['status'] === 'error') {
        throw new Exception($saveResult['message']);
    }

    echo 'ok';

} catch (Exception $e) {
    echo $e->getMessage();
}
?>