<?php
/**
 * Privacy Notice / Cookie Banner
 * Matches the requested light-mode style with customisation options.
 * Hides for 7 days once a choice is made.
 */
?>
<div id="privacy-banner-root" class="fixed bottom-6 left-6 z-[9999] hidden">
    <!-- Main Banner -->
    <div id="cookie-banner-main"
        class="w-full max-w-[420px] select-none overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl p-4 transition-all duration-300 transform translate-y-0 opacity-100">
        <div class="flex flex-col space-y-4">
            <div class="flex flex-1 items-center">
                <div class="w-full space-y-1">
                    <p class="font-bold text-slate-900 text-[15px]">Privacy Notice</p>
                    <p class="font-medium text-slate-500 text-[13px] leading-relaxed">
                        This site uses cookies to improve your browsing experience, analyse site traffic, and show
                        personalised content.
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-between pt-2">
                <div class="flex shrink-0 gap-2">
                    <button onclick="rejectAllCookies()"
                        class="px-4 py-2 rounded-xl text-[13px] font-bold bg-slate-100 text-slate-900 hover:bg-slate-200 transition">Reject
                        all</button>
                    <button onclick="acceptAllCookies()"
                        class="px-4 py-2 rounded-xl text-[13px] font-bold bg-slate-900 text-white hover:bg-black transition">Accept
                        all</button>
                </div>
                <button onclick="showCustomiseView()"
                    class="px-3 py-2 rounded-xl text-[13px] font-bold text-slate-500 hover:text-slate-900 transition">Customise</button>
            </div>
        </div>
    </div>

    <!-- Customise View -->
    <div id="cookie-banner-customise"
        class="hidden w-full max-w-[420px] select-none overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl p-5 transition-all duration-300">
        <div class="space-y-4">
            <h3 class="font-black text-slate-900 text-lg">Privacy Settings</h3>
            <p class="text-[13px] text-slate-500 leading-relaxed">
                Customise your privacy settings here. You can choose which types of cookies and tracking technologies
                you allow. Read our <a href="#" class="underline font-bold text-slate-900">Privacy Policy</a> and <a
                    href="#" class="underline font-bold text-slate-900">Terms of Service</a>.
            </p>

            <div class="space-y-2 pt-2" id="cookie-accordion">
                <!-- Option 1: Strictly Necessary -->
                <div class="border-b border-slate-50 pb-2">
                    <div class="flex items-center justify-between py-2 cursor-pointer group"
                        onclick="toggleAccordion('necessary-details')">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 bg-slate-100 rounded flex items-center justify-center text-slate-400 font-bold text-sm accordion-icon-box">
                                <span class="accordion-icon" id="necessary-details-icon">+</span>
                            </div>
                            <span
                                class="text-[14px] font-bold text-slate-900 group-hover:text-blue-600 transition">Strictly
                                Necessary</span>
                        </div>
                        <div class="relative inline-flex items-center cursor-not-allowed"
                            title="Required for the site to function">
                            <input type="checkbox" checked disabled class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:bg-blue-400 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full opacity-60">
                            </div>
                        </div>
                    </div>
                    <div id="necessary-details" class="hidden pl-9 pr-4 pb-2 transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            These cookies are essential for the website to function properly and cannot be disabled.
                        </p>
                    </div>
                </div>

                <!-- Option 2: Analytics -->
                <div class="border-b border-slate-50 pb-2">
                    <div class="flex items-center justify-between py-2 cursor-pointer group"
                        onclick="toggleAccordion('analytics-details')">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 bg-slate-100 rounded flex items-center justify-center text-slate-400 font-bold text-sm accordion-icon-box">
                                <span class="accordion-icon" id="analytics-details-icon">+</span>
                            </div>
                            <span
                                class="text-[14px] font-bold text-slate-900 group-hover:text-blue-600 transition">Analytics</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer"
                            onclick="event.stopPropagation()">
                            <input type="checkbox" id="cookie-analytics" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full opacity-100 transition-opacity">
                            </div>
                        </label>
                    </div>
                    <div id="analytics-details" class="hidden pl-9 pr-4 pb-2 transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            These cookies help us understand how visitors interact with the website and improve its
                            performance.
                        </p>
                    </div>
                </div>

                <!-- Option 3: Experience -->
                <div class="border-b border-slate-50 pb-2">
                    <div class="flex items-center justify-between py-2 cursor-pointer group"
                        onclick="toggleAccordion('experience-details')">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 bg-slate-100 rounded flex items-center justify-center text-slate-400 font-bold text-sm accordion-icon-box">
                                <span class="accordion-icon" id="experience-details-icon">+</span>
                            </div>
                            <span
                                class="text-[14px] font-bold text-slate-900 group-hover:text-blue-600 transition">Experience</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer"
                            onclick="event.stopPropagation()">
                            <input type="checkbox" id="cookie-experience" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full">
                            </div>
                        </label>
                    </div>
                    <div id="experience-details" class="hidden pl-9 pr-4 pb-2 transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            These cookies help us provide a better user experience and test new features.
                        </p>
                    </div>
                </div>

                <!-- Option 4: Marketing -->
                <div class="border-b border-slate-50 pb-2">
                    <div class="flex items-center justify-between py-2 cursor-pointer group"
                        onclick="toggleAccordion('marketing-details')">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 bg-slate-100 rounded flex items-center justify-center text-slate-400 font-bold text-sm accordion-icon-box">
                                <span class="accordion-icon" id="marketing-details-icon">+</span>
                            </div>
                            <span
                                class="text-[14px] font-bold text-slate-900 group-hover:text-blue-600 transition">Marketing</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer"
                            onclick="event.stopPropagation()">
                            <input type="checkbox" id="cookie-marketing" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full">
                            </div>
                        </label>
                    </div>
                    <div id="marketing-details" class="hidden pl-9 pr-4 pb-2 transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            These cookies are used to deliver relevant advertisements and track their effectiveness.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-6">
                <div class="flex gap-2">
                    <button onclick="rejectAllCookies()"
                        class="px-4 py-2 rounded-xl text-[13px] font-bold bg-slate-100 text-slate-900 hover:bg-slate-200 transition">Reject
                        all</button>
                    <button onclick="acceptAllCookies()"
                        class="px-4 py-2 rounded-xl text-[13px] font-bold bg-slate-100 text-slate-900 hover:bg-slate-200 transition">Accept
                        all</button>
                </div>
                <button onclick="saveSettings()"
                    class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-[13px] font-bold hover:bg-black transition shadow-lg shadow-blue-100 active:scale-95">Save
                    Settings</button>
            </div>
        </div>
    </div>
