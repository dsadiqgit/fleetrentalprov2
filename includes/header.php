<?php
// includes/header.php - Centralized header for landing pages

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);

function is_header_active($page)
{
    global $current_page;
    return $current_page === $page ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900';
}
?>

<!-- Navbar -->
<nav id="mainNav"
    class="bg-white border-b border-gray-100 sticky top-0 z-[100] w-full" style="transition: transform 0.3s ease-in-out;">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex justify-between items-center h-16">
            <a href="/" class="flex items-center">
                <img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro" class="h-10">
            </a>
            <div class="hidden lg:flex items-center space-x-8">
                <div class="group">
                    <button
                        class="<?= is_header_active('index.php')?> text-sm font-medium flex items-center space-x-1 py-4">
                        <span>Features</span>
                        <svg class="w-4 h-4 text-gray-400 group-hover:rotate-180 transition-transform duration-200"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <!-- Megamenu dropdown -->
                    <div class="absolute left-0 right-0 top-full mx-auto w-full max-w-[900px] bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden transform group-hover:translate-y-0 translate-y-2"
                        style="max-width: min(900px, calc(100vw - 2rem));">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Left Column: Highlight Block -->
                            <div class="w-full lg:w-1/3 p-4 bg-white lg:border-r border-gray-100">
                                <div class="bg-blue-600 rounded-xl overflow-hidden relative group/image cursor-pointer h-full min-h-[180px]"
                                    onclick="window.location.href='/contact.php'">
                                    <img src="/assets/images/team-demo.png" alt="Team"
                                        class="w-full h-full object-cover opacity-90 group-hover/image:scale-105 transition duration-500"
                                        onerror="this.style.display='none'">
                                    <div class="absolute inset-0 bg-gradient-to-t from-blue-900/80 to-transparent">
                                    </div>
                                    <div class="absolute bottom-4 left-4 right-4 flex justify-between items-end">
                                        <div class="text-white font-medium text-sm">Talk with the Team</div>
                                        <div
                                            class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle Column: Featured Items -->
                            <div class="w-full lg:w-5/12 p-6 bg-white lg:border-r border-gray-100">
                                <div class="space-y-6">
                                    <a href="/features/id-verification.php" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                ID Verification</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">AI-native ID verification
                                                with liveness, face match, and fraud prevention.</p>
                                        </div>
                                    </a>

                                    <a href="/features/booking-calendar.php" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Booking Calendar</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Real-time availability,
                                                conflict prevention, and drag-and-drop scheduling.</p>
                                        </div>
                                    </a>

                                    <a href="/features/visual-condition-reports.php"
                                        class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Condition Reports</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Timestamped photo
                                                documentation before and after every rental.</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Right Column: Simple Links -->
                            <div class="flex-1 p-6 bg-gray-50/50">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
                                    <a href="/features/e-signature.php"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">E-Signature
                                        Contracts</a>
                                    <a href="/features/dynamic-pricing.php"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Dynamic Pricing</a>
                                    <a href="/features/website-builder.php"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Website Builder</a>
                                    <a href="/features/vehicle-management.php"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Vehicle
                                        Management</a>
                                    <a href="/features/customer-portal.php"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Customer Portal</a>
                                    <a href="/features/stripe-integration.php"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Stripe
                                        Integration</a>
                                    <a href="/pricing.php"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Pricing Plans</a>
                                    <a href="/auth/signup.php"
                                        class="text-sm text-blue-600 hover:text-blue-700 font-medium transition">Start
                                        Free Trial &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Locations Megamenu -->
                <div class="group">
                    <button
                        class="<?= is_header_active('locations')?> text-sm font-medium flex items-center space-x-1 py-4">
                        <span>Locations</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <!-- Megamenu dropdown -->
                    <div class="absolute left-0 right-0 top-full mx-auto w-full max-w-[900px] bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden transform group-hover:translate-y-0 translate-y-2"
                        style="max-width: min(900px, calc(100vw - 2rem));">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Left Column: Highlight Block -->
                            <div class="w-full lg:w-1/3 p-4 bg-white lg:border-r border-gray-100">
                                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl overflow-hidden relative group/image cursor-pointer h-full min-h-[180px] flex items-center justify-center"
                                    onclick="window.location.href='/uk/london/'">
                                    <div
                                        class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMC41IiBvcGFjaXR5PSIwLjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30">
                                    </div>
                                    <div class="relative z-10 text-center px-4">
                                        <svg class="w-12 h-12 text-white mx-auto mb-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <div class="text-white font-semibold text-sm mb-1">UK Coverage</div>
                                        <div class="text-blue-100 text-xs">20+ Major Cities</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle Column: Featured Cities -->
                            <div class="w-full lg:w-5/12 p-6 bg-white lg:border-r border-gray-100">
                                <div class="space-y-6">
                                    <a href="/uk/london/" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                London</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Serving Central London,
                                                Heathrow, and surrounding areas.</p>
                                        </div>
                                    </a>

                                    <a href="/uk/manchester/" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Manchester</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Greater Manchester and
                                                airport locations available.</p>
                                        </div>
                                    </a>

                                    <a href="/uk/birmingham/" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                                </path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Birmingham</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">City centre and West
                                                Midlands coverage.</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Right Column: All Cities -->
                            <div class="flex-1 p-6 bg-gray-50/50">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
                                    <a href="/uk/glasgow/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Glasgow</a>
                                    <a href="/uk/bristol/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Bristol</a>
                                    <a href="/uk/leeds/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Leeds</a>
                                    <a href="/uk/liverpool/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Liverpool</a>
                                    <a href="/uk/edinburgh/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Edinburgh</a>
                                    <a href="/uk/sheffield/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Sheffield</a>
                                    <a href="/uk/newcastle/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Newcastle</a>
                                    <a href="/uk/nottingham/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Nottingham</a>
                                    <a href="/uk/belfast/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Belfast</a>
                                    <a href="/uk/cardiff/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Cardiff</a>
                                    <a href="/uk/leicester/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Leicester</a>
                                    <a href="/uk/southampton/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Southampton</a>
                                    <a href="/uk/bradford/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Bradford</a>
                                    <a href="/uk/aberdeen/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Aberdeen</a>
                                    <a href="/uk/coventry/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Coventry</a>
                                    <a href="/uk/reading/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Reading</a>
                                    <a href="/uk/milton-keynes/"
                                        class="text-sm text-gray-600 hover:text-blue-600 transition">Milton Keynes</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resources Megamenu -->
                <div class="group">
                    <button
                        class="<?= is_header_active('resources')?> text-sm font-medium flex items-center space-x-1 py-4">
                        <span>Resources</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <!-- Megamenu dropdown -->
                    <div class="absolute left-0 right-0 top-full mx-auto w-full max-w-[900px] bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden transform group-hover:translate-y-0 translate-y-2"
                        style="max-width: min(900px, calc(100vw - 2rem));">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Left Column: Highlight Block -->
                            <div class="w-full lg:w-[280px] p-4 bg-white lg:border-r border-gray-100">
                                <a href="/supported-documents"
                                    class="block bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl overflow-hidden relative group/image cursor-pointer h-full min-h-[180px]">
                                    <div
                                        class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLXdpZHRoPSIwLjUiIG9wYWNpdHk9IjAuMDUiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30">
                                    </div>
                                    <div class="relative z-10 p-6 flex flex-col justify-between h-full">
                                        <div>
                                            <div
                                                class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center mb-4">
                                                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <div class="font-semibold text-gray-900 text-sm mb-1">Supported Documents
                                            </div>
                                            <div class="text-gray-600 text-xs leading-relaxed">View all accepted ID
                                                types and documents</div>
                                        </div>
                                        <div class="flex items-center text-blue-600 text-xs font-medium mt-4">
                                            <span>Learn more</span>
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Middle Column: Primary Resources -->
                            <div class="w-full lg:w-[380px] p-6 bg-white lg:border-r border-gray-100">
                                <div class="space-y-6">
                                    <a href="/blog" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Blog</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Industry insights, updates,
                                                and best practices.</p>
                                        </div>
                                    </a>


                                    <a href="/documentation.php" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Documentation</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Complete guides and API
                                                reference.</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Right Column: Additional Resources -->
                            <div class="flex-1 p-6 bg-gray-50/50">
                                <div class="space-y-4">
                                    <a href="/success-stories" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-white flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0 shadow-sm">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Success Stories</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">See how companies use our
                                                platform.</p>
                                        </div>
                                    </a>

                                    <a href="/roi-calculator" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-white flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0 shadow-sm">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                ROI Calculator</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Calculate your potential
                                                savings.</p>
                                        </div>
                                    </a>

                                    <a href="/business-console" class="flex items-start group/item">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-white flex items-center justify-center mr-4 group-hover/item:bg-blue-50 transition flex-shrink-0 shadow-sm">
                                            <svg class="w-5 h-5 text-gray-700 group-hover/item:text-blue-600 transition"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-900 mb-1 group-hover/item:text-blue-600 transition">
                                                Business Console</h4>
                                            <p class="text-xs text-gray-500 leading-relaxed">Manage your fleet
                                                operations dashboard.</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="/company/"
                    class="text-gray-600 hover:text-gray-900 text-sm font-medium flex items-center space-x-1 py-4 group">
                    <span>Company</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-900 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7">
                        </path>
                    </svg>
                </a>
                <a href="/pricing.php"
                    class="<?= is_header_active('pricing.php')?> group text-sm font-medium flex items-center space-x-1 py-4">
                    <span>Pricing</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-900 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7">
                        </path>
                    </svg>
                </a>
                <a href="/contact.php"
                    class="<?= is_header_active('contact.php')?> group text-sm font-medium flex items-center space-x-1 py-4">
                    <span>Contact</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-900 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7">
                        </path>
                    </svg>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/dashboard/"
                    class="px-5 py-2.5 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800 inline-flex items-center space-x-2">
                    <span>Dashboard</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
                <?php
