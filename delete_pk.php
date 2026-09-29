<?php

if (isset($_POST['target'])) {
    $file_name = basename($_POST['target']);
    $file_path = $_SERVER['DOCUMENT_ROOT'] . "/profile_karbar/" . $file_name;

    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            echo "ok";
        } else {
            echo "خطا در حذف فایل. ممکن است فایل قفل شده یا مجوزهای لازم وجود نداشته باشد.";
        }
    } else {
        echo "فایل پیدا نشد.";
    }
} else {
    echo "پارامتر target ارسال نشده است.";
}
?>