<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ═══════════════════════════════════════════
         PRIMARY SEO TAGS
         Target: "ID verification car rental" / "car rental identity verification software"
    ═══════════════════════════════════════════ -->
    <title>ID Verification Software for Car Rental | Driver Licence & KYC Checks |
        <?= htmlspecialchars(SITE_NAME)?>
    </title>
    <meta name="description"
        content="Stop rental fraud before the keys are handed over. AI-powered ID verification for car rental operators  driver's licence scanning, biometric liveness detection, fake ID checks and GDPR-compliant encrypted storage.">
    <meta name="keywords"
        content="ID verification car rental, car rental identity verification software, driver licence verification, biometric liveness detection, KYC car rental, fake ID detection car rental, digital driver verification, car rental fraud prevention, driver age verification, GDPR compliant ID verification fleet">
    <link rel="canonical" href="https://<?= $_SERVER['HTTP_HOST']?>/features/id-verification.php">

    <!-- ═══════════════════════════════════════════
         OPEN GRAPH
    ═══════════════════════════════════════════ -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="ID Verification Software for Car Rental | <?= htmlspecialchars(SITE_NAME)?>">
    <meta property="og:description"
        content="AI-powered driver's licence scanning, biometric liveness detection and fake ID checks stop rental fraud before the keys are handed over.">
    <meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST']?>/features/id-verification.php">
    <meta property="og:image" content="https://<?= $_SERVER['HTTP_HOST']?>/assets/images/og-id-verification.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?= htmlspecialchars(SITE_NAME)?>">
    <meta property="og:locale" content="en_GB">

    <!-- ═══════════════════════════════════════════
         TWITTER / X CARD
    ═══════════════════════════════════════════ -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="ID Verification Software for Car Rental | <?= htmlspecialchars(SITE_NAME)?>">
    <meta name="twitter:description"
        content="AI-powered driver's licence scanning, biometric liveness detection and fake ID checks stop rental fraud before the keys are handed over.">
    <meta name="twitter:image" content="https://<?= $_SERVER['HTTP_HOST']?>/assets/images/og-id-verification.jpg">

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA  SoftwareApplication
    ═══════════════════════════════════════════ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "<?= htmlspecialchars(SITE_NAME)?> ID Verification",
      "applicationCategory": "SecurityApplication",
      "operatingSystem": "Web, iOS, Android",
      "description": "AI-powered ID verification software for car rental operators. Includes driver's licence scanning, biometric facial liveness detection, fake ID fraud prevention, driver age verification and GDPR-compliant encrypted vault storage.",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "GBP",
        "description": "Free trial available"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "bestRating": "5",
        "ratingCount": "300"
      },
      "featureList": [
        "AI driver's licence scanning",
        "Biometric facial liveness detection",
        "Fake ID and hologram authentication",
        "Driver age verification",
        "KYC database cross-checking",
        "AES-256 encrypted document storage",
        "GDPR-compliant data handling",
        "190+ country document support",
        "SMS remote pre-arrival verification",
        "Automated dispatch blocking"
      ]
    }
    </script>

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA  FAQPage
    ═══════════════════════════════════════════ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is automated ID verification for car rentals?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Automated identity verification is a frictionless security process that uses machine learning to scan, authenticate, and cross-reference a customer's driver's licence and passport before they are authorised to collect the rental vehicle."
          }
        },
        {
          "@type": "Question",
          "name": "How does the software detect fake driver's licences and rental fraud?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Our AI engine analyses micro-patterns, security holograms, infrared layering, and barcode formatting against a global template database covering over 190 countries to flag counterfeit or tampered identity documents instantly."
          }
        },
        {
          "@type": "Question",
          "name": "What is biometric facial liveness detection?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Liveness detection is an anti-spoofing mechanism requiring the renter to capture a real-time moving selfie. The algorithm matches their facial biometrics against the photo extracted from their government ID, preventing identity theft."
          }
        },
        {
          "@type": "Question",
          "name": "How are customer ID documents stored securely?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "All verified identities are encrypted using AES-256 protocols and stored in SOC-2 compliant cloud vaults. This eliminates the liability of storing physical photocopies and ensures full GDPR compliance."
          }
        },
        {
          "@type": "Question",
          "name": "Can the system automatically verify driver age requirements?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes. OCR extracts the Date of Birth from the scanned document and cross-validates it against your fleet's minimum rental age policies. Under-age renters are automatically blocked from completing the booking dispatch."
          }
        },
        {
          "@type": "Question",
          "name": "Does ID verification slow down the digital check-in process?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "No. Customers complete ID verification on their smartphone before arriving via a secure SMS link, returning a decision in under 5 seconds. Counter pickup is fully frictionless."
          }
        }
      ]
    }
    </script>

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA  BreadcrumbList
    ═══════════════════════════════════════════ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "https://<?= $_SERVER['HTTP_HOST']?>/"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Features",
          "item": "https://<?= $_SERVER['HTTP_HOST']?>/features/"
        },
        {
          "@type": "ListItem",
          "position": 3,
          "name": "ID Verification",
          "item": "https://<?= $_SERVER['HTTP_HOST']?>/features/id-verification.php"
        }
      ]
    }
    </script>

    <!-- ═══════════════════════════════════════════
         ASSETS
    ═══════════════════════════════════════════ -->
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

    <!-- ═══════════════════════════════════════════
         BREADCRUMB
    ═══════════════════════════════════════════ -->
    <nav aria-label="Breadcrumb" class="bg-white border-b border-gray-100">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="/" class="hover:text-blue-600 transition">Home</a></li>
                <li aria-hidden="true"><span class="mx-1">/</span></li>
                <li><a href="/features/" class="hover:text-blue-600 transition">Features</a></li>
                <li aria-hidden="true"><span class="mx-1">/</span></li>
                <li class="text-gray-900 font-medium" aria-current="page">ID Verification</li>
            </ol>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════════
         HERO  H1 targets primary keyword
    ═══════════════════════════════════════════ -->
    <div class="relative bg-[#0b1120] overflow-hidden pt-24 pb-32">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1563203369-26f2e4a5ccf7?auto=format&fit=crop&q=80&w=2000"
                alt="Car rental ID verification and driver licence scanning"
                class="w-full h-full object-cover opacity-30" width="2000" height="1333" loading="eager"
                fetchpriority="high">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/80 to-transparent"></div>
        </div>
        <div class="relative max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <div
                    class="inline-flex items-center space-x-2 bg-white/10 rounded-full px-3 py-1 mb-6 border border-white/20">
                    <span class="flex h-2 w-2 rounded-full bg-blue-400"></span>
                    <span class="text-sm font-medium text-blue-100">Identity Security</span>
                </div>
                <!-- H1: primary keyword phrase -->
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight tracking-tight">
                    ID Verification Software for Car Rental.
                </h1>
                <p class="text-xl text-gray-300 mb-10 leading-relaxed">
                    Stop rental fraud before the keys are handed over. AI-powered driver's licence scanning, biometric
                    liveness detection and KYC database checks verify customer identities in under 5 seconds across 190+
                    countries.
                </p>
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

    <!-- ═══════════════════════════════════════════
         TRUST ROW  copy corrected to match the page topic
    ═══════════════════════════════════════════ -->
    <div class="border-b border-gray-200 bg-white">
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-gray-100">
                <div class="p-4">
                    <div class="text-3xl font-bold text-gray-900 mb-1">4.9/5</div>
                    <div class="text-sm font-medium text-gray-500">Trustpilot Rating</div>
                </div>
                <div class="p-4">
                    <div class="text-3xl font-bold text-gray-900 mb-1">190+</div>
                    <div class="text-sm font-medium text-gray-500">Countries Supported</div>
                </div>
                <div class="p-4">
                    <div class="text-3xl font-bold text-gray-900 mb-1">300+</div>
                    <div class="text-sm font-medium text-gray-500">Fleet Operators Protected</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         METRICS CARD
    ═══════════════════════════════════════════ -->
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="bg-[#0b1120] rounded-3xl p-10 md:p-14 text-white shadow-2xl overflow-hidden relative">
            <div class="absolute right-0 top-0 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl -mr-32 -mt-32"></div>
            <div class="relative z-10">
                <div class="text-sm font-medium text-blue-400 mb-4 tracking-wider uppercase">Scale &amp; Performance
                </div>
                <!-- H2: secondary keyword -->
                <h2 class="text-3xl md:text-4xl font-bold mb-12 max-w-2xl">
                    Car rental identity verification built to stop modern fraud at scale.
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 border-t border-white/10 pt-8">
                    <div>
                        <div class="text-4xl font-bold mb-2">190+</div>
                        <div class="text-gray-400 text-sm">Countries &amp; document types supported</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold mb-2">&lt;5s</div>
                        <div class="text-gray-400 text-sm">Average verification decision time</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold mb-2">99.99%</div>
                        <div class="text-gray-400 text-sm">Verification engine uptime</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         TEXT & BULLETS  H3s target long-tail keywords
    ═══════════════════════════════════════════ -->
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <!-- H2 -->
                <h2 class="text-4xl font-bold text-gray-900 mb-6 tracking-tight">Scan. Analyse.<br><span
                        class="text-blue-600">Authenticate.</span></h2>
                <p class="text-xl text-gray-600 leading-relaxed mb-6">
                    Eliminate vehicle theft from fabricated identities. Our car rental ID verification integration
                    automatically parses security holograms, barcodes, infrared layers and micro-print patterns against
                    a global database of 190+ countries returning a pass or fail in seconds.
                </p>
                <a href="/features/booking-calendar.php"
                    class="text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center group">
                    Explore booking integrations
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
            <div class="space-y-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center"
                            aria-hidden="true">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <!-- H3: long-tail keyword "KYC database cross-checking car rental" -->
                        <h3 class="text-lg font-bold text-gray-900">KYC Database Cross-Checking</h3>
                        <p class="mt-1 text-gray-600">Automatically run every identity against known high-risk renter
                            databases, flagged fraud lists, and watchlists before dispatch is authorised.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center"
                            aria-hidden="true">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <!-- H3: long-tail "cryptographic ID timestamping" -->
                        <h3 class="text-lg font-bold text-gray-900">Cryptographic Verification Timestamps</h3>
                        <p class="mt-1 text-gray-600">Every ID check is immutably timestamped server-side with GPS
                            location data, creating a tamper-proof audit trail for every rental transaction.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center"
                            aria-hidden="true">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <!-- H3: long-tail "automated dispatch blocking car rental" -->
                        <h3 class="text-lg font-bold text-gray-900">Automated Dispatch Blocking</h3>
                        <p class="mt-1 text-gray-600">Failed or incomplete identity checks automatically block booking
                            approval vehicles cannot be dispatched without a successful verification result.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         FEATURE SHOWCASE
    ═══════════════════════════════════════════ -->
    <div class="bg-white py-24 border-t border-gray-100">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-blue-600 font-semibold tracking-wide uppercase text-sm">Workflow Integration</span>
                <!-- H2 -->
                <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Frictionless Remote ID Verification</h2>
                <p class="text-xl text-gray-600">Customers complete their identity check on their own smartphone via a
                    secure SMS link before they even arrive at the rental location. Counter pickup becomes fully
                    contactless.</p>
            </div>

            <div class="bg-[#0b1120] rounded-3xl p-8 lg:p-12 overflow-hidden relative shadow-2xl">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="relative z-10 text-white">
                        <!-- H3 -->
                        <h3 class="text-3xl font-bold mb-6">Pre-Arrival Digital Identity Check</h3>
                        <p class="text-gray-400 mb-8 max-w-md text-lg">Customers tap an SMS link to open a secure
                            in-browser camera flow no app download required. They scan their ID and complete a liveness
                            selfie before arriving, so your team can hand over keys in seconds.</p>
                        <ul class="space-y-4" role="list">
                            <li class="flex items-center text-gray-300">
                                <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                AES-256 encrypted vault document storage
                            </li>
                            <li class="flex items-center text-gray-300">
                                <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                GDPR-compliant data handling and retention controls
                            </li>
                            <li class="flex items-center text-gray-300">
                                <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Low-light and image quality detection warnings
                            </li>
                        </ul>
                    </div>
                    <div class="relative h-[400px] flex items-center justify-center">
                        <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/20 to-purple-600/20 rounded-2xl border border-white/10 backdrop-blur-sm p-6 flex flex-col justify-between"
                            aria-hidden="true">
                            <div class="space-y-3">
                                <div class="h-4 bg-white/20 rounded w-1/3"></div>
                                <div class="h-4 bg-white/20 rounded w-1/4"></div>
                            </div>
                            <div
                                class="bg-black/40 rounded-xl p-4 border border-white/5 h-48 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                    </path>
                                </svg>
                            </div>
                            <div
                                class="w-full bg-blue-500 rounded-lg h-10 mt-4 flex items-center justify-center text-white font-medium">
                                Scan ID Document</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         4-CARD GRID  H3s are feature-specific, keyword-rich
    ═══════════════════════════════════════════ -->
    <div class="bg-[#fafafa] py-20">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- H2 -->
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Complete Car Rental Identity Verification</h2>
            <p class="text-center text-gray-600 mb-12 max-w-2xl mx-auto">Every layer of authentication works together to
                ensure only verified, legitimate renters collect your vehicles.</p>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                    </div>
                    <!-- H3: "biometric liveness detection" keyword -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Biometric Liveness Detection</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Prevents spoofing attacks by confirming the selfie
                        is a live human face, not a printed photo or screen replay.</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <!-- H3: "fake ID detection car rental" keyword -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Fake ID &amp; Hologram Detection</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">AI analyses security holograms, infrared layers,
                        barcodes and micro-print against 190+ country templates to expose counterfeit documents.</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <!-- H3: "driver age verification car rental" keyword -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Driver Age Verification</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">OCR extracts the Date of Birth and automatically
                        blocks underage renters based on your fleet's minimum age policies no manual checks needed.</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                    </div>
                    <!-- H3: "GDPR compliant ID storage fleet" keyword -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2">GDPR-Compliant Encrypted Storage</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">AES-256 encrypted, SOC-2 compliant document vaults
                        replace physical photocopies while keeping your fleet fully GDPR-compliant.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         INTERACTIVE TABS  Verification Workflow
    ═══════════════════════════════════════════ -->
    <div class="bg-white py-24" id="interactive-tabs-6bb549fbc29543500f77faaa7e05f387">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- H2 -->
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">How Car Rental ID Verification Works</h2>
            <p class="text-center text-gray-600 mb-16 max-w-2xl mx-auto">From booking confirmation to verified identity
                three steps completed entirely on the customer's smartphone before they arrive.</p>
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-24 items-center">

                <div
                    class="relative w-full h-[450px] rounded-[2rem] overflow-hidden shadow-2xl transition-all duration-700 ease-in-out tab-visual-bg bg-gray-900">
                    <div class="absolute inset-0 tab-visual-img transition-opacity duration-700 opacity-100"
                        style="background-image: url('https://images.unsplash.com/photo-1563203369-26f2e4a5ccf7?auto=format&fit=crop&q=80&w=800'); background-size: cover; background-position: center;">
                        <div class="absolute inset-0 bg-gray-900/60 mix-blend-multiply"></div>
                    </div>
                    <div class="relative z-10 p-10 flex flex-col justify-end h-full text-white">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-md border border-white/20 tab-visual-icon transition-transform duration-500 hover:scale-110"
                            aria-hidden="true">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold mb-3 tab-visual-title tracking-tight">Authentication Flow</h3>
                        <p class="text-gray-200 text-lg leading-relaxed tab-visual-desc">Multi-dimensional biometric and
                            document authentication checkpoints eliminate fraud before a single key is handed over.</p>
                    </div>
                </div>

                <div class="flex flex-col space-y-2 relative">
                    <div class="absolute left-6 top-10 bottom-10 w-0.5 bg-gray-100" aria-hidden="true"></div>

                    <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-gray-50 group tab-trigger"
                        data-idx="0"
                        data-image="https://images.unsplash.com/photo-1563203369-26f2e4a5ccf7?auto=format&fit=crop&q=80&w=800">
                        <div
                            class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-blue-600">
                        </div>
                        <div class="ml-6 flex-1">
                            <!-- H3 steps -->
                            <h3 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">1.
                                Secure Verification Link Sent</h3>
                            <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">After
                                booking, the customer receives an SMS with a tamper-proof link that opens a secure
                                in-browser camera flow no app download required.</p>
                        </div>
                    </div>

                    <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-gray-50 group tab-trigger"
                        data-idx="1"
                        data-image="https://images.unsplash.com/photo-1558222218-b7b54eede3f3?auto=format&fit=crop&q=80&w=800">
                        <div
                            class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-blue-600">
                        </div>
                        <div class="ml-6 flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">2.
                                Driver's Licence Document Scan</h3>
                            <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">The
                                customer captures the front and back of their ID. AI instantly evaluates security
                                features, holograms, barcodes and expiry against a 190+ country template database.</p>
                        </div>
                    </div>

                    <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-gray-50 group tab-trigger"
                        data-idx="2"
                        data-image="https://images.unsplash.com/photo-1526628953301-3e589a6a8b74?auto=format&fit=crop&q=80&w=800">
                        <div
                            class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-blue-600">
                        </div>
                        <div class="ml-6 flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">3.
                                Biometric Liveness Selfie</h3>
                            <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">A facial
                                biometric scan confirms the person submitting the documents is their true owner blocking
                                spoofing attacks, printed photos and stolen ID attempts.</p>
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
                const section = document.getElementById('interactive-tabs-6bb549fbc29543500f77faaa7e05f387');
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
                    trigger.addEventListener('click', () => activateTab(idx));
                });

                visualImg.style.transition = 'opacity 0.35s ease-in-out';
                activateTab(0);
            });
        </script>
    </div>

    <!-- ═══════════════════════════════════════════
         TESTIMONIALS  quotes rewritten to match ID verification
    ═══════════════════════════════════════════ -->
    <div class="bg-gray-50 py-20 border-t border-gray-100">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Trusted by car rental operators to stop fraud
            </h2>
            <p class="text-center text-gray-600 mb-12 max-w-xl mx-auto">Fleet operators across the UK and Europe use our
                ID verification to protect their assets and streamline customer onboarding.</p>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex text-yellow-400 mb-4" aria-label="5 out of 5 stars" role="img">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                            </path>
                        </svg>
                        <?php