else: ?>
                <a href="/auth/login.php" class="text-gray-600 hover:text-gray-900 text-sm">Sign In</a>
                <a href="/auth/signup.php"
                    class="px-5 py-2.5 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800 inline-flex items-center space-x-2">
                    <span>Start free trial</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
                <?php
endif; ?>
            </div>

            <!-- Mobile menu button -->
            <button class="lg:hidden p-2 rounded-lg hover:bg-gray-100" onclick="toggleMobileMenu()">
                <svg id="menuIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
                <svg id="closeIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile menu - Full screen overlay -->
    <div id="mobileMenu" class="hidden fixed inset-0 bg-white z-50 lg:hidden overflow-y-auto">
        <div class="flex flex-col h-full">
            <!-- Mobile menu header -->
            <div class="flex justify-between items-center h-16 px-4 border-b border-gray-100">
                <a href="/" class="flex items-center">
                    <img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro" class="h-10">
                </a>
                <button onclick="toggleMobileMenu()" class="p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu content -->
            <div class="flex-1 px-6 py-6 space-y-6">
                <!-- Features Section -->
                <div class="border-b border-gray-100 pb-6">
                    <button onclick="toggleMobileSection('features')"
                        class="flex items-center justify-between w-full text-left">
                        <h3 class="text-lg font-bold text-gray-900">Features</h3>
                        <svg id="featuresChevron" class="w-5 h-5 text-gray-400 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div id="featuresSection" class="mt-4 space-y-3 hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <a href="/features/id-verification.php" class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-blue-50 transition border border-transparent hover:border-blue-100">
                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2" stroke-width="2"></path></svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">ID Verification</span>
                            </a>
                            <a href="/features/booking-calendar.php" class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-blue-50 transition border border-transparent hover:border-blue-100">
                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"></path></svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Booking Calendar</span>
                            </a>
                            <a href="/features/visual-condition-reports.php" class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-blue-50 transition border border-transparent hover:border-blue-100">
                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"></path></svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">Condition Reports</span>
                            </a>
                            <a href="/features/e-signature.php" class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-blue-50 transition border border-transparent hover:border-blue-100">
                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"></path></svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">E-Signature</span>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2 pt-4 border-t border-gray-50">
                            <a href="/features/dynamic-pricing.php" class="text-xs font-bold text-gray-400 hover:text-blue-600 uppercase tracking-widest">Pricing</a>
                            <a href="/features/website-builder.php" class="text-xs font-bold text-gray-400 hover:text-blue-600 uppercase tracking-widest">Builder</a>
                            <a href="/features/vehicle-management.php" class="text-xs font-bold text-gray-400 hover:text-blue-600 uppercase tracking-widest">Fleet</a>
                            <a href="/features/customer-portal.php" class="text-xs font-bold text-gray-400 hover:text-blue-600 uppercase tracking-widest">Portal</a>
                        </div>
                    </div>
                </div>

                <!-- Locations Section -->
                <div class="border-b border-gray-100 pb-6">
                    <button onclick="toggleMobileSection('locations')"
                        class="flex items-center justify-between w-full text-left">
                        <h3 class="text-lg font-bold text-gray-900">Locations</h3>
                        <svg id="locationsChevron" class="w-5 h-5 text-gray-400 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div id="locationsSection" class="mt-4 space-y-4 hidden">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                            <a href="/uk/london/" class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-600 rounded-full"></span> London
                            </a>
                            <a href="/uk/birmingham/" class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-600 rounded-full"></span> Birmingham
                            </a>
                            <a href="/uk/manchester/" class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-600 rounded-full"></span> Manchester
                            </a>
                            <a href="/uk/glasgow/" class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-600 rounded-full"></span> Glasgow
                            </a>
                        </div>
                        <div class="pt-4 border-t border-gray-50">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-3">All Cities</label>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                <a href="/uk/bristol/" class="text-xs text-gray-600">Bristol</a>
                                <a href="/uk/leeds/" class="text-xs text-gray-600">Leeds</a>
                                <a href="/uk/liverpool/" class="text-xs text-gray-600">Liverpool</a>
                                <a href="/uk/edinburgh/" class="text-xs text-gray-600">Edinburgh</a>
                                <a href="/uk/sheffield/" class="text-xs text-gray-600">Sheffield</a>
                                <a href="/uk/newcastle/" class="text-xs text-gray-600">Newcastle</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Links -->
                <div class="space-y-4">
                    <a href="/" class="flex items-center justify-between text-gray-900 font-medium">
                        <span>Resources</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="/company/" class="flex items-center justify-between text-gray-900 font-medium">
                        <span>Company</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="/pricing.php" class="flex items-center justify-between text-gray-900 font-medium">
                        <span>Pricing</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="/contact.php" class="flex items-center justify-between text-gray-900 font-medium">
                        <span>Contact</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="/#faq" class="flex items-center justify-between text-gray-900 font-medium">
                        <span>FAQs</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Mobile menu footer -->
            <div class="p-6 border-t border-gray-100">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/dashboard/"
                    class="block w-full px-6 py-3 bg-black text-white rounded-lg text-center font-medium hover:bg-gray-800">
                    Dashboard
                </a>
                <?php
