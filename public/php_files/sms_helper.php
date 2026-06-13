<?php
function send_sms($number, $message) {
    $apiKey = 'YOUR_SEMAPHORE_API_KEY';
    $senderName = 'ZEPPELIN';

    if (empty($number) || empty($message)) {
        return false;
    }

    $ch = curl_init();

    $parameters = [
        'apikey' => $apiKey,
        'number' => $number,
        'message' => $message,
        'sendername' => $senderName
    ];

    curl_setopt($ch, CURLOPT_URL, 'https://semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('SMS Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    error_log('SMS Response: ' . $output);

    return true;
}
?>