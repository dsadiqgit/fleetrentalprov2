<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental Software in London - <?= SITE_NAME ?></title>
    <link rel="preload" href="/public/font/Inter_Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_Bold.ttf" as="font" type="font/ttf" crossorigin>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-white" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-blue-50 to-white py-20">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">Car Rental Software in London</h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Streamline your London car rental business with our comprehensive management platform. Trusted by rental companies across the UK.</p>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-20 bg-white">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Why London Rental Companies Choose Us</h2>
                <p class="text-xl text-gray-600">Everything you need to manage your fleet efficiently</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">ID Verification</h3>
                    <p class="text-gray-600">Verify customer identities instantly with AI-powered document scanning and fraud detection.</p>
                </div>

                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Booking Calendar</h3>
                    <p class="text-gray-600">Real-time availability tracking with drag-and-drop scheduling for your London fleet.</p>
                </div>

                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Payment Processing</h3>
                    <p class="text-gray-600">Secure Stripe integration for seamless payment collection and processing.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-br from-blue-600 to-blue-700">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Ready to Transform Your London Rental Business?</h2>
            <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">Join hundreds of rental companies using our platform to streamline operations and grow revenue.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/auth/signup.php" class="px-8 py-4 bg-white text-blue-600 rounded-lg font-medium hover:bg-gray-100 inline-flex items-center justify-center">
                    Start Free Trial
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
                <a href="/contact.php" class="px-8 py-4 border-2 border-white text-white rounded-lg font-medium hover:bg-white hover:text-blue-600 inline-flex items-center justify-center">
                    Contact Sales
                </a>
            </div>
        </div>
    </div>

    <!-- Local Info Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Serving London's Car Rental Industry</h2>
                    <p class="text-gray-600 mb-4">Our platform is designed specifically for car rental businesses operating in London and across the UK. Whether you're managing a fleet near Heathrow Airport, in Central London, or across multiple locations, our software adapts to your needs.</p>
                    <p class="text-gray-600 mb-4">We understand the unique challenges of operating in London - from congestion charges to ULEZ compliance. Our system helps you manage these complexities while providing an exceptional customer experience.</p>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Multi-location fleet management</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">24/7 customer support</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">UK-compliant documentation</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Other UK Locations We Serve</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="/uk/birmingham/" class="text-blue-600 hover:text-blue-700">Birmingham</a>
                        <a href="/uk/manchester/" class="text-blue-600 hover:text-blue-700">Manchester</a>
                        <a href="/uk/glasgow/" class="text-blue-600 hover:text-blue-700">Glasgow</a>
                        <a href="/uk/bristol/" class="text-blue-600 hover:text-blue-700">Bristol</a>
                        <a href="/uk/leeds/" class="text-blue-600 hover:text-blue-700">Leeds</a>
                        <a href="/uk/liverpool/" class="text-blue-600 hover:text-blue-700">Liverpool</a>
                        <a href="/uk/edinburgh/" class="text-blue-600 hover:text-blue-700">Edinburgh</a>
                        <a href="/uk/sheffield/" class="text-blue-600 hover:text-blue-700">Sheffield</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