else: ?>
                <a href="/auth/signup.php"
                    class="block w-full px-6 py-3 bg-black text-white rounded-lg text-center font-medium hover:bg-gray-800 mb-3">
                    Sign up
                </a>
                <a href="/auth/login.php"
                    class="block w-full px-6 py-3 border border-gray-300 text-gray-900 rounded-lg text-center font-medium hover:bg-gray-50">
                    Sign in
                </a>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const body = document.body;
        menu.classList.toggle('hidden');
        body.classList.toggle('overflow-hidden');
    }

    function toggleMobileSection(section) {
        const sections = ['features', 'locations'];
        const sectionEl = document.getElementById(section + 'Section');
        const chevron = document.getElementById(section + 'Chevron');

        // Close all other sections
        sections.forEach(s => {
            if (s !== section) {
                const otherSection = document.getElementById(s + 'Section');
                const otherChevron = document.getElementById(s + 'Chevron');
                if (otherSection && !otherSection.classList.contains('hidden')) {
                    otherSection.classList.add('hidden');
                    otherChevron.classList.remove('rotate-180');
                }
            }
        });

        // Toggle current section
        sectionEl.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    // Scroll-aware sticky header
    let lastScrollPos = 0;
    const scrollNav = document.getElementById('mainNav');

    window.addEventListener('scroll', () => {
        if (!scrollNav) return;

        const currentScrollPos = window.pageYOffset || document.documentElement.scrollTop;

        if (currentScrollPos <= 0) {
            // At the top
            scrollNav.style.transform = '';
            scrollNav.classList.remove('shadow-md');
        } else {
            // Add shadow slightly
            scrollNav.classList.add('shadow-md');

            // Limit hiding threshold so it doesn't accidentally trigger heavily on bounce
            if (currentScrollPos > lastScrollPos && currentScrollPos > 60) {
                // Scrolling DOWN
                scrollNav.style.transform = 'translateY(-100%)';
            } else if (currentScrollPos < lastScrollPos) {
                // Scrolling UP
                scrollNav.style.transform = 'translateY(0)';
            }
        }
        lastScrollPos = currentScrollPos;
    });

    // Mega Menu Backdrop Blur Overlay
    document.addEventListener('DOMContentLoaded', () => {
        const backdrop = document.createElement('div');
        backdrop.className = 'fixed inset-0 top-[64px] bg-slate-900/20 backdrop-blur-[3px] opacity-0 invisible transition-all duration-300 z-40 pointer-events-none';
        document.body.appendChild(backdrop);

        let hoverTimeout;
        const navGroups = document.querySelectorAll('#mainNav .group');

        navGroups.forEach(group => {
            // Only attach to groups that actually contain a dropdown panel
            if (group.querySelector('.absolute')) {
                group.addEventListener('mouseenter', () => {
                    clearTimeout(hoverTimeout);
                    backdrop.classList.remove('opacity-0', 'invisible');
                    backdrop.classList.add('opacity-100', 'visible');
                });

                group.addEventListener('mouseleave', () => {
                    hoverTimeout = setTimeout(() => {
                        backdrop.classList.remove('opacity-100', 'visible');
                        backdrop.classList.add('opacity-0', 'invisible');
                    }, 100); // 100ms debounce
                });
            }
        });
    });
</script>