</div>

<script>
    const STORAGE_KEY = 'privacy_consent_granted';
    const EXPIRY_DAYS = 7;

    document.addEventListener('DOMContentLoaded', () => {
        if (!shouldShowBanner()) return;

        const root = document.getElementById('privacy-banner-root');
        root.classList.remove('hidden');
    });

    function shouldShowBanner() {
        const consentData = localStorage.getItem(STORAGE_KEY);
        if (!consentData) return true;

        const { timestamp } = JSON.parse(consentData);
        const now = new Date().getTime();
        const expiryMs = EXPIRY_DAYS * 24 * 60 * 60 * 1000;

        return (now - timestamp) > expiryMs;
    }

    function acceptAllCookies() {
        setConsent({
            analytics: true,
            experience: true,
            marketing: true
        });
    }

    function rejectAllCookies() {
        setConsent({
            analytics: false,
            experience: false,
            marketing: false
        });
    }

    function showCustomiseView() {
        document.getElementById('cookie-banner-main').classList.add('hidden');
        document.getElementById('cookie-banner-customise').classList.remove('hidden');
    }

    function saveSettings() {
        setConsent({
            analytics: document.getElementById('cookie-analytics').checked,
            experience: document.getElementById('cookie-experience').checked,
            marketing: document.getElementById('cookie-marketing').checked
        });
    }

    function toggleAccordion(id) {
        const details = document.getElementById(id);
        const icon = document.getElementById(id + '-icon');
        const allDetails = document.querySelectorAll('#cookie-accordion [id$="-details"]');
        const allIcons = document.querySelectorAll('#cookie-accordion .accordion-icon');

        const isClosing = !details.classList.contains('hidden');

        // Close all
        allDetails.forEach(d => d.classList.add('hidden'));
        allIcons.forEach(i => i.textContent = '+');

        // If it was hidden, open it
        if (!isClosing) {
            details.classList.remove('hidden');
            icon.textContent = '-';
        }
    }

    function setConsent(options) {
        const consentData = {
            choices: options,
            timestamp: new Date().getTime()
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(consentData));
        hideBanner();
    }

    function hideBanner() {
        const root = document.getElementById('privacy-banner-root');
        root.classList.add('opacity-0', 'scale-95', 'translate-y-4');
        setTimeout(() => {
            root.classList.add('hidden');
        }, 300);
    }

    function toggleDetails(id) {
        // Simple toggle logic if needed for expansion
    }
</script>