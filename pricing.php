<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title>Pricing - <?= SITE_NAME ?></title>

    <!-- Preload fonts (matching index.php) -->
    <link rel="preload" href="/public/font/Inter_Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_Bold.ttf" as="font" type="font/ttf" crossorigin>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="stylesheet" href="/public/css/blog.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-white" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="bg-white pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-block px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-sm font-medium mb-6">
                PRICING
            </div>
            <h1 class="text-5xl font-bold text-gray-900 mb-6">
                Pricing that scales with<br>your rental business.
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">
                Start with 30 days free trial. No credit card required. Cancel anytime. And scale to hundreds of vehicles at the same affordable price.
            </p>
            <div class="flex justify-center gap-4">
                <a href="/auth/signup.php" class="px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">
                    Start Free Trial
                </a>
                <a href="#compare" class="px-8 py-3 border-2 border-gray-300 text-gray-900 rounded-lg font-semibold hover:bg-gray-50">
                    View Features
                </a>
            </div>
        </div>
    </div>

    <!-- Feature Comparison Table -->
    <div id="compare" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Everything runs on one platform.</h2>
                <p class="text-lg text-gray-600">All your tools in one place for managing your rental business. No add-ons or hidden fees.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-4 px-6 font-semibold text-gray-900">Features</th>
                                <th class="text-center py-4 px-6 font-semibold text-gray-900">Starter</th>
                                <th class="text-center py-4 px-6 font-semibold text-gray-900">Growth</th>
                                <th class="text-center py-4 px-6 font-semibold text-gray-900">Scale</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="py-4 px-6 text-gray-900">Max Vehicles</td>
                                <td class="py-4 px-6 text-center text-gray-600">5</td>
                                <td class="py-4 px-6 text-center text-gray-600">25</td>
                                <td class="py-4 px-6 text-center text-gray-600">Unlimited</td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-gray-900">Booking engine</td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-gray-900">Your own website</td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-gray-900">Payment processing</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-gray-900">API & integrations</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-gray-900">Custom branding</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-gray-900">Advanced analytics</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-gray-900">White-label solution</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center text-gray-400">-</td>
                                <td class="py-4 px-6 text-center"><svg class="w-5 h-5 text-blue-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Cards -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Choose the plan that fits your current stage.</h2>
                <p class="text-lg text-gray-600">It's easy to switch between plans as you grow. No hidden fees or long-term contracts.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Starter Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 hover:shadow-lg transition">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
                        <div class="flex items-baseline mb-4">
                            <span class="text-5xl font-bold text-gray-900">$99</span>
                            <span class="text-gray-600 ml-2">/month</span>
                        </div>
                        <p class="text-gray-600">Perfect for small rental businesses just getting started.</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Up to 5 vehicles in your fleet</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Online booking and scheduling</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Basic analytics and reporting</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Email and chat support</span>
                        </li>
                    </ul>
                    <a href="/auth/signup.php" class="block w-full text-center py-3 px-6 bg-gray-100 text-gray-900 rounded-lg font-semibold hover:bg-gray-200 transition">
                        Start Free Trial
                    </a>
                </div>

                <!-- Growth Plan (Popular) -->
                <div class="bg-white border-2 border-blue-600 rounded-2xl p-8 relative shadow-xl">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-yellow-400 text-gray-900 px-4 py-1 rounded-full text-sm font-bold">MOST POPULAR</span>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Growth</h3>
                        <div class="flex items-baseline mb-4">
                            <span class="text-5xl font-bold text-gray-900">$249</span>
                            <span class="text-gray-600 ml-2">/month</span>
                        </div>
                        <p class="text-gray-600">For growing businesses that need more power and flexibility.</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Up to 25 vehicles in your fleet</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Automated payment processing</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Custom branding and white-label</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Advanced analytics and insights</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Priority support</span>
                        </li>
                    </ul>
                    <a href="/auth/signup.php" class="block w-full text-center py-3 px-6 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Start Free Trial
                    </a>
                </div>

                <!-- Scale Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 hover:shadow-lg transition">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Scale</h3>
                        <div class="flex items-baseline mb-4">
                            <span class="text-5xl font-bold text-gray-900">Custom</span>
                        </div>
                        <p class="text-gray-600">Enterprise-grade solution for large rental operations.</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Unlimited vehicles and locations</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Custom integrations and API access</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Dedicated account manager</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">Custom onboarding and training</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-700">24/7 phone support and SLA</span>
                        </li>
                    </ul>
                    <a href="/contact" class="block w-full text-center py-3 px-6 bg-gray-100 text-gray-900 rounded-lg font-semibold hover:bg-gray-200 transition">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Enterprise Section -->
    <div class="py-20 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-block px-3 py-1 bg-blue-600 text-white rounded-full text-sm font-medium mb-6">
                        ENTERPRISE SOLUTIONS
                    </div>
                    <h2 class="text-4xl font-bold mb-6">Need a custom rollout for a complex operation?</h2>
                    <p class="text-lg text-gray-300 mb-8">
                        Work with us to get a tailored solution, complete implementation support, hands-on training, and dedicated success management.
                    </p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-400 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-300">Custom rollout among key fleets across multi-locations</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-400 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-300">Guided training for bigger teams and multi-locations</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-400 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-300">Dedicated success manager to help you scale</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-400 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-300">Custom integrations, white-label, and more</span>
                        </li>
                    </ul>
                    <a href="/contact" class="inline-block px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">
                        Contact Sales →
                    </a>
                </div>
                <div class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                    <div class="text-center mb-6">
                        <div class="text-6xl font-bold mb-2">30 days</div>
                        <p class="text-gray-400">Average time from signup to go-live</p>
                    </div>
                    <div class="border-t border-gray-700 pt-6 text-center">
                        <div class="text-6xl font-bold mb-2">99.9%</div>
                        <p class="text-gray-400">Uptime SLA with 24/7 monitoring and everything</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <section class="flex flex-col max-w-7xl mx-auto items-center gap-10 px-4 py-10 sm:gap-12 sm:py-12 md:px-6 md:py-14 lg:gap-12 lg:px-10 lg:py-16 bg-white">
        <div class="flex w-full flex-col items-start gap-3 text-left lg:items-center lg:gap-4 lg:text-center">
            <p class="text-[11px] font-bold tracking-[0.2em] uppercase [&>span]:text-[#2567ff]"><span>FAQ</span></p>
            <h2 class="w-full text-4xl font-bold text-gray-900 lg:w-auto [&>span]:bg-gradient-to-r [&>span]:from-[#2567ff] [&>span]:to-[#38bdf8] [&>span]:bg-clip-text [&>span]:text-transparent">
                <span>Frequently</span> Asked Questions
            </h2>
            <p class="w-full text-[15px] text-gray-600 lg:max-w-[600px] lg:text-center">Everything you need to know about our pricing and plans.</p>
        </div>
        <div class="flex w-full max-w-[1400px] flex-col" id="faqAccordion">
            <!-- FAQ Item 1 -->
            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(0)" class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Is there a free trial?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Yes, all plans come with a 30-day free trial. No credit card required to start your trial. You can explore all features and see if <?= SITE_NAME ?> is the right fit for your business.</p>
                </div>
            </div>
            <!-- FAQ Item 2 -->
            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(1)" class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Can I change plans later?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Absolutely! You can upgrade or downgrade your plan at any time. Changes take effect immediately, and we'll prorate any charges or credits to your account.</p>
                </div>
            </div>
            <!-- FAQ Item 3 -->
            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(2)" class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">What integrations are available?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">We offer integrations with Stripe, PayPal, QuickBooks, Xero, Zapier, and more. Custom integrations are available on the Scale plan through our API.</p>
                </div>
            </div>
            <!-- FAQ Item 4 -->
            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(3)" class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Is my data secure?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Yes, we take security seriously. All data is encrypted in transit and at rest. We perform regular security audits and maintain SOC 2 compliance. Your data is backed up daily.</p>
                </div>
            </div>
            <!-- FAQ Item 5 -->
            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(4)" class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] sm:tracking-[-0.6px] md:text-[22px] md:tracking-[-0.8px] lg:text-[24px] lg:tracking-[-1px]">Do you offer refunds?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">We offer a 30-day money-back guarantee. If you're not satisfied with <?= SITE_NAME ?> within the first 30 days, we'll refund your payment in full, no questions asked.</p>
                </div>
            </div>
        </div>

        <div class="mt-12 text-center">
            <p class="text-gray-600 mb-4">Still have questions?</p>
            <a href="/contact" class="inline-block px-10 py-4 bg-blue-600 text-white rounded-full font-bold hover:bg-blue-700 transition shadow-[0_10px_20px_-5px_rgba(37,103,255,0.3)] hover:scale-[1.02] active:scale-95">
                Contact Us →
            </a>
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

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
