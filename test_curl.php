<?php
$ch = curl_init('https://api.deepseek.com/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => 'deepseek-chat', 'messages' => []]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer sk-6dfc93617fc949c3aa8f60f59026c6e3'
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    echo 'Response: ' . $response;
}
curl_close($ch);
