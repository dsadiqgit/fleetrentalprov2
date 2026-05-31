<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/posts_data.php';

// Pagination Logic
$posts_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Category Filter
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'All';

$filtered_posts = array_filter($posts, function($post) use ($selected_category) {
    if ($selected_category === 'All') return true;
    return $post['category'] === $selected_category;
});

// Sort posts (newest first)
usort($filtered_posts, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});

$total_posts = count($filtered_posts);
$total_pages = ceil($total_posts / $posts_per_page);
$offset = ($current_page - 1) * $posts_per_page;

// Slice the posts for the current page
$display_posts = array_slice(array_values($filtered_posts), $offset, $posts_per_page);

// Identify Featured Post (only on page 1 of 'All')
$featured_post = ($current_page == 1 && $selected_category == 'All') ? array_shift($display_posts) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Blog - Insights, News & Fleet Management Trends - <?= SITE_NAME?></title>
    <meta name="description" content="Explore the latest insights on fleet management, car subscriptions, and rental industry trends. Learn how to scale your vehicle rental business with <?= SITE_NAME ?>.">
    <?php include __DIR__ . '/../includes/head-content.php'; ?>
</head>

<body class="bg-gray-50/10" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Announcement Bar -->
    <div class="bg-white border-b border-gray-100 py-2.5 hidden sm:block">
        <div class="max-w-7xl mx-auto px-4 flex justify-center items-center gap-2 text-[11px] font-bold tracking-widest text-gray-400 uppercase">
            Announcing our 2026 fleet expansion. <a href="#" class="text-blue-600 hover:text-blue-700 ml-1">Read more &rarr;</a>
        </div>
    </div>

    <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Hero Header -->
        <div class="mb-16">
            <p class="text-[11px] font-bold text-blue-600 uppercase tracking-[0.2em] mb-4">Articles, announcements, news, and more</p>
            <h1 class="text-6xl md:text-7xl font-bold text-gray-900 tracking_tighter">
                The <?= SITE_NAME?> Blog
            </h1>
        </div>

        <?php if ($featured_post): ?>
        <!-- Main Featured Article -->
        <div class="grid lg:grid-cols-12 gap-10 items-center mb-24 cursor-pointer group blog-card-hover"
            onclick="window.location.href='/blog/<?= $featured_post['slug'] ?>'">
            <div class="lg:col-span-8">
                <div class="relative aspect-[16/9] w-full overflow-hidden rounded-[40px] border border-gray-100 shadow-sm bg-gray-50">
                    <img src="<?= $featured_post['image'] ?>" alt="<?= htmlspecialchars($featured_post['title']) ?>"
                        class="object-cover w-full h-full group-hover:scale-[1.01] transition-transform duration-700">

                    <div class="absolute bottom-8 left-8 bg-white/10 backdrop-blur-xl border border-white/20 p-10 rounded-[30px] hidden md:block max-w-[340px]">
                        <div class="text-white">
                            <h3 class="text-3xl font-bold mb-3 tracking-tight"><?= htmlspecialchars($featured_post['title']) ?></h3>
                            <p class="text-sm opacity-90 leading-relaxed font-medium"><?= htmlspecialchars($featured_post['excerpt']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-6 lg:pl-4">
                <div class="flex items-center gap-3 text-[11px] font-bold">
                    <span class="text-blue-600 tracking-widest uppercase"><?= $featured_post['category'] ?></span>
                    <span class="text-gray-300">•</span>
                    <span class="text-gray-400"><?= $featured_post['date'] ?></span>
                </div>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 leading-[1.05] tracking-tighter group-hover:text-blue-600 transition duration-300">
                    <?= htmlspecialchars($featured_post['title']) ?>
                </h2>
                <p class="text-gray-500 text-lg leading-relaxed font-medium">
                    <?= htmlspecialchars($featured_post['excerpt']) ?>
                </p>
                <div class="pt-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 p-0.5 border border-gray-200">
                        <img src="<?= $featured_post['author_image'] ?>" alt="Author" class="w-full h-full rounded-full object-cover">
                    </div>
                    <span class="text-sm font-bold text-gray-900"><?= $featured_post['author'] ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Newsletter Bar -->
        <div class="bg-white border border-gray-100 rounded-[40px] p-8 md:p-14 mb-24 flex flex-col md:flex-row items-center justify-between gap-10 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 h-64 w-64 rounded-full bg-blue-50/30 blur-[100px]"></div>
            <div class="relative z-10 max-w-lg">
                <h3 class="text-2xl font-bold text-gray-900 mb-2 tracking-tight">Never miss an update</h3>
                <p class="text-gray-500 font-medium">Get the latest news, blog posts and product updates from <?= SITE_NAME ?>, directly to your inbox.</p>
            </div>
            <div class="relative z-10 w-full md:w-auto flex flex-col sm:flex-row gap-4">
                <input type="email" placeholder="Email address" class="px-8 py-4 bg-gray-50/50 border border-gray-100 rounded-full w-full md:w-80 outline-none focus:bg-white focus:ring-4 focus:ring-blue-100/50 transition duration-300">
                <button class="px-10 py-4 bg-blue-600 text-white rounded-full font-bold hover:bg-blue-700 transition shadow-[0_10px_20px_-5px_rgba(37,103,255,0.3)] hover:scale-[1.02] active:scale-95">Signup</button>
            </div>
        </div>

        <!-- Filter Pill Navigation -->
        <div class="flex flex-wrap items-center gap-2 mb-16 py-4 border-y border-gray-100 overflow-x-auto">
            <a href="?category=All" class="px-5 py-2 <?= $selected_category === 'All' ? 'bg-[#2567ff] text-white' : 'bg-white text-gray-500 border border-gray-100' ?> rounded-full text-sm font-bold shadow-md shadow-blue-200 transition whitespace-nowrap">All</a>
            <?php 
            $categories_list = array_unique(array_column($posts, 'category'));
            foreach($categories_list as $cat): ?>
            <a href="?category=<?= urlencode($cat) ?>" class="px-5 py-2 <?= $selected_category === $cat ? 'bg-[#2567ff] text-white' : 'bg-white text-gray-500 border border-gray-100' ?> rounded-full text-sm font-semibold hover:bg-gray-50 transition whitespace-nowrap">
                <?= $cat ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Three Column Post Grid -->
        <div class="grid md:grid-cols-3 gap-10 mb-24">
            <?php foreach (array_slice($display_posts, 0, 3) as $post): ?>
            <div class="group cursor-pointer blog-card-hover" onclick="window.location.href='/blog/<?= $post['slug'] ?>'">
                <div class="aspect-[4/3] rounded-[32px] overflow-hidden border border-gray-100 mb-6 shadow-sm">
                    <img src="<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                </div>
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-[11px] font-bold tracking-widest text-[#2567ff] uppercase">
                        <?= $post['category'] ?> <span class="text-gray-300">•</span> <?= $post['date'] ?>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 leading-tight tracking-tight group-hover:text-blue-600 transition"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="text-gray-500 text-base leading-relaxed line-clamp-2"><?= htmlspecialchars($post['excerpt']) ?></p>
                    <div class="flex items-center gap-2 pt-2">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= $post['author'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Feed of Smaller Post List Items -->
        <div class="grid md:grid-cols-2 lg:gap-x-16 gap-y-16 mb-24">
            <?php foreach (array_slice($display_posts, 3) as $post): ?>
            <div class="flex gap-6 group cursor-pointer items-center blog-card-hover" onclick="window.location.href='/blog/<?= $post['slug'] ?>'">
                <div class="w-40 md:w-56 aspect-[1.3/1] rounded-2xl overflow-hidden border border-gray-100 bg-gray-50 flex-shrink-0">
                    <img src="<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-700">
                </div>
                <div class="space-y-2">
                    <div class="text-[10px] font-bold tracking-[0.15em] text-[#2567ff] uppercase"><?= $post['category'] ?> <span class="text-gray-300 mx-1">•</span> <?= $post['date'] ?></div>
                    <h4 class="text-xl md:text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition tracking-tighter leading-tight"><?= htmlspecialchars($post['title']) ?></h4>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mt-3"><?= $post['author'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-center gap-1.5 border-t border-gray-100 pt-16">
            <a href="?page=<?= max(1, $current_page - 1) ?>&category=<?= urlencode($selected_category) ?>" class="w-10 h-10 flex items-center justify-center <?= $current_page == 1 ? 'text-gray-200 pointer-events-none' : 'text-gray-400 hover:bg-gray-50' ?> rounded-full transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&category=<?= urlencode($selected_category) ?>" class="w-9 h-9 flex items-center justify-center <?= $current_page == $i ? 'bg-gray-50 text-blue-600' : 'text-gray-400 hover:text-gray-900' ?> rounded-full text-sm font-bold transition"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?page=<?= min($total_pages, $current_page + 1) ?>&category=<?= urlencode($selected_category) ?>" class="w-10 h-10 flex items-center justify-center <?= $current_page == $total_pages ? 'text-gray-200 pointer-events-none' : 'text-gray-400 hover:bg-gray-50' ?> rounded-full transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M9 5l7 7-7 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>