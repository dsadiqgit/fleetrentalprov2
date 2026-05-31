<?php
/**
 * Universal Tenant Header
 * This component brings brand consistency to every page of the tenant's rental website.
 */
require_once __DIR__ . '/../../includes/tenant_init.php';

// Fetch common data if not already defined
$tenant = getTenant();
$tenant_id = getTenantId();
$pdo = getDB();

if (!isset($content)) {
    $stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $content = $stmt->fetch();

    if (!$content) {
        $content = [
            'company_name' => $tenant['name'],
            'font_family' => 'Inter',
            'primary_color' => '#3b82f6',
            'secondary_color' => '#1e40af',
            'header_color' => '#ffffff',
            'text_color' => '#111827',
            'background_color' => '#ffffff'
        ];
    }
}

// Ensure base URLs work in any environment
$tenant_home = "/templates/template-1-preview.php?tenant=" . urlencode($tenant['subdomain']);
$fleet_url = "/templates/fleet.php?tenant=" . urlencode($tenant['subdomain']);
?>

<style>
    :root {
        --primary-color: <?= $content['primary_color'] ?? '#3b82f6'?>;
        --secondary-color: <?= $content['secondary_color'] ?? '#1e40af'?>;
        --header-color: <?= $content['header_color'] ?? '#ffffff'?>;
        --text-color: <?= $content['text_color'] ?? '#111827'?>;
        --background-color: <?= $content['background_color'] ?? '#ffffff'?>;
    }

    body {
        font-family: '<?= $content['font_family'] ?? 'Inter'?>', sans-serif;
        background-color: var(--background-color);
        color: var(--text-color);
    }

    .nav-link {
        color: var(--text-color);
        opacity: 0.7;
        transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
        font-size: 0.875rem;
    }

    .nav-link:hover {
        opacity: 1;
    }

    .btn-primary-custom {
        background-color: var(--primary-color) !important;
        color: white !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 14px 0 rgba(var(--primary-color-rgb, 59, 130, 246), 0.3);
    }

    .btn-primary-custom:hover {
        background-color: var(--secondary-color) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(var(--primary-color-rgb, 59, 130, 246), 0.23);
    }
</style>

<header class="border-b border-gray-100 sticky top-0 z-[1200] transition-all duration-300" 
        style="background-color: var(--header-color); backdrop-filter: blur(8px);">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20">
            <!-- Logo Section -->
            <a href="<?= $tenant_home ?>" class="flex items-center space-x-3 group">
                <div class="relative">
                    <?php if (!empty($tenant['logo_url'])): ?>
                        <img src="<?= htmlspecialchars($tenant['logo_url']) ?>" alt="Logo" 
                             class="h-8 md:h-10 w-auto transition-transform group-hover:scale-105">
                    <?php else: ?>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black shadow-lg transition-transform group-hover:scale-105">
                            <?= strtoupper(substr($content['company_name'] ?? $tenant['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-base md:text-lg font-black tracking-tight text-slate-900">
                        <?= htmlspecialchars($content['company_name'] ?? $tenant['name']) ?>
                    </span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">
                        Premium Car Rental
                    </span>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-10">
                <a href="<?= $tenant_home ?>" class="nav-link">Home</a>
                <a href="<?= $fleet_url ?>" class="nav-link">Our Fleet</a>
                <a href="<?= $tenant_home ?>#about" class="nav-link">About</a>
                <a href="<?= $tenant_home ?>#contact" class="nav-link">Contact</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_SESSION['role'] === 'customer' ? '/dashboard/customer.php' : '/dashboard/' ?>" 
                       class="btn-primary-custom px-6 py-2.5 rounded-xl text-sm font-black tracking-tight">
                        My Dashboard
                    </a>
                <?php else: ?>
                    <a href="/auth/login.php" 
                       class="btn-primary-custom px-6 py-2.5 rounded-xl text-sm font-black tracking-tight">
                        Login
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Mobile Menu Button -->
            <button class="md:hidden p-2 text-slate-900 focus:outline-none" onclick="toggleTenantMobileMenu()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Navigation Overlay -->
    <div id="tenantMobileMenu" class="hidden fixed inset-0 min-h-screen bg-white/95 backdrop-blur-lg z-[9999] p-6 lg:hidden flex flex-col overflow-y-auto">
        <div class="flex justify-between items-center mb-10">
            <span class="text-xl font-black italic"><?= htmlspecialchars($content['company_name'] ?? $tenant['name']) ?></span>
            <button onclick="toggleTenantMobileMenu()" class="text-slate-900">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flex flex-col space-y-6 text-center flex-1">
            <a href="<?= $tenant_home ?>" class="text-2xl font-black text-slate-900" onclick="toggleTenantMobileMenu()">Home</a>
            <a href="<?= $fleet_url ?>" class="text-2xl font-black text-slate-900" onclick="toggleTenantMobileMenu()">Our Fleet</a>
            <a href="<?= $tenant_home ?>#about" class="text-2xl font-black text-slate-900" onclick="toggleTenantMobileMenu()">About</a>
            <a href="<?= $tenant_home ?>#contact" class="text-2xl font-black text-slate-900" onclick="toggleTenantMobileMenu()">Contact</a>
            
            <div class="pt-10 mt-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_SESSION['role'] === 'customer' ? '/dashboard/customer.php' : '/dashboard/' ?>" 
                       class="btn-primary-custom w-full py-5 rounded-2xl text-xl font-black shadow-2xl block">
                        My Dashboard
                    </a>
                <?php else: ?>
                    <a href="/auth/login.php" 
                       class="btn-primary-custom w-full py-5 rounded-2xl text-xl font-black shadow-2xl block">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleTenantMobileMenu() {
        const menu = document.getElementById('tenantMobileMenu');
        const isOpen = menu.classList.contains('hidden');
        menu.classList.toggle('hidden');
        document.body.classList.toggle('overflow-hidden');
        document.body.classList.toggle('touch-none');
        if (isOpen) {
            menu.scrollTop = 0;
        }
    }
</script>
