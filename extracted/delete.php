<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['target'])) {
        $target = basename($_POST['target']);
        $filePath = 'uploads/' . $target;

        if (file_exists($filePath)) {
            $previewPath = 'preview/' . $target;
            @unlink($previewPath); // تلاش برای حذف پیش‌نمایش در صورت وجود

            if (unlink($filePath)) {
                echo "ok";
            } else {
                $error_message = "\ndelete.php : خطا در حذف فایل: " . $filePath;
                file_put_contents('error.txt', $error_message, FILE_APPEND);
                echo "خطا در حذف فایل";
            }
        } else {
            $error_message = "\ndelete.php : فایل برای حذف یافت نشد: " . $filePath;
            file_put_contents('error.txt', $error_message, FILE_APPEND);
            echo "فایل یافت نشد";
        }
    } else {
        $error_message = "\n delete.php : target ارسال نشده است.";
        file_put_contents('error.txt', $error_message, FILE_APPEND);
        echo "target ارسال نشده است";
    }
} else {
    echo "درخواست نامعتبر";
}
?>