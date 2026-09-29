<?php

if (isset($_POST['target'])) {
    $file_name = basename($_POST['target']);
    
    $file_path = $_SERVER['DOCUMENT_ROOT'] . "/profile_group/" . $file_name;
    
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            echo "ok";
        } else {
            echo "خطا در حذف فایل.";
        }
    } else {
        echo "فایل پیدا نشد.";
    }
} else {
    echo "پارامتر target ارسال نشده است.";
}

?>