<?php
// includes/sidebar.php

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

function is_active($page, $current_page)
{
    if (is_array($page)) {
        return in_array($current_page, $page) ? 'active' : 'text-gray-600';
    }
    return $current_page === $page ? 'active' : 'text-gray-600';
}

// Calculate trial days specifically for the sidebar to ensure consistency "one source of truth"
if (isset($tenant['plan']) && $tenant['plan'] === 'trial' && isset($tenant['created_at'])) {
    $trial_start = new DateTime($tenant['created_at']);
    $trial_end = !empty($tenant['trial_ends_at']) ? new DateTime($tenant['trial_ends_at']) : (clone $trial_start)->modify('+30 days');
    $now = new DateTime();

    if ($now < $trial_end) {
        $interval = $now->diff($trial_end);
        $trial_days_remaining = $interval->days;
        if ($trial_days_remaining > 30)
            $trial_days_remaining = 30;
        $trial_percentage = ($trial_days_remaining / 30) * 100;
    } else {
        $trial_days_remaining = 0;
        $trial_percentage = 0;
    }
}
// Dynamically construct precise tenant root URL depending on localhost subdirectory paths vs live subdomains
$is_localhost_env = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
$app_base_route = substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], '/dashboard'));
$tenant_site_url = $is_localhost_env ? $app_base_route . '/?tenant=' . urlencode($tenant['subdomain'] ?? '') : '/';

// Sidebar is ready and uses $tenant['logo'] for branding
?>

