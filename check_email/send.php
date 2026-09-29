<?php
require_once 'jdf.php';

function send_email($email, &$data) {
    $code = rand(100000, 999999);
    $timeSend = jdate('Y-m-d__H-i-s');
    $new_email = [
        "email" => $email,
        "time_send" => $timeSend,
        "status" => "waiting",
        "code" => $code,
        "trying_count" => 0
    ];
    
    $subject = "کد تایید";

    $message = "سلام\n کد اعتبار سنجی ایمیل شما به این شرح است:\n" . $code;
    $header = "From:noreply@zotos.ir \r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-type: text/html\r\n";
    
    $res = mail ($email,$subject,$message,$header);
    
    if( $res == true ) {
       echo "email sent";
    } else {
       echo "error";
    }
    
    $data[] = $new_email;
    file_put_contents('email.json', json_encode($data, JSON_PRETTY_PRINT));
}

function process_email($email) {
    $data = json_decode(file_get_contents('email.json'), true);
    $revolont_maps = [];
    foreach ($data as $item) {
        if ($item['email'] === $email) {
            $revolont_maps[] = $item;
        }
    }

    if (empty($revolont_maps)) {
        send_email($email, $data);
    } else {
        $final_map = end($revolont_maps);
        if ($final_map['status'] === 'waiting') {
            echo "There is an active email\n";
        } else {
            send_email($email, $data);
        }
    }
}

$email = $_POST['email'];
if (empty($email)) {
    echo "Fill in the email parameter.";
} else {
    process_email($email);
}
?>