<?php
// public/sync_images.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

set_time_limit(0);

$pdo = connectDB();

echo "<h1>Starting Image Synchronization...</h1>";

// 1. Scan semua file gambar di dalam folder uploads/
$uploadDir = __DIR__ . '/uploads/';
$images = [];

// Fungsi rekursif untuk scan folder
function scanImages($dir, &$results = []) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            scanImages($path, $results);
        } else {
            // Filter ekstensi gambar
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'])) {
                // Simpan relative path dari folder public
                // Contoh: /uploads/04/ayam.jpg
                // Kita perlu path relative terhadap public/ agar bisa diakses browser
                
                // Cari posisi 'uploads' dalam path
                $pos = strpos($path, 'uploads');
                if ($pos !== false) {
                    $relativePath = '/' . substr($path, $pos);
                    $relativePath = str_replace('\\', '/', $relativePath); // Fix Windows path
                    
                    // Key: Nama file (untuk pencarian cepat)
                    // Value: Path lengkap relatif (untuk update DB)
                    $results[$file] = $relativePath;
                }
            }
        }
    }
}

echo "Scanning uploads folder... ";
scanImages($uploadDir, $images);
echo "Found " . count($images) . " files.<br><hr>";

$totalUpdated = 0;

// 2. Loop setiap file gambar yang ditemukan
foreach ($images as $filename => $publicPath) {
    // Kita cari apakah ada artikel yang mereferensikan nama file ini
    // Tapi path-nya MUNGKIN SALAH (masih URL lama wp-content/...)
    
    // Update Tutorials
    // Cari yang mengandung nama file TAPI TIDAK mengandung path baru kita
    // (Agar tidak mengupdate yang sudah benar berkali-kali)
    
    $sql = "SELECT id, description FROM tutorials WHERE description LIKE ? AND description NOT LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$filename%", "%$publicPath%"]);
    $tutorials = $stmt->fetchAll();
    
    foreach ($tutorials as $tut) {
        // Ganti URL lama dengan Path Baru
        // Gunakan Regex agar lebih aman, ganti src="..." apapun yang mengandung filename ini
        
        $newDesc = preg_replace(
            '/(src=["\'])([^"\']*' . preg_quote($filename, '/') . ')(["\'])/i',
            '$1' . $publicPath . '$3',
            $tut['description']
        );
        
        // Juga ganti href (untuk link PDF atau link gambar)
        $newDesc = preg_replace(
            '/(href=["\'])([^"\']*' . preg_quote($filename, '/') . ')(["\'])/i',
            '$1' . $publicPath . '$3',
            $newDesc
        );
        
        if ($newDesc !== $tut['description']) {
            $upd = $pdo->prepare("UPDATE tutorials SET description = ? WHERE id = ?");
            $upd->execute([$newDesc, $tut['id']]);
            echo "Updated Tutorial ID: <strong>{$tut['id']}</strong> -> Linked <strong>$filename</strong> to <em>$publicPath</em><br>";
            $totalUpdated++;
        }
    }

    // Update Steps
    $sqlSteps = "SELECT id, content FROM steps WHERE content LIKE ? AND content NOT LIKE ?";
    $stmtSteps = $pdo->prepare($sqlSteps);
    $stmtSteps->execute(["%$filename%", "%$publicPath%"]);
    $steps = $stmtSteps->fetchAll();
    
    foreach ($steps as $step) {
        $newContent = preg_replace(
            '/(src=["\'])([^"\']*' . preg_quote($filename, '/') . ')(["\'])/i',
            '$1' . $publicPath . '$3',
            $step['content']
        );
        
        $newContent = preg_replace(
            '/(href=["\'])([^"\']*' . preg_quote($filename, '/') . ')(["\'])/i',
            '$1' . $publicPath . '$3',
            $newContent
        );
        
        if ($newContent !== $step['content']) {
            $upd = $pdo->prepare("UPDATE steps SET content = ? WHERE id = ?");
            $upd->execute([$newContent, $step['id']]);
            echo "Updated Step ID: <strong>{$step['id']}</strong> -> Linked <strong>$filename</strong> to <em>$publicPath</em><br>";
            $totalUpdated++;
        }
    }
    
    // Flush output agar tidak timeout di browser
    if ($totalUpdated % 10 == 0) {
        if (ob_get_level() > 0) {
            ob_flush();
            flush();
        }
    }
}

echo "<hr>";
echo "<h2>Synchronization Completed!</h2>";
echo "Total Database Records Updated: $totalUpdated<br>";
?>