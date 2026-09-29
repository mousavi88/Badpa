<?php
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
$target = isset($_GET['target']) ? $_GET['target'] : '';

if (!$mode || !$target) {
    echo "Missing parameters!";
    exit;
}

$target = strtoupper($target);

$file = '';
if ($mode == 'group') {
    $file = 'group.json';
} elseif ($mode == 'karbari') {
    $file = 'name_karbari.json';
} else {
    echo "Invalid mode!";
    exit;
}

if (!file_exists($file)) {
    echo "File not found!";
    exit;
}

$json_data = file_get_contents($file);
$data = json_decode($json_data, true);

if (in_array($target, $data)) {
    echo "not";
} else {
    echo "ok";
}
?>
