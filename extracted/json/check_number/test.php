<?php
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.sms.ir/v1/send/626557498', // MessageID واقعی
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'X-API-KEY: 50AK2p6ESXCulLmsugd8yUIjSfExX54R3CcfawxYmfm7mY0q' // کلید خودتان
    ),
));
$response = curl_exec($curl);
curl_close($curl);
echo $response;
      ?>