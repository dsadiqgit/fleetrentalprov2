<?php
$current_page = basename($_SERVER['PHP_SELF']);
$active_bookings = ($current_page === 'customer.php') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50';
$active_contracts = ($current_page === 'customer-contracts.php') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50';
?>
<nav class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Left side: Logo and Desktop Nav -->
            <div class="flex items-center gap-8">
                <a href="/dashboard/customer.php" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center transition-transform group-hover:scale-105">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-900 tracking-tight"><?= htmlspecialchars($tenant['name']) ?></span>
                </a>
                
                <div class="hidden md:flex items-center gap-1">
                    <a href="/dashboard/customer.php" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all <?= $active_bookings ?>">
                        My Bookings
                    </a>
                    <a href="/dashboard/customer-contracts.php" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all <?= $active_contracts ?>">
                        My Contracts
                    </a>
                </div>
            </div>

            <!-- Right side: Profile and Mobile Toggle -->
            <div class="flex items-center gap-3">
                <!-- Desktop Profile -->
                <div class="hidden sm:flex items-center gap-3 pl-4 border-l border-gray-100">
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-900 truncate max-w-[150px]"><?= htmlspecialchars($user['full_name'] ?? $user_email) ?></p>
                        <p class="text-[10px] text-gray-500 font-medium">Customer Portal</p>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
                    </div>
                </div>

                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                    <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <a href="/auth/logout.php" class="hidden sm:flex px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-xl transition-all">
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Nav -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white">
        <div class="px-4 py-4 space-y-2">
            <a href="/dashboard/customer.php" class="block px-4 py-3 rounded-xl text-base font-bold <?= $active_bookings ?>">
                My Bookings
            </a>
            <a href="/dashboard/customer-contracts.php" class="block px-4 py-3 rounded-xl text-base font-bold <?= $active_contracts ?>">
                My Contracts
            </a>
            <div class="pt-4 mt-4 border-t border-gray-100">
                <div class="flex items-center gap-3 px-4 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-base">
                        <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($user['full_name'] ?? $user_email) ?></p>
                        <p class="text-xs text-gray-500">Customer Portal</p>
                    </div>
                </div>
                <a href="/auth/logout.php" class="block w-full px-4 py-3 text-left text-base font-bold text-red-600 hover:bg-red-50 rounded-xl transition-all">
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    document.getElementById('mobile-menu-toggle')?.addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');
        
        const isHidden = menu.classList.contains('hidden');
        
        if (isHidden) {
            menu.classList.remove('hidden');
            menuIcon.classList.add('hidden');
            closeIcon.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
        }
    });
</script>
