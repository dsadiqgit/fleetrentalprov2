<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal -
        <?= SITE_NAME?>
    </title>
    <link rel="preload" href="/public/font/Inter_Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_Bold.ttf" as="font" type="font/ttf" crossorigin>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="stylesheet" href="/public/css/blog.css">
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-[#fafafa]" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="relative bg-[#0b1120] overflow-hidden pt-24 pb-32">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=2000"
                alt="Condition Reports Hero" class="w-full h-full object-cover opacity-30"
                onerror="this.src='https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=2000'">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/80 to-transparent"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <div
                    class="inline-flex items-center space-x-2 bg-white/10 rounded-full px-3 py-1 mb-6 border border-white/20">
                    <span class="flex h-2 w-2 rounded-full bg-blue-400"></span>
                    <span class="text-sm font-medium text-blue-100">Tenant Experience</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight tracking-tight">A World-Class
                    Customer Dashboard.</h1>
                <p class="text-xl text-gray-300 mb-10 leading-relaxed">Give your renters total autonomy. A fully
                    white-labeled portal where tenants can modify bookings, download invoices, and handle check-ins
                    themselves.</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="/auth/signup.php"
                        class="inline-flex justify-center items-center px-8 py-3.5 border border-transparent text-base font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition shadow-lg shadow-blue-600/20">
                        Start free trial
                    </a>
                    <a href="/contact.php"
                        class="inline-flex justify-center items-center px-8 py-3.5 border border-white/20 text-base font-semibold rounded-lg text-white hover:bg-white/10 transition backdrop-blur-sm">
                        Book a demo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Trust Row -->
    <div class="border-b border-gray-200 bg-white">
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-gray-100">
                <div class="p-4">
                    <div class="text-3xl font-bold text-gray-900 mb-1">4.9/5</div>
                    <div class="text-sm font-medium text-gray-500">Trustpilot Rating</div>
                </div>
                <div class="p-4">
                    <div class="text-3xl font-bold text-gray-900 mb-1">10M+</div>
                    <div class="text-sm font-medium text-gray-500">Condition Reports Logged</div>
                </div>
                <div class="p-4">
                    <div class="text-3xl font-bold text-gray-900 mb-1">300+</div>
                    <div class="text-sm font-medium text-gray-500">Fleet Fleets Protected</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wide Metrics Card -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="bg-[#0b1120] rounded-3xl p-10 md:p-14 text-white shadow-2xl overflow-hidden relative">
            <div class="absolute right-0 top-0 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl -mr-32 -mt-32"></div>
            <div class="relative z-10">
                <div class="text-sm font-medium text-blue-400 mb-4 tracking-wider uppercase">Scale & Performance</div>
                <h2 class="text-3xl md:text-4xl font-bold mb-12 max-w-2xl">Empowering modern self-service rentals.</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 border-t border-white/10 pt-8">
                    <div>
                        <div class="text-4xl font-bold mb-2">
                            < 1min</div>
                                <div class="text-gray-400 text-sm">Average check-in time</div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold mb-2">300+</div>
                            <div class="text-gray-400 text-sm">Enterprise fleets supported</div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold mb-2">99.99%</div>
                            <div class="text-gray-400 text-sm">Uptime for media storage</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Text & Bullets Split -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6 tracking-tight">Empower. Automate.<br><span
                            class="text-blue-600">Delight.</span></h2>
                    <p class="text-xl text-gray-600 leading-relaxed mb-6">Reduce your operational overhead by allowing
                        customers to securely manage their own rental lifecycle without ever needing to call your front
                        desk.</p>
                    <a href="/features/booking-calendar.php"
                        class="text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center group">
                        Explore booking integrations
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                </div>
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-bold text-gray-900">Digital Document Hub</h4>
                            <p class="mt-1 text-gray-600">Securely host rental agreements and condition reports for
                                instant renter access.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-bold text-gray-900">Cryptographic Timestamping</h4>
                            <p class="mt-1 text-gray-600">Prevent photo manipulation with server-side time and location
                                tagging.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-bold text-gray-900">Automated Dispatch Blocking</h4>
                            <p class="mt-1 text-gray-600">Require condition sign-offs before the digital key or booking
                                is approved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Showcase Focus -->
        <div class="bg-white py-24 border-t border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-blue-600 font-semibold tracking-wide uppercase text-sm">Workflow
                        Integration</span>
                    <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Seamless Self Check-in</h2>
                    <p class="text-xl text-gray-600">Transform your fleet into a contactless operation. Renters verify
                        their identity and unlock vehicles directly from the portal.</p>
                </div>

                <div class="bg-[#0b1120] rounded-3xl p-8 lg:p-12 overflow-hidden relative shadow-2xl">
                    <div class="grid lg:grid-cols-2 gap-12 items-center">
                        <div class="relative z-10 text-white">
                            <h3 class="text-3xl font-bold mb-6">Seamless Self Check-in</h3>
                            <p class="text-gray-400 mb-8 max-w-md text-lg">Transform your fleet into a contactless
                                operation. Renters verify their identity and unlock vehicles directly from the portal.
                            </p>
                            <ul class="space-y-4">
                                <li class="flex items-center text-gray-300">
                                    <svg class="w-5 h-5 text-blue-400 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg> Virtual key deployment (if integrated)
                                </li>
                                <li class="flex items-center text-gray-300">
                                    <svg class="w-5 h-5 text-blue-400 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Low-light detection warnings
                                </li>
                                <li class="flex items-center text-gray-300">
                                    <svg class="w-5 h-5 text-blue-400 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Damage zooming & notation
                                </li>
                            </ul>
                        </div>
                        <div class="relative h-[400px] flex items-center justify-center">
                            <!-- Mockup illustration inside the dark box -->
                            <div
                                class="absolute inset-0 bg-gradient-to-tr from-blue-600/20 to-purple-600/20 rounded-2xl border border-white/10 backdrop-blur-sm p-6 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="h-4 bg-white/20 rounded w-1/3"></div>
                                    <div class="h-4 bg-white/20 rounded w-1/4"></div>
                                </div>
                                <div
                                    class="bg-black/40 rounded-xl p-4 border border-white/5 h-48 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="w-full bg-blue-500 rounded-lg h-10 mt-4 flex items-center justify-center text-white font-sm font-medium">
                                    Access Dashboard</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4-Card Grid -->
        <div class="bg-[#fafafa] py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Everything your customer needs</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div
                            class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Payment Management</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">Tenants can securely update expired credit
                            cards or settle outstanding tolls and balances autonomously.</p>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div
                            class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Immutable Timestamps</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">Exact millisecond logs and GPS location data
                            embedded to prevent liability-shifting metadata fraud.</p>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div
                            class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Digital Sign-off</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">Both tenant and fleet manager input secure
                            e-signatures authenticating the accepted visual state.</p>
                    </div>
                    <!-- Card 4 -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div
                            class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Instant Claims Export</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">Instantly generate PDF incident reports
                            complete with before/after comparisons for insurance claims.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Tab Split Section -->
        <div class="bg-white py-24" id="interactive-tabs-1a8d87e80c6742036b1d4587c3655e8d">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-16">Customer Portal Configuration</h2>
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-24 items-center">

                    <!-- Left Dynamic Visual Window -->
                    <div
                        class="relative w-full h-[450px] rounded-[2rem] overflow-hidden shadow-2xl transition-all duration-700 ease-in-out tab-visual-bg bg-gray-900">
                        <!-- Background Images -->
                        <div class="absolute inset-0 tab-visual-img transition-opacity duration-700 opacity-100"
                            style="background-image: url('https://images.unsplash.com/photo-1616469829581-73993eb86b02?auto=format&fit=crop&q=80&w=800'); background-size: cover; background-position: center;">
                            <div class="absolute inset-0 bg-gray-900/60 mix-blend-multiply"></div>
                        </div>

                        <div class="relative z-10 p-10 flex flex-col justify-end h-full text-white">
                            <div
                                class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-md border border-white/20 tab-visual-icon transition-transform duration-500 hover:scale-110">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-3xl font-bold mb-3 tab-visual-title tracking-tight">Portal Interaction</h3>
                            <p class="text-gray-200 text-lg leading-relaxed tab-visual-desc">Give renters absolute
                                freedom to govern their own bookings, reducing reliance on your front desk and lowering
                                administrative latency.</p>
                        </div>
                    </div>

                    <!-- Right Interactive Timeline -->
                    <div class="flex flex-col space-y-2 relative">
                        <!-- Continuous Timeline Line -->
                        <div class="absolute left-6 top-10 bottom-10 w-0.5 bg-gray-100"></div>
                        <!-- Interactive Step 0 -->
                        <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-gray-50 group tab-trigger"
                            data-idx="0"
                            data-image="https://images.unsplash.com/photo-1616469829581-73993eb86b02?auto=format&fit=crop&q=80&w=800">

                            <div
                                class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-blue-600">
                            </div>

                            <div class="ml-6 flex-1">
                                <h4
                                    class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">
                                    1. Magic Link Access</h4>
                                <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">
                                    Following a booking, the customer receives a secure, passwordless magic link to
                                    their profile.</p>
                            </div>
                        </div> <!-- Interactive Step 1 -->
                        <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-gray-50 group tab-trigger"
                            data-idx="1"
                            data-image="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=800">

                            <div
                                class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-blue-600">
                            </div>

                            <div class="ml-6 flex-1">
                                <h4
                                    class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">
                                    2. Pre-Check Preparation</h4>
                                <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">They
                                    sign the waiver, upload their license, and input payment details before they ever
                                    arrive.</p>
                            </div>
                        </div> <!-- Interactive Step 2 -->
                        <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-gray-50 group tab-trigger"
                            data-idx="2"
                            data-image="https://images.unsplash.com/photo-1512428559087-560fa5ceab42?auto=format&fit=crop&q=80&w=800">

                            <div
                                class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-blue-600">
                            </div>

                            <div class="ml-6 flex-1">
                                <h4
                                    class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">
                                    3. Active Rental Dashboard</h4>
                                <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">During
                                    the rental, the portal transforms to show extension options, roadside assistance
                                    buttons, and return instructions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .tab-trigger.active .tab-title {
                    color: #2563eb;
                }

                .tab-trigger.active .tab-text {
                    color: #4b5563;
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const section = document.getElementById('interactive-tabs-1a8d87e80c6742036b1d4587c3655e8d');
                    if (!section) return;

                    const triggers = section.querySelectorAll('.tab-trigger');
                    const visualImg = section.querySelector('.tab-visual-img');

                    function activateTab(index) {
                        triggers.forEach((t, i) => {
                            const dot = t.querySelector('.tab-dot');
                            if (i === index) {
                                t.classList.add('active', 'bg-blue-50/50');
                                t.classList.remove('hover:bg-gray-50');
                                dot.classList.replace('border-gray-200', 'border-blue-600');
                                dot.classList.add('scale-125');

                                // Visual transition
                                visualImg.style.opacity = '0';
                                setTimeout(() => {
                                    visualImg.style.backgroundImage = `url('${t.dataset.image}')`;
                                    visualImg.style.opacity = '1';
                                }, 350);

                            } else {
                                t.classList.remove('active', 'bg-blue-50/50');
                                t.classList.add('hover:bg-gray-50');
                                dot.classList.replace('border-blue-600', 'border-gray-200');
                                dot.classList.remove('scale-125');
                            }
                        });
                    }

                    triggers.forEach((trigger, idx) => {
                        trigger.addEventListener('click', () => {
                            activateTab(idx);
                        });
                    });

                    // Init
                    visualImg.style.transition = 'opacity 0.35s ease-in-out';
                    activateTab(0);
                });
            </script>
        </div>

        <!-- Testimonials / Quote Grid -->
        <div class="bg-gray-50 py-20 border-t border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Built for the operations of modern fleets
                </h2>
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex text-yellow-400 mb-4">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                        </div>
                        <p class="text-gray-800 italic mb-6">"Since rolling out FleetRentalPro's condition checks, our
                            damage claim payouts from customers resolving disputes have risen by 40%. The irrefutable
                            proof eliminates arguments instantly."</p>
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-full mr-3"></div>
                            <div>
                                <div class="font-bold text-gray-900 text-sm">Marcus V.</div>
                                <div class="text-xs text-gray-500">Regional Director, DriveNow</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex text-yellow-400 mb-4">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                        </div>
                        <p class="text-gray-800 italic mb-6">"The before and after views are incredible. The process is
                            completely frictionless for our users, and it looks beautiful on mobile."</p>
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-full mr-3"></div>
                            <div>
                                <div class="font-bold text-gray-900 text-sm">Sarah L.</div>
                                <div class="text-xs text-gray-500">Fleet Owner</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="py-24 text-center">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-gray-900 mb-6">Put flawless customer portal into your fleet</h2>
                <p class="text-xl text-gray-600 mb-10">Get started today and protect your assets from the very next
                    booking.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="/auth/signup.php"
                        class="inline-flex justify-center items-center px-8 py-3.5 border border-transparent text-base font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition">
                        Start free trial
                    </a>
                    <a href="/contact.php"
                        class="inline-flex justify-center items-center px-8 py-3.5 border border-gray-300 text-base font-semibold rounded-lg text-gray-900 bg-white hover:bg-gray-50 transition">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>

        <!-- FAQ Component -->
        <section
            class="flex flex-col max-w-7xl mx-auto items-center gap-10 px-4 py-10 sm:gap-12 sm:py-12 md:px-6 md:py-14 lg:gap-12 lg:px-10 lg:py-16 bg-gray-50">
            <div class="flex w-full flex-col items-start gap-3 text-left lg:items-center lg:gap-4 lg:text-center">
                <p class="text-[11px] font-bold tracking-[0.2em] uppercase [&>span]:text-[#2567ff]"><span>FAQ</span></p>
                <h2
                    class="w-full text-4xl font-bold text-gray-900 lg:w-auto [&>span]:bg-gradient-to-r [&>span]:from-[#2567ff] [&>span]:to-[#38bdf8] [&>span]:bg-clip-text [&>span]:text-transparent">
                    <span>Questions about</span> Customer Portal
                </h2>
                <p class="w-full text-[15px] text-gray-600 lg:max-w-[600px] lg:text-center">Everything you need to know
                    about damage tracking, photo storage, and resolution workflows.</p>
            </div>
            <div class="flex w-full max-w-[1400px] flex-col" id="faqAccordion">
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(0)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">What
                            features does the car rental customer portal include?</span>
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
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">The tenant portal is a
                            centralised digital self-service dashboard where renters can view their active reservations,
                            download PDF invoices, request booking extensions, upload driving licences, and perform
                            contactless vehicle check-ins directly from their smartphone.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(1)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">How
                            does the self-service web portal reduce support tickets?</span>
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
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">By deploying an
                            asynchronous customer portal, tenants no longer need to call the rental desk to verify their
                            pickup time, resend email contracts, or update their billing information. This self-serve
                            autonomy significantly decreases your administrative phone volume.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(2)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Do
                            renters need to download a specialised mobile app?</span>
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
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">No. The entire tenant
                            experience is designed as a frictionless Progressive Web App (PWA). Customers access their
                            secure dashboard via a magic SMS or email link, instantly loading optimised mobile
                            interfaces directly in their Safari or Chrome browser.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(3)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Can
                            customers extend their car rental duration online?</span>
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
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Yes. Renters can
                            easily request extensions through the portal infrastructure. The system automatically
                            cross-references your master booking calendar for overlapping reservations before approving
                            the extension and charging their vaulted credit card.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(4)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Is
                            the customer portal white-labeled with my local brand logo?</span>
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
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Absolutely. We employ
                            deep customisation frameworks allowing you to upload your specific vector logos, define your
                            brand color palettes, and seamlessly attach the portal experience to your primary agency
                            domain for total brand consistency.</p>
                    </div>
                </div>
                <div class="border-b border-[#e5e5e5] last:border-b-0">
                    <button onclick="toggleFaq(5)"
                        class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                        <span
                            class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">How
                            do users handle damage reporting and claims via the portal?</span>
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
                        <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">During their active
                            rental timeline, tenants can navigate to the support hub within their dashboard to
                            immediately upload geo-tagged photos and incident descriptions from their location,
                            instantly notifying your fleet management team of damage.</p>
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

        <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>