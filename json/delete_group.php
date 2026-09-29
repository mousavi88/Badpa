<?php
if (isset($_POST['group'])) {
    $group = $_POST['group'];
    $filePath = "chat/{$group}.json";
    file_put_contents($filePath, '[null]');
    echo "ok";
} else {
    echo "مقدار group ارسال نشده است.";
}
?>