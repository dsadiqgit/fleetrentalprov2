<?php
$current_page = basename($_SERVER['PHP_SELF']);
function is_active_customer($page, $current) {
    return $page === $current ? 'active' : 'text-gray-600';
}
?>
<div class="w-64 bg-white border-r border-gray-200 flex flex-col h-full">
    <!-- Logo -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <?php if (!empty($tenant['logo'])): ?>
                <a href="/dashboard/customer.php"><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="Logo" class="h-8 w-auto object-contain"></a>
            <?php else: ?>
                <div class="w-8 h-8 bg-black rounded flex items-center justify-center text-white font-bold text-sm">⚡</div>
                <span class="text-xl font-bold truncate"><?= htmlspecialchars($tenant['name'] ?? 'Car Rental') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1">
        <a href="/dashboard/customer.php" class="sidebar-item <?= is_active_customer('customer.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="font-medium text-sm">My Bookings</span>
        </a>
        <a href="/dashboard/customer-contracts.php" class="sidebar-item <?= is_active_customer('customer-contracts.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="font-medium text-sm">Digital Contracts</span>
        </a>
    </nav>

    <!-- Account section -->
    <div class="px-4 pb-2">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 mb-1">Account</p>
        <a href="/dashboard/customer-profile.php" class="sidebar-item <?= is_active_customer('customer-profile.php', $current_page) ?> flex items-center space-x-3 px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="font-medium text-sm">My Profile</span>
        </a>
    </div>

    <!-- Bottom user profile -->
    <div class="p-4 border-t border-gray-200 bg-white">
        <a href="/auth/logout.php" class="flex items-center space-x-2 text-gray-500 hover:text-gray-900 mb-4 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Logout</span>
        </a>

        <div class="flex items-center space-x-3 mb-4">
            <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[12px] font-bold text-gray-900 truncate"><?= htmlspecialchars($user['full_name'] ?? 'Customer') ?></p>
                <p class="text-[10px] text-gray-500 truncate"><?= htmlspecialchars($user_email) ?></p>
            </div>
        </div>

        <div class="flex text-[11px] text-gray-400 space-x-4 justify-between">
            <a href="/terms.php" target="_blank" class="hover:text-gray-600 text-center">Terms and<br>conditions</a>
            <a href="/privacy.php" target="_blank" class="hover:text-gray-600 text-center">Privacy<br>Policy</a>
        </div>
    </div>
</div>