<div class="w-64 bg-white border-r border-gray-200 flex flex-col h-full">
    <!-- Logo -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <?php if (!empty($tenant['logo'])): ?>
                <a href="/dashboard"><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="Logo"
                        class="h-8 w-auto object-contain"></a>
                <?php
            else: ?>
                <div class="w-8 h-8 bg-black rounded flex items-center justify-center text-white font-bold">⚡</div>
                <span class="text-xl font-bold truncate">
                    <?= htmlspecialchars($tenant['name'] ?? 'Car Rental') ?>
                </span>
                <?php
            endif; ?>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <a href="/dashboard/"
            class="sidebar-item <?= is_active('index.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span class="font-medium text-sm">Calendar</span>
        </a>

        <a href="/dashboard/vehicles.php"
            class="sidebar-item <?= is_active('vehicles.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 17h.01M16 17h.01M3 11l1.5-5.25A2 2 0 016.43 4h11.14a2 2 0 011.93 1.75L21 11M3 11v5a2 2 0 002 2h1a2 2 0 002-2v-1h8v1a2 2 0 002 2h1a2 2 0 002-2v-5M3 11h18">
                </path>
            </svg>
            <span class="font-medium text-sm">Vehicles</span>
        </a>

        <a href="/dashboard/customers.php"
            class="sidebar-item <?= is_active('customers.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
            </svg>
            <span class="font-medium text-sm">Customers</span>
        </a>

        <a href="/dashboard/bookings.php"
            class="sidebar-item <?= is_active('bookings.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                </path>
            </svg>
            <span class="font-medium text-sm">Bookings</span>
        </a>

        <a href="/dashboard/contracts.php"
            class="sidebar-item <?= is_active(['contracts.php', 'e-signing.php', 'contract-designer.php'], $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <span class="font-medium text-sm">Contracts</span>
        </a>

        <a href="/dashboard/media-library.php"
            class="sidebar-item <?= is_active('media-library.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>
            <span class="font-medium text-sm">Media Library</span>
        </a>

        <a href="/dashboard/website.php"
            class="sidebar-item <?= is_active(['website.php', 'website-builder.php', 'website-editor.php'], $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 9h16M9 20V9"></path>
            </svg>
            <span class="font-medium text-sm">Website</span>
        </a>

        <?php
        $tenant_url = (ROOT_DOMAIN === 'localhost')
            ? "http://{$tenant['subdomain']}." . ROOT_DOMAIN . ":" . PORT
            : "http://{$tenant['subdomain']}." . ROOT_DOMAIN;
        ?>
        <div class="px-4 mt-2 mb-2">
            <a href="<?= htmlspecialchars($tenant_url) ?>" target="_blank"
                class="flex items-center justify-center space-x-2 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg transition-colors shadow-sm font-medium text-sm group">
                <svg class="w-4 h-4 text-blue-200 group-hover:text-white transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                <span>View Live Website</span>
            </a>
        </div>

        <div class="pt-4 mt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Account</p>

            <a href="/dashboard/dealership.php"
                class="sidebar-item <?= is_active('dealership.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
                <span class="font-medium text-sm">Dealership</span>
            </a>

            <a href="/dashboard/settings.php"
                class="sidebar-item <?= is_active('settings.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-medium text-sm">Settings</span>
            </a>
        </div>
    </nav>

    <!-- Trial Banner -->
    <?php if (isset($tenant['plan']) && $tenant['plan'] === 'trial' && isset($trial_days_remaining) && $trial_days_remaining > 0): ?>
        <div class="p-4 m-4 bg-[#1a1f2b] rounded-xl text-white shadow-lg">
            <div class="flex items-center space-x-2 mb-3">
                <svg class="w-5 h-5 text-[#3b82f5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z">
                    </path>
                </svg>
                <span class="font-medium text-sm">
                    <?= $trial_days_remaining ?> day
                    <?= $trial_days_remaining != 1 ? 's' : '' ?> left on your free trial
                </span>
            </div>
            <div class="w-full bg-gray-700/50 rounded-full h-1.5 mb-4 overflow-hidden">
                <div class="bg-[#3b82f5] h-full rounded-full transition-all duration-500"
                    style="width: <?= round($trial_percentage ?? 0) ?>%"></div>
            </div>
            <a href="/upgrade.php"
                class="block w-full text-center bg-white text-gray-900 font-medium py-2 px-4 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                Upgrade
            </a>
        </div>
        <?php
    elseif (isset($tenant['plan']) && $tenant['plan'] === 'trial' && isset($trial_days_remaining) && $trial_days_remaining <= 0): ?>
        <div class="p-4 m-4 bg-red-900 rounded-xl text-white shadow-lg">
            <div class="flex items-center space-x-2 mb-3">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium text-sm">Free trial expired</span>
            </div>
            <div class="w-full bg-red-800/50 rounded-full h-1.5 mb-4 overflow-hidden">
                <div class="bg-red-500 h-full rounded-full" style="width: 0%"></div>
            </div>
            <a href="/upgrade.php"
                class="block w-full text-center bg-white text-red-900 font-medium py-2 px-4 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                Upgrade Now
            </a>
        </div>
        <?php
    endif; ?>

    <!-- User Profile -->
    <div class="p-4 border-t border-gray-200 bg-white">
        <a href="/auth/logout.php" class="flex items-center space-x-2 text-gray-500 hover:text-gray-900 mb-4 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                </path>
            </svg>
            <span>Logout</span>
        </a>

        <div class="flex items-center space-x-3 mb-4">
            <div
                class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(substr($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[12px] font-bold text-gray-900 truncate">
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></p>
                <p class="text-[10px] text-gray-500 truncate"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
            </div>
        </div>

        <div class="flex text-[11px] text-gray-400 space-x-4 justify-between">
            <a href="/terms.php" class="hover:text-gray-600 text-center">Terms and<br>conditions</a>
            <a href="/privacy.php" class="hover:text-gray-600 text-center">Privacy<br>Policy</a>
        </div>
    </div>
</div>

<!-- Global Mobile Menu Logic -->
<script>
    (function () {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const body = document.body;

        if (mobileMenuBtn && sidebar && sidebarOverlay) {
            mobileMenuBtn.addEventListener('click', () => {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                sidebar.classList.toggle('-translate-x-full');
                sidebarOverlay.classList.toggle('hidden');

                if (isHidden) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            });

            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                body.style.overflow = '';
            });
        }
    })();
</script>