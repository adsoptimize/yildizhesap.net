<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api-pay.cryptomus.com/v1/payment/info');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: */*',
    'accept-language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
    'authorization: Bearer 2381504|yMj6simE18PNwJL9mB8vywO3EjWWtLuubEQAcN9E',
    'content-type: application/json',
    'language: tr',
    'origin: https://pay.cryptomus.com',
    'priority: u=1, i',
    'referer: https://pay.cryptomus.com/',
    'sec-ch-ua: "Not;A=Brand";v="99", "Google Chrome";v="139", "Chromium";v="139"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-site',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"search":"","uuid":"6c8d8ade-b048-4815-8234-154bc70ed232"}');

$response = curl_exec($ch);

curl_close($ch);

//echo $response;

 $b = json_decode($response, true);
 
echo $b["result"]["payment"]["is_final"];