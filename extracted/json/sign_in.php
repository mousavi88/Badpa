<?php
header('Content-Type: application/json');

// دریافت داده‌های ورودی از POST
$mobile = $_POST['mobile'] ?? '';
$code = $_POST['code'] ?? '';

// آماده‌سازی پاسخ پیش‌فرض
$response = [
    'status' => 'error',
    'user' => null
];

// بررسی اینکه آیا شماره موبایل و کد دریافت شده‌اند
if (empty($mobile) || empty($code)) {
    $response['message'] = 'Mobile number and code are required';
    echo json_encode($response);
    exit;
}

// اعتبارسنجی اولیه شماره موبایل
if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
    $response['message'] = 'Invalid mobile number format';
    echo json_encode($response);
    exit;
}

// ارسال درخواست به سرور برای بررسی شماره موبایل و کد
$checkUrl = 'https://zotos.ir/json/check_number/check.php';
$postData = [
    'mobile' => $mobile,
    'code' => $code
];

$ch = curl_init($checkUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
$checkResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// بررسی پاسخ سرور
if ($checkResponse === false) {
    $response['message'] = 'Failed to connect to verification server';
    echo json_encode($response);
    exit;
}

if ($checkResponse === 'ok') {
    // جستجو در فایل‌های JSON برای یافتن کاربر
    $directory = 'data_karbars/';
    $files = glob($directory . '*.json');
    $userFound = null;
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (isset($data['mobile']) && $data['mobile'] === $mobile) {
            $userFound = $data;
            break;
        }
    }
    
    if ($userFound !== null) {
        $response['status'] = 'success';
        $response['user'] = $userFound;
        $response['message'] = 'Verification successful';
    } else {
        $response['message'] = 'User not found';
    }
} else {
    $response['message'] = $checkResponse;
    if ($httpCode !== 200) {
        $response['message'] = 'Verification server error';
    }
}

// ارسال پاسخ نهایی
echo json_encode($response);
?>