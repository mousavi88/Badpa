<?php
include('jdf.php');

$data = json_decode(file_get_contents("email.json"), true);

$input_code = (double)$_POST['code'];
$input_email = $_POST['email'];

$relevant_maps = [];

foreach ($data as $index => $item) {
    if ($input_email == $item['email']) {
        $item['id'] = $index;
        $relevant_maps[] = $item;
    }
}

if (empty($relevant_maps)) {
    echo "No code has been sent to this email";
} else {
    $final_map = $relevant_maps[count($relevant_maps) - 1];

    if ($final_map['status'] == "waiting") {
        if ((double)$final_map['code'] == $input_code) {
            $data[$final_map['id']]['status'] = "approved";
            $data[$final_map['id']]['reason_approved'] = "The attempt was successful";
            $data[$final_map['id']]['time_approved'] = jdate("Y-m-d__H-i-s");
            echo "ok";
        } else {
            if ((double)$final_map['trying_count'] == 2) {
                $data[$final_map['id']]['status'] = "expired";
                $data[$final_map['id']]['reason_expired'] = "The number of attempts allowed has expired";
                $data[$final_map['id']]['time_expired'] = jdate("Y-m-d__H-i-s");
            }
            echo "The code is wrong";
        }

        $data[$final_map['id']]['trying_count'] += 1;
        file_put_contents("email.json", json_encode($data, JSON_PRETTY_PRINT));
    } else {
        if ($final_map['status'] == "expired") {
            echo $final_map['reason_expired'];
        } else {
            if ($final_map['status'] == "approved") {
                echo "Active code is not available for this email";
            }
        }
    }
}
?>
