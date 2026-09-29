<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت آپدیت BadpaChat</title>
    <style>
        body { font-family: Tahoma, sans-serif; padding: 20px; line-height: 1.6; }
        .container { max-width: 600px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        input[type="submit"] { background: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        .msg { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h2>انتشار نسخه جدید اپلیکیشن</h2>

        <?php
        $versionsFile = 'versions.json';
        $apkPath = '../../apks/Badpa.apk';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $versionCode = (int)$_POST['versionCode'];
            $versionName = $_POST['versionName'];
            $releaseNotes = $_POST['releaseNotes'];
            $isMandatory = isset($_POST['isMandatory']);

            if (isset($_FILES['apkFile']) && $_FILES['apkFile']['error'] === UPLOAD_ERR_OK) {
                if (move_uploaded_file($_FILES['apkFile']['tmp_name'], $apkPath)) {
                    $versions = file_exists($versionsFile) ? json_decode(file_get_contents($versionsFile), true) : [];
                    $versions[] = [
                        'versionCode' => $versionCode,
                        'versionName' => $versionName,
                        'releaseNotes' => $releaseNotes,
                        'isMandatory' => $isMandatory
                    ];
                    file_put_contents($versionsFile, json_encode($versions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    echo '<div class="msg success">نسخه جدید با موفقیت منتشر شد.</div>';
                } else {
                    echo '<div class="msg error">خطا در آپلود فایل APK.</div>';
                }
            } else {
                echo '<div class="msg error">لطفاً فایل APK را انتخاب کنید.</div>';
            }
        }
        ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>کد نسخه (Version Code):</label>
                <input type="number" name="versionCode" required>
            </div>
            <div class="form-group">
                <label>نام نسخه (Version Name):</label>
                <input type="text" name="versionName" required placeholder="مثلاً 1.2.0">
            </div>
            <div class="form-group">
                <label>توضیحات تغییرات:</label>
                <textarea name="releaseNotes" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="isMandatory"> آپدیت اجباری است
                </label>
            </div>
            <div class="form-group">
                <label>فایل APK:</label>
                <input type="file" name="apkFile" accept=".apk" required>
            </div>
            <input type="submit" value="انتشار نسخه جدید">
        </form>
    </div>
</body>
</html>
