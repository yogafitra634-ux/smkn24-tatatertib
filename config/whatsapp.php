<?php

function kirimWA($nomor, $pesan)
{
    $token = 'TOKEN_KAMU';

    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token
        ],
        CURLOPT_POSTFIELDS => [
            'target'  => $nomor,
            'message' => $pesan
        ]
    ]);

    $response = curl_exec($curl);

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error    = curl_error($curl);

    curl_close($curl);

    file_put_contents(
        __DIR__ . '/wa-debug.log',
        date('Y-m-d H:i:s') .
        "\nHTTP: " . $httpCode .
        "\nERROR: " . $error .
        "\nRESP: " . $response .
        "\n----------------\n",
        FILE_APPEND
    );

    return [
        'http' => $httpCode,
        'error' => $error,
        'response' => $response
    ];
}
