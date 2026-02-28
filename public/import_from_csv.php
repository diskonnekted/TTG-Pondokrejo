<?php
// public/import_from_csv.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

set_time_limit(0);

$pdo = connectDB();

echo "<h1>Starting Image Import from CSV...</h1>";

$csvFile = __DIR__ . '/uploads/media-url.csv';

if (!file_exists($csvFile)) {
    die("Error: File uploads/media-url.csv not found.");
}

// Folder tujuan
$uploadDir = __DIR__ . '/uploads/imported/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Baca CSV
$handle = fopen($csvFile, "r");
$header = fgetcsv($handle); // Skip header row

$totalProcessed = 0;
$totalDownloaded = 0;
$totalUpdated = 0;

// Cari kolom URL dan Filename
$urlIndex = -1;
$filenameIndex = -1;

foreach ($header as $index => $colName) {
    if (strcasecmp(trim($colName), 'URL') === 0) $urlIndex = $index;
    if (strcasecmp(trim($colName), 'File Name') === 0) $filenameIndex = $index;
}

if ($urlIndex === -1 || $filenameIndex === -1) {
    die("Error: CSV must have 'URL' and 'File Name' columns.");
}

while (($data = fgetcsv($handle)) !== FALSE) {
    $url = trim($data[$urlIndex]);
    $originalFilename = trim($data[$filenameIndex]);
    
    if (empty($url)) continue;

    // Bersihkan nama file agar aman
    $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalFilename);
    
    // Pastikan nama file unik jika perlu, tapi sebaiknya gunakan nama asli agar mudah dicari di konten
    // Kita tambahkan prefix 'csv_' untuk membedakan
    $localFilename = 'csv_' . $safeFilename;
    $localPath = $uploadDir . $localFilename;
    $publicPath = '/uploads/imported/' . $localFilename;

    echo "<div style='margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 2px;'>";
    echo "Processing: <strong>$originalFilename</strong><br>";

    // 1. Download Gambar
    $downloaded = false;
    if (file_exists($localPath)) {
        echo "<span style='color:orange'>File exists (Skipped download)</span>: $localFilename<br>";
        $downloaded = true;
    } else {
        echo "Downloading from: $url ... ";
        $content = @file_get_contents($url);
        if ($content !== false) {
            file_put_contents($localPath, $content);
            echo "<span style='color:green'>Success</span><br>";
            $downloaded = true;
            $totalDownloaded++;
        } else {
            echo "<span style='color:red'>Failed to download</span><br>";
        }
    }

    // 2. Update Database (Replace URL lama atau filename lama dengan path baru)
    if ($downloaded) {
        // Kita akan mencari string nama file asli di dalam konten (description di tutorials, content di steps)
        // Dan menggantinya dengan path baru kita.
        
        // Pola pencarian: Mencari nama file asli di dalam atribut src
        // Karena di database mungkin tersimpan sebagai URL lengkap lama atau path relatif
        // Kita cari yang mengandung nama file tersebut.
        
        $searchPattern = $originalFilename;
        
        // Update Tutorials
        $sql = "UPDATE tutorials SET description = REPLACE(description, ?, ?) WHERE description LIKE ?";
        // Kita ganti URL lama (jika ada full URL) dengan path baru
        // TAPI: Karena URL lama mungkin variatif, kita coba replace filename-nya saja jika memungkinkan
        // Atau lebih aman: Replace Full URL dari CSV dengan Public Path baru
        
        // Strategi: Replace Full URL dari CSV -> Public Path Baru
        $stmt = $pdo->prepare("UPDATE tutorials SET description = REPLACE(description, ?, ?) WHERE description LIKE ?");
        $stmt->execute([$url, $publicPath, "%$url%"]);
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            echo "Updated <strong>$count</strong> tutorial(s) (Full URL match).<br>";
            $totalUpdated += $count;
        } else {
            // Coba replace berdasarkan nama file saja (beresiko tapi mungkin diperlukan jika URL di DB beda dikit)
            // Hati-hati: hanya replace jika ada di dalam src="..."
            // SQLite tidak punya REGEX replace bawaan yang mudah, jadi kita gunakan REPLACE string biasa
            // Asumsi: di database tersimpan sebagai ".../filename.jpg"
            
            // Kita coba cari variasi common path WP
            $wpPath = 'wp-content/uploads/';
            
            // Query untuk mencari apakah ada string nama file ini di deskripsi
            $checkStmt = $pdo->prepare("SELECT id, description FROM tutorials WHERE description LIKE ?");
            $checkStmt->execute(["%$originalFilename%"]);
            $tutorials = $checkStmt->fetchAll();
            
            foreach ($tutorials as $tut) {
                // Gunakan PHP untuk replace agar lebih aman dan fleksibel
                // Ganti semua kemunculan yang mengandung nama file ini di dalam src tag
                $newDesc = preg_replace(
                    '/(src=["\'])([^"\']*' . preg_quote($originalFilename, '/') . ')(["\'])/i',
                    '$1' . $publicPath . '$3',
                    $tut['description']
                );
                
                if ($newDesc !== $tut['description']) {
                    $upd = $pdo->prepare("UPDATE tutorials SET description = ? WHERE id = ?");
                    $upd->execute([$newDesc, $tut['id']]);
                    echo "Updated tutorial ID: {$tut['id']} (Filename match)<br>";
                    $totalUpdated++;
                }
            }
        }

        // Update Steps (Lakukan hal yang sama untuk tabel steps)
        $stmt = $pdo->prepare("UPDATE steps SET content = REPLACE(content, ?, ?) WHERE content LIKE ?");
        $stmt->execute([$url, $publicPath, "%$url%"]);
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            echo "Updated <strong>$count</strong> step(s) (Full URL match).<br>";
            $totalUpdated += $count;
        } else {
             $checkStmt = $pdo->prepare("SELECT id, content FROM steps WHERE content LIKE ?");
            $checkStmt->execute(["%$originalFilename%"]);
            $steps = $checkStmt->fetchAll();
            
            foreach ($steps as $step) {
                $newContent = preg_replace(
                    '/(src=["\'])([^"\']*' . preg_quote($originalFilename, '/') . ')(["\'])/i',
                    '$1' . $publicPath . '$3',
                    $step['content']
                );
                
                if ($newContent !== $step['content']) {
                    $upd = $pdo->prepare("UPDATE steps SET content = ? WHERE id = ?");
                    $upd->execute([$newContent, $step['id']]);
                    echo "Updated step ID: {$step['id']} (Filename match)<br>";
                    $totalUpdated++;
                }
            }
        }
    }
    
    echo "</div>";
    $totalProcessed++;
    
    // Flush output buffer
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

fclose($handle);

echo "<hr>";
echo "<h2>Process Completed!</h2>";
echo "Total Rows Processed: $totalProcessed<br>";
echo "Total Downloaded: $totalDownloaded<br>";
echo "Total Database Updates: $totalUpdated<br>";
?>