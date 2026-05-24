<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Your Plan | <?= SITE_NAME ?></title>

    <!-- Preload fonts -->
    <link rel="preload" href="/public/font/Inter_Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_Bold.ttf" as="font" type="font/ttf" crossorigin>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="stylesheet" href="/public/css/blog.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-white" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="pt-20">
        <!-- Breadcrumbs -->
        <div class="max-w-7xl mx-auto px-6 pt-12">
            <div class="flex items-center space-x-2 text-[11px] font-bold text-slate-400 uppercase tracking-widest">
                <a href="/dashboard/" class="hover:text-slate-900 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-900">Upgrade</span>
            </div>
        </div>

        <section class="py-20">
            <?php include __DIR__ . '/includes/pricing-grid.php'; ?>
        </section>

        <!-- Bottom FAQ or Trust -->
        <section class="py-20 border-t border-slate-100">
            <div class="max-w-3xl mx-auto px-6 text-center">
                <h3 class="text-xl font-bold text-slate-900 mb-6">Need a custom solution?</h3>
                <p class="text-slate-500 mb-10">If your needs don't fit into our standard plans, our enterprise team can build a custom package for you. Includes custom integrations, SLAs, and dedicated support.</p>
                <a href="/contact.php" class="text-slate-900 font-bold border-b-2 border-slate-900 pb-1 hover:text-blue-600 hover:border-blue-600 transition">Talk to Enterprise Sales →</a>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
