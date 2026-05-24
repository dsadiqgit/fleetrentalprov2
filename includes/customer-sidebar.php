<?php
$current_page = basename($_SERVER['PHP_SELF']);
function is_active_customer($page, $current) {
    return $page === $current ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
}
?>
<div class="flex flex-col h-full bg-white border-r border-gray-200 w-64 flex-shrink-0">
    <!-- Brand -->
    <div class="px-6 py-8 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center shadow-lg transform rotate-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <span class="text-xl font-black text-gray-900 tracking-tight leading-none"><?= htmlspecialchars($tenant['name']) ?></span>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Customer Portal</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-8 space-y-2">
        <a href="/dashboard/customer.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all <?= is_active_customer('customer.php', $current_page) ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            My Bookings
        </a>
        <a href="/dashboard/customer-contracts.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all <?= is_active_customer('customer-contracts.php', $current_page) ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Digital Contracts
        </a>
    </nav>

    <!-- User Profile -->
    <div class="p-4 border-t border-gray-100">
        <div class="bg-gray-50 rounded-3xl p-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                    <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-black text-gray-900 truncate"><?= htmlspecialchars($user['full_name'] ?? $user_email) ?></p>
                    <p class="text-[10px] text-gray-400 font-bold uppercase truncate"><?= $user_email ?></p>
                </div>
            </div>
            <a href="/auth/logout.php" class="flex items-center justify-center gap-2 w-full px-4 py-3 text-xs font-black uppercase tracking-widest text-red-600 bg-white border border-red-100 rounded-2xl hover:bg-red-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-6 0v-1m6-10V7a3 3 0 00-6 0v1" />
                </svg>
                Logout
            </a>
        </div>
    </div>
</div>
