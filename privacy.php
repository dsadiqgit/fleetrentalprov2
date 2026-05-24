<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy |
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <style>
        .legal-content h2 {
            scroll-margin-top: 100px;
        }

        .toc-link.active {
            color: #0f172a;
            font-weight: 500;
            border-left-color: #0f172a;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-white font-light text-slate-900 leading-relaxed selection:bg-slate-100">
    <div id="progress-bar" class="fixed top-0 left-0 h-1 bg-slate-900 z-50 transition-all duration-150"
        style="width: 0%"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar Navigation -->
        <aside class="hidden lg:block w-80 border-r border-slate-50 sticky top-0 h-screen overflow-y-auto p-12">
            <div class="mb-12">
                <a href="/">
                    <img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro" class="h-10 w-auto">
                </a>
            </div>

            <nav class="space-y-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 mb-6">Privacy Navigation</p>
                <a href="#roles"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">1.
                    Key Roles</a>
                <a href="#data-collection"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">2.
                    Data We Collect</a>
                <a href="#sources"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">3.
                    Sources of Data</a>
                <a href="#purpose"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">4.
                    Why We Process</a>
                <a href="#legal-base"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">5.
                    Legal Basis</a>
                <a href="#sharing"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">6.
                    Data Sharing</a>
                <a href="#retention"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">7.
                    Data Retention</a>
                <a href="#security"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">8.
                    Security Protocol</a>
                <a href="#rights"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">9.
                    Your Rights</a>
            </nav>

            <div class="mt-20">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 mb-4">Request Access</p>
                <a href="mailto:support@fleetrentalpro.com"
                    class="text-sm text-slate-400 hover:text-slate-900 transition-colors">Data Portability</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:max-w-5xl">
            <header
                class="lg:hidden p-6 border-b border-slate-50 flex justify-between items-center sticky top-0 bg-white/80 backdrop-blur-md z-40">
                <img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro" class="h-8 w-auto">
                <button id="mobile-menu-toggle" class="text-slate-400"><svg class="w-6 h-6" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16m-7 6h7" stroke-width="2"></path>
                    </svg></button>
            </header>

            <div class="px-6 py-16 lg:px-24 lg:py-32 legal-content">
                <div class="mb-16">
                    <h1 class="text-5xl font-light tracking-tighter text-slate-950 mb-6 leading-tight">Privacy <span
                            class="text-slate-400 italic">Policy</span></h1>
                    <div class="flex items-center gap-6 text-xs uppercase tracking-widest text-slate-400 font-medium">
                        <span>Version 1.0</span>
                        <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                        <span>Updated Jan 12, 2025</span>
                    </div>
                </div>

                <div
                    class="prose prose-slate max-w-none prose-h2:font-light prose-h2:text-3xl prose-h2:tracking-tight prose-h2:mt-20 prose-h2:mb-8 prose-p:text-slate-600 prose-p:text-lg prose-p:leading-relaxed prose-strong:text-slate-900">
                    <p
                        class="bg-slate-50 p-6 rounded-2xl border border-slate-100 text-slate-500 font-light italic mb-12">
                        Controller: Fleet Rental Pro Sàrl, Rue de Genève 100, 1004 Lausanne, Switzerland (“Fleet Rental
                        Pro”, “we”, “us”)
                    </p>

                    <p class="text-xl text-slate-900 font-normal mb-12">Fleet Rental Pro takes privacy and data
                        sovereignty seriously. This policy details how we handle the complex datasets required for elite
                        fleet management.</p>

                    <h2 id="roles">1) Key Roles & Data Flow</h2>
                    <p>Fleet Rental Pro operates as a SaaS platform. Depending on the action, we act as both Controller
                        (for your workspace accounts) and Processor (for the End User data you collect via booking
                        pages). If you are an End User, the primary data controller is the rental company you are
                        booking with.</p>

                    <h2 id="data-collection">2) Personal Data Categories</h2>
                    <p><strong>Customer Identity:</strong> Name, Email, Workspace settings, and Billing identifiers used
                        for service provision.</p>
                    <p><strong>Fleet Operations Data:</strong> Vehicle metadata, pricing structures, pickup locations,
                        and operational notes managed by the Customer.</p>
                    <p><strong>End User PII:</strong> Names, contact details, and booking histories submitted via
                        autonomous booking flows.</p>
                    <p><strong>Biometric/Identity Metadata:</strong> Results from ID verification (Veriff) and
                        electronic signature audit trails (Skribble).</p>

                    <h2 id="sources">3) Sources of Information</h2>
                    <p>We aggregate data from (a) direct inputs by workspace administrators, (b) End User interactions
                        with booking pages, and (c) technical handshakes with third-party sub-processors including
                        Stripe and Google.</p>

                    <h2 id="purpose">4) Purpose of Processing</h2>
                    <p>Information is utilised to optimise fleet utilisation, automate billing lifecycles, and prevent
                        fraudulent rental activities. We do not sell datasets for marketing purposes; all data
                        processing is strictly for the maintenance and enhancement of the Service.</p>

                    <h2 id="legal-base">5) Legal Bases for Processing</h2>
                    <p>Our operations rely on (i) Contract Necessity for paid subscribers, (ii) Legitimate Interests for
                        platform security, and (iii) Legal Obligations regarding fiscal record-keeping.</p>

                    <h2 id="sharing">6) International Data Sharing</h2>
                    <p>Data may be shared with specialised sub-processors: Stripe (Payments), Veriff (ID Verification),
                        Skribble (E-Sign), Intercom (Support), and Cloudflare (Security). We ensure all partners adhere
                        to equivalent or higher stringency regarding data protection.</p>

                    <h2 id="retention">7) Data Sovereignty & Retention</h2>
                    <p>We retain operational data as long as a workspace is active. Upon account termination, all
                        identifying data is purged from production databases within 30 days, except where legal
                        retention is mandatory.</p>

                    <h2 id="security">8) Security Protocol</h2>
                    <p>Fleet Rental Pro employs role-based access controls (RBAC), end-to-end TLS encryption, and
                        automated intrusion detection. We recommend all administrators enable Hardware-based
                        Multi-Factor Authentication.</p>

                    <h2 id="rights">9) Your Rights Under GDPR/FADP</h2>
                    <p>You have the right to access, rectify, or purge your data. For End User requests, we facilitate
                        the Customer’s response as the technical processor. All data portability requests should be
                        directed to support@fleetrentalpro.com.</p>

                    <div class="mt-32 p-12 bg-slate-900 rounded-3xl text-white">
                        <h3 class="text-2xl font-light mb-4">Data Access Request</h3>
                        <p class="text-slate-400 font-light mb-8">Need a full export of your fleet data or an audit of
                            your organizational access logs?</p>
                        <a href="mailto:support@fleetrentalpro.com"
                            class="inline-block px-8 py-3 bg-white text-slate-900 rounded-xl font-medium hover:bg-slate-100 transition-colors uppercase tracking-widest text-xs font-black">Request
                            Access</a>
                    </div>
                </div>

                <footer
                    class="mt-32 pt-10 border-t border-slate-50 flex justify-between items-center text-[10px] text-slate-400 uppercase tracking-widest">
                    <span>&copy;
                        <?= date('Y')?> Fleet Rental Pro Sàrl
                    </span>
                    <div class="flex gap-8">
                        <a href="/privacy.php" class="hover:text-slate-900 transition-colors">Privacy</a>
                        <a href="/terms.php" class="hover:text-slate-900 transition-colors">Terms</a>
                    </div>
                </footer>
            </div>
        </main>
    </div>

    <script>
        window.addEventListener('scroll', () => {
            const h = document.documentElement,
                b = document.body,
                st = 'scrollTop',
                sh = 'scrollHeight';
            const percent = (h[st] || b[st]) / ((h[sh] || b[sh]) - h.clientHeight) * 100;
            document.getElementById('progress-bar').style.width = percent + '%';
        });

        // Scroll-spy: highlight the TOC link for the section nearest the top of the viewport
        const headings = Array.from(document.querySelectorAll('.legal-content h2'));
        const tocLinks = document.querySelectorAll('.toc-link');

        function updateActiveToc() {
            let activeId = null;
            for (const h of headings) {
                if (h.getBoundingClientRect().top <= 140) {
                    activeId = h.id;
                } else {
                    break;
                }
            }
            tocLinks.forEach(link => {
                link.classList.toggle('active', link.getAttribute('href') === '#' + activeId);
            });
        }

        window.addEventListener('scroll', updateActiveToc, { passive: true });
        updateActiveToc();
    </script>
</body>

</html>