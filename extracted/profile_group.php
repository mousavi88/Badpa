<?php
header('Content-Type: application/json; charset=utf-8');

if (!is_dir('profile_group')) {
    mkdir('profile_group', 0755, true);
}

function GENNAME($LEN = 16) {
    $CHAR = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $CHARLEN = strlen($CHAR);
    $NAMERANDOM = '';
    for ($i = 0; $i < $LEN; $i++) {
        $NAMERANDOM .= $CHAR[rand(0, $CHARLEN - 1)];
    }
    return $NAMERANDOM;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $Target_DIR = 'profile_group/';
    $FEX = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $NAME = GENNAME() . '.' . $FEX;
    $Target_File = $Target_DIR . $NAME;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $Target_File)) {
        $TEMP = ['Status' => true, 'MSG' => 'فایل شما با موفقیت اپلود شد', 'Link' => $NAME];
    } else {
        $TEMP = ['Status' => false, 'MSG' => 'فایل شما مشکلی دارد که نمیشود آن را اپلود کرد'];
    }
} else {
    $TEMP = ['Status' => false, 'MSG' => 'مشکلی بوجود آمد'];
}

exit(json_encode($TEMP, JSON_UNESCAPED_UNICODE));
?>
