<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

require_once __DIR__ . '/jdf.php';

define('SMSIR_USERNAME',    '9966387246');
define('SMSIR_APIKEY',      '50AK2p6ESXCulLmsugd8yUIjSfExX54R3CcfawxYmfm7mY0q');
define('SMSIR_LINE_NUMBER', '50003181890144');
define('JSON_FILE',         __DIR__ . '/mobile.json');
define('CODE_EXPIRE_SECS',  300);

function respond($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function loadData() {
    if (!file_exists(JSON_FILE)) {
        file_put_contents(JSON_FILE, '[]');
    }
    $data = json_decode(file_get_contents(JSON_FILE), true);
    return is_array($data) ? $data : [];
}

function saveData($data) {
    file_put_contents(JSON_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function sendViaSmsIr($mobile, $code) {
    $message = "کد تایید بادپا چت: {$code}";

    $url = 'https://api.sms.ir/v1/send'
        . '?username=' . urlencode(SMSIR_USERNAME)
        . '&password=' . urlencode(SMSIR_APIKEY)
        . '&mobile='   . urlencode($mobile)
        . '&line='     . urlencode(SMSIR_LINE_NUMBER)
        . '&text='     . urlencode($message);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'GET',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => ['Accept: text/plain'],
    ]);

    $response = curl_exec($curl);
    $curlErr  = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        return ['status' => false, 'message' => 'خطا در اتصال به sms.ir: ' . $curlErr];
    }

    // پاسخ sms.ir معمولاً یک عدد مثبت (شناسه پیام) یا متن خطاست
    $trimmed = trim($response);
    if (is_numeric($trimmed) && (int)$trimmed > 0) {
        return ['status' => true];
    }

    // اگه JSON برگردوند چک کن
    $result = json_decode($response, true);
    if (is_array($result)) {
        $ok = ($result['status'] ?? 0) == 1 || ($result['IsSuccessful'] ?? false);
        if ($ok) return ['status' => true];
        return ['status' => false, 'message' => $result['Message'] ?? $result['message'] ?? $trimmed];
    }

    return ['status' => false, 'message' => 'پاسخ sms.ir: ' . substr($trimmed, 0, 200)];
}

// ── اجرای اصلی ──────────────────────────────────────────────────
$mobile = trim($_POST['mobile'] ?? '');
if (empty($mobile)) {
    respond('error', 'پارامتر mobile الزامی است');
}

$data = loadData();
$now  = time();

$last = null;
foreach ($data as $item) {
    if ($item['mobile'] === $mobile) {
        $last = $item;
    }
}

if ($last !== null && $last['status'] === 'waiting') {
    $sentAt  = strtotime(str_replace('__', ' ', $last['time_send']));
    $elapsed = $now - $sentAt;
    if ($elapsed < CODE_EXPIRE_SECS) {
        $remaining = CODE_EXPIRE_SECS - $elapsed;
        respond('error', "کد تایید فعالی ارسال شده. {$remaining} ثانیه صبر کنید.");
    }
}

$code   = rand(100000, 999999);

// ابتدا اطلاعات را در فایل ذخیره می‌کنیم تا حتی در صورت شکست ارسال پیامک، کد در دسترس باشد
$data[] = [
    'mobile'       => $mobile,
    'time_send'    => jdate('Y-m-d__H-i-s'),
    'status'       => 'waiting',
    'code'         => $code,
    'trying_count' => 0
];
saveData($data);

// سپس برای ارسال پیامک تلاش می‌کنیم
$result = sendViaSmsIr($mobile, $code);

if (!$result['status']) {
    // پیام ارور را برمی‌گردانیم اما کد از قبل ذخیره شده است
    respond('error', $result['message'] ?? 'خطا در ارسال پیامک');
}

respond('ok', 'کد تایید ارسال شد');
?>
