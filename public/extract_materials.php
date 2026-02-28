<?php
// public/extract_materials.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = connectDB();

echo "<h1>Starting Material Extraction...</h1>";

// 1. Ambil semua tutorial yang memiliki kata kunci "BAHAN DAN PERALATAN" atau mirip
$keywords = [
    'BAHAN DAN PERALATAN',
    'ALAT DAN BAHAN',
    'BAHAN-BAHAN',
    'PERALATAN',
    'BAHAN BAKU',
    'KEBUTUHAN BAHAN'
];

// Buat query LIKE OR
$whereClause = [];
$params = [];
foreach ($keywords as $k) {
    $whereClause[] = "description LIKE ?";
    $params[] = "%$k%";
}
$sql = "SELECT id, title, description FROM tutorials WHERE " . implode(' OR ', $whereClause);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($tutorials) . " potential tutorials.<br><hr>";

$totalUpdated = 0;

foreach ($tutorials as $tut) {
    $desc = $tut['description'];
    $id = $tut['id'];
    
    // Cari posisi kata kunci
    $startPos = false;
    $foundKeyword = '';
    
    foreach ($keywords as $k) {
        $pos = stripos($desc, $k);
        if ($pos !== false) {
            // Pastikan ini bukan di tengah kalimat (misal dengan cek newline sebelumnya atau tag <p>)
            // Tapi untuk amannya kita ambil saja dulu
            $startPos = $pos;
            $foundKeyword = $k;
            break; 
        }
    }
    
    if ($startPos === false) continue;

    // Cari akhir dari bagian bahan (biasanya sampai ketemu Judul Bagian berikutnya yang huruf besar semua atau tag tertentu)
    // Kata kunci penutup umum:
    $endKeywords = ['PEMBUATAN', 'CARA MEMBUAT', 'LANGKAH KERJA', 'PROSES PEMBUATAN', 'CARA KERJA', 'URAIAN SINGKAT', 'GAMBAR', 'PENUTUP'];
    
    $endPos = strlen($desc); // Default sampai akhir
    $foundEndKeyword = '';
    
    // Cari keyword penutup yang posisinya SETELAH startPos
    $nearestEndPos = strlen($desc);
    
    foreach ($endKeywords as $ek) {
        $pos = stripos($desc, $ek, $startPos + strlen($foundKeyword));
        if ($pos !== false && $pos < $nearestEndPos) {
            $nearestEndPos = $pos;
            $foundEndKeyword = $ek;
        }
    }
    
    $endPos = $nearestEndPos;
    
    // Ekstrak teks bahan
    // Ambil dari akhir keyword pembuka sampai awal keyword penutup
    $rawMaterials = substr($desc, $startPos + strlen($foundKeyword), $endPos - ($startPos + strlen($foundKeyword)));
    
    // Bersihkan HTML tags
    $cleanMaterials = strip_tags($rawMaterials);
    // Ubah &nbsp; jadi spasi
    $cleanMaterials = str_replace('&nbsp;', ' ', $cleanMaterials);
    
    // Pecah menjadi baris-baris
    $lines = preg_split("/\r\n|\n|\r/", $cleanMaterials);
    
    $validMaterials = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Filter baris kosong atau terlalu pendek atau cuma tanda baca
        if (strlen($line) < 2) continue;
        if ($line == '-') continue;
        
        // Bersihkan bullet points manual jika ada (-, *, angka)
        $line = ltrim($line, "-*•1234567890. ");
        
        if (!empty($line)) {
            $validMaterials[] = $line;
        }
    }
    
    if (count($validMaterials) > 0) {
        echo "<div style='background:#f9f9f9; border:1px solid #ddd; padding:10px; margin-bottom:10px;'>";
        echo "<strong>Tutorial #$id: {$tut['title']}</strong><br>";
        echo "Keyword: <em>$foundKeyword</em> -> End: <em>" . ($foundEndKeyword ?: 'End of Text') . "</em><br>";
        echo "Found " . count($validMaterials) . " items:<br>";
        echo "<ul>";
        
        // Insert ke tabel materials
        // Hapus dulu material lama untuk tutorial ini agar tidak duplikat jika script dijalankan ulang
        $del = $pdo->prepare("DELETE FROM materials WHERE tutorial_id = ?");
        $del->execute([$id]);
        
        foreach ($validMaterials as $mat) {
            echo "<li>$mat</li>";
            // Insert
            $ins = $pdo->prepare("INSERT INTO materials (tutorial_id, name, quantity) VALUES (?, ?, ?)");
            // Kita anggap quantity kosong dulu, atau mau coba ekstrak angka?
            // Sederhana: Masukkan semua ke name, quantity biarkan kosong atau '-'.
            $ins->execute([$id, $mat, '-']);
        }
        echo "</ul>";
        
        // Hapus bagian teks tersebut dari deskripsi agar tidak duplikat
        // Hati-hati: Kita hapus mulai dari startPos sampai endPos
        // Tapi jangan hapus Keyword Penutupnya
        
        // Potong string
        $newDesc = substr_replace($desc, '', $startPos, $endPos - $startPos);
        
        // Bersihkan sisa-sisa tag HTML kosong yang mungkin tertinggal (seperti <ul></ul> kosong atau <br><br>)
        // $newDesc = str_replace(['<p></p>', '<ul></ul>', '<br><br><br>'], '', $newDesc);
        
        $upd = $pdo->prepare("UPDATE tutorials SET description = ? WHERE id = ?");
        $upd->execute([$newDesc, $id]);
        
        echo "<span style='color:green'>Database Updated! Description trimmed.</span>";
        echo "</div>";
        $totalUpdated++;
    }
}

echo "<hr>Done. Updated $totalUpdated tutorials.";
?>