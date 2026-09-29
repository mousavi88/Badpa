<?php

function logError($errorMessage) {
    $errorFile = '../error.txt';
    $time = date('Y-m-d H:i');
    $formattedMessage = "[{$time}] - {$errorMessage}\n";
    file_put_contents($errorFile, $formattedMessage, FILE_APPEND);
}

function deleteFromServer($filename) {
    $ch = curl_init();
    // آدرس فایل دیلیت در همان هاست
    curl_setopt($ch, CURLOPT_URL, "https://zotos.ir/delete.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'target' => $filename
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $group = isset($_POST['group']) ? $_POST['group'] : null;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

    if ($id === null) {
        $error = "خطا: پارامتر 'id' باید وارد شود.";
        echo $error;
        logError($error);
        exit;
    }

    if (!$group) {
        $error = "خطا: پارامتر 'group' باید وارد شود.";
        echo $error;
        logError($error);
        exit;
    }

    $filePath = "chat/{$group}.json";

    if (!file_exists($filePath)) {
        $error = "فایل JSON پیدا نشد. {$filePath}";
        echo $error;
        logError($error);
        exit;
    }

    $jsonData = file_get_contents($filePath);
    $data = json_decode($jsonData, true);

    if ($data && isset($data['chat'])) {
        $messageKey = array_search($id, array_column($data['chat'], 'id'));

        if ($messageKey !== false) {
            $msg = $data['chat'][$messageKey];

            // حذف تصویر
            if (isset($msg['image']) && $msg['image'] !== "false") {
                deleteFromServer($msg['image']);
            }
            // حذف ویدیو
            if (isset($msg['video']) && $msg['video'] !== "false") {
                deleteFromServer($msg['video']);
            }
            // حذف فایل
            if (isset($msg['file']) && $msg['file'] !== "false") {
                deleteFromServer($msg['file']);
            }

            // حذف از آرایه
            unset($data['chat'][$messageKey]);

            // بازنشانی کلیدهای آرایه برای اطمینان از خروجی JSON آرایه‌ای (نه آبجکت با کلیدهای عددی)
            $data['chat'] = array_values($data['chat']);
            
            // بروزرسانی زمان تغییرات گروه با دقت میلی‌ثانیه و اطمینان از تغییر حتمی زمان
            $microTime = microtime(true);
            $time_group = date("Y-m-d H:i:s.", $microTime) . sprintf("%03d", ($microTime - floor($microTime)) * 1000);

            // اگر زمان جدید با زمان قبلی برابر بود (بسیار نادر)، یک میلی‌ثانیه اضافه می‌کنیم
            if (isset($data['time']) && $time_group <= $data['time']) {
                $microTime += 0.001;
                $time_group = date("Y-m-d H:i:s.", $microTime) . sprintf("%03d", ($microTime - floor($microTime)) * 1000);
            }

            $data['time'] = $time_group;

            // ذخیره فایل با قفل انحصاری برای جلوگیری از تداخل با سایر عملیات (مثل ارسال پیام همزمان)
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

            // لاگ برای عیب‌یابی
            $logMsg = date('Y-m-d H:i:s') . " | Delete | Group: $group | MsgID: $id | New Group Time: $time_group\n";
            file_put_contents('polling_log.txt', $logMsg, FILE_APPEND);

            echo "ok";
        } else {
            $error = "پیام با id مشخص پیدا نشد.";
            echo $error;
            logError($error);
        }
    } else {
        $error = "خطا در بارگذاری داده‌ها.";
        echo $error;
        logError($error);
    }

} else {
    $error = "لطفاً درخواست را با متد POST ارسال کنید.";
    echo $error;
    logError($error);
}

?>