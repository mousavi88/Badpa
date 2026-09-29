<?php

$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';

$name = strtoupper($name);

$groupFile = 'group.json';

if ($mode == 'group') {
    if (file_exists($groupFile)) {
        $data = json_decode(file_get_contents($groupFile), true);
        if (is_array($data)) {
            $data[] = $name;
        } else {
            $data = [$name];
        }
        file_put_contents($groupFile, json_encode($data, JSON_PRETTY_PRINT));
        echo "ok";
    } else {
        echo "فایل group.json پیدا نشد.";
    }
} elseif ($mode == 'karbari') {
    $karbariFile = 'name_karbari.json';
    if (file_exists($karbariFile)) {
        $data = json_decode(file_get_contents($karbariFile), true);
        if (is_array($data)) {
            $data[] = $name;
        } else {
            $data = [$name];
        }
        file_put_contents($karbariFile, json_encode($data, JSON_PRETTY_PRINT));
        echo "ok";
    } else {
        echo "فایل name_karbari.json پیدا نشد.";
    }
} else {
    echo "مقدار mode معتبر نیست.";
}

?>
