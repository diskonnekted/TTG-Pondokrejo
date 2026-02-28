<?php
// public/index.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = connectDB();

// Fetch Categories
$categories = getCategories($pdo);

// Fetch Featured Tutorial (Random or specific logic)
$stmt = $pdo->query("SELECT t.*, c.name as category_name 
                     FROM tutorials t 
                     JOIN categories c ON t.category_id = c.id 
                     ORDER BY RANDOM() LIMIT 1");
$featured = $stmt->fetch();

// Fetch Latest Tutorials (excluding featured if possible, but for simplicity just latest 10)
$stmt = $pdo->query("SELECT t.*, c.name as category_name, c.icon_class 
                     FROM tutorials t 
                     JOIN categories c ON t.category_id = c.id 
                     ORDER BY created_at DESC LIMIT 10");
$latestTutorials = $stmt->fetchAll();

$pageTitle = "Beranda";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Modern Hero Section -->
<div class="relative bg-gradient-to-br from-olive-leaf to-black-forest text-cornsilk pb-10 pt-6 px-6 rounded-b-[2.5rem] shadow-xl overflow-hidden">
    <!-- Decorative Circles -->
    <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-0 left-0 -ml-10 -mb-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>

    <div class="relative z-10">
        <div class="flex justify-between items-start mb-6">
            <div>
                <p class="text-cornsilk/80 text-sm font-medium mb-1">Selamat Datang di</p>
                <h1 class="text-3xl font-extrabold tracking-tight text-white">TTG Pondokrejo</h1>
            </div>
            <div class="bg-white/20 p-1 rounded-lg backdrop-blur-sm text-white">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo TTG" class="w-12 h-auto object-contain">
        </div>
        </div>

        <p class="mb-6 text-cornsilk/90 text-sm leading-relaxed max-w-xs">
            Pusat informasi teknologi tepat guna untuk kemandirian dan kemajuan desa kita.
        </p>
        
        <form action="search.php" method="GET" class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <input type="text" name="q" placeholder="Cari panduan (misal: hidroponik)..." 
                   class="w-full py-4 pl-11 pr-4 rounded-2xl text-black-forest bg-cornsilk shadow-lg focus:outline-none focus:ring-4 focus:ring-sunlit-clay/50 transition-all placeholder-gray-500 font-medium">
        </form>
    </div>
</div>

<!-- Categories (Horizontal Scroll) -->
<div class="mt-8 px-4">
    <div class="flex justify-between items-end mb-4 px-2">
        <h2 class="text-lg font-bold text-black-forest">Kategori Populer</h2>
        <a href="category.php" class="text-xs text-olive-leaf font-bold hover:underline">Lihat Semua</a>
    </div>
    
    <div class="flex overflow-x-auto space-x-4 pb-4 px-2 no-scrollbar snap-x">
        <?php foreach($categories as $cat): ?>
        <a href="category.php?id=<?php echo $cat['id']; ?>" class="snap-start flex-shrink-0 flex flex-col items-center group">
            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-gray-500 shadow-sm border border-gray-100 group-hover:border-olive-leaf group-hover:bg-cornsilk group-hover:text-olive-leaf transition-all duration-300 p-3">
                <img src="<?php echo getCategoryIcon($cat['name']); ?>" class="w-full h-full object-contain filter group-hover:brightness-110 transition">
            </div>
            <span class="text-xs font-medium text-gray-600 mt-2 group-hover:text-olive-leaf transition-colors"><?php echo $cat['name']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Featured Section -->
<?php if($featured): ?>
<div class="px-6 mt-4">
    <h2 class="text-lg font-bold text-black-forest mb-4 px-2">Pilihan Hari Ini</h2>
    <a href="tutorial.php?id=<?php echo $featured['id']; ?>" class="block relative h-48 rounded-3xl overflow-hidden shadow-md group">
        <?php if($featured['image_path']): ?>
            <img src="<?php echo BASE_URL . $featured['image_path']; ?>" alt="<?php echo $featured['title']; ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
        <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-gray-400">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-black-forest/90 via-black-forest/20 to-transparent"></div>
        <div class="absolute bottom-0 left-0 p-5">
            <span class="bg-sunlit-clay/90 backdrop-blur-sm text-black-forest text-[10px] font-bold px-2 py-1 rounded-md mb-2 inline-block shadow-sm">
                <?php echo $featured['category_name']; ?>
            </span>
            <h3 class="text-white font-bold text-lg leading-tight line-clamp-2 mb-1 drop-shadow-md">
                <?php echo $featured['title']; ?>
            </h3>
            <div class="flex items-center text-gray-300 text-xs gap-3">
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php echo $featured['duration']; ?> min
                </span>
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <?php echo $featured['difficulty']; ?>
                </span>
            </div>
        </div>
    </a>
</div>
<?php endif; ?>

<!-- Latest Tutorials (Masonry Grid) -->
<div class="px-6 mt-8 mb-24">
    <h2 class="text-lg font-bold text-black-forest mb-4 px-2">Terbaru</h2>
    <div class="columns-2 gap-4 space-y-4">
        <?php foreach($latestTutorials as $tutorial): ?>
        <a href="tutorial.php?id=<?php echo $tutorial['id']; ?>" class="break-inside-avoid block bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition group border border-gray-100">
            <div class="relative">
                <?php if($tutorial['image_path']): ?>
                    <img src="<?php echo BASE_URL . $tutorial['image_path']; ?>" alt="<?php echo $tutorial['title']; ?>" class="w-full h-32 object-cover">
                <?php else: ?>
                    <div class="w-full h-32 bg-gray-100 flex items-center justify-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                <?php endif; ?>
                <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm p-1.5 rounded-lg shadow-sm">
                    <?php if(strpos($tutorial['icon_class'] ?? '', 'fas fa-') !== false): ?>
                         <i class="<?php echo $tutorial['icon_class']; ?> text-olive-leaf text-xs"></i>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 text-olive-leaf">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    <?php endif; ?>
                </div>
            </div>
            <div class="p-3">
                <h3 class="font-bold text-sm text-black-forest mb-1 line-clamp-2 group-hover:text-olive-leaf transition-colors"><?php echo $tutorial['title']; ?></h3>
                <p class="text-xs text-gray-500 line-clamp-2 mb-2"><?php echo $tutorial['description']; ?></p>
                <div class="flex items-center justify-between text-[10px] text-gray-400">
                    <span><?php echo timeAgo($tutorial['created_at']); ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
