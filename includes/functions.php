<?php
// ============================================================
// includes/functions.php
// Fungsi helper umum
// ============================================================

/**
 * Generate URL foto yang benar
 * Handles berbagai format path yang mungkin tersimpan di DB:
 * - "assets/img/uploads/foto.jpg"
 * - "img/uploads/foto.jpg"  
 * - "uploads/foto.jpg"
 * - "foto.jpg"
 * - URL penuh "https://..."
 */
function foto_url(?string $foto, int $depth = 1): string
{
    if (empty($foto)) return '';

    // Kalau sudah URL penuh
    if (str_starts_with($foto, 'http')) return $foto;

    // Bersihkan slash di awal
    $foto = ltrim($foto, '/');

    // Prefix berdasarkan kedalaman folder
    // depth=1 → dari dalam subfolder (siswa/, operator/, admin/)
    // depth=0 → dari root project
    $prefix = $depth > 0 ? '../' : '';

    // Kalau sudah ada prefix assets/
    if (str_starts_with($foto, 'assets/')) {
        return $prefix . $foto;
    }

    // Kalau hanya nama file
    if (!str_contains($foto, '/')) {
        return $prefix . 'assets/img/uploads/' . $foto;
    }

    return $prefix . $foto;
}

/**
 * Format tanggal ke Indonesia
 */
function tgl_indo(?string $iso): string
{
    if (!$iso) return '-';
    $b = ['','Januari','Februari','Maret','April','Mei','Juni',
          'Juli','Agustus','September','Oktober','November','Desember'];
    [$y, $m, $d] = explode('-', substr($iso, 0, 10));
    return (int)$d . ' ' . $b[(int)$m] . ' ' . $y;
}

/**
 * Format tanggal pendek
 */
function tgl_pendek(?string $iso): string
{
    if (!$iso) return '-';
    $b = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    [$y, $m, $d] = explode('-', substr($iso, 0, 10));
    return (int)$d . ' ' . $b[(int)$m] . ' ' . $y;
}

/**
 * SVG avatar placeholder
 */
function avatar_placeholder(int $size = 20): string
{
    return '<svg width="'.$size.'" height="'.$size.'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>';
}
