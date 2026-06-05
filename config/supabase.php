<?php
// ============================================================
// config/supabase.php
// Konfigurasi koneksi ke Supabase
// ============================================================

// Set timezone WIB
date_default_timezone_set('Asia/Jakarta');

define('SUPABASE_URL', 'https://vonqdfkppelfwpbudyma.supabase.co'); // Ganti dengan URL project kamu
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZvbnFkZmtwcGVsZndwYnVkeW1hIiwicm9sZSI6ImFub24iLCJpYXQiOjE3Nzk5NDE3MDUsImV4cCI6MjA5NTUxNzcwNX0.-v9vMl8CK6X1yYbijofNdQK5qRHdVJbYyP2RQny0a60'); // Ganti dengan anon key kamu

/**
 * Helper utama untuk request ke Supabase REST API
 *
 * @param string      $endpoint  Contoh: "users?role=eq.siswa"
 * @param string      $method    GET | POST | PATCH | DELETE
 * @param array|null  $data      Body request (untuk POST/PATCH)
 * @param array       $extra     Header tambahan, contoh: ['Prefer: resolution=merge-duplicates']
 * @return array                 ['status' => int, 'data' => array]
 */
function supabase(string $endpoint, string $method = 'GET', array $data = null, array $extra = []): array
{
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;

    $headers = array_merge([
        'apikey: '                . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation',
    ], $extra);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_SSL_VERIFYPEER => false,   // Fix SSL XAMPP Windows
        CURLOPT_SSL_VERIFYHOST => false,   // Fix SSL XAMPP Windows
        CURLOPT_TIMEOUT        => 15,      // Timeout 15 detik
        CURLOPT_CONNECTTIMEOUT => 10,      // Connect timeout 10 detik
    ]);

    if ($data !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['status' => 0, 'data' => null, 'error' => $error];
    }

    $decoded = json_decode($response, true);

    return [
        'status' => $httpCode,
        'data'   => $decoded,
    ];
}

/**
 * Shortcut: ambil satu baris pertama dari response
 */
function supabase_first(string $endpoint): ?array
{
    $result = supabase($endpoint . '&limit=1');
    if ($result['status'] === 200 && !empty($result['data'])) {
        return $result['data'][0];
    }
    return null;
}