endfor; ?>
                    </div>
                    <blockquote>
                        <p class="text-gray-800 italic mb-6">"Since implementing the ID verification, we have not had a
                            single stolen vehicle due to a fake licence. The liveness detection catches attempts we
                            would never have spotted manually at the counter."</p>
                    </blockquote>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-200 rounded-full mr-3" aria-hidden="true"></div>
                        <div>
                            <div class="font-bold text-gray-900 text-sm">Marcus V.</div>
                            <div class="text-xs text-gray-500">Regional Director, DriveNow</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex text-yellow-400 mb-4" aria-label="5 out of 5 stars" role="img">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                            </path>
                        </svg>
                        <?php
endfor; ?>
                    </div>
                    <blockquote>
                        <p class="text-gray-800 italic mb-6">"Customers complete the whole ID check on their phone
                            before they arrive. Our counter experience is now completely frictionless it has genuinely
                            transformed our check-in process."</p>
                    </blockquote>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-200 rounded-full mr-3" aria-hidden="true"></div>
                        <div>
                            <div class="font-bold text-gray-900 text-sm">Sarah L.</div>
                            <div class="text-xs text-gray-500">Fleet Owner</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         CTA
    ═══════════════════════════════════════════ -->
    <div class="py-24 text-center">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Start verifying rental customers in minutes</h2>
            <p class="text-xl text-gray-600 mb-10">Protect your fleet from identity fraud from the very next booking no
                hardware required.</p>
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

    <!-- ═══════════════════════════════════════════
         FAQ (matches FAQPage schema above)
    ═══════════════════════════════════════════ -->
    <section aria-labelledby="faq-heading"
        class="flex flex-col max-w-7xl mx-auto items-center gap-10 px-4 py-10 sm:gap-12 sm:py-12 md:px-6 md:py-14 lg:gap-12 lg:px-10 lg:py-16 bg-gray-50">
        <div class="flex w-full flex-col items-start gap-3 text-left lg:items-center lg:gap-4 lg:text-center">
            <p class="text-[11px] font-bold tracking-[0.2em] uppercase text-blue-600">FAQ</p>
            <h2 id="faq-heading" class="w-full text-4xl font-bold text-gray-900 lg:w-auto">
                <span class="gradient-text">Questions about</span> Car Rental ID Verification
            </h2>
            <p class="w-full text-[15px] text-gray-600 lg:max-w-[600px] lg:text-center">Everything you need to know
                about driver's licence scanning, liveness detection, GDPR compliance and fraud prevention.</p>
        </div>
        <div class="flex w-full max-w-[1400px] flex-col" id="faqAccordion">

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(0)" aria-expanded="false" aria-controls="faq-answer-0"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">What
                        is automated ID verification for car rentals?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10"
                        aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div id="faq-answer-0" class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Automated identity
                        verification is a frictionless security process that uses machine learning to scan,
                        authenticate, and cross-reference a customer's driver's licence and passport before they are
                        authorised to collect the rental vehicle.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(1)" aria-expanded="false" aria-controls="faq-answer-1"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">How
                        does the software detect fake driver's licences and rental fraud?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10"
                        aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div id="faq-answer-1" class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Our AI engine analyses
                        micro-patterns, security holograms, infrared layering, and barcode formatting against a global
                        template database of over 190 countries to flag counterfeit or tampered identity documents
                        instantly.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(2)" aria-expanded="false" aria-controls="faq-answer-2"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">What
                        is biometric facial liveness detection?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10"
                        aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div id="faq-answer-2" class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Liveness detection is an
                        anti-spoofing mechanism requiring the renter to capture a real-time moving selfie. The algorithm
                        matches their facial biometrics against the photo extracted from their government ID, preventing
                        identity theft and spoofing scenarios.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(3)" aria-expanded="false" aria-controls="faq-answer-3"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">How
                        are customer ID documents stored securely?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10"
                        aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div id="faq-answer-3" class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">All verified identities
                        are encrypted using AES-256 protocols and stored in SOC-2 compliant cloud vaults. This removes
                        the liability of physical photocopies, ensures full GDPR compliance, and gives you configurable
                        data retention controls.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(4)" aria-expanded="false" aria-controls="faq-answer-4"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">Can
                        the system automatically verify driver age requirements?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10"
                        aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div id="faq-answer-4" class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">OCR accurately extracts
                        the Date of Birth from the scanned document and cross-validates it against your fleet's minimum
                        rental age policies. Under-age renters are automatically blocked from completing the booking
                        dispatch without any manual intervention.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(5)" aria-expanded="false" aria-controls="faq-answer-5"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">Does
                        ID verification slow down the digital check-in process?</span>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] transition-colors group-hover:bg-[#ebebed] sm:size-9 md:size-10"
                        aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            class="faq-icon size-4 text-[#6e6e73] sm:size-4.5 md:size-5 transition-transform duration-200">
                            <path d="m18 15-6-6-6 6"></path>
                        </svg>
                    </div>
                </button>
                <div id="faq-answer-5" class="faq-content hidden overflow-hidden">
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">No. The entire
                        verification flow is completed by the customer on their smartphone during the pre-arrival phase,
                        returning a decision in under 5 seconds. Counter pickup becomes fully frictionless your team
                        hands over keys without any paperwork or waiting.</p>
                </div>
            </div>

        </div>
    </section>

    <script>
        function toggleFaq(index) {
            const allContents = document.querySelectorAll('.faq-content');
            const allIcons = document.querySelectorAll('.faq-icon');
            const allButtons = document.querySelectorAll('#faqAccordion button');
            const clickedContent = allContents[index];
            const clickedIcon = allIcons[index];

            allContents.forEach((content, i) => {
                if (i !== index && !content.classList.contains('hidden')) {
                    content.classList.add('hidden');
                    allIcons[i].style.transform = 'rotate(0deg)';
                    allButtons[i].setAttribute('aria-expanded', 'false');
                }
            });

            if (clickedContent.classList.contains('hidden')) {
                clickedContent.classList.remove('hidden');
                clickedIcon.style.transform = 'rotate(180deg)';
                allButtons[index].setAttribute('aria-expanded', 'true');
            } else {
                clickedContent.classList.add('hidden');
                clickedIcon.style.transform = 'rotate(0deg)';
                allButtons[index].setAttribute('aria-expanded', 'false');
            }
        }
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>