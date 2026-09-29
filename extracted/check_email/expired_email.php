<?php
include('jdf.php');
date_default_timezone_set('Asia/Tehran'); // تنظیم منطقه زمانی

$filePath = 'email.json';
$jsonData = file_get_contents($filePath);
$emails = json_decode($jsonData, true);

function convertPersianToLatinNumbers($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $latin, $string);
}

$now = new DateTime();

foreach ($emails as &$email) {
    if ($email['status'] === 'waiting') {
        $timeSendLatin = convertPersianToLatinNumbers($email['time_send']);
        list($datePart, $timePart) = explode('__', $timeSendLatin);
        $dateTimeStr = $datePart . ' ' . str_replace('-', ':', $timePart);
        
        $sendTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeStr);
        if (!$sendTime) continue;
        
        $interval = $now->diff($sendTime);
        $totalSeconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
        
        if ($totalSeconds >= 180) { // تغییر شرط به >=
            $email['status'] = 'expired';
            $email['reason_expired'] = 'waiting time over';
            $email['time_expired'] = jdate("Y-m-d__H-i-s");
        }
    }
}

file_put_contents($filePath, json_encode($emails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "ok";
?>