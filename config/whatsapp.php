<?php

function kirimWA($nomor, $pesan)
{
    $token = 'GnWUa6uZedzxHLPHGmGi';

    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token
        ],
        CURLOPT_POSTFIELDS => [
            'target'  => $nomor,
            'message' => $pesan
        ]
    ]);

   $response = curl_exec($curl);

if ($response === false) {
    error_log('Fonnte CURL Error: ' . curl_error($curl));
}

    curl_close($curl);

    return $response;
}
