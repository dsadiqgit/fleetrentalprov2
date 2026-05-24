<?php
// includes/admin-sidebar.php - Admin Dashboard Sidebar

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);

function is_admin_active($page, $current_page) {
    return $current_page === $page ? 'active' : 'text-gray-600';
}
?>

<aside class="w-64 bg-white border-r border-gray-200 flex flex-col hidden lg:flex h-full">
    <!-- Logo -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-black rounded flex items-center justify-center text-white font-bold">⚡</div>
            <span class="text-xl font-bold">Admin Dashboard</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <a href="/admin/super-admin.php" class="sidebar-item <?= is_admin_active('super-admin.php', $current_page) ?> flex items-center space-x-3 px-4 py-3 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <span class="font-medium text-sm">Companies</span>
        </a>
        
        <a href="/admin/revenue.php" class="sidebar-item <?= is_admin_active('revenue.php', $current_page) ?> flex items-center space-x-3 px-4 py-3 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-medium text-sm">Revenue</span>
        </a>
        
        <a href="/admin/subscriptions.php" class="sidebar-item <?= is_admin_active('subscriptions.php', $current_page) ?> flex items-center space-x-3 px-4 py-3 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            <span class="font-medium text-sm">Subscriptions</span>
        </a>

        <a href="/admin/templates.php" class="sidebar-item <?= is_admin_active('templates.php', $current_page) ?> flex items-center space-x-3 px-4 py-3 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
            </svg>
            <span class="font-medium text-sm">Templates</span>
        </a>

        <div class="pt-4 mt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Account</p>
            
            <a href="/admin/account.php" class="sidebar-item <?= is_admin_active('account.php', $current_page) ?> flex items-center space-x-3 px-4 py-3 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="font-medium text-sm">Account</span>
            </a>
            
            <a href="/admin/settings.php" class="sidebar-item <?= is_admin_active('settings.php', $current_page) ?> flex items-center space-x-3 px-4 py-3 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-medium text-sm">Settings</span>
            </a>
        </div>
    </nav>

    <!-- User Profile -->
    <div class="p-4 border-t border-gray-200 bg-white">
        <a href="/auth/logout.php" class="flex items-center space-x-2 text-gray-500 hover:text-gray-900 mb-4 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Logout</span>
        </a>
        
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-8 h-8 bg-gray-900 rounded-full flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(substr($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></p>
                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
            </div>
        </div>
        
        <div class="flex text-[11px] text-gray-400 space-x-4">
            <a href="#" class="hover:text-gray-600">Terms and<br>conditions</a>
            <a href="#" class="hover:text-gray-600">Privacy<br>Policy</a>
        </div>
    </div>
</aside>
