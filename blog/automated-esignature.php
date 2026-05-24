<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Why Your Car Rental Business Needs to Ditch Paper for Automated E-Signatures - <?= SITE_NAME?></title>
    <meta name="description" content="If you’re still handing people a physical pen and a stack of paper, you’re basically running a business from 1995. Learn why automated e-signatures are the 2026 standard for fleet operators.">
    <?php include __DIR__ . '/../includes/head-content.php'; ?>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-white" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Announcement Bar (matching CaaS style) -->
    <div class="bg-[#2567ff] py-2.5 text-center text-white text-[11px] font-bold tracking-[0.2em] uppercase">
        New Feature: Automated E-Signatures now live. <a href="/features/e-signature.php" class="underline ml-1">Learn More</a>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <!-- Date / Segment -->
        <div class="mb-8">
            <span class="text-xs font-bold text-blue-600 uppercase tracking-widest mr-4">Operations</span>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">March 31, 2026</span>
        </div>

        <!-- Hero Section -->
        <div class="grid lg:grid-cols-2 gap-20 items-center mb-20">
            <div>
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-gray-900 tracking-tighter leading-[1] mb-8">
                    Ditch Paper for Automated E-Signatures
                </h1>
                <p class="text-xl text-gray-500 leading-relaxed font-medium max-w-xl">
                    If you’re still handing people a physical pen and a stack of coffee-stained paper, you’re basically running a business from 1995.
                </p>
            </div>
            <div class="relative">
                <div class="aspect-[4/3] bg-[#0a0a0b] rounded-[48px] flex items-center justify-center p-16 overflow-hidden shadow-2xl relative">
                    <img src="https://images.unsplash.com/photo-1565514020179-026b92b84bb6?auto=format&fit=crop&q=80&w=800"
                        alt="E-Signatures" class="absolute inset-0 w-full h-full object-cover opacity-40">
                    <div class="flex flex-col items-center gap-6 relative z-10">
                        <div class="w-24 h-24 bg-blue-600 rounded-3xl flex items-center justify-center shadow-lg transform -rotate-3 hover:rotate-0 transition-transform">
                             <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div class="mt-4 px-6 py-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-full text-white text-xs font-bold tracking-widest uppercase italic">
                             Digital Standard 2026
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Points Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-24">
            <?php 
            $topics = [
                "Speeding Up Key Handover", "Legal Compliance & Protection",
                "Reducing Administrative Costs", "Digital Audit Trails",
                "Branding & Customer Trust", "Automated Checkout Rules"
            ];
            foreach($topics as $topic): ?>
                <div class="bg-gray-50/50 border border-gray-100 p-7 rounded-2xl hover:bg-gray-100/50 transition duration-300 flex items-center justify-between group cursor-pointer">
                    <span class="text-[15px] font-bold text-gray-800 tracking-tight"><?= $topic ?></span>
                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center border border-gray-200 group-hover:bg-blue-600 group-hover:border-blue-600 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Article Body -->
        <article class="max-w-4xl mx-auto prose-custom">
            <p class="text-2xl font-semibold !text-gray-900 mb-10 tracking-tight">Starting a car rental business is a massive hustle. But if your onboarding process relies on physical pens and stacks of paper, your most valuable asset: time, is being bled dry by 1990s-era administration.</p>

            <p>In 2026, if you want to look like a pro and actually grow your fleet, you need automated e-signatures. It sounds technical, but it’s actually the simplest way to protect your cars and keep your sanity. Let’s break down why this is a total game-changer for your business.</p>

            <h2>Why Speed Matters: The "Key Handover"</h2>
            <p>Nobody likes standing around in a parking lot filling out forms. Your customers want to get in the car and go. When you use automated contracts, the "boring stuff" happens while the customer is sitting on their couch.</p>
            
            <ul>
                <li><strong>Instant Booking Confirmation:</strong> Contracts are built and sent the second the booking is confirmed.</li>
                <li><strong>Mobile-First Signing:</strong> Customers sign with their finger on any device.</li>
                <li><strong>Zero Manual Data Entry:</strong> The system pulls data directly from your <a href="/features/vehicle-management.php" class="text-blue-600 font-bold hover:underline">Vehicle Management</a> suite.</li>
            </ul>

            <div class="bg-blue-50/50 border border-blue-100 p-8 rounded-[32px] my-12">
                <h4 class="text-blue-900 font-bold text-xl mb-4">Did you know?</h4>
                <p class="!text-blue-800 !mb-0 opacity-80">Automated e-signatures reduce front-desk administrative time by an average of 75%. It transforms a 20-minute headache into a 30-second "hello".</p>
            </div>

            <h3>Manual Paperwork vs. Digital Automation</h3>
            <!-- Comparison Table -->
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Paper/Manual</th>
                            <th>Automated E-Sign</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Signing Time</td>
                            <td>12-15 Minutes</td>
                            <td class="text-blue-600 font-bold">Under 60 Seconds</td>
                        </tr>
                        <tr>
                            <td>Storage Cost</td>
                            <td>Physical filing / High</td>
                            <td class="text-green-600 font-bold">Cloud-Based / Included</td>
                        </tr>
                        <tr>
                            <td>Error Rate</td>
                            <td>High (Typos, missed sigs)</td>
                            <td class="text-gray-900 font-bold">Zero (System-driven)</td>
                        </tr>
                        <tr>
                            <td>Legal Defense</td>
                            <td>Hard to track audit trail</td>
                            <td class="text-blue-600 font-bold">Court-Ready Digital Trail</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2>How to Implement Automation</h2>
            <p>Moving to a digital contract system doesn't require a degree in computer science. It's about setting up a loop that protects your assets automatically.</p>

            <!-- Numbered Steps -->
            <div class="space-y-16 my-20">
                <div class="flex flex-col md:flex-row gap-10">
                    <div class="w-16 h-16 rounded-3xl bg-gray-900 text-white flex items-center justify-center flex-shrink-0 font-black text-2xl shadow-xl transform rotate-3">1</div>
                    <div>
                        <h4 class="text-2xl font-bold text-gray-900 mb-3">Upload Your Template</h4>
                        <p class="text-gray-500 leading-relaxed">Turn your existing rental agreement into a digital master template. Use dynamic placeholders for customer names, dates, and vehicle details.</p>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-10">
                    <div class="w-16 h-16 rounded-3xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 font-black text-2xl shadow-xl transform -rotate-3">2</div>
                    <div>
                        <h4 class="text-2xl font-bold text-gray-900 mb-3">Activate "Mandatory Sign" Rules</h4>
                        <p class="text-gray-500 leading-relaxed">Flip the switch that prevents bookings from moving to "active" status until the legal agreement has been fingerprinted and timestamped.</p>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-10">
                    <div class="w-16 h-16 rounded-3xl bg-gray-900 text-white flex items-center justify-center flex-shrink-0 font-black text-2xl shadow-xl transform rotate-3">3</div>
                    <div>
                        <h4 class="text-2xl font-bold text-gray-900 mb-3">Sync with Your Fleet Calendar</h4>
                        <p class="text-gray-500 leading-relaxed">Once signed, the document is moved to the cloud and a copy is sent to the customer instantly. Your dashboard updates the vehicle status to secured.</p>
                    </div>
                </div>
            </div>

            <p>Ready to stop chasing paper and start growing your business? Professionalising your operation doesn't just save time: it saves your reputation. When you look "big time," you earn big-time results.</p>
        </article>

        <!-- Dynamic CTA banner -->
        <div class="mt-32">
            <div class="bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 rounded-[56px] p-12 md:p-20 text-center md:text-left flex flex-col lg:flex-row items-center justify-between gap-12 relative overflow-hidden shadow-[0_20px_50px_-15px_rgba(37,103,255,0.4)]">
                <!-- Abstract Glow -->
                <div class="absolute -right-20 -top-20 w-[600px] h-[600px] bg-white/5 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute -left-20 -bottom-20 w-[400px] h-[400px] bg-indigo-400/10 rounded-full blur-[80px] pointer-events-none"></div>
                
                <div class="relative z-10 max-w-2xl">
                    <span class="inline-block px-4 py-1 bg-white/10 rounded-full text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-6">Automation Suite</span>
                    <h2 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-6 tracking-tighter leading-tight">Ready to Modernise Your Onboarding?</h2>
                    <p class="text-blue-100 text-lg md:text-xl font-medium opacity-90 max-w-xl">Join hundreds of fleet operators using <?= SITE_NAME?> to automate their legal workflows.</p>
                </div>
                <div class="relative z-10 w-full lg:w-auto">
                    <a href="/features/e-signature.php" class="bg-white text-blue-700 px-10 py-6 rounded-full font-black flex items-center justify-center gap-4 hover:bg-gray-50 transition-all hover:scale-[1.03] active:scale-95 shadow-2xl group text-lg">
                        Explore E-Signatures
                        <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center group-hover:translate-x-1 transition-transform">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>