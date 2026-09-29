<?php
header('Content-Type: application/json; charset=utf-8');

// ==================== تنظیمات رسانه (فقط محدودیت‌ها) ====================
$MAX_SIZE_IMAGE = 10 * 1024 * 1024; // 10 مگابایت
$MAX_SIZE_FILE = 100 * 1024 * 1024; // 100 مگابایت

$IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/bmp' => 'bmp',
    'image/webp' => 'webp',
    'image/heic' => 'heic',
    'image/heif' => 'heif',
    'image/tiff' => 'tiff',
    'image/x-icon' => 'ico',
    'image/svg+xml' => 'svg'
];

$OTHER_ALLOWED_TYPES = [
    // Video
    'video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/x-msvideo' => 'avi',
    'video/x-matroska' => 'mkv', 'video/x-ms-wmv' => 'wmv', 'video/x-flv' => 'flv',
    'video/webm' => 'webm', 'video/3gpp' => '3gp', 'video/x-m4v' => 'm4v',
    // Audio
    'audio/mpeg' => 'mp3', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav',
    'audio/aac' => 'aac', 'audio/flac' => 'flac', 'audio/ogg' => 'ogg',
    'audio/mp4' => 'm4a', 'audio/x-ms-wma' => 'wma', 'audio/opus' => 'opus',
    // Documents & Others
    'application/pdf' => 'pdf', 'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'text/plain' => 'txt', 'application/zip' => 'zip', 'application/x-rar-compressed' => 'rar'
];

$UPLOAD_DIR = 'uploads/';
$PREVIEW_DIR = 'preview/';

if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);
if (!is_dir($PREVIEW_DIR)) mkdir($PREVIEW_DIR, 0755, true);

function GENNAME($LEN = 16) {
    $CHAR = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $NAMERANDOM = '';
    for ($i = 0; $i < $LEN; $i++) {
        $NAMERANDOM .= $CHAR[rand(0, strlen($CHAR) - 1)];
    }
    return $NAMERANDOM;
}

// ==================== پردازش آپلود ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        exit(json_encode(['Status' => false, 'MSG' => 'خطا در آپلود فایل'], JSON_UNESCAPED_UNICODE));
    }
    
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = explode(';', @finfo_file($finfo, $file['tmp_name']))[0];
    finfo_close($finfo);

    $is_image = array_key_exists($mime_type, $IMAGE_TYPES);
    $is_other = array_key_exists($mime_type, $OTHER_ALLOWED_TYPES);

    if (!$is_image && !$is_other) {
        exit(json_encode(['Status' => false, 'MSG' => 'فرمت فایل مجاز نیست'], JSON_UNESCAPED_UNICODE));
    }

    $max_allowed = $is_image ? $MAX_SIZE_IMAGE : $MAX_SIZE_FILE;
    if ($file['size'] > $max_allowed) {
        exit(json_encode(['Status' => false, 'MSG' => 'حجم فایل غیرمجاز است'], JSON_UNESCAPED_UNICODE));
    }
    
    $ext = $is_image ? $IMAGE_TYPES[$mime_type] : $OTHER_ALLOWED_TYPES[$mime_type];
    $NAME = GENNAME() . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $UPLOAD_DIR . $NAME)) {

        // اگر اپلیکیشن پیش‌نمایش هم فرستاده باشد، آن را ذخیره می‌کنیم
        if (isset($_FILES['preview']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
            move_uploaded_file($_FILES['preview']['tmp_name'], $PREVIEW_DIR . $NAME);
        }

        exit(json_encode([
            'Status' => true,
            'MSG' => 'فایل با موفقیت آپلود شد',
            'Link' => $NAME
        ], JSON_UNESCAPED_UNICODE));
    }
}

exit(json_encode(['Status' => false, 'MSG' => 'درخواست نامعتبر'], JSON_UNESCAPED_UNICODE));
