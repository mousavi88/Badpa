<?php
$userTime = isset($_POST['time']) ? trim($_POST['time']) : '';
$userGroup = isset($_POST['group']) ? trim($_POST['group']) : '';

if (empty($userGroup)) {
    echo 'error';
    exit;
}

$jsonFilePath = 'chat/' . $userGroup . '.json';

if (!file_exists($jsonFilePath)) {
    echo '[null]';
} else {
    $jsonData = file_get_contents($jsonFilePath);
    $dataArray = json_decode($jsonData, true);

    // بررسی حذف گروه
    if (trim($jsonData) === '[null]' || $dataArray === [null] || !is_array($dataArray)) {
        echo '[null]';
        exit;
    }

    if (!isset($dataArray['time'])) {
        echo 'error';
        exit;
    }

    $serverTime = $dataArray['time'];

    if (empty($userTime)) {
        $userTime = '0000-00-00 00:00:00.000';
    }

    // لاگ برای عیب‌یابی پولینگ (می‌توان بعداً غیرفعال کرد)
    $logMsg = date('Y-m-d H:i:s') . " | Poll | Group: $userGroup | UserTime: [$userTime] | ServerTime: [$serverTime]\n";
    file_put_contents('polling_log.txt', $logMsg, FILE_APPEND);

    // اگر زمان سرور جدیدتر است، کل لیست را برمی‌گردانیم (شامل موارد حذف شده یا ویرایش شده)
    if ($userTime < $serverTime) {
        $result = [
            'time' => $serverTime,
            'chat' => json_encode($dataArray['chat'], JSON_UNESCAPED_UNICODE),
            'karbars' => [],
        ];
        $result = [
            'time' => $dataArray['time'],
            'chat' => json_encode($dataArray['chat'], JSON_UNESCAPED_UNICODE),
            'karbars' => [],
        ];

        // خواندن فایل tic.json
        $ticFilePath = 'tic/tic.json';
        $ticData = [];
        if (file_exists($ticFilePath)) {
            $ticJson = file_get_contents($ticFilePath);
            $ticData = json_decode($ticJson, true);
        }

        $users = [];
        foreach ($dataArray['chat'] as $chatItem) {
            if (isset($chatItem['user'])) {
                $users[$chatItem['user']] = true;
            }
        }

        $karbars = [];
        foreach (array_keys($users) as $user) {
            $userFilePath = "data_karbars/{$user}.json";
            if (file_exists($userFilePath)) {
                $userData = json_decode(file_get_contents($userFilePath), true);

                if (isset($userData['name']) && isset($userData['profile'])) {
                    $karbars["{$user}"] = $userData['name'];
                    $karbars["{$user}_profile+"] = $userData['profile'];
                    
                    // بررسی نقش کاربر در tic.json
                    $userTic = '';
                    foreach ($ticData as $role => $members) {
                        if (in_array($user, $members)) {
                            $userTic = $role;
                            break;
                        }
                    }
                    $karbars["{$user}_tic+"] = $userTic;
                }
            }
        }

        $result['karbars'] = json_encode($karbars, JSON_UNESCAPED_UNICODE); 
        echo json_encode($result, JSON_UNESCAPED_UNICODE); 
    } else {
        echo 'false';
        exit;
    }
}
?>