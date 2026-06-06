<?php

function kirimWA($nomor, $pesan)
{
    $token = 'GnWUa6uZedzxHLPHGmGi';

    // Bersihkan nomor
    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    // Ubah 08xxxx menjadi 628xxxx
    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,

        // Timeout supaya website tidak loading selamanya
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,

        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token
        ],

        CURLOPT_POSTFIELDS => [
            'target'  => $nomor,
            'message' => $pesan
        ]
    ]);

    $response = curl_exec($curl);

    // Tangkap error CURL
    if ($response === false) {

        $error = curl_error($curl);

        error_log(
            '[FONNTE ERROR] ' .
            date('Y-m-d H:i:s') .
            ' | Nomor: ' . $nomor .
            ' | Error: ' . $error
        );

        curl_close($curl);

        return [
            'success' => false,
            'error'   => $error
        ];
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    error_log(
        '[FONNTE RESPONSE] ' .
        date('Y-m-d H:i:s') .
        ' | HTTP: ' . $httpCode .
        ' | Response: ' . $response
    );

    return [
        'success'  => ($httpCode >= 200 && $httpCode < 300),
        'httpCode' => $httpCode,
        'response' => $response
    ];
}
