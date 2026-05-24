<?php
/**
 * Universal Pricing Grid Component
 * Used in upgrade.php and other marketing pages.
 */
?>
<div id="pricing-plans" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
        <h2 class="text-4xl font-black text-gray-900 mb-4 tracking-tight">Choose the plan that fits your current stage.
        </h2>
        <p class="text-lg text-gray-500 font-medium">It's easy to switch between plans as you grow. No hidden fees or
            long-term contracts.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 max-w-7xl mx-auto items-stretch">
        <!-- Growth Plan -->
        <div
            class="bg-white border border-slate-100 rounded-[32px] p-10 flex flex-col premium-shadow-sm hover:border-slate-200 transition-all group">
            <div class="mb-8">
                <h3 class="text-3xl font-[900] text-slate-900 mb-2">Growth</h3>
                <p class="text-xl text-slate-900 mb-2 font-bold"><span class="font-[900]">£100</span> <span
                        class="text-slate-500 font-medium text-sm">/ month</span></p>
                <p class="text-[13px] text-slate-400 font-semibold tracking-tight">For small fleets starting their
                    digital journey</p>
            </div>

            <a href="/checkout.php?plan=growth"
                class="block w-full text-center py-4 px-6 bg-slate-100 text-slate-900 rounded-full font-bold mb-10 hover:bg-slate-900 hover:text-white transition-all transform active:scale-95 text-sm">
                Get started
            </a>

            <ul class="space-y-4 mb-12 flex-grow">
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> up to 5 vehicles
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Digital E-signing
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Basic Fleet Dashboard
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Email Support
                </li>
            </ul>

            <div class="pt-8 border-t border-slate-50 mt-auto">
                <p class="text-[11px] text-slate-400 font-bold mb-4 uppercase tracking-widest">Growth <span
                        class="text-slate-900">Essentials:</span></p>
                <ul class="space-y-3">
                    <li class="text-xs text-slate-500 font-bold flex items-center">
                        <span class="mr-2 text-slate-300 font-black">+</span> Stripe Integration
                    </li>
                </ul>
            </div>
        </div>

        <!-- Core Plan -->
        <div
            class="bg-white border border-slate-200 rounded-[32px] p-10 flex flex-col premium-shadow-lg scale-105 z-10 border-blue-100">
            <div class="mb-8">
                <div
                    class="inline-block px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase tracking-widest mb-4">
                    Most Popular</div>
                <h3 class="text-3xl font-[900] text-slate-900 mb-2">Core</h3>
                <p class="text-xl text-slate-900 mb-2 font-bold"><span class="font-[900]">£160</span> <span
                        class="text-slate-500 font-medium text-sm">/ month</span></p>
                <p class="text-[13px] text-slate-400 font-semibold tracking-tight">Professional fleet management at
                    scale</p>
            </div>

            <a href="/checkout.php?plan=core"
                class="block w-full text-center py-4 px-6 bg-slate-900 text-white rounded-full font-bold mb-10 hover:bg-black transition-all transform active:scale-95 text-sm shadow-xl shadow-slate-200">
                Get started
            </a>

            <p class="text-[11px] text-slate-400 font-black mb-6 uppercase tracking-widest">Everything in <span
                    class="text-slate-900">Growth</span> plus</p>
            <ul class="space-y-4 mb-12 flex-grow">
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Unlimited vehicles
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Custom Domain
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Identity Verification
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Priority Support
                </li>
            </ul>

            <div class="pt-8 border-t border-slate-50 mt-auto">
                <p class="text-[11px] text-slate-400 font-bold mb-4 uppercase tracking-widest">Core <span
                        class="text-slate-900">Power:</span></p>
                <ul class="space-y-3">
                    <li class="text-xs text-slate-500 font-bold flex items-center">
                        <span class="mr-2 text-slate-300 font-black">+</span> API Access
                    </li>
                </ul>
            </div>
        </div>

        <!-- Scale Plan -->
        <div
            class="bg-white border border-slate-100 rounded-[32px] p-10 flex flex-col premium-shadow-sm hover:border-slate-200 transition-all">
            <div class="mb-8">
                <h3 class="text-3xl font-[900] text-slate-900 mb-2">Scale</h3>
                <p class="text-xl text-slate-900 mb-2 font-bold">Bespoke</p>
                <p class="text-[13px] text-slate-400 font-semibold tracking-tight">Complex requirements & large
                    enterprise</p>
            </div>

            <a href="/contact.php?plan=scale"
                class="block w-full text-center py-4 px-6 bg-slate-100 text-slate-900 rounded-full font-bold mb-10 hover:bg-slate-900 hover:text-white transition-all transform active:scale-95 text-sm">
                Talk to sales
            </a>

            <p class="text-[11px] text-slate-400 font-black mb-6 uppercase tracking-widest">Everything in <span
                    class="text-slate-900">Core</span> plus</p>
            <ul class="space-y-4 mb-12 flex-grow">
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Dedicated Manager
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Migration Service
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Engineer Support
                </li>
                <li class="flex items-center text-sm font-semibold text-slate-600">
                    <span class="mr-3 text-blue-600 font-black">✓</span> Support SLAs
                </li>
            </ul>

            <div class="pt-8 border-t border-slate-50 mt-auto">
                <p class="text-[11px] text-slate-400 font-bold mb-4 uppercase tracking-widest">Scale <span
                        class="text-slate-900">Enterprise:</span></p>
                <ul class="space-y-3">
                    <li class="text-xs text-slate-500 font-bold flex items-center">
                        <span class="mr-2 text-slate-300 font-black">+</span> Full White-label
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>

<style>
    .premium-shadow-sm {
        box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.05);
    }

    .premium-shadow-lg {
        box-shadow: 0 30px 100px -20px rgba(0, 0, 0, 0.12);
    }
</style>