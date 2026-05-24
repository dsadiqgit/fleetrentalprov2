<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Blog Post - <?= SITE_NAME?></title>
    <?php include __DIR__ . '/../includes/head-content.php'; ?>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-white" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Announcement Bar -->
    <div class="bg-[#3b82f6] py-2 text-center text-white text-[11px] font-bold tracking-widest uppercase">
        Announcing our latest Security & Compliance updates. <a href="#" class="underline ml-1">Learn more</a>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <!-- Breadcrumbs / Date -->
        <div class="mb-8">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">January 15, 2026</span>
        </div>

        <!-- Hero Section -->
        <div class="grid lg:grid-cols-2 gap-16 items-center mb-20">
            <div>
                <h1
                    class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-gray-900 tracking-tighter leading-[1.05] mb-8">
                    Beyond Background Checks: How Identity Verification Stops Rental Fraud
                </h1>
                <p class="text-xl text-gray-500 leading-relaxed font-medium max-w-xl">
                    Traditional screening methods are no longer enough. Learn how AI-native verification protects your
                    fleet before the keys are even handed over.
                </p>
            </div>
            <div class="relative">
                <div
                    class="aspect-[4/3] bg-gray-900 rounded-[40px] flex items-center justify-center p-12 overflow-hidden">
                    <div class="flex gap-8 items-center">
                        <div class="w-20 h-20 bg-blue-500 rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div
                            class="w-20 h-20 bg-purple-500 rounded-full flex items-center justify-center -ml-4 border-4 border-gray-900">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div
                            class="w-20 h-20 bg-orange-500 rounded-2xl flex items-center justify-center -ml-4 border-4 border-gray-900">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table of Contents / Key Points Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-20">
            <?php
$points = [
    "The Identity Verification Layer", "Mapping Verification to Each Fraud Type",
    "Why Background Checks Are Not Enough", "The Verification Stack for Rentals",
    "Cost Comparison: Verification vs Losses", "Global Coverage for Remote Hiring"
];
foreach ($points as $point): ?>
            <div
                class="bg-gray-50/50 border border-gray-100 p-6 rounded-xl hover:bg-gray-100 transition flex items-center justify-between group cursor-pointer">
                <span class="text-sm font-bold text-gray-700">
                    <?= $point?>
                </span>
                <svg class="w-4 h-4 text-gray-400 group-hover:translate-x-1 transition-transform" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            <?php
