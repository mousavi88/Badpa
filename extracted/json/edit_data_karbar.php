<?php

$user = isset($_POST['user']) ? $_POST['user'] : '';
$name = isset($_POST['name']) ? $_POST['name'] : '';
$old_profile = isset($_POST['old_profile']) ? $_POST['old_profile'] : '';
$new_profile = isset($_POST['new_profile']) ? $_POST['new_profile'] : '';

$file_path = "data_karbars/{$user}.json";

if (empty($new_profile)) {
    if (file_exists($file_path)) {
        $json_data = json_decode(file_get_contents($file_path), true);
        if ($json_data !== null) {
            $json_data['name'] = $name;
            file_put_contents($file_path, json_encode($json_data, JSON_PRETTY_PRINT));
            echo "ok";
        } else {
            echo "خطا در خواندن داده‌های JSON.";
        }
    } else {
        echo "فایل JSON مربوط به کاربر پیدا نشد.";
    }
} else {
    if (file_exists($file_path)) {
        $json_data = json_decode(file_get_contents($file_path), true);
        if ($json_data !== null) {
            $json_data['name'] = $name;
            $json_data['profile'] = $new_profile;
            file_put_contents($file_path, json_encode($json_data, JSON_PRETTY_PRINT));
            
            $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://zotos.ir/delete_pk.php");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'target' => $old_profile]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response === "ok") {
                echo "ok";
            } else {
                echo "جواب کد حذف عکس قبلی:" . $response;
            }
            
            curl_close($ch);
        } else {
            echo "خطا در خواندن داده‌های JSON.";
        }
    } else {
        echo "فایل JSON مربوط به کاربر پیدا نشد.";
    }
}

?>
