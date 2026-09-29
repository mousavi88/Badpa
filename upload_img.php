<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Chunk-Index, X-Total-Chunks, X-File-Id, X-Original-Filename');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

$target_dir = "uploads2/";
$temp_dir = "uploads2/temp/";
$upload_chmod = 0755;

if (!file_exists($target_dir)) {
    mkdir($target_dir, $upload_chmod, true);
}
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, $upload_chmod, true);
}

$allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'avi', 'mov', 'mkv', 'webm', 'pdf', 'zip', 'apk', 'gz', 'exe', 'rar', 'doc', 'docx'];
$maxFileSize = 500 * 1024 * 1024; // 500 مگابایت

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . '/' . $target_dir;

// ============================================================================
// دریافت تکه (Chunk) فایل
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_CHUNK_INDEX'])) {
    $chunkIndex = (int)$_SERVER['HTTP_X_CHUNK_INDEX'];
    $totalChunks = (int)$_SERVER['HTTP_X_TOTAL_CHUNKS'];
    $fileId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SERVER['HTTP_X_FILE_ID'] ?? '');
    $originalFilename = isset($_SERVER['HTTP_X_ORIGINAL_FILENAME']) 
        ? basename(urldecode($_SERVER['HTTP_X_ORIGINAL_FILENAME'])) 
        : 'unknown';
    
    if (empty($fileId)) {
        echo json_encode(['status' => 'error', 'message' => 'شناسه فایل نامعتبر است']);
        exit;
    }
    
    $fileType = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowed_types)) {
        echo json_encode(['status' => 'error', 'message' => 'نوع فایل مجاز نیست']);
        exit;
    }

    // ذخیره متادیتا برای اولین تکه
    $metaFile = $temp_dir . $fileId . '_meta.json';
    if ($chunkIndex === 0) {
        $meta = [
            'filename' => $originalFilename,
            'filetype' => $fileType,
            'totalChunks' => $totalChunks,
            'created' => time()
        ];
        file_put_contents($metaFile, json_encode($meta));
    }

    $chunkFileName = $temp_dir . $fileId . '_chunk_' . $chunkIndex;
    
    $input = fopen('php://input', 'rb');
    $output = fopen($chunkFileName, 'wb');
    
    if ($input && $output) {
        while (!feof($input)) {
            $buffer = fread($input, 8192);
            if ($buffer === false) break;
            fwrite($output, $buffer);
        }
        fclose($input);
        fclose($output);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطا در ذخیره تکه']);
        exit;
    }
    
    $receivedChunks = 0;
    $allChunksReceived = true;
    
    for ($i = 0; $i < $totalChunks; $i++) {
        $checkChunkFile = $temp_dir . $fileId . '_chunk_' . $i;
        if (file_exists($checkChunkFile) && filesize($checkChunkFile) > 0) {
            $receivedChunks++;
        } else {
            $allChunksReceived = false;
        }
    }
    
    if ($allChunksReceived && $totalChunks > 0) {
        $meta = json_decode(file_get_contents($metaFile), true);
        $finalFileType = $meta['filetype'] ?? $fileType;
        
        $random_string = bin2hex(random_bytes(8));
        $finalFileName = $random_string . "." . $finalFileType;
        $finalPath = $target_dir . $finalFileName;
        
        $finalFile = fopen($finalPath, 'wb');
        if ($finalFile) {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFile = $temp_dir . $fileId . '_chunk_' . $i;
                $chunkHandle = fopen($chunkFile, 'rb');
                if ($chunkHandle) {
                    while (!feof($chunkHandle)) {
                        $buffer = fread($chunkHandle, 8192);
                        if ($buffer === false) break;
                        fwrite($finalFile, $buffer);
                    }
                    fclose($chunkHandle);
                }
                @unlink($chunkFile);
            }
            fclose($finalFile);
            @unlink($metaFile);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'فایل با موفقیت آپلود شد.',
                'filename' => $finalFileName,
                'file_url' => $baseUrl . $finalFileName,
                'chunks_received' => $receivedChunks
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'خطا در ایجاد فایل نهایی']);
        }
    } else {
        echo json_encode([
            'status' => 'partial',
            'message' => 'تکه دریافت شد',
            'chunk_index' => $chunkIndex,
            'chunks_received' => $receivedChunks,
            'total_chunks' => $totalChunks,
            'progress' => $totalChunks > 0 ? round(($receivedChunks / $totalChunks) * 100) : 0
        ]);
    }
    exit;
}

// ============================================================================
// بررسی وضعیت آپلود
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'status') {
    $fileId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['file_id'] ?? '');
    $totalChunks = isset($_GET['total_chunks']) ? (int)$_GET['total_chunks'] : 0;
    
    if (empty($fileId)) {
        echo json_encode(['status' => 'error', 'message' => 'شناسه فایل نامعتبر است.']);
        exit;
    }
    
    $receivedChunks = [];
    $hasMeta = false;
    
    $metaFile = $temp_dir . $fileId . '_meta.json';
    if (file_exists($metaFile)) {
        $hasMeta = true;
        $meta = json_decode(file_get_contents($metaFile), true);
        $totalChunks = $meta['totalChunks'] ?? $totalChunks;
    }
    
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkFile = $temp_dir . $fileId . '_chunk_' . $i;
        if (file_exists($chunkFile) && filesize($chunkFile) > 0) {
            $receivedChunks[] = $i;
        }
    }
    
    echo json_encode([
        'status' => 'ok',
        'file_id' => $fileId,
        'received_chunks' => $receivedChunks,
        'total_chunks' => $totalChunks,
        'has_meta' => $hasMeta,
        'progress' => $totalChunks > 0 ? round((count($receivedChunks) / $totalChunks) * 100) : 0
    ]);
    exit;
}

