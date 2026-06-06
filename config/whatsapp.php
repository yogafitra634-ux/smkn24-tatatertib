<?php

function kirimWA($nomor, $pesan)
{
    $token = 'TOKEN_FONNTE_KAMU';

    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,

        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 60,

        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,

        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token
        ],

        CURLOPT_POSTFIELDS => [
            'target'  => $nomor,
            'message' => $pesan
        ]
    ]);

    $response = curl_exec($curl);
    $error    = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    error_log("=== FONNTE DEBUG ===");
    error_log("Nomor: " . $nomor);
    error_log("HTTP Code: " . $httpCode);

    if ($error) {
        error_log("cURL Error: " . $error);
    }

    error_log("Response: " . $response);

    return [
        'success'  => empty($error),
        'httpcode' => $httpCode,
        'response' => $response,
        'error'    => $error
    ];
}
