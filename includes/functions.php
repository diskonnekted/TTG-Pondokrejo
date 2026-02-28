<?php
// includes/functions.php

// Sanitize Input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate Slug
function createSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

// Redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Require Login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/admin/login.php');
    }
}

// Get all categories
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll();
}

// Upload File (Image/PDF)
function uploadFile($file, $targetDir = '/uploads/') {
    // Check if file is uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', // Images
        'application/pdf' // PDF
    ];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false; // Invalid file type
    }

    // Ensure upload directory exists
    $absoluteTargetDir = __DIR__ . '/../public' . $targetDir;
    if (!is_dir($absoluteTargetDir)) {
        mkdir($absoluteTargetDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('file_') . '.' . $extension;
    $targetFile = $absoluteTargetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $targetDir . $filename;
    }

    return false;
}

// Upload Image (Legacy Wrapper)
function uploadImage($file, $targetDir = '/uploads/') {
    return uploadFile($file, $targetDir);
}

// Get Category Heroicon (SVG String)
function getCategoryHeroicon($categoryName) {
    $name = strtolower(trim($categoryName));
    
    // Mapping Kategori ke SVG
    // Berdasarkan: Berita, Daur Ulang, Energi, Kerajinan, Pengairan, Pengolahan, Pengolahan Limbah, Perikanan, Pertanian, Peternakan, Ramah Lingkungan, Tanaman Obat
    
    $icons = [
        'berita' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" /></svg>', // Newspaper
        'daur ulang' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>', // Recycle (Arrow Path)
        'energi' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>', // Bolt
        'kerajinan' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.418a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" /></svg>', // Paint Brush / Craft
        'pengairan' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" /></svg>', // Sun / Water Drop Concept (Using Sparkles for now or maybe Cloud Rain) -> Let's use Cloud Rain
        'pengolahan' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>', // Adjustments (Processing)
        'limbah' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>', // Trash
        'perikanan' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" /></svg>', // Bug/Animal (Fish like) -> Using Bug for now as Fish icon not in standard Heroicons Outline set, but looks organic. Or Lifebuoy. Let's use Lifebuoy for now as closest aquatic symbol.
        'pertanian' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>', // Sun (Agriculture needs sun)
        'peternakan' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>', // Heart (Care for animals) - or maybe Home for Barn.
        'ramah lingkungan' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12.75 3.03v.568c0 .334.148.65.405.864l1.068.89c.442.369.535 1.01.216 1.49l-.51.766a2.25 2.25 0 01-1.161.886l-.143.048a1.107 1.107 0 00-.57 1.664c.369.555.169 1.307-.279 1.625l-1.202.866a1.99 1.99 0 01-2.285-.013l-.226-.168a2.992 2.992 0 00-3.596 0l-.346.258a1.986 1.986 0 00-.638 2.372l.492.983a1.118 1.118 0 001.378.497l.112-.042a2.25 2.25 0 012.87.892l.81 1.619c.28.56.963.784 1.516.496l.666-.347c.553-.288 1.23-.064 1.51.496l.81 1.62a2.25 2.25 0 01-.89 2.868l-.112.043a1.118 1.118 0 00-.497 1.379l.983.491a1.986 1.986 0 002.372-.638l.258-.346a2.992 2.992 0 000-3.596l-.168-.226a1.99 1.99 0 01.013-2.285l.866-1.202c.318-.448 1.07-.648 1.625-.279l1.664.57a1.108 1.108 0 001.664-.57l.048-.142a2.25 2.25 0 01.886-1.161l.766-.51c.48-.319.572-.968.203-1.41l-.89-1.068a1.125 1.125 0 00-.864-.405H19.5a2.25 2.25 0 00-2.25-2.25h-4.5z" /></svg>', // Globe
        'obat' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>', // Beaker (Medicine) -> Exclamation Triangle as fallback or Beaker
    ];

    foreach ($icons as $key => $svg) {
        if (strpos($name, $key) !== false) {
            return $svg;
        }
    }

    // Default: Cube
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>';
}

// Get Category Icon (Legacy Wrapper - Now returns URL to default image if needed or we change logic)
function getCategoryIcon($categoryName) {
    // Return default image path for fallback if old code relies on <img src>
    // But ideally we should switch to getCategoryHeroicon
    return BASE_URL . '/assets/images/default-icon.png';
}

// Format Date (Indonesia)
function formatDateIndo($date) {
    $months = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $timestamp = strtotime($date);
    $formatted = date('d F Y', $timestamp);
    foreach ($months as $en => $id) {
        $formatted = str_replace($en, $id, $formatted);
    }
    return $formatted;
}

// Time Ago Function (e.g. "2 jam yang lalu")
function timeAgo($timestamp) {
    // If input is string, convert to timestamp
    if (!is_numeric($timestamp)) {
        $timestamp = strtotime($timestamp);
    }
    
    $time_difference = time() - $timestamp;

    if ($time_difference < 1) { return 'baru saja'; }

    $condition = array( 
        12 * 30 * 24 * 60 * 60 => 'tahun',
        30 * 24 * 60 * 60      => 'bulan',
        24 * 60 * 60           => 'hari',
        60 * 60                => 'jam',
        60                     => 'menit',
        1                      => 'detik'
    );

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;

        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ' yang lalu';
        }
    }
}
?>
