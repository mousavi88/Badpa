<?php
header('Content-Type: application/json; charset=utf-8');

$versionsFile = 'versions.json';
$currentVersionCode = isset($_GET['versionCode']) ? (int)$_GET['versionCode'] : 0;

if (!file_exists($versionsFile)) {
    echo json_encode(['hasUpdate' => false]);
    exit;
}

$versions = json_decode(file_get_contents($versionsFile), true);
if (!$versions) {
    echo json_encode(['hasUpdate' => false]);
    exit;
}

// Sort by versionCode descending to get latest first
usort($versions, function($a, $b) {
    return $b['versionCode'] - $a['versionCode'];
});

$latestVersion = $versions[0];
$hasUpdate = $latestVersion['versionCode'] > $currentVersionCode;

if (!$hasUpdate) {
    echo json_encode(['hasUpdate' => false]);
    exit;
}

$isMandatory = false;
$releaseNotes = [];

foreach ($versions as $v) {
    if ($v['versionCode'] > $currentVersionCode) {
        $releaseNotes[] = "نسخه " . $v['versionName'] . ":\n" . $v['releaseNotes'];
        if (isset($v['isMandatory']) && $v['isMandatory']) {
            $isMandatory = true;
        }
    }
}

echo json_encode([
    'hasUpdate' => true,
    'latestVersionName' => $latestVersion['versionName'],
    'latestVersionCode' => $latestVersion['versionCode'],
    'isMandatory' => $isMandatory,
    'releaseNotes' => implode("\n\n", array_reverse($releaseNotes)),
    'apkUrl' => 'https://zotos.ir/apks/Badpa.apk'
]);