// ============================================================================
// حذف تکه‌های ناقص (پاکسازی)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    $fileId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['file_id'] ?? '');
    
    if (empty($fileId)) {
        echo json_encode(['status' => 'error', 'message' => 'شناسه فایل نامعتبر']);
        exit;
    }
    
    $deletedCount = 0;
    $tempFiles = glob($temp_dir . $fileId . '_*');
    foreach ($tempFiles as $file) {
        if (@unlink($file)) $deletedCount++;
    }
    
    echo json_encode([
        'status' => 'success',
        'deleted_chunks' => $deletedCount
    ]);
    exit;
}

// ============================================================================
// آپلود معمولی (برای فایل‌های کوچک)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploadType']) && $_POST['uploadType'] === 'file') {
    $uploadedFiles = [];
    
    if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
        $files = $_FILES['files'];
        $fileCount = count($files['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $original_filename = $files['name'][$i];
                $fileType = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                
                if (!in_array($fileType, $allowed_types)) {
                    continue;
                }
                
                $random_string = bin2hex(random_bytes(8));
                $target_file_name = $random_string . "." . $fileType;
                $target_file_path = $target_dir . $target_file_name;
                
                if (move_uploaded_file($files['tmp_name'][$i], $target_file_path)) {
                    $uploadedFiles[] = [
                        'filename' => $target_file_name,
                        'file_url' => $baseUrl . $target_file_name
                    ];
                }
            }
        }
        
        if (empty($uploadedFiles)) {
            echo json_encode(['message' => 'هیچ فایلی با موفقیت آپلود نشد.', 'status' => 'error']);
        } else {
            echo json_encode([
                'message' => count($uploadedFiles) . ' فایل آپلود شد.',
                'status' => 'success',
                'files' => $uploadedFiles
            ]);
        }
    } else {
        echo json_encode(['message' => 'فایلی برای آپلود انتخاب نشده است.', 'status' => 'error']);
    }
    exit;
}

// ============================================================================
// آپلود از لینک
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploadType']) && $_POST['uploadType'] === 'url') {
    $uploadedFiles = [];
    $urlsData = $_POST['urls'] ?? '[]';
    $urls = json_decode($urlsData, true);
    
    if (!is_array($urls) || empty($urls)) {
        echo json_encode(['message' => 'هیچ لینکی برای دانلود ارائه نشده است.', 'status' => 'error']);
        exit;
    }

    foreach ($urls as $fileUrl) {
        if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
            continue;
        }
        
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowed_types)) {
            continue;
        }
        
        $random_string = bin2hex(random_bytes(8));
        $target_file_name = $random_string . "." . $fileExtension;
        $target_file_path = $target_dir . $target_file_name;
        
        try {
            $contextOptions = [
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: Mozilla/5.0\r\n",
                    "timeout" => 30
                ],
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ];
            $context = stream_context_create($contextOptions);
            $fileContent = @file_get_contents($fileUrl, false, $context);
            
            if ($fileContent !== false && file_put_contents($target_file_path, $fileContent)) {
                $uploadedFiles[] = [
                    'filename' => $target_file_name,
                    'file_url' => $baseUrl . $target_file_name
                ];
            }
        } catch (Exception $e) { 
            continue; 
        }
    }
    
    if (empty($uploadedFiles)) {
        echo json_encode(['message' => 'هیچ فایلی با موفقیت دانلود نشد.', 'status' => 'error']);
    } else {
        echo json_encode([
            'message' => count($uploadedFiles) . ' فایل دانلود شد.',
            'status' => 'success',
            'files' => $uploadedFiles
        ]);
    }
    exit;
}

// ============================================================================
// لیست فایل‌ها
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    $files = glob($target_dir . '*');
    $fileList = [];
    $excludeNames = ['delete.php', 'index.php', 'upload_img.php', 'temp'];
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $filename = basename($file);
            
            // رد کردن فایل‌های سیستمی
            if (in_array($filename, $excludeNames)) continue;
            
            // رد کردن فایل‌های تکه‌ای و متادیتا
            if (strpos($filename, '_chunk_') !== false || strpos($filename, '_meta.json') !== false) continue;
            
            $fileList[] = [
                'filename' => $filename,
                'file_url' => $baseUrl . $filename,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }
    }
    
    usort($fileList, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    echo json_encode(['files' => $fileList]);
    exit;
}

// ============================================================================
// حذف فایل
// ============================================================================
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $filename = basename($_POST['filename'] ?? '');
    $filepath = $target_dir . $filename;
    $protectedFilenames = ['delete.php', 'index.php', 'upload_img.php'];
    
    if (in_array($filename, $protectedFilenames)) {
        echo json_encode(['message' => 'این فایل قابل حذف نیست.', 'status' => 'error']);
        exit;
    }
    
    if ($filename && file_exists($filepath) && is_file($filepath)) {
        if (unlink($filepath)) {
            echo json_encode(['message' => 'فایل با موفقیت حذف شد.', 'status' => 'success']);
        } else {
            echo json_encode(['message' => 'خطا در حذف فایل.', 'status' => 'error']);
        }
    } else {
        echo json_encode(['message' => 'فایل یافت نشد.', 'status' => 'error']);
    }
    exit;
}

// پاسخ پیش‌فرض
echo json_encode(['message' => 'درخواست نامعتبر.', 'status' => 'error']);
?>