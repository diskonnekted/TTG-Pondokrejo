<?php
// public/tutorial.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$pdo = connectDB();
$id = (int)$_GET['id'];

// Fetch Tutorial
$stmt = $pdo->prepare("SELECT t.*, c.name as category_name, u.username as author_name 
                       FROM tutorials t 
                       JOIN categories c ON t.category_id = c.id 
                       LEFT JOIN users u ON t.author_id = u.id 
                       WHERE t.id = ?");
$stmt->execute([$id]);
$tutorial = $stmt->fetch();

if (!$tutorial) {
    die("Tutorial not found.");
}

// Fetch Steps
$stmt = $pdo->prepare("SELECT * FROM steps WHERE tutorial_id = ? ORDER BY step_number ASC");
$stmt->execute([$id]);
$steps = $stmt->fetchAll();

// Fetch Materials
$stmt = $pdo->prepare("SELECT * FROM materials WHERE tutorial_id = ?");
$stmt->execute([$id]);
$materials = $stmt->fetchAll();

$pageTitle = $tutorial['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="{ 
    dataSaver: false, 
    completedSteps: JSON.parse(localStorage.getItem('completed_steps_<?php echo $id; ?>') || '[]'),
    toggleStep(stepId) {
        if (this.completedSteps.includes(stepId)) {
            this.completedSteps = this.completedSteps.filter(id => id !== stepId);
        } else {
            this.completedSteps.push(stepId);
        }
        localStorage.setItem('completed_steps_<?php echo $id; ?>', JSON.stringify(this.completedSteps));
    },
    isCompleted(stepId) {
        return this.completedSteps.includes(stepId);
    }
}" class="pb-24">

    <!-- Hero Image -->
    <div class="relative h-64 w-full bg-gray-200">
        <template x-if="!dataSaver">
            <img src="<?php echo BASE_URL . ($tutorial['image_path'] ?? '/assets/images/default.jpg'); ?>" 
                 alt="<?php echo $tutorial['title']; ?>" 
                 class="w-full h-full object-cover">
        </template>
        <template x-if="dataSaver">
             <div class="w-full h-full flex items-center justify-center bg-gray-300 text-gray-500 flex-col">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 1.664M21 21l-1.5-1.5m-5.485-1.242L12.072 15.5a.75.75 0 01-1.072 0l-.108-.108a.75.75 0 010-1.072l.966-.966m-1.429-1.429A4.483 4.483 0 0113.5 10.5c.966 0 1.861.272 2.635.743m-5.694-5.694a4.483 4.483 0 012.635-.743c2.485 0 4.5 2.015 4.5 4.5 0 .652-.167 1.26-.463 1.795M9.196 9.196l-.966.966a.75.75 0 01-1.072 0l-.108-.108a.75.75 0 010-1.072l.966-.966m.535-3.535a9.04 9.04 0 012.955-.502c4.97 0 9 4.03 9 9 0 1.66-.448 3.21-1.233 4.552m-14.534 0A8.995 8.995 0 013 12c0-4.97 4.03-9 9-9" />
                </svg>
                <span>Mode Hemat Data Aktif</span>
            </div>
        </template>
        
        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur rounded-full px-3 py-1 shadow-sm text-xs font-semibold">
             <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" x-model="dataSaver" class="form-checkbox text-primary rounded focus:ring-primary">
                <span>Hemat Data</span>
            </label>
        </div>
        
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 pt-20">
            <span class="bg-primary text-white text-xs px-2 py-1 rounded mb-2 inline-block">
                <?php echo $tutorial['category_name']; ?>
            </span>
            <h1 class="text-2xl font-bold text-white leading-tight mb-2">
                <?php echo $tutorial['title']; ?>
            </h1>
            <div class="flex text-white/80 text-sm gap-4">
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <?php echo $tutorial['author_name'] ?? 'Admin'; ?>
                </span>
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php echo $tutorial['duration']; ?> min
                </span>
            </div>
        </div>
    </div>

    <!-- Content Container -->
    <div class="px-4 py-6 max-w-md mx-auto">
        
        <!-- Video Embed -->
        <?php if(!empty($tutorial['video_url'])): ?>
        <div class="mb-6 rounded-xl overflow-hidden shadow-sm">
            <template x-if="!dataSaver">
                <div class="aspect-w-16 aspect-h-9">
                    <?php 
                        // Extract Youtube ID
                        $videoId = '';
                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $tutorial['video_url'], $matches)) {
                            $videoId = $matches[1];
                        }
                    ?>
                    <?php if($videoId): ?>
                        <iframe src="https://www.youtube.com/embed/<?php echo $videoId; ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-64"></iframe>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($tutorial['video_url']); ?>" target="_blank" class="block bg-gray-100 p-8 text-center text-primary hover:bg-gray-200 group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-2 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" />
                            </svg>
                            Tonton Video Tutorial
                        </a>
                    <?php endif; ?>
                </div>
            </template>
            <template x-if="dataSaver">
                <div class="bg-gray-100 p-4 text-center rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mx-auto mb-2 text-gray-400">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 10.5l-3 3m0 0l3 3m-3-3h7.5" />
                    </svg>
                    <p class="text-sm text-gray-500">Video disembunyikan (Hemat Data)</p>
                    <a href="<?php echo htmlspecialchars($tutorial['video_url']); ?>" target="_blank" class="text-primary text-sm font-semibold mt-2 inline-block">Buka di YouTube</a>
                </div>
            </template>
        </div>
        <?php endif; ?>

        <!-- Description -->
        <div class="mb-8 text-gray-700 leading-loose prose prose-green max-w-none">
            <?php 
            // Membersihkan tag HTML yang tidak diinginkan tapi membiarkan formatting dasar
            // Dan mengubah newlines menjadi paragraf yang rapi, bukan sekadar <br>
            $content = $tutorial['description'];
            
            // Jika konten sudah mengandung tag HTML (seperti <p>), jangan gunakan nl2br
            if (strpos($content, '<p>') !== false || strpos($content, '<br>') !== false) {
                // Hapus multiple br/p kosong yang berlebihan
                $content = preg_replace('/(<br\s*\/?>\s*){2,}/', '<br><br>', $content);
                $content = preg_replace('/(<p>\s*<\/p>\s*){2,}/', '<p></p>', $content);
                echo $content;
            } else {
                // Jika plain text, bungkus dalam paragraf
                echo '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
            }
            ?>
        </div>

        <!-- PDF Download -->
        <?php if(!empty($tutorial['pdf_path'])): ?>
        <a href="<?php echo BASE_URL . $tutorial['pdf_path']; ?>" target="_blank" class="flex items-center gap-4 bg-red-50 border border-red-100 p-4 rounded-xl mb-8 hover:bg-red-100 transition group">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center text-red-500 group-hover:bg-white group-hover:scale-110 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-gray-800">Download Panduan PDF</h4>
                <p class="text-xs text-gray-500">Simpan untuk dibaca nanti</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 ml-auto text-gray-400 group-hover:text-red-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
        </a>
        <?php endif; ?>

        <!-- Materials & Tools -->
        <div class="bg-blue-50 rounded-xl p-5 mb-8 border border-blue-100">
            <h3 class="font-bold text-blue-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                </svg>
                Alat & Bahan
            </h3>
            <ul class="space-y-3">
                <?php foreach($materials as $mat): ?>
                <li class="flex items-start gap-3 text-sm text-gray-700 border-b border-blue-100 pb-2 last:border-0 last:pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" style="width: 20px; height: 20px;">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-semibold block"><?php echo $mat['name']; ?> (<?php echo $mat['quantity']; ?>)</span>
                        <?php if($mat['local_source']): ?>
                            <span class="text-xs text-blue-600 block mt-0.5 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1" style="width: 12px; height: 12px;">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                Tersedia di: <?php echo $mat['local_source']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Steps -->
        <div class="space-y-8">
            <h3 class="font-bold text-xl text-gray-800 mb-6">Langkah-langkah</h3>
            
            <?php foreach($steps as $index => $step): ?>
            <div class="relative pl-8 pb-8 border-l-2 border-gray-200 last:border-0">
                <!-- Step Number Bubble -->
                <div class="absolute -left-4 top-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-white transition-colors duration-300"
                     :class="isCompleted(<?php echo $step['id']; ?>) ? 'bg-primary' : 'bg-gray-400'">
                    <?php echo $step['step_number']; ?>
                </div>

                <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 transition-all duration-300"
                     :class="isCompleted(<?php echo $step['id']; ?>) ? 'opacity-60 grayscale' : ''">
                    
                    <h4 class="font-bold text-lg mb-2 text-gray-800"><?php echo $step['title']; ?></h4>
                    
                    <?php if($step['image_path']): ?>
                    <div class="mb-3 rounded-lg overflow-hidden h-48 bg-gray-100">
                         <template x-if="!dataSaver">
                            <img src="<?php echo BASE_URL . $step['image_path']; ?>" class="w-full h-full object-cover">
                        </template>
                        <template x-if="dataSaver">
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 1.664M21 21l-1.5-1.5m-5.485-1.242L12.072 15.5a.75.75 0 01-1.072 0l-.108-.108a.75.75 0 010-1.072l.966-.966m-1.429-1.429A4.483 4.483 0 0113.5 10.5c.966 0 1.861.272 2.635.743m-5.694-5.694a4.483 4.483 0 012.635-.743c2.485 0 4.5 2.015 4.5 4.5 0 .652-.167 1.26-.463 1.795M9.196 9.196l-.966.966a.75.75 0 01-1.072 0l-.108-.108a.75.75 0 010-1.072l.966-.966m.535-3.535a9.04 9.04 0 012.955-.502c4.97 0 9 4.03 9 9 0 1.66-.448 3.21-1.233 4.552m-14.534 0A8.995 8.995 0 013 12c0-4.97 4.03-9 9-9" />
                                </svg>
                            </div>
                        </template>
                    </div>
                    <?php endif; ?>

                    <div class="text-gray-700 text-base mb-4 leading-loose space-y-4 prose prose-sm max-w-none">
                        <?php 
                        $stepContent = $step['content'];
                        // Sama seperti deskripsi, bersihkan spasi berlebih
                        if (strpos($stepContent, '<p>') !== false || strpos($stepContent, '<br>') !== false) {
                            $stepContent = preg_replace('/(<br\s*\/?>\s*){2,}/', '<br>', $stepContent);
                            echo $stepContent;
                        } else {
                            echo nl2br(htmlspecialchars($stepContent));
                        }
                        ?>
                    </div>

                    <button @click="toggleStep(<?php echo $step['id']; ?>)" 
                            class="w-full py-2 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2"
                            :class="isCompleted(<?php echo $step['id']; ?>) 
                                ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                        <i class="fas" :class="isCompleted(<?php echo $step['id']; ?>) ? 'fa-check' : 'fa-circle'"></i>
                        <span x-text="isCompleted(<?php echo $step['id']; ?>) ? 'Selesai' : 'Tandai Selesai'"></span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Sticky Share Button -->
        <div class="fixed bottom-20 right-4 z-40">
            <a href="https://wa.me/?text=<?php echo urlencode('Cek tutorial ini: ' . $tutorial['title'] . ' - ' . "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
               target="_blank"
               class="bg-green-500 hover:bg-green-600 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center transition transform hover:scale-110">
                <i class="fab fa-whatsapp text-3xl"></i>
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
