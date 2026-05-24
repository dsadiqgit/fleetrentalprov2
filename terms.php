<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service |
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

<body class="bg-white font-light text-slate-900 leading-relaxed selection:bg-slate-100 terms">
    <!-- Progress Bar -->
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
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 mb-6">Table of Contents</p>
                <a href="#definitions"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">1.
                    Definitions</a>
                <a href="#scope"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">2.
                    Scope of Service</a>
                <a href="#geography"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">3.
                    Geographic Availability</a>
                <a href="#account"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">4.
                    Security & Access</a>
                <a href="#trial"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">5.
                    Trial Policy</a>
                <a href="#billing"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">6.
                    Subscriptions</a>
                <a href="#payments"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">7.
                    Stripe Integration</a>
                <a href="#verification"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">8.
                    ID & E-Sign</a>
                <a href="#ip"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">9.
                    Intellectual Property</a>
                <a href="#liability"
                    class="toc-link block py-2 border-l-2 border-transparent pl-4 text-sm text-slate-400 hover:text-slate-900 transition-all">10.
                    Liability</a>
            </nav>

            <div class="mt-20">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300 mb-4">Support</p>
                <a href="/contact" class="text-sm text-slate-400 hover:text-slate-900 transition-colors">Contact
                    Us</a>
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
                    <h1 class="text-5xl font-light tracking-tighter text-slate-950 mb-6 leading-tight">General Terms &
                        <span class="text-slate-400 italic">Conditions</span>
                    </h1>
                    <div class="flex items-center gap-6 text-xs uppercase tracking-widest text-slate-400 font-medium">
                        <span>Version 2.0</span>
                        <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                        <span>Effective Jan 15, 2025</span>
                    </div>
                </div>

                <div
                    class="prose prose-slate max-w-none prose-h2:font-light prose-h2:text-3xl prose-h2:tracking-tight prose-h2:mt-20 prose-h2:mb-8 prose-p:text-slate-600 prose-p:text-lg prose-p:leading-relaxed prose-strong:text-slate-900">
                    <p
                        class="bg-slate-50 p-6 rounded-2xl border border-slate-100 text-slate-500 font-light italic mb-12">
                        Provider: Fleet Rental Pro Sàrl, Rue de Genève 100, 1004 Lausanne, Switzerland (“Fleet Rental
                        Pro”, “we”, “us”)
                    </p>

                    <p class="text-xl text-slate-900 font-normal mb-12">These General Terms and Conditions (“Terms”)
                        govern access to and use of the Fleet Rental Pro platform. By creating an account or using the
                        Service, you signify your irrevocable acceptance of these Terms.</p>

                    <h2 id="definitions">1) Definitions</h2>
                    <p><strong>Customer:</strong> The individual or legal entity subscribing to the Service (including
                        sole proprietors) who manages fleet operations via the platform.</p>
                    <p><strong>Authorised Users:</strong> Individuals authorised by the Customer to access the Service,
                        including administrators, operators, and staff members.</p>
                    <p><strong>End User:</strong> Any individual who interacts with a booking page, checkout flow, or
                        digital contract generated by the Service to reserve or rent a vehicle from the Customer.</p>

                    <h2 id="scope">2) Scope of the Service</h2>
                    <p>2.1 <strong>Software as a Service (SaaS):</strong> Fleet Rental Pro provides exclusive
                        cloud-based tools for fleet management, dynamic pricing, and booking automation. We do not own,
                        rent, or manage vehicles directly.</p>
                    <p>2.2 <strong>Operational Autonomy:</strong> The Customer maintains full responsibility for vehicle
                        maintenance, insurance coverage, and compliance with local transportation regulations.</p>

                    <h2 id="geography">3) Geographic Availability</h2>
                    <p>3.1 <strong>Territorial Restrictions:</strong> The Service is not offered to entities or
                        individuals operating within the United States or Canada. Attempts to utilise the Service for
                        USA/Canada-based rentals may result in immediate account termination.</p>

                    <h2 id="account">4) Security & Core Access</h2>
                    <p>Security is a shared responsibility. While we employ industry-standard encryption, Customers must
                        ensure all Authorised Users maintain strict credential hygiene and utilise Multi-Factor
                        Authentication where provided.</p>

                    <h2 id="trial">5) Trial Policy</h2>
                    <p>Trial accounts are provided for internal evaluation only. We reserve the right to limit API
                        throughput and booking volume during the 30-day trial period to ensure platform stability for
                        paid subscribers.</p>

                    <h2 id="billing">6) Subscriptions & Billing</h2>
                    <p>Our "No-Refund" policy is absolute to the height of the law. Subscriptions are billed in advance
                        and maintain auto-renewal status unless explicitly cancelled via the dashboard 48 hours prior to
                        the next billing cycle.</p>

                    <h2 id="payments">7) Stripe & Financial Flows</h2>
                    <p>Financial transactions are handled via Stripe Connect. Fleet Rental Pro acts as a software
                        bridge; we do not touch, hold, or transmit funds directly, and all financial disputes are
                        subject to the Stripe Service Agreement.</p>

                    <h2 id="verification">8) Biometric ID & E-Sign</h2>
                    <p>Automated verification (Veriff) and digital signatures (Skribble) are provided as risk-reduction
                        tools. They do not constitute legal advice or a guarantee of ID authenticity.</p>

                    <h2 id="ip">9) Intellectual Property</h2>
                    <p>All source code, UI/UX designs, and logic underlying Fleet Rental Pro remain our exclusive
                        property. Customers are granted a non-transferable licence to utilise these tools for their
                        specific organization.</p>

                    <h2 id="liability">10) Limitation of Liability</h2>
                    <p>Fleet Rental Pro Sàrl shall not be liable for any revenue loss, vehicle damage, or legal disputes
                        arising from Customer's rental activities. Our total liability is capped at the fees paid during
                        the preceding 12-month period.</p>

                    <div class="mt-32 p-12 bg-slate-900 rounded-3xl text-white">
                        <h3 class="text-2xl font-light mb-4">Regulatory Compliance</h3>
                        <p class="text-slate-400 font-light mb-8">Need a custom Data Processing Addendum (DPA) or have
                            specific regulatory requirements for your jurisdiction?</p>
                        <a href="mailto:support@fleetrentalpro.com"
                            class="inline-block px-8 py-3 bg-white text-slate-900 rounded-xl font-medium hover:bg-slate-100 transition-colors uppercase tracking-widest text-xs font-black">Contact
                            Legal</a>
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
        // Progress bar logic
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