endforeach; ?>
        </div>

        <!-- Article Content -->
        <div class="max-w-4xl mx-auto prose-custom">
            <p>In the high-stakes world of car rentals, the "trust" model is being challenged like never before. With
                the rise of sophisticated synthetic identity fraud and automated application bots, traditional
                background checks are no longer sufficient to protect your business assets.</p>

            <h2>Why Background Checks Are Not Enough</h2>
            <p>Background checks are reactive. They tell you what happened in the past, but they don't confirm who is
                standing in front of you today. Identity verification (IDV) is the missing link that bridges the gap
                between a record and a real human being.</p>

            <ul>
                <li><strong>Identity Theft:</strong> Stolen credentials used to bypass credit checks.</li>
                <li><strong>Credential Stuffing:</strong> Automated attempts to gain access to member accounts.</li>
                <li><strong>Ghost Renters:</strong> Fake profiles created specifically for one-time high-value theft.
                </li>
            </ul>

            <h3>The Identity Verification Layer: What Background Checks Miss</h3>
            <p>While a background check looks for criminal records, IDV uses AI to verify documents in real-time,
                matching biometric data to the provided ID.</p>

            <!-- Comparison Table -->
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>ID Verification</th>
                            <th>Background Check</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Real-time</td>
                            <td>Yes, instant results</td>
                            <td>No, usually 1-3 days</td>
                            <td class="text-green-600 font-bold">Recommended</td>
                        </tr>
                        <tr>
                            <td>Biometric Match</td>
                            <td>Yes, face-to-document</td>
                            <td>No, record match only</td>
                            <td>Exclusive</td>
                        </tr>
                        <tr>
                            <td>Fraud Detection</td>
                            <td>Stops synthetic identities</td>
                            <td>Finds past offenses</td>
                            <td class="text-blue-600 font-bold">Critical</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2>The Verification Stack for Hiring</h2>
            <p>Implementing a modern verification stack doesn't have to be a friction-filled experience for your
                customers. It can be a seamless part of the onboarding process.</p>

            <!-- Numbered Steps -->
            <div class="space-y-12 my-16">
                <div class="flex gap-8">
                    <div
                        class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center flex-shrink-0 font-black">
                        1</div>
                    <div>
                        <h4 class="text-xl font-bold text-gray-900 mb-2">Stage 1: Document Upload</h4>
                        <p class="text-gray-500 leading-relaxed italic">The user snaps a high-resolution photo of their
                            government-issued ID. AI instantly analyses for security features like holograms and
                            watermarks.</p>
                    </div>
                </div>
                <div class="flex gap-8">
                    <div
                        class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center flex-shrink-0 font-black">
                        2</div>
                    <div>
                        <h4 class="text-xl font-bold text-gray-900 mb-2">Stage 2: Liveness Check</h4>
                        <p class="text-gray-500 leading-relaxed italic">A quick 3D selfie prevents presentation attacks
                            (using a mask or a photo of a screen). This ensures the person is present in real-time.</p>
                    </div>
                </div>
            </div>

            <h2>Setting Up the Tool</h2>
            <p>Integrate directly into your website builder or custom dashboard in minutes. Our API handle the heavy
                lifting while you focus on scaling your operations.</p>

            <div
                class="bg-gray-50 rounded-2xl p-8 border border-gray-100 my-10 font-mono text-sm overflow-x-auto text-blue-600">
                $fleetRental->verifyIdentity($customerId) -> then(function($result) { <br>
                &nbsp;&nbsp; if($result->verified) { grantAccess(); } <br>
                });
            </div>
        </div>

        <!-- Related Articles Section -->
        <div class="mt-32 pt-16 border-t border-gray-100">
            <h3 class="text-3xl font-bold text-gray-900 mb-12 tracking-tight">Related Articles</h3>
            <div class="grid md:grid-cols-3 gap-10">
                <!-- Related 1 -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-[32px] overflow-hidden border border-gray-100 mb-6 shadow-sm">
                        <img src="https://images.unsplash.com/photo-1542362567-b054cd1321c1?q=80&w=600" alt="News" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-[11px] font-bold tracking-widest text-blue-600 uppercase">Product <span class="text-gray-300">•</span> Dec 15, 2025</div>
                        <h4 class="text-xl font-bold text-gray-900 tracking-tight leading-tight group-hover:text-blue-600 transition">Fleet secures $20M Series A</h4>
                    </div>
                </div>
                <!-- Related 2 -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-[32px] overflow-hidden border border-gray-100 mb-6 shadow-sm bg-[#f8f9ff] flex items-center justify-center p-10">
                        <h4 class="text-2xl font-black text-gray-900 tracking-tight">Sequence 2.0</h4>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-[11px] font-bold tracking-widest text-blue-600 uppercase">The Experience <span class="text-gray-300">•</span> Oct 2, 2025</div>
                        <h4 class="text-xl font-bold text-gray-900 tracking-tight leading-tight group-hover:text-blue-600 transition">Introducing Fleet 2.0 Dashboard</h4>
                    </div>
                </div>
                <!-- Related 3 -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-[32px] overflow-hidden border border-gray-100 mb-6 shadow-sm bg-gray-50 p-10">
                         <div class="w-full h-full border-2 border-dashed border-gray-200 rounded-2xl"></div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-[11px] font-bold tracking-widest text-[#d97706] uppercase">Strategy <span class="text-gray-300">•</span> July 14, 2025</div>
                        <h4 class="text-xl font-bold text-gray-900 tracking-tight leading-tight group-hover:text-blue-600 transition">10 Reasons to Move from Legacy Software</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blue CTA banner -->
        <div class="mt-24">
            <div
                class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-[40px] p-10 md:p-16 text-center md:text-left flex flex-col md:flex-row items-center justify-between gap-10 relative overflow-hidden">
                <!-- Decorative Circles -->
                <div class="absolute right-0 top-0 -mr-20 -mt-20 w-80 h-80 border-t-2 border-white/10 rounded-full">
                </div>
                <div class="absolute right-0 top-0 -mr-10 -mt-10 w-60 h-60 border-t-2 border-white/10 rounded-full">
                </div>

                <div class="relative z-10">
                    <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-4 tracking-tighter">Ready for Free KYC?
                    </h2>
                    <p class="text-blue-100 text-lg font-medium opacity-90">Discover unlimited verifications and
                        industry-leading technology.</p>
                </div>
                <div class="relative z-10">
                    <button
                        class="bg-gray-900 text-white px-10 py-5 rounded-full font-bold flex items-center gap-3 hover:bg-black transition-all hover:scale-[1.02] active:scale-95 shadow-xl">
                        Talk with the Team
                        <svg class="w-5 h-5 bg-white/20 rounded-full p-1" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>