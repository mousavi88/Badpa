<?php
header('Content-Type: text/plain');

function save_raw_data_to_file($filename, $data) {
    $file_path = $filename.'.json';

    if (file_put_contents($file_path, $data) !== false) {
        return "داده‌ها با موفقیت در فایل ذخیره شدند.";
    } else {
        return "خطا در ذخیره‌سازی داده‌ها.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && isset($_POST['data'])) {
        $filename = $_POST['name'];
        $raw_data = $_POST['data'];

        $response = save_raw_data_to_file($filename, $raw_data);
    } else {
        $response = "داده‌های لازم ارسال نشده‌اند.";
    }

    echo $response;
} else {
    echo "لطفاً از متد POST برای ارسال داده‌ها استفاده کنید.";
}
?>
