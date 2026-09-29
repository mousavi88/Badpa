<?php
$group = $_POST['group'];
$mode = $_POST['mode'];
$modir = $_POST['modir'];
$name_group = $_POST['name_group'];
$link_prof_group = $_POST['link_prof_group'];
$pass = $_POST['pass'];

$file_path = "group_setting/{$group}.json";

if (file_exists($file_path)) {
    $old_data = json_decode(file_get_contents($file_path), true);
    $old_link_prof_group = $old_data['link_prof_group'];

    if ($old_link_prof_group !== $link_prof_group) {
        $post_data = ['target' => $old_link_prof_group];
        $ch = curl_init('https://zotos.ir/delete_pg.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }
}

$data = [
    'mode' => $mode,
    'link_prof_group' => $link_prof_group,
    'pass' => $pass,
    'name_group' => $name_group,
    'modir' => $modir
];

$json_data = json_encode($data, JSON_PRETTY_PRINT);

if (!is_dir('group_setting')) {
    mkdir('group_setting', 0777, true);
}

file_put_contents($file_path, $json_data);

echo "ok";
?>