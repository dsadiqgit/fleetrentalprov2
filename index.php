<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Check if this is a subdomain request
$host = $_SERVER['HTTP_HOST'] ?? '';
$host_parts = explode('.', str_replace(':' . PORT, '', $host)); // Remove port from host
$subdomain = '';

// Check if it's a subdomain (e.g., fresh.localhost)
if (count($host_parts) >= 2 && $host_parts[0] !== 'localhost' && $host_parts[0] !== 'www') {
    $subdomain = $host_parts[0];

    // Verify subdomain exists in database
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ? AND status = 'active'");
    $stmt->execute([$subdomain]);
    $tenant = $stmt->fetch();

    if ($tenant) {
        // Load tenant website template directly
        require_once __DIR__ . '/templates/template-1-preview.php';
        exit;
    }
    else {
        // Subdomain was provided but no tenant found
        require_once __DIR__ . '/404.php';
        exit;
    }
}

// Handle non-existent paths on main domain
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($request_uri !== '/' && $request_uri !== '/index.php' && $request_uri !== '') {
    // If we've reached here, it means .htaccess rewrote a non-existent file to index.php
    // We should show the 404 page for any path that isn't the root
    require_once __DIR__ . '/404.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title>
        <?= SITE_NAME?> - Car Rental Management Platform
    </title>

    <!-- Preload fonts -->
    <link rel="preload" href="/public/font/Inter_Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_Bold.ttf" as="font" type="font/ttf" crossorigin>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-white" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="hero-features-gradient">
        <!-- Hero Section -->
        <div class="relative overflow-hidden">

            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                            Grow Your Car Rental Business
                        </h1>
                        <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                            Increase efficiency and improve your customer experience with HQ Rental Software. The
                            complete platform for modern rental businesses.
                        </p>
                        <div class="mb-6">
                            <div class="flex gap-3">
                                <a href="/auth/signup.php"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium whitespace-nowrap inline-flex items-center justify-center text-sm gap-2">
                                    Sign Up
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </a>
                                <a href="/contact.php"
                                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium whitespace-nowrap inline-flex items-center justify-center text-sm gap-2">
                                    Contact Us
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="relative lg:ml-12">
                        <!-- Main car image with overflow -->
                        <div class="relative rounded-3xl overflow-visible">
                            <div
                                class="relative rounded-2xl overflow-hidden shadow-[0_20px_50px_rgba(37,103,255,0.15)] border border-gray-100/50">
                                <img src="https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1400&q=90"
                                    alt="Luxury Car"
                                    class="w-full h-[500px] object-cover hover:scale-105 transition-transform duration-700">
                            </div>

                            <!-- Customer testimonial overlay - top right -->
                            <div
                                class="absolute -top-4 right-0 sm:-right-4 z-20 hero-fade-in-up backdrop-blur-xl rounded-2xl px-3 py-2 shadow-xl border border-white/20">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex -space-x-2">
                                        <img src="https://i.pravatar.cc/40?img=1" class="w-9 h-9 rounded-full"
                                            alt="Customer 1">
                                        <img src="https://i.pravatar.cc/40?img=2" class="w-9 h-9 rounded-full"
                                            alt="Customer 2">
                                        <img src="https://i.pravatar.cc/40?img=3" class="w-9 h-9 rounded-full"
                                            alt="Customer 3">
                                        <img src="https://i.pravatar.cc/40?img=4" class="w-9 h-9 rounded-full"
                                            alt="Customer 4">
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 mb-1">
                                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                    </svg>
                                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                    </svg>
                                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                    </svg>
                                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                    </svg>
                                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-black">10k+ Happy Customers</p>
                            </div>

                            <!-- Booking quote overlay - top left -->
                            <div class="absolute top-24 left-0 sm:-left-8 z-20 hero-fade-in-up backdrop-blur-xl rounded-2xl px-3 py-3 shadow-xl border border-white/20"
                                style="animation-delay: 1.3s;">
                                <p class="text-xs font-medium text-black">"Can I extend for two more days??"</p>
                            </div>

                            <!-- Date range overlay - bottom -->
                            <div class="absolute -bottom-6 left-2 sm:left-8 z-20 animate-fade-in-up backdrop-blur-xl rounded-2xl px-5 py-3 shadow-xl border border-white/20"
                                style="animation-delay: 0.2s;">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-calendar w-4 h-4 text-black">
                                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                        <line x1="16" x2="16" y1="2" y2="6"></line>
                                        <line x1="8" x2="8" y1="2" y2="6"></line>
                                        <line x1="3" x2="21" y1="10" y2="10"></line>
                                    </svg>
                                    <span class="text-xs font-medium text-black">26 April, 2026 - 30 April, 2026</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-2 lg:px-8">
                <div class="text-center mb-16">
                    <div class="inline-block px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-sm font-medium mb-4">
                        FEATURES
                    </div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">Powerful features to simplify your<br>web building
                        experience</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-2 mb-16">
                    <!-- Fleet Managament Dashboard -->
                    <div
                        class="bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl border border-gray-200 hover:shadow-xl transition">
                        <div class="bg-white rounded-xl p-6 mb-6 border border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-gray-500">Performance</span>
                                <span class="text-xs font-medium text-blue-600">+25%</span>
                            </div>
                            <div class="flex items-end space-x-1 h-24">
                                <div class="w-full bg-blue-200 rounded-t" style="height: 45%"></div>
                                <div class="w-full bg-blue-300 rounded-t" style="height: 65%"></div>
                                <div class="w-full bg-blue-400 rounded-t" style="height: 75%"></div>
                                <div class="w-full bg-blue-300 rounded-t" style="height: 55%"></div>
                                <div class="w-full bg-blue-400 rounded-t" style="height: 85%"></div>
                                <div class="w-full bg-blue-500 rounded-t" style="height: 95%"></div>
                                <div class="w-full bg-blue-400 rounded-t" style="height: 70%"></div>
                                <div class="w-full bg-blue-300 rounded-t" style="height: 60%"></div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Fleet Management Dashboard</h3>
                        <p class="text-gray-600 text-sm">Boost your website's visibility with integrated SEO tools.</p>
                    </div>

                    <!-- Licence & Identity Validation -->
                    <div
                        class="bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl border border-gray-200 hover:shadow-xl transition">
                        <div
                            class="bg-white rounded-xl p-6 mb-6 border border-gray-100 relative overflow-hidden min-h-[140px] flex items-center justify-center">
                            <div
                                class="absolute top-4 left-4 w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <span class="text-lg">💰</span>
                            </div>
                            <div
                                class="absolute top-4 right-4 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-lg">💳</span>
                            </div>
                            <div
                                class="absolute bottom-4 left-8 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <span class="text-lg">⚙️</span>
                            </div>
                            <div
                                class="absolute bottom-4 right-8 w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center">
                                <span class="text-lg">📊</span>
                            </div>
                            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Automated Contracts & E-Signatures</h3>
                        <p class="text-gray-600 text-sm">easily connect with your favorite apps and services for a
                            website experience.</p>
                    </div>

                    <!-- Automated Contracts & E-Signatures -->
                    <div
                        class="bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl border border-gray-200 hover:shadow-xl transition">
                        <div
                            class="bg-white rounded-xl p-6 mb-6 border border-gray-100 min-h-[140px] flex items-center justify-center">
                            <div class="relative w-full h-full flex flex-col gap-2 p-1">
                                <div class="h-4 w-full bg-blue-50 rounded-sm"></div>
                                <div class="grid grid-cols-2 gap-2 h-16">
                                    <div class="bg-gray-50 rounded"></div>
                                    <div class="bg-gray-50 rounded"></div>
                                </div>
                                <div class="h-8 w-full bg-blue-600/10 rounded-sm mt-auto"></div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Custom Rental Website</h3>
                        <p class="text-gray-600 text-sm">Create websites that look stunning on any device.</p>
                    </div>
                </div>

            </div>

            <!-- 4 Steps Section -->
            <section
                class="flex flex-col items-center gap-8 overflow-hidden px-4 py-10 sm:gap-10 sm:py-12 md:gap-12 md:px-6 md:py-14 lg:px-10 lg:pb-0">
                <div
                    class="flex w-full max-w-[1400px] flex-col items-start gap-3 text-left lg:items-center lg:gap-4 lg:text-center">
                    <p class="text-[11px] font-bold tracking-[0.2em] uppercase [&>span]:text-[#2567ff]">HOW IT
                        <span>WORKS</span>
                    </p>
                    <h2
                        class="w-full text-4xl font-bold text-gray-900 lg:w-auto [&>span]:bg-gradient-to-r [&>span]:from-[#2567ff] [&>span]:to-[#38bdf8] [&>span]:bg-clip-text [&>span]:text-transparent">
                        From Setup to Bookings in <span>Minutes</span></h2>
                </div>

                <!-- Mobile/Tablet Layout (Single/Double Column) -->
                <div class="grid w-full max-w-[1400px] grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:hidden">
                    <div
                        class="w-full overflow-hidden rounded-2xl bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)] sm:rounded-[24px]">
                        <div
                            class="relative aspect-[16/11] w-full overflow-hidden rounded-xl bg-[#f4f4f6] sm:rounded-[18px]">
                            <img alt="setup" class="object-cover object-top absolute h-full w-full left-0 top-0"
                                src="/assets/images/step-1-design.png">
                        </div>
                        <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                            <div
                                class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" class="w-4 h-4 text-[#1a1a1a] sm:w-5 sm:h-5">
                                    <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                                    <line x1="8" x2="16" y1="21" y2="21"></line>
                                    <line x1="12" x2="12" y1="17" y2="21"></line>
                                </svg>
                                <span class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Business
                                    Console</span>
                            </div>
                            <div class="flex flex-col gap-3 text-left sm:gap-4">
                                <h3 class="text-xl font-bold text-gray-900">Create your account</h3>
                                <p class="text-[15px] text-gray-600">Sign up and instantly secure your workspace.
                                    Connect your custom domain or use a branded subdomain to keep your identity
                                    professional. Set up your business profile and local tax rules in a few clicks
                                    to build your digital storefront.</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="w-full overflow-hidden rounded-2xl bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)] sm:rounded-[24px]">
                        <div
                            class="relative aspect-[16/11] w-full overflow-hidden rounded-xl bg-[#f4f4f6] sm:rounded-[18px]">
                            <img alt="link" class="object-cover object-top absolute h-full w-full left-0 top-0"
                                src="/assets/images/step-2-integration.png">
                        </div>
                        <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                            <div
                                class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" class="w-4 h-4 text-[#1a1a1a] sm:w-5 sm:h-5">
                                    <path d="M9 17H7A5 5 0 0 1 7 7h2"></path>
                                    <path d="M15 7h2a5 5 0 1 1 0 10h-2"></path>
                                    <line x1="8" x2="16" y1="12" y2="12"></line>
                                </svg>
                                <span class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Hosted
                                    Flow / SDK</span>
                            </div>
                            <div class="flex flex-col gap-3 text-left sm:gap-4">
                                <h3 class="text-xl font-bold text-gray-900">Choose your integration</h3>
                                <p class="text-[15px] text-gray-600">Generate a hosted link and share it with your
                                    users. Or integrate Didit using our native iOS/Android SDKs, Web SDK, or embed
                                    as an iframe/webview. For full control, use our standalone APIs for
                                    server-to-server integration. You choose what works best.</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="w-full overflow-hidden rounded-2xl bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)] sm:rounded-[24px]">
                        <div
                            class="relative aspect-[16/11] w-full overflow-hidden rounded-xl bg-[#f4f4f6] sm:rounded-[18px]">
                            <img alt="results" class="object-cover object-top absolute h-full w-full left-0 top-0"
                                src="/assets/images/step-3-results.png">
                        </div>
                        <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                            <div
                                class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" class="w-4 h-4 text-[#1a1a1a] sm:w-5 sm:h-5">
                                    <path d="m16 18 6-6-6-6"></path>
                                    <path d="m8 6-6 6 6 6"></path>
                                </svg>
                                <span class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Dashboard
                                    &amp; API</span>
                            </div>
                            <div class="flex flex-col gap-3 text-left sm:gap-4">
                                <h3 class="text-xl font-bold text-gray-900">Get instant results</h3>
                                <p class="text-[15px] text-gray-600">Track verification outcomes in real time via
                                    the dashboard, webhooks, or API. All data syncs instantly with your app, CRM, or
                                    backend systems.</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="w-full overflow-hidden rounded-2xl bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)] sm:rounded-[24px]">
                        <div
                            class="relative aspect-[16/11] w-full overflow-hidden rounded-xl bg-[#f4f4f6] sm:rounded-[18px]">
                            <img alt="automate" class="object-cover object-top absolute h-full w-full left-0 top-0"
                                src="/assets/images/step-4-automation.png">
                        </div>
                        <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                            <div
                                class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" class="w-4 h-4 text-[#1a1a1a] sm:w-5 sm:h-5">
                                    <line x1="6" x2="6" y1="3" y2="15"></line>
                                    <circle cx="18" cy="6" r="3"></circle>
                                    <circle cx="6" cy="18" r="3"></circle>
                                    <path d="M18 9a9 9 0 0 1-9 9"></path>
                                </svg>
                                <span
                                    class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Automation</span>
                            </div>
                            <div class="flex flex-col gap-3 text-left sm:gap-4">
                                <h3 class="text-xl font-bold text-gray-900">Automate decisions</h3>
                                <p class="text-[15px] text-gray-600">Set up auto-approve and auto-reject rules based
                                    on verification outcomes. Let Didit handle routine decisions while your team
                                    focuses on edge cases. Scale verification without scaling your operations team.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Layout (Staggered Vertical Timeline) -->
                <div class="relative hidden w-full max-w-[900px] lg:block mt-8">
                    <div
                        class="absolute left-1/2 top-0 h-full w-px -translate-x-1/2 bg-gradient-to-b from-transparent via-[#2567ff]/20 to-transparent">
                    </div>
                    <div class="flex flex-col">
                        <!-- Step 1 (Left) -->
                        <div class="flex w-full justify-start -mb-[140px]">
                            <div
                                class="max-w-[400px] overflow-hidden rounded-[24px] bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)]">
                                <div class="relative aspect-[16/11] w-full overflow-hidden rounded-[18px] bg-[#f4f4f6]">
                                    <img alt="setup" class="object-cover object-top absolute h-full w-full left-0 top-0"
                                        src="/assets/images/step-1-design.png">
                                </div>
                                <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                                    <div
                                        class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="w-5 h-5 text-[#1a1a1a]">
                                            <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                                            <line x1="8" x2="16" y1="21" y2="21"></line>
                                            <line x1="12" x2="12" y1="17" y2="21"></line>
                                        </svg>
                                        <span class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Step
                                            1</span>
                                    </div>
                                    <div class="flex flex-col gap-3 text-left sm:gap-4">
                                        <h3 class="text-xl font-bold text-gray-900">Instant Multi-Tenant SaaS
                                            Onboarding</h3>
                                        <p class="text-[15px] text-gray-600">Register your business in seconds to
                                            access a dedicated car rental
                                            management website. Our cloud-based platform is built for scalability,
                                            allowing you to manage
                                            multiple locations and team members from one centralised, secure
                                            dashboard.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 (Right) -->
                        <div class="flex w-full justify-end -mb-[140px]">
                            <div
                                class="max-w-[400px] overflow-hidden rounded-[24px] bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)]">
                                <div class="relative aspect-[16/11] w-full overflow-hidden rounded-[18px] bg-[#f4f4f6]">
                                    <img alt="link" class="object-cover object-top absolute h-full w-full left-0 top-0"
                                        src="/assets/images/step-2-integration.png">
                                </div>
                                <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                                    <div
                                        class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="w-5 h-5 text-[#1a1a1a]">
                                            <path d="M9 17H7A5 5 0 0 1 7 7h2"></path>
                                            <path d="M15 7h2a5 5 0 1 1 0 10h-2"></path>
                                            <line x1="8" x2="16" y1="12" y2="12"></line>
                                        </svg>
                                        <span class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Step
                                            2</span>
                                    </div>
                                    <div class="flex flex-col gap-3 text-left sm:gap-4">
                                        <h3 class="text-xl font-bold text-gray-900">Connect your domain</h3>
                                        <p class="text-[15px] text-gray-600">Build trust with a white-label booking
                                            system.
                                            Connect your own custom domain so every customer interaction, from
                                            digital contracts to booking confirmations, comes directly from your
                                            professional brand identity.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 (Left) -->
                        <div class="flex w-full justify-start -mb-[140px]">
                            <div
                                class="max-w-[400px] overflow-hidden rounded-[24px] bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)]">
                                <div class="relative aspect-[16/11] w-full overflow-hidden rounded-[18px] bg-[#f4f4f6]">
                                    <img alt="results"
                                        class="object-cover object-top absolute h-full w-full left-0 top-0"
                                        src="/assets/images/step-3-results.png">
                                </div>
                                <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                                    <div
                                        class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="w-5 h-5 text-[#1a1a1a]">
                                            <path d="m16 18 6-6-6-6"></path>
                                            <path d="m8 6-6 6 6 6"></path>
                                        </svg>
                                        <span class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Step
                                            3</span>
                                    </div>
                                    <div class="flex flex-col gap-3 text-left sm:gap-4">
                                        <h3 class="text-xl font-bold text-gray-900">Automated Vehicle Fleet
                                            Management</h3>
                                        <p class="text-[15px] text-gray-600">Effortlessly catalog your inventory
                                            with our bulk fleet upload tool.
                                            Add detailed vehicle specifications, manage availability calendars, and
                                            upload high-resolution photos
                                            for an optimised online car rental storefront that converts visitors
                                            into customers.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 (Right) -->
                        <div class="flex w-full justify-end">
                            <div
                                class="max-w-[400px] overflow-hidden rounded-[24px] bg-white p-1.5 shadow-[0px_4px_12px_0px_rgba(120,120,128,0.16)] mb-4">
                                <div class="relative aspect-[16/11] w-full overflow-hidden rounded-[18px] bg-[#f4f4f6]">
                                    <img alt="automate"
                                        class="object-cover object-top absolute h-full w-full left-0 top-0"
                                        src="/assets/images/step-4-automation.png">
                                </div>
                                <div class="flex flex-col gap-2.5 px-3 py-3 sm:gap-3 sm:px-3.5 sm:py-4">
                                    <div
                                        class="flex w-fit items-center gap-1.5 rounded-full bg-[rgba(120,120,128,0.05)] py-1.5 pl-2 pr-2.5 sm:gap-2 sm:py-2 sm:pl-2.5 sm:pr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="w-5 h-5 text-[#1a1a1a]">
                                            <line x1="6" x2="6" y1="3" y2="15"></line>
                                            <circle cx="18" cy="6" r="3"></circle>
                                            <circle cx="6" cy="18" r="3"></circle>
                                            <path d="M18 9a9 9 0 0 1-9 9"></path>
                                        </svg>
                                        <span class="text-[11px] font-bold tracking-wider uppercase text-[#2567ff]">Step
                                            4</span>
                                    </div>
                                    <div class="flex flex-col gap-3 text-left sm:gap-4">
                                        <h3 class="text-xl font-bold text-gray-900">Start receiving bookings</h3>
                                        <p class="text-[15px] text-gray-600">Launch your fully automated rental
                                            platform in under 30 minutes.
                                            Start accepting real-time bookings with integrated identity verification
                                            and digital e-signature contracts,
                                            ensuring every rental is secure, documented, and ready for pickup.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div> <!-- End hero-features-gradient -->

    <!-- Core Features Section -->
    <div class="py-20 bg-white">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-8 items-start">
                <!-- Left side - Feature grid (5 columns) -->
                <div class="lg:col-span-5">
                    <div class="grid grid-cols-3 gap-3">
                        <!-- ID Verification -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/id-verification.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">ID Verification</p>
                        </div>

                        <!-- Booking Calendar -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/booking-calendar.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">Booking Calendar</p>
                        </div>

                        <!-- Visual Condition Reports -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/visual-condition-reports.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">Vehicle Photo Logs</p>
                        </div>

                        <!-- E-Signature -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/e-signature.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">E-Signature</p>
                        </div>

                        <!-- Dynamic Pricing -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/dynamic-pricing.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">Dynamic Pricing</p>
                        </div>

                        <!-- Website Builder -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/website-builder.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">Website Builder</p>
                        </div>

                        <!-- Vehicle Management -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/vehicle-management.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">Vehicle Management</p>
                        </div>

                        <!-- Customer Portal -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/customer-portal.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">Customer Portal</p>
                        </div>

                        <!-- Stripe Integration -->
                        <div class="bg-white rounded-lg px-5 py-2 text-center hover:border-blue-500 transition border border-gray-200 cursor-pointer"
                            onclick="window.location.href='/features/stripe-integration.php'">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                            <p class="text-xs font-medium text-gray-700">Stripe Integration</p>
                        </div>
                    </div>
                </div>

                <!-- Right side - Content (7 columns) -->
                <div class="lg:col-span-7 lg:pl-8">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-sm font-medium mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                            </path>
                        </svg>
                        ALL-IN-ONE PLATFORM
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6 leading-tight">Everything you need
                        to run your rental business.</h2>
                    <p class="text-lg text-gray-600 leading-relaxed">
                        Manage bookings, vehicles, contracts, and payments all in one place. Verify customer
                        identities, document vehicle conditions, accept online reservations 24/7, collect
                        e-signatures, process payments with Stripe, and give your customers a beautiful self-service
                        portal. Your complete rental management solution with dynamic pricing and automated
                        workflows.
                    </p>
                </div>
            </div>
        </div>
        <!-- Pricing Cards -->
        <section class="py-20 bg-white">
            <?php include __DIR__ . '/includes/pricing-grid.php'; ?>
        </section>

        <!-- Reviews Section -->
        <section
            class="flex flex-col items-center gap-10 px-4 py-10 sm:gap-12 sm:py-12 md:px-6 md:py-14 lg:gap-12 lg:px-10 lg:py-16">
            <div class="flex w-full flex-col items-start gap-3 text-left lg:items-center lg:gap-4 lg:text-center">
                <p class="text-[11px] font-bold tracking-[0.2em] uppercase [&>span]:text-[#2567ff]">TRUSTED
                    <span>WORLDWIDE</span>
                </p>
                <h2
                    class="w-full text-4xl font-bold text-gray-900 lg:w-auto [&>span]:bg-gradient-to-r [&>span]:from-[#2567ff] [&>span]:to-[#38bdf8] [&>span]:bg-clip-text [&>span]:text-transparent">
                    What our <span>customers</span> say</h2>
                <p class="w-full text-[15px] text-gray-600 lg:max-w-[600px] lg:text-center">Join thousands of companies
                    that trust Fleet Rental Pro for their fleet management needs</p>
            </div>
            <div class="flex w-full max-w-[1400px] flex-col gap-6">
                <div id="reviewsGrid" class="flex w-full snap-x snap-mandatory gap-4 overflow-x-auto pb-2 sm:gap-5"
                    style="scrollbar-width: none; -ms-overflow-style: none;">
                    <!-- Review Card 1 -->
                    <div data-testimonial-card
                        class="group flex min-h-[280px] w-[300px] shrink-0 snap-start flex-col justify-between rounded-2xl border border-[#e5e5e5] bg-white p-6 transition-all duration-300 hover:border-[#2567ff]/20 hover:shadow-[0px_8px_24px_0px_rgba(37,103,255,0.08)] sm:min-h-[300px] sm:w-[340px]">
                        <div class="flex items-start justify-between">
                            <div class="relative h-8 w-24 sm:h-10 sm:w-28 flex items-center">
                                <span class="text-lg font-bold text-gray-800">Enterprise Co</span>
                            </div>
                            <div class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="size-4 text-[#9da1a1]">
                                    <path
                                        d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                    <path
                                        d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 py-4">
                            <p class="text-[15px] leading-[1.6] tracking-[-0.3px] text-[#4b5058] sm:text-[16px]">Fleet
                                Rental Pro transformed our operations. The booking system is intuitive and the ID
                                verification gives us complete peace of mind.</p>
                        </div>
                        <div class="flex flex-col gap-1 border-t border-[#e5e5e5] pt-4">
                            <p class="text-[15px] font-semibold tracking-[-0.3px] text-[#1a1a1a] sm:text-[16px]">Sarah
                                Johnson</p>
                            <p class="text-[13px] tracking-[-0.2px] text-[#6e6e73] sm:text-[14px]">Fleet Manager at
                                Enterprise Co</p>
                        </div>
                    </div>

                    <!-- Review Card 2 -->
                    <div data-testimonial-card
                        class="group flex min-h-[280px] w-[300px] shrink-0 snap-start flex-col justify-between rounded-2xl border border-[#e5e5e5] bg-white p-6 transition-all duration-300 hover:border-[#2567ff]/20 hover:shadow-[0px_8px_24px_0px_rgba(37,103,255,0.08)] sm:min-h-[300px] sm:w-[340px]">
                        <div class="flex items-start justify-between">
                            <div class="relative h-8 w-24 sm:h-10 sm:w-28 flex items-center">
                                <span class="text-lg font-bold text-gray-800">AutoRent</span>
                            </div>
                            <div class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="size-4 text-[#9da1a1]">
                                    <path
                                        d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                    <path
                                        d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 py-4">
                            <p class="text-[15px] leading-[1.6] tracking-[-0.3px] text-[#4b5058] sm:text-[16px]">The
                                condition reports feature has saved us countless disputes. Visual documentation before
                                and after every rental is a game-changer.</p>
                        </div>
                        <div class="flex flex-col gap-1 border-t border-[#e5e5e5] pt-4">
                            <p class="text-[15px] font-semibold tracking-[-0.3px] text-[#1a1a1a] sm:text-[16px]">Michael
                                Chen</p>
                            <p class="text-[13px] tracking-[-0.2px] text-[#6e6e73] sm:text-[14px]">Operations Director
                                at AutoRent</p>
                        </div>
                    </div>

                    <!-- Review Card 3 -->
                    <div data-testimonial-card
                        class="group flex min-h-[280px] w-[300px] shrink-0 snap-start flex-col justify-between rounded-2xl border border-[#e5e5e5] bg-white p-6 transition-all duration-300 hover:border-[#2567ff]/20 hover:shadow-[0px_8px_24px_0px_rgba(37,103,255,0.08)] sm:min-h-[300px] sm:w-[340px]">
                        <div class="flex items-start justify-between">
                            <div class="relative h-8 w-24 sm:h-10 sm:w-28 flex items-center">
                                <span class="text-lg font-bold text-gray-800">CityDrive</span>
                            </div>
                            <div class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="size-4 text-[#9da1a1]">
                                    <path
                                        d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                    <path
                                        d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 py-4">
                            <p class="text-[15px] leading-[1.6] tracking-[-0.3px] text-[#4b5058] sm:text-[16px]">Dynamic
                                pricing and automated contracts have streamlined our entire workflow. We've reduced
                                admin time by 60%.</p>
                        </div>
                        <div class="flex flex-col gap-1 border-t border-[#e5e5e5] pt-4">
                            <p class="text-[15px] font-semibold tracking-[-0.3px] text-[#1a1a1a] sm:text-[16px]">Emma
                                Williams</p>
                            <p class="text-[13px] tracking-[-0.2px] text-[#6e6e73] sm:text-[14px]">CEO at CityDrive</p>
                        </div>
                    </div>

                    <!-- Review Card 4 -->
                    <div data-testimonial-card
                        class="group flex min-h-[280px] w-[300px] shrink-0 snap-start flex-col justify-between rounded-2xl border border-[#e5e5e5] bg-white p-6 transition-all duration-300 hover:border-[#2567ff]/20 hover:shadow-[0px_8px_24px_0px_rgba(37,103,255,0.08)] sm:min-h-[300px] sm:w-[340px]">
                        <div class="flex items-start justify-between">
                            <div class="relative h-8 w-24 sm:h-10 sm:w-28 flex items-center">
                                <span class="text-lg font-bold text-gray-800">FleetHub</span>
                            </div>
                            <div class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="size-4 text-[#9da1a1]">
                                    <path
                                        d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                    <path
                                        d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 py-4">
                            <p class="text-[15px] leading-[1.6] tracking-[-0.3px] text-[#4b5058] sm:text-[16px]">The
                                Stripe integration made payment processing seamless. Our customers love the smooth
                                checkout experience.</p>
                        </div>
                        <div class="flex flex-col gap-1 border-t border-[#e5e5e5] pt-4">
                            <p class="text-[15px] font-semibold tracking-[-0.3px] text-[#1a1a1a] sm:text-[16px]">James
                                Martinez</p>
                            <p class="text-[13px] tracking-[-0.2px] text-[#6e6e73] sm:text-[14px]">COO at FleetHub</p>
                        </div>
                    </div>

                    <!-- Review Card 5 -->
                    <div data-testimonial-card
                        class="group flex min-h-[280px] w-[300px] shrink-0 snap-start flex-col justify-between rounded-2xl border border-[#e5e5e5] bg-white p-6 transition-all duration-300 hover:border-[#2567ff]/20 hover:shadow-[0px_8px_24px_0px_rgba(37,103,255,0.08)] sm:min-h-[300px] sm:w-[340px]">
                        <div class="flex items-start justify-between">
                            <div class="relative h-8 w-24 sm:h-10 sm:w-28 flex items-center">
                                <span class="text-lg font-bold text-gray-800">VehicleShare</span>
                                ƒƒ
                            </div>
                            <div class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="size-4 text-[#9da1a1]">
                                    <path
                                        d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                    <path
                                        d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 py-4">
                            <p class="text-[15px] leading-[1.6] tracking-[-0.3px] text-[#4b5058] sm:text-[16px]">
                                Customer portal is fantastic. Renters can manage everything themselves, which has
                                significantly reduced our support tickets.</p>
                        </div>
                        <div class="flex flex-col gap-1 border-t border-[#e5e5e5] pt-4">
                            <p class="text-[15px] font-semibold tracking-[-0.3px] text-[#1a1a1a] sm:text-[16px]">Lisa
                                Anderson</p>
                            <p class="text-[13px] tracking-[-0.2px] text-[#6e6e73] sm:text-[14px]">Customer Success Lead
                                at VehicleShare</p>
                        </div>
                    </div>

                    <!-- Review Card 6 -->
                    <div data-testimonial-card
                        class="group flex min-h-[280px] w-[300px] shrink-0 snap-start flex-col justify-between rounded-2xl border border-[#e5e5e5] bg-white p-6 transition-all duration-300 hover:border-[#2567ff]/20 hover:shadow-[0px_8px_24px_0px_rgba(37,103,255,0.08)] sm:min-h-[300px] sm:w-[340px]">
                        <div class="flex items-start justify-between">
                            <div class="relative h-8 w-24 sm:h-10 sm:w-28 flex items-center">
                                <span class="text-lg font-bold text-gray-800">RentWise</span>
                            </div>
                            <div class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="size-4 text-[#9da1a1]">
                                    <path
                                        d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                    <path
                                        d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 py-4">
                            <p class="text-[15px] leading-[1.6] tracking-[-0.3px] text-[#4b5058] sm:text-[16px]">Best
                                investment we've made. The platform is reliable, feature-rich, and the support team is
                                always responsive.</p>
                        </div>
                        <div class="flex flex-col gap-1 border-t border-[#e5e5e5] pt-4">
                            <p class="text-[15px] font-semibold tracking-[-0.3px] text-[#1a1a1a] sm:text-[16px]">Robert
                                Taylor</p>
                            <p class="text-[13px] tracking-[-0.2px] text-[#6e6e73] sm:text-[14px]">Founder at RentWise
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-3">
                    <button onclick="scrollTestimonials('prev')" aria-label="Previous slide"
                        class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7] text-[#1a1a1a] transition-all duration-200 hover:bg-[#ebebed] disabled:cursor-not-allowed disabled:opacity-40">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="size-5">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                    </button>
                    <button onclick="scrollTestimonials('next')" aria-label="Next slide"
                        class="flex size-10 items-center justify-center rounded-full bg-[#f5f5f7] text-[#1a1a1a] transition-all duration-200 hover:bg-[#ebebed] disabled:cursor-not-allowed disabled:opacity-40">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="size-5">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <script>
            let currentTestimonialIndex = 0;
            let testimonialInterval;

            function scrollTestimonials(direction) {
                const container = document.querySelector('.flex.w-full.snap-x');
                const cards = container.querySelectorAll('[data-testimonial-card]');
                if (!cards.length) return;

                if (direction === 'next') {
                    currentTestimonialIndex++;
                    if (currentTestimonialIndex >= cards.length) {
                        currentTestimonialIndex = 0;
                    }
                } else if (direction === 'prev') {
                    currentTestimonialIndex--;
                    if (currentTestimonialIndex < 0) {
                        currentTestimonialIndex = cards.length - 1;
                    }
                }

                // Use scrollTo on the container only to avoid window jumping
                const targetCard = cards[currentTestimonialIndex];
                container.scrollTo({
                    left: targetCard.offsetLeft - container.offsetLeft,
                    behavior: 'smooth'
                });
            }

            // Only auto-loop when testimonials are in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (!testimonialInterval) {
                            testimonialInterval = setInterval(() => {
                                scrollTestimonials('next');
                            }, 5000);
                        }
                    } else {
                        clearInterval(testimonialInterval);
                        testimonialInterval = null;
                    }
                });
            }, { threshold: 0.1 });

            const testimonialsSection = document.querySelector('#reviewsGrid');
            if (testimonialsSection) observer.observe(testimonialsSection);

            // Reset timer on manual click
            document.querySelectorAll('button[onclick^="scrollTestimonials"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    clearInterval(testimonialInterval);
                    if (testimonialInterval) {
                        testimonialInterval = setInterval(() => {
                            scrollTestimonials('next');
                        }, 5000);
                    }
                });
            });
        </script>

        <!-- FAQ Section -->
        <section
            class="flex flex-col max-w-7xl mx-auto items-center gap-10 px-4 py-10 sm:gap-12 sm:py-12 md:px-6 md:py-14 lg:gap-12 lg:px-10 lg:py-16 bg-gray-50">
            <div class="flex w-full flex-col items-start gap-3 text-left lg:items-center lg:gap-4 lg:text-center">
                <p class="text-[11px] font-bold tracking-[0.2em] uppercase [&>span]:text-[#2567ff]"><span>FAQ</span></p>
                <h2
                    class="w-full text-4xl font-bold text-gray-900 lg:w-auto [&>span]:bg-gradient-to-r [&>span]:from-[#2567ff] [&>span]:to-[#38bdf8] [&>span]:bg-clip-text [&>span]:text-transparent">
                    <span>Frequently</span> Asked Questions
                </h2>
                <p class="w-full text-[15px] text-gray-600 lg:max-w-[600px] lg:text-center">Everything you need to know
                    about Fleet Rental Pro, pricing, and integration.</p>
            </div>
            <div class="flex w-full max-w-[1400px] flex-col" id="faqAccordion">
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(0)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">What
                            is Fleet Rental Pro?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Fleet Rental Pro is a
                            comprehensive car rental management platform that helps you manage your entire fleet,
                            bookings, customers, and operations from one centralised dashboard.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(1)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Is
                            there a free trial?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Yes! We offer a 30-day
                            free trial with full access to all features. No credit card required to start.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(2)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">How
                            does ID verification work?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Our ID verification
                            system uses advanced AI to verify driver's licences and government IDs in real-time.
                            Customers simply upload a photo of their ID, and our system validates authenticity within
                            seconds.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(3)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Can
                            I customise the booking calendar?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Absolutely! You can
                            customise availability, blackout dates, minimum rental periods, buffer times between
                            bookings, and more to match your business needs.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(4)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">How
                            long does integration take?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Most businesses are up
                            and running within 24 hours. Our team provides white-glove onboarding support to help you
                            migrate data and configure your system.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(5)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">What
                            payment methods are supported?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">We support all major
                            credit cards, debit cards, and digital wallets through Stripe integration. You can also
                            accept cash payments and track them in the system.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(6)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Do
                            you offer customer support?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Yes! We provide 24/7
                            email support and live chat during business hours. Premium plans include phone support and a
                            dedicated account manager.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(7)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Can
                            I manage multiple locations?</span>
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                                <path d="m18 15-6-6-6 6"></path>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-content hidden overflow-hidden">
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Yes! Our platform
                            supports multi-location management with separate inventories, pricing, and staff permissions
                            for each location, all from one dashboard.</p>
                    </div>
                </div>
            </div>
        </section>

        <script>
            function toggleFaq(index) {
                const allContents = document.querySelectorAll('.faq-content');
                const allIcons = document.querySelectorAll('.faq-icon');
                const clickedContent = allContents[index];
                const clickedIcon = allIcons[index];

                // Close all other FAQs
                allContents.forEach((content, i) => {
                    if (i !== index && !content.classList.contains('hidden')) {
                        content.classList.add('hidden');
                        allIcons[i].style.transform = 'rotate(0deg)';
                    }
                });

                // Toggle clicked FAQ
                if (clickedContent.classList.contains('hidden')) {
                    clickedContent.classList.remove('hidden');
                    clickedIcon.style.transform = 'rotate(180deg)';
                } else {
                    clickedContent.classList.add('hidden');
                    clickedIcon.style.transform = 'rotate(0deg)';
                }
            }
        </script>


        <!-- Final Call to Action -->
        <div class="max-w-[1400px] mx-auto px-4 py-24 mb-10">
            <div
                class="relative overflow-hidden bg-white border border-gray-100 rounded-[35px] sm:rounded-[50px] p-12 md:p-24 text-center group">
                <!-- Animated Background Rings -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden">
                    <div class="absolute w-[800px] h-[800px] border border-blue-50 rounded-full"></div>
                    <div
                        class="absolute w-[600px] h-[600px] border border-dashed border-blue-50/70 rounded-full animate-[spin_60s_linear_infinite]">
                    </div>
                    <div
                        class="absolute w-[450px] h-[450px] rounded-full bg-gradient-to-b from-blue-50/30 to-purple-50/30 opacity-60">
                    </div>
                    <div
                        class="absolute left-1/4 top-1/2 -translate-y-1/2 w-3 h-3 bg-blue-100 rounded-full blur-[2px] shadow-[0_0_10px_rgba(37,103,255,0.2)]">
                    </div>
                    <div
                        class="absolute right-1/4 top-1/2 -translate-y-1/2 w-3 h-3 bg-purple-100 rounded-full blur-[2px] shadow-[0_0_10px_rgba(168,85,247,0.2)]">
                    </div>
                </div>

                <div class="relative z-10 max-w-3xl mx-auto">
                    <h2 class="text-4xl md:text-5xl lg:text-6xl text-gray-900 mb-6 leading-[1.1] tracking-tight">
                        Your rental management<br class="hidden md:block"> shouldn't be a chore.
                    </h2>
                    <p class="text-gray-500 mb-12 text-lg md:text-xl font-medium hidden sm:block">
                        Let us show you a better way to scale your fleet.
                    </p>
                    <p class="text-gray-500 mb-12 text-lg sm:hidden">
                        Let us show you a better way.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-8">
                        <a href="#demo"
                            class="px-10 py-4 mb-4 sm:mb-0 bg-[#2d2d2d] text-white rounded-full font-bold text-lg hover:bg-black transition-all shadow-[0_10px_30px_-10px_rgba(0,0,0,0.3)] hover:scale-105 active:scale-95">
                            Book demo
                        </a>
                        <a href="#tour"
                            class="flex items-center gap-3 text-gray-500 font-bold hover:text-gray-900 transition-colors group">
                            Take a tour
                            <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-300 py-12">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8">
                    <div>
                        <div class="text-white text-xl font-bold mb-4">
                            <?= SITE_NAME?>
                        </div>
                        <p class="text-sm">Complete car rental management platform for modern businesses.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Product</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#features" class="hover:text-white">Features</a></li>
                            <li><a href="#pricing" class="hover:text-white">Pricing</a></li>
                            <li><a href="/blog" class="hover:text-white">Blog</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Company</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white">About</a></li>
                            <li><a href="#" class="hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm">
                    <p>&copy;
                        <?= date('Y')?>
                        <?= SITE_NAME?>. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>

        <script>
            // Reviews Carousel for Mobile
            function initReviewsCarousel() {
                const reviewsGrid = document.getElementById('reviewsGrid');
                if (!reviewsGrid) return;

                function setupCarousel() {
                    const isMobile = window.innerWidth <= 768;

                    if (isMobile) {
                        const reviewCards = Array.from(reviewsGrid.querySelectorAll('[data-testimonial-card]'));
                        if (!reviewCards.length) return;

                        // Clear existing structure
                        reviewsGrid.innerHTML = '';

                        // Split reviews into 2 rows
                        const midPoint = Math.ceil(reviewCards.length / 2);
                        const row1Cards = reviewCards.slice(0, midPoint);
                        const row2Cards = reviewCards.slice(midPoint);

                        // Create row 1
                        const row1 = document.createElement('div');
                        row1.className = 'reviews-carousel-row';
                        row1Cards.forEach(card => row1.appendChild(card.cloneNode(true)));
                        // Duplicate for seamless loop
                        row1Cards.forEach(card => row1.appendChild(card.cloneNode(true)));

                        // Create row 2
                        const row2 = document.createElement('div');
                        row2.className = 'reviews-carousel-row';
                        row2Cards.forEach(card => row2.appendChild(card.cloneNode(true)));
                        // Duplicate for seamless loop
                        row2Cards.forEach(card => row2.appendChild(card.cloneNode(true)));

                        reviewsGrid.appendChild(row1);
                        reviewsGrid.appendChild(row2);
                    } else {
                        // Desktop: restore original structure if needed
                        const hasCarouselRows = reviewsGrid.querySelector('.reviews-carousel-row');
                        if (hasCarouselRows) {
                            location.reload(); // Simple reload to restore original structure
                        }
                    }
                }

                setupCarousel();

                // Re-setup on resize with debounce
                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(setupCarousel, 250);
                });
            }

            // Initialise on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initReviewsCarousel);
            } else {
                initReviewsCarousel();
            }
        </script>
</body>

</html>