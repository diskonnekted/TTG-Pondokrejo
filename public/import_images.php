<?php
// public/import_images.php

// Pastikan script hanya dijalankan jika user sudah login sebagai admin (opsional, tapi disarankan)
// require_once __DIR__ . '/../includes/auth_check.php'; // Uncomment jika sudah ada sistem auth

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Tingkatkan batas waktu eksekusi karena download gambar butuh waktu
set_time_limit(0); 

$pdo = connectDB();

echo "<h1>Starting Image Import Process...</h1>";

// 1. Ambil semua tutorial yang memiliki konten
$stmt = $pdo->query("SELECT id, title, description FROM tutorials");
$tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Folder tujuan penyimpanan gambar
$uploadDir = __DIR__ . '/uploads/imported/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// URL dasar website lama (untuk referensi relatif jika ada)
$oldBaseUrl = 'https://ttg.web.id';

$totalImagesProcessed = 0;
$totalTutorialsUpdated = 0;

foreach ($tutorials as $tutorial) {
    $content = $tutorial['description']; // Asumsi konten utama ada di kolom 'description'
    $id = $tutorial['id'];
    $title = $tutorial['title'];
    
    // Cari semua tag <img>
    preg_match_all('/<img[^>]+src="([^">]+)"/i', $content, $matches);
    
    if (!empty($matches[1])) {
        $updatedContent = $content;
        $imagesFound = false;

        foreach ($matches[1] as $src) {
            // Cek apakah URL gambar valid dan berasal dari domain lama atau eksternal
            // Jika path relatif (misal /wp-content/...), tambahkan domain lama
            $fullUrl = $src;
            if (strpos($src, 'http') !== 0) {
                $fullUrl = rtrim($oldBaseUrl, '/') . '/' . ltrim($src, '/');
            }
            
            // Filter: Hanya proses gambar dari ttg.web.id atau domain sendiri
            // (Opsional: Anda bisa menghapus filter ini jika ingin mendownload SEMUA gambar eksternal)
            // if (strpos($fullUrl, 'ttg.web.id') === false) continue;

            // Generate nama file baru yang unik
            $extension = pathinfo(parse_url($fullUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (!$extension) $extension = 'jpg'; // Default extension
            // Bersihkan query string dari extension jika ada (misal .jpg?ver=1.2)
            $extension = explode('?', $extension)[0];
            
            $newFileName = 'import_' . uniqid() . '.' . $extension;
            $localPath = $uploadDir . $newFileName;
            $publicPath = '/uploads/imported/' . $newFileName;

            // Download gambar
            echo "Downloading: $fullUrl ... ";
            
            try {
                $imageContent = @file_get_contents($fullUrl);
                
                if ($imageContent !== false) {
                    file_put_contents($localPath, $imageContent);
                    echo "<span style='color:green'>Success</span> -> Saved to $publicPath<br>";
                    
                    // Ganti URL lama dengan path lokal baru di konten
                    $updatedContent = str_replace($src, $publicPath, $updatedContent);
                    $imagesFound = true;
                    $totalImagesProcessed++;
                } else {
                    echo "<span style='color:red'>Failed (Not Found/Access Denied)</span><br>";
                }
            } catch (Exception $e) {
                echo "<span style='color:red'>Error: " . $e->getMessage() . "</span><br>";
            }
        }

        // Jika ada gambar yang berhasil di-download dan diganti, update database
        if ($imagesFound) {
            $updateStmt = $pdo->prepare("UPDATE tutorials SET description = ? WHERE id = ?");
            $updateStmt->execute([$updatedContent, $id]);
            $totalTutorialsUpdated++;
            echo "Updated content for tutorial: <strong>$title</strong><br>";
        }
    }
}

// 2. Lakukan hal yang sama untuk tabel STEPS jika konten langkah-langkah juga mengandung gambar
$stmtSteps = $pdo->query("SELECT id, tutorial_id, content FROM steps");
$steps = $stmtSteps->fetchAll(PDO::FETCH_ASSOC);

foreach ($steps as $step) {
    $content = $step['content'];
    $id = $step['id'];
    
    preg_match_all('/<img[^>]+src="([^">]+)"/i', $content, $matches);
    
    if (!empty($matches[1])) {
        $updatedContent = $content;
        $imagesFound = false;

        foreach ($matches[1] as $src) {
            $fullUrl = $src;
            if (strpos($src, 'http') !== 0) {
                $fullUrl = rtrim($oldBaseUrl, '/') . '/' . ltrim($src, '/');
            }

            $extension = pathinfo(parse_url($fullUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (!$extension) $extension = 'jpg';
            $extension = explode('?', $extension)[0];
            
            $newFileName = 'import_step_' . uniqid() . '.' . $extension;
            $localPath = $uploadDir . $newFileName;
            $publicPath = '/uploads/imported/' . $newFileName;

            echo "Downloading Step Image: $fullUrl ... ";
            
            $imageContent = @file_get_contents($fullUrl);
            if ($imageContent !== false) {
                file_put_contents($localPath, $imageContent);
                echo "<span style='color:green'>Success</span><br>";
                $updatedContent = str_replace($src, $publicPath, $updatedContent);
                $imagesFound = true;
                $totalImagesProcessed++;
            } else {
                echo "<span style='color:red'>Failed</span><br>";
            }
        }

        if ($imagesFound) {
            $updateStmt = $pdo->prepare("UPDATE steps SET content = ? WHERE id = ?");
            $updateStmt->execute([$updatedContent, $id]);
            echo "Updated step content ID: $id<br>";
        }
    }
}

echo "<hr>";
echo "<h2>Process Completed!</h2>";
echo "Total Images Downloaded: $totalImagesProcessed<br>";
echo "Total Tutorials Updated: $totalTutorialsUpdated<br>";
?>