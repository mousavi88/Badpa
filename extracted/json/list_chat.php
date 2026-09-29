<?php

require_once('jdf.php');

$data = isset($_GET['data']) ? json_decode($_GET['data'], true) : [];
$currentUser = isset($_GET['user']) ? strtoupper($_GET['user']) : '';

$chatFolder = 'chat';
$groupFolder = 'group_setting';
$userFolder = 'data_karbars';

$output = [];

function persian_to_english($string) {
    $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian_digits, $english_digits, $string);
}

foreach ($data as $item) {
    $group = $item['g'];
    $lastId = $item['l'];

    $chatFile = $chatFolder . '/' . $group . '.json';
    if (file_exists($chatFile)) {
        $chatData = json_decode(file_get_contents($chatFile), true);

        $newCount = 0;
        foreach ($chatData['chat'] as $message) {
            if ($message['id'] > $lastId) {
                $newCount++;
            }
        }

        $lastMessage = end($chatData['chat']);
        $lastChat = isset($lastMessage['chat']) ? $lastMessage['chat'] : '';
        $lastTime = isset($lastMessage['time']) ? $lastMessage['time'] : '';

        $lastTime = preg_replace_callback('/\p{N}/u', function($matches) {
            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            return str_replace($persianDigits, $englishDigits, $matches[0]);
        }, $lastTime);

        $lastTimeNumeric = 0;
        $dynamicTime = '';

        if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $lastTime, $matches)) {
            $lastTime = $matches[0];
            
            $converted_date = persian_to_english($lastTime);
            $parts = explode(' ', $converted_date);
            $date_part = $parts[0];
            $time_part = isset($parts[1]) ? $parts[1] : '00:00';

            list($year, $month, $day) = explode('-', $date_part);
           
            $year = (int)$year;
            $month = (int)$month;
            $day = (int)$day;
            
            list($hour, $minute) = explode(':', $time_part);

            $timestamp = jmktime($hour, $minute, 0, $month, $day, $year);
            $now = time();
            $diff = $now - $timestamp;

            $lastTimeNumeric = $timestamp;

            $current_year = (int)jdate('Y', $now, '', 'Asia/Tehran', 'en');
            $current_month = (int)jdate('m', $now, '', 'Asia/Tehran', 'en');
            $current_day = (int)jdate('d', $now, '', 'Asia/Tehran', 'en');

            if ($diff < 0) {
                $dynamicTime = "آینده";
            } else {
                if ($year < $current_year) {
                    $dynamicTime = jdate('y/m/d', $timestamp);
                } else {
                    if ($year === $current_year && $month === $current_month && $day === $current_day) {
                        $dynamicTime = jdate('H:i', $timestamp);
                    } elseif ($year === $current_year && $month === $current_month && $day === ($current_day - 1)) {
                        if ($diff <= 7200) {
                            $dynamicTime = jdate('H:i', $timestamp);
                        } else {
                            $dynamicTime = jdate('l', $timestamp);
                        }
                    } elseif ($diff <= 604800) {
                        $dynamicTime = jdate('l', $timestamp);
                    } else {
                        $dynamicTime = jdate('d F', $timestamp);
                    }
                }
            }
        } else {
            $dynamicTime = '';
        }

        $dynamicTime = convertToPersianDigits($dynamicTime);

        if (isset($lastMessage['image']) && $lastMessage['image'] !== "false") {
            $lastChat = '🖼️ ' . $lastChat;
        }

        $lastChat = mb_strlen($lastChat, 'UTF-8') > 40 ? mb_substr($lastChat, 0, 40, 'UTF-8') . '...' : $lastChat;
        
    } else {
        $newCount = 0;
        $lastChat = '';
        $dynamicTime = '';
        $lastTimeNumeric = 0;
    }

    $name = '';
    $profile = '';

    // اگر پیوی بود، اطلاعات طرف مقابل را لود کن
    if (strpos($group, 'pv_') === 0 && !empty($currentUser)) {
        $parts = explode('_', $group);
        // pv_USER1_USER2 -> قطعات 1 و 2 نام کاربران هستند
        $otherUser = ($parts[1] === $currentUser) ? $parts[2] : $parts[1];
        
        $userFile = $userFolder . '/' . $otherUser . '.json';
        if (file_exists($userFile)) {
            $userData = json_decode(file_get_contents($userFile), true);
            $name = isset($userData['name']) ? $userData['name'] : $otherUser;
            $profile = isset($userData['profile']) ? $userData['profile'] : '';
        } else {
            $name = $otherUser;
        }
    } else {
        // اگر گروه معمولی بود
        $groupFile = $groupFolder . '/' . $group . '.json';
        if (file_exists($groupFile)) {
            $groupData = json_decode(file_get_contents($groupFile), true);
            $name = isset($groupData['name_group']) && !empty($groupData['name_group']) ? $groupData['name_group'] : '';
            $profile = isset($groupData['link_prof_group']) && !empty($groupData['link_prof_group']) ? $groupData['link_prof_group'] : '';
        }
    }

    $output[] = [
        'new_data' => intval($newCount),
        'profile' => $profile,
        'last_chat' => $lastChat,
        'last_time' => $dynamicTime,
        'name' => $name,
        'last_time_numeric' => $lastTimeNumeric,
        'user' => $group,
    ];
}

usort($output, function($a, $b) {
    return $b['last_time_numeric'] - $a['last_time_numeric'];
});

foreach ($output as &$item) {
    unset($item['last_time_numeric']);
}

echo json_encode($output);

function convertToPersianDigits($string) {
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace($englishDigits, $persianDigits, $string);
}