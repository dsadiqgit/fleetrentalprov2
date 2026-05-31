<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ═══════════════════════════════════════════
         PRIMARY SEO TAGS
    ═══════════════════════════════════════════ -->
    <title>Fleet Booking Calendar Software for Car Rental |
        <?= htmlspecialchars(SITE_NAME)?>
    </title>
    <meta name="description"
        content="Replace spreadsheets with a drag-and-drop fleet booking calendar. Real-time vehicle availability, automated conflict prevention, maintenance blocking and iCal sync: built for car rental operators.">
    <meta name="keywords"
        content="fleet booking calendar, car rental scheduling software, vehicle dispatch calendar, fleet management booking system, rental reservation software, drag and drop fleet calendar">
    <link rel="canonical" href="https://<?= $_SERVER['HTTP_HOST']?>/features/booking-calendar.php">

    <!-- ═══════════════════════════════════════════
         OPEN GRAPH (Facebook / LinkedIn)
    ═══════════════════════════════════════════ -->
    <meta property="og:type" content="website">
    <meta property="og:title"
        content="Fleet Booking Calendar Software for Car Rental | <?= htmlspecialchars(SITE_NAME)?>">
    <meta property="og:description"
        content="Replace spreadsheets with a drag-and-drop fleet booking calendar. Real-time vehicle availability, automated conflict prevention, maintenance blocking and iCal sync.">
    <meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST']?>/features/booking-calendar.php">
    <meta property="og:image" content="https://<?= $_SERVER['HTTP_HOST']?>/assets/images/og-booking-calendar.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?= htmlspecialchars(SITE_NAME)?>">
    <meta property="og:locale" content="en_GB">

    <!-- ═══════════════════════════════════════════
         TWITTER / X CARD
    ═══════════════════════════════════════════ -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title"
        content="Fleet Booking Calendar Software for Car Rental | <?= htmlspecialchars(SITE_NAME)?>">
    <meta name="twitter:description"
        content="Replace spreadsheets with a drag-and-drop fleet booking calendar. Real-time sync, conflict prevention and maintenance blocking.">
    <meta name="twitter:image" content="https://<?= $_SERVER['HTTP_HOST']?>/assets/images/og-booking-calendar.jpg">

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA: SoftwareApplication
    ═══════════════════════════════════════════ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "<?= htmlspecialchars(SITE_NAME)?> Fleet Booking Calendar",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web, iOS, Android",
      "description": "Drag-and-drop fleet booking calendar for car rental operators. Real-time vehicle availability, automated double-booking prevention, maintenance blocking and iCal synchronisation.",
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
        "Drag-and-drop vehicle assignment",
        "Real-time booking sync",
        "Maintenance blocking",
        "iCal / ICS synchronisation",
        "Double-booking prevention",
        "Mobile-first PWA interface"
      ]
    }
    </script>

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA: FAQPage
    ═══════════════════════════════════════════ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is a fleet management booking calendar software?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Fleet management booking calendar software is a centralised digital dispatch dashboard for car rental businesses. It provides real-time visualisation of vehicle availability, maintenance schedules, and reservation conflicts to optimise daily fleet operations."
          }
        },
        {
          "@type": "Question",
          "name": "How does the drag-and-drop car rental dispatch system work?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "The drag-and-drop interface allows fleet managers to reassign vehicle bookings instantly. When a reservation conflict or late return occurs, simply drag the rental block to another available asset, and the system updates without page reloads."
          }
        },
        {
          "@type": "Question",
          "name": "Can I sync the booking calendar with third-party rental platforms?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes. The booking calendar features bidirectional iCal (ICS) synchronisation, allowing you to import reservations from third-party marketplaces and export direct website bookings, preventing double-bookings across your multi-channel rental strategy."
          }
        },
        {
          "@type": "Question",
          "name": "Does the vehicle scheduling software prevent double reservations?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes. The scheduling algorithm uses millisecond-level database locking to guarantee that two customers cannot book the same vehicle simultaneously, preventing double-booking during peak seasonal demand."
          }
        },
        {
          "@type": "Question",
          "name": "How do you handle maintenance and repair out-of-service periods?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "You can instantly create maintenance blocks on the calendar for servicing. These tags automatically remove the vehicle from your online storefront, ensuring customers only see road-ready bookable vehicles."
          }
        },
        {
          "@type": "Question",
          "name": "Is the fleet calendar view optimised for mobile devices?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes. The booking calendar is built using mobile-first PWA standards. Dispatchers and lot attendants can swipe horizontally through the timeline on tablets and smartphones."
          }
        }
      ]
    }
    </script>

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA: BreadcrumbList
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
          "name": "Booking Calendar",
          "item": "https://<?= $_SERVER['HTTP_HOST']?>/features/booking-calendar.php"
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
         HERO: H1 is the primary on-page keyword
    ═══════════════════════════════════════════ -->
    <div class="relative bg-[#0b1120] overflow-hidden pt-24 pb-32">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1506784951206-39622414e365?auto=format&fit=crop&q=80&w=2000"
                alt="Car rental fleet scheduling calendar overview" class="w-full h-full object-cover opacity-30"
                width="2000" height="1333" loading="eager" fetchpriority="high">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/80 to-transparent"></div>
        </div>
        <div class="relative max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <div
                    class="inline-flex items-center space-x-2 bg-white/10 rounded-full px-3 py-1 mb-6 border border-white/20">
                    <span class="flex h-2 w-2 rounded-full bg-blue-400"></span>
                    <span class="text-sm font-medium text-blue-100">Fleet Scheduling</span>
                </div>
                <!-- H1: primary keyword phrase -->
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight tracking-tight">
                    Fleet Booking Calendar for Car Rental.
                </h1>
                <p class="text-xl text-gray-300 mb-10 leading-relaxed">
                    Replace spreadsheets with a high-performance drag-and-drop scheduling engine. Get a master view of
                    your entire vehicle inventory, prevent double-bookings in milliseconds, and coordinate every
                    dispatch from one screen.
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
         TRUST ROW
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
                    <div class="text-3xl font-bold text-gray-900 mb-1">10M+</div>
                    <div class="text-sm font-medium text-gray-500">Bookings Managed</div>
                </div>
                <div class="p-4">
                    <div class="text-3xl font-bold text-gray-900 mb-1">300+</div>
                    <div class="text-sm font-medium text-gray-500">Fleet Operators Worldwide</div>
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
                    Booking calendar built to manage thousands of vehicles.
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 border-t border-white/10 pt-8">
                    <div>
                        <div class="text-4xl font-bold mb-2">50ms</div>
                        <div class="text-gray-400 text-sm">Calendar synchronisation speed</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold mb-2">300+</div>
                        <div class="text-gray-400 text-sm">Enterprise fleets supported</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold mb-2">99.99%</div>
                        <div class="text-gray-400 text-sm">Scheduling engine uptime</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         TEXT & BULLETS SPLIT
    ═══════════════════════════════════════════ -->
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <!-- H2: secondary keyword -->
                <h2 class="text-4xl font-bold text-gray-900 mb-6 tracking-tight">
                    Visualise. Schedule.<br><span class="text-blue-600">Optimise.</span>
                </h2>
                <p class="text-xl text-gray-600 leading-relaxed mb-6">
                    Get a birds-eye view of your entire operational week. Spot utilisation gaps instantly and drag
                    unassigned bookings onto available vehicles without leaving the calendar view.
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
                <!-- H3s describe specific features for long-tail SEO -->
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
                        <h3 class="text-lg font-bold text-gray-900">Maintenance Blocking</h3>
                        <p class="mt-1 text-gray-600">Block out dates for scheduled servicing so vehicles are
                            automatically removed from your booking calendar and cannot be reserved.</p>
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
                        <h3 class="text-lg font-bold text-gray-900">Real-Time Conflict Prevention</h3>
                        <p class="mt-1 text-gray-600">Millisecond-level database locking ensures two customers can never
                            successfully book the same vehicle simultaneously.</p>
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
                        <h3 class="text-lg font-bold text-gray-900">Automated Dispatch Blocking</h3>
                        <p class="mt-1 text-gray-600">Require digital sign-offs before a booking is approved for
                            dispatch, creating a clear audit trail for every rental.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         FEATURE SHOWCASE — Real-Time Sync
    ═══════════════════════════════════════════ -->
    <div class="bg-white py-24 border-t border-gray-100">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-blue-600 font-semibold tracking-wide uppercase text-sm">Workflow Integration</span>
                <!-- H2 -->
                <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Real-Time Booking Calendar Sync</h2>
                <p class="text-xl text-gray-600">When a customer books on your website, the availability block appears
                    on your dispatcher's screen in milliseconds: no page refresh required.</p>
            </div>

            <div class="bg-[#0b1120] rounded-3xl p-8 lg:p-12 overflow-hidden relative shadow-2xl">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="relative z-10 text-white">
                        <!-- H3 -->
                        <h3 class="text-3xl font-bold mb-6">Instant Cross-Channel Availability Updates</h3>
                        <p class="text-gray-400 mb-8 max-w-md text-lg">Whether a booking comes in via your direct
                            website, a third-party marketplace, or manually from a phone call, every channel sees the
                            same live availability the moment the reservation is confirmed.</p>
                        <ul class="space-y-4" role="list">
                            <li class="flex items-center text-gray-300">
                                <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Bidirectional iCal / ICS synchronisation
                            </li>
                            <li class="flex items-center text-gray-300">
                                <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Conflict prevention lockouts at point of booking
                            </li>
                            <li class="flex items-center text-gray-300">
                                <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Drag-and-drop reassignment with zero page reloads
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
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                            <div
                                class="w-full bg-blue-500 rounded-lg h-10 mt-4 flex items-center justify-center text-white font-medium">
                                Assign Vehicle</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         4-CARD GRID
    ═══════════════════════════════════════════ -->
    <div class="bg-[#fafafa] py-20">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- H2 -->
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Advanced Fleet Calendar Capabilities</h2>
            <p class="text-center text-gray-600 mb-12 max-w-2xl mx-auto">Every feature is designed to reduce operational
                overhead and maximise vehicle utilisation across your rental fleet.</p>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <!-- H3 -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Turnaround Buffer Times</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Automatically inject cleaning and turnaround
                        buffers between returning and departing rentals to prevent rushed handovers.</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Immutable Booking Timestamps</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Exact millisecond logs prevent liability-shifting
                        disputes by creating a tamper-proof record of every reservation event.</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Digital Rental Sign-off</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Both tenant and fleet manager submit secure
                        e-signatures authenticating the accepted vehicle state before keys are handed over.</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-4"
                        aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Instant Claims Export</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Generate PDF incident reports with before/after
                        comparisons instantly, ready to submit to your insurer.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         INTERACTIVE TABS — Scheduling Workflow
    ═══════════════════════════════════════════ -->
    <div class="bg-white py-24" id="interactive-scheduling-section">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-blue-600 font-bold tracking-[0.2em] uppercase text-xs">Efficient Logistics</span>
                <h2 class="text-4xl font-extrabold text-gray-900 mt-3 mb-4">How the Fleet Booking Calendar Works</h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto leading-relaxed">From inbound reservation to confirmed
                    dispatch: three steps, zero confusion.</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">

                <!-- Left Dynamic Visual Window -->
                <div
                    class="relative w-full h-[520px] rounded-[2.5rem] overflow-hidden shadow-2xl transition-all duration-700 ease-in-out bg-gray-900 group">
                    <!-- Background Image Layer -->
                    <div class="absolute inset-0 tab-visual-img transition-all duration-700 opacity-100 scale-105 group-hover:scale-100"
                        style="background-image: url('https://images.unsplash.com/photo-1506784951206-39622414e365?auto=format&fit=crop&q=80&w=800'); background-size: cover; background-position: center;">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent">
                        </div>
                    </div>

                    <!-- Content Overlay -->
                    <div class="relative z-10 p-12 flex flex-col justify-end h-full">
                        <div class="inline-flex items-center mb-6">
                            <div
                                class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/30 tab-visual-icon-container">
                                <svg class="w-6 h-6 text-white tab-visual-svg" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="ml-4 overflow-hidden">
                                <p class="text-blue-400 font-bold uppercase tracking-widest text-[10px] mb-0.5">Active
                                    Step</p>
                                <span
                                    class="text-white font-bold text-sm uppercase tracking-wider tab-visual-phase-label">Phase
                                    01</span>
                            </div>
                        </div>

                        <h3 class="text-4xl font-bold text-white mb-4 tab-visual-title leading-tight">Scheduling
                            Sequence</h3>
                        <p class="text-gray-300 text-lg leading-relaxed tab-visual-desc max-w-md">A centralised
                            graphical overview of every asset allowing instant conflict resolution and flawless daily
                            coordination.</p>

                        <!-- Mini Progress Bar in Image -->
                        <div class="mt-8 flex gap-2">
                            <div class="h-1 flex-1 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 w-full transition-all duration-500 tab-progress-0"></div>
                            </div>
                            <div class="h-1 flex-1 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 w-0 transition-all duration-500 tab-progress-1"></div>
                            </div>
                            <div class="h-1 flex-1 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 w-0 transition-all duration-500 tab-progress-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Interactive Steps -->
                <div class="flex flex-col space-y-4 relative">
                    <!-- Progress Line background -->
                    <div class="absolute left-10 top-0 bottom-0 w-px bg-gray-100" aria-hidden="true"></div>

                    <!-- Step 0 -->
                    <button
                        class="relative flex items-start p-8 text-left rounded-2xl transition-all duration-500 hover:bg-gray-50 border border-transparent group tab-trigger"
                        data-idx="0" data-phase="Phase 01" data-title="Booking Inbound"
                        data-desc="A new rental enters the booking calendar unassigned, alerting the dispatcher instantly via real-time push notifications."
                        data-image="https://images.unsplash.com/photo-1506784951206-39622414e365?auto=format&fit=crop&q=80&w=800">

                        <div
                            class="relative z-10 flex items-center justify-center shrink-0 w-4 h-4 rounded-full border-4 border-white bg-gray-200 ring-1 ring-gray-200 transition-all duration-500 mt-1.5 tab-dot group-hover:ring-blue-200">
                        </div>

                        <div class="ml-8">
                            <h4 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">1.
                                Booking Inbound</h4>
                            <p class="text-gray-500 leading-relaxed text-sm tab-text transition-colors duration-300">New
                                rentals appear as unassigned blocks, waiting for asset allocation.</p>
                        </div>
                    </button>

                    <!-- Step 1 -->
                    <button
                        class="relative flex items-start p-8 text-left rounded-2xl transition-all duration-500 hover:bg-gray-50 border border-transparent group tab-trigger"
                        data-idx="1" data-phase="Phase 02" data-title="Asset Assignment"
                        data-desc="The dispatcher drags the booking block onto an available vehicle that matches the requested rental class with one smooth motion."
                        data-image="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&q=80&w=800">

                        <div
                            class="relative z-10 flex items-center justify-center shrink-0 w-4 h-4 rounded-full border-4 border-white bg-gray-200 ring-1 ring-gray-200 transition-all duration-500 mt-1.5 tab-dot group-hover:ring-blue-200">
                        </div>

                        <div class="ml-8">
                            <h4 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">2.
                                Drag-and-Drop Assignment</h4>
                            <p class="text-gray-500 leading-relaxed text-sm tab-text transition-colors duration-300">
                                Efficiently match vehicles to bookings using our intuitive interface.</p>
                        </div>
                    </button>

                    <!-- Step 2 -->
                    <button
                        class="relative flex items-start p-8 text-left rounded-2xl transition-all duration-500 hover:bg-gray-50 border border-transparent group tab-trigger"
                        data-idx="2" data-phase="Phase 03" data-title="Automated Lock"
                        data-desc="The vehicle is locked to the booking across all channels, eliminating any possibility of a double reservation instantly."
                        data-image="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=800">

                        <div
                            class="relative z-10 flex items-center justify-center shrink-0 w-4 h-4 rounded-full border-4 border-white bg-gray-200 ring-1 ring-gray-200 transition-all duration-500 mt-1.5 tab-dot group-hover:ring-blue-200">
                        </div>

                        <div class="ml-8">
                            <h4 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">3.
                                Automated Calendar Lock</h4>
                            <p class="text-gray-500 leading-relaxed text-sm tab-text transition-colors duration-300">
                                Real-time synchronisation ensures your fleet is never double-booked.</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const section = document.getElementById('interactive-scheduling-section');
                if (!section) return;

                const triggers = section.querySelectorAll('.tab-trigger');
                const visualImg = section.querySelector('.tab-visual-img');
                const visualTitle = section.querySelector('.tab-visual-title');
                const visualDesc = section.querySelector('.tab-visual-desc');
                const visualLabel = section.querySelector('.tab-visual-phase-label');

                function activateTab(index) {
                    triggers.forEach((btn, i) => {
                        const dot = btn.querySelector('.tab-dot');
                        const isActive = i === index;

                        if (isActive) {
                            btn.classList.add('bg-white', 'shadow-xl', 'shadow-gray-200/50', 'border-gray-100');
                            btn.querySelector('.tab-title').classList.add('text-blue-600');
                            dot.classList.add('bg-blue-600', 'ring-blue-100', 'scale-150');

                            // Update Visual Section with Smooth Transitions
                            visualImg.classList.add('opacity-0', 'scale-110');
                            visualTitle.classList.add('opacity-0', 'translate-y-4');
                            visualDesc.classList.add('opacity-0', 'translate-y-4');

                            setTimeout(() => {
                                visualImg.style.backgroundImage = `url('${btn.dataset.image}')`;
                                visualTitle.textContent = btn.dataset.title;
                                visualDesc.textContent = btn.dataset.desc;
                                visualLabel.textContent = btn.dataset.phase;

                                visualImg.classList.remove('opacity-0', 'scale-110');
                                visualTitle.classList.remove('opacity-0', 'translate-y-4');
                                visualDesc.classList.remove('opacity-0', 'translate-y-4');
                            }, 300);

                            // Progress bars
                            for (let j = 0; j <= 2; j++) {
                                const bar = section.querySelector('.tab-progress-' + j);
                                bar.style.width = j <= index ? '100%' : '0%';
                            }

                        } else {
                            btn.classList.remove('bg-white', 'shadow-xl', 'shadow-gray-200/50', 'border-gray-100');
                            btn.querySelector('.tab-title').classList.remove('text-blue-600');
                            dot.classList.remove('bg-blue-600', 'ring-blue-100', 'scale-150');
                        }
                    });
                }

                triggers.forEach((trigger, idx) => {
                    trigger.addEventListener('click', () => activateTab(idx));
                });

                // Initial Transition Setup
                visualTitle.style.transition = 'all 0.5s ease-out';
                visualDesc.style.transition = 'all 0.5s ease-out 0.1s';
                activateTab(0);
            });
        </script>
    </div>

    <!-- ═══════════════════════════════════════════
         TESTIMONIALS
    ═══════════════════════════════════════════ -->
    <div class="bg-gray-50 py-20 border-t border-gray-100">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Trusted by modern car rental operators</h2>
            <p class="text-center text-gray-600 mb-12 max-w-xl mx-auto">Fleet managers across the UK and Europe use our
                booking calendar to run tighter, more profitable operations.</p>
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
                        <p class="text-gray-800 italic mb-6">"Since switching to this fleet booking calendar, our
                            double-booking incidents dropped to zero. The real-time sync across channels is genuinely
                            impressive: our team can't imagine going back to spreadsheets."</p>
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
                        <p class="text-gray-800 italic mb-6">"The drag-and-drop scheduling is incredibly intuitive. The
                            whole dispatch process is now frictionless for our team and the mobile calendar view works
                            beautifully on the lot."</p>
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
         FAQ (matches FAQPage schema above)
    ═══════════════════════════════════════════ -->
    <section aria-labelledby="faq-heading"
        class="flex flex-col max-w-7xl mx-auto items-center gap-10 px-4 py-10 sm:gap-12 sm:py-12 md:px-6 md:py-14 lg:gap-12 lg:px-10 lg:py-16 bg-gray-50">
        <div class="flex w-full flex-col items-start gap-3 text-left lg:items-center lg:gap-4 lg:text-center">
            <p class="text-[11px] font-bold tracking-[0.2em] uppercase text-blue-600">FAQ</p>
            <h2 id="faq-heading" class="w-full text-4xl font-bold text-gray-900 lg:w-auto">
                <span class="gradient-text">Questions about</span> Fleet Booking Calendar Software
            </h2>
            <p class="w-full text-[15px] text-gray-600 lg:max-w-[600px] lg:text-center">Everything you need to know
                about scheduling, sync, conflict prevention, and mobile access.</p>
        </div>
        <div class="flex w-full max-w-[1400px] flex-col" id="faqAccordion">

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(0)" aria-expanded="false" aria-controls="faq-answer-0"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">What
                        is a fleet management booking calendar software?</span>
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
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Fleet management booking
                        calendar software is a centralised digital dispatch dashboard designed specifically for car
                        rental businesses. It provides real-time visualisation of vehicle availability, maintenance
                        schedules, and reservation conflicts to optimise your daily fleet operations.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(1)" aria-expanded="false" aria-controls="faq-answer-1"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">How
                        does the drag-and-drop car rental dispatch system work?</span>
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
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">The intuitive
                        drag-and-drop interface lets fleet managers reassign vehicle bookings instantly. When a
                        reservation conflict or late return occurs, simply drag the rental block to another available
                        asset in your inventory, and the entire system updates without page reloads.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(2)" aria-expanded="false" aria-controls="faq-answer-2"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">Can
                        I sync the booking calendar with third-party rental platforms?</span>
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
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Yes. The booking calendar
                        features bidirectional iCal (ICS) synchronisation. You can automatically import reservations
                        from third-party marketplaces and export your direct website bookings, ensuring zero
                        double-bookings across your multi-channel strategy.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(3)" aria-expanded="false" aria-controls="faq-answer-3"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">Does
                        the vehicle scheduling software prevent double reservations?</span>
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
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">Absolutely. The scheduling
                        algorithm uses millisecond-level database locking to guarantee that two customers cannot
                        successfully book the same vehicle simultaneously, preventing costly double-booking during peak
                        seasonal demand.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(4)" aria-expanded="false" aria-controls="faq-answer-4"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">How
                        do you handle maintenance and repair out-of-service periods?</span>
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
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">You can instantly create
                        maintenance blocks on the calendar for oil changes, tyre rotations, or bodywork. These service
                        tags automatically remove the affected vehicle from your online storefront, ensuring customers
                        only see bookable, road-ready vehicles.</p>
                </div>
            </div>

            <div class="border-b border-[#e5e5e5] last:border-b-0">
                <button onclick="toggleFaq(5)" aria-expanded="false" aria-controls="faq-answer-5"
                    class="group flex w-full items-center justify-between gap-4 py-5 text-left transition-colors hover:opacity-80 sm:gap-6 sm:py-6 md:gap-10 md:py-7">
                    <span
                        class="text-[18px] font-semibold leading-[1.3] tracking-[-0.5px] text-[#1a1a1a] sm:text-[19px] md:text-[22px] lg:text-[24px]">Is
                        the fleet calendar view optimised for mobile devices?</span>
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
                    <p class="pb-5 text-[18px] text-gray-600 leading-relaxed sm:pb-6 md:pb-7">The entire booking
                        calendar is built using mobile-first PWA standards. Dispatchers and lot attendants can swipe
                        horizontally through the timeline to review reservations or scan drop-offs on tablets and
                        smartphones.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         CTA
    ═══════════════════════════════════════════ -->
    <div class="py-24 text-center">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Ready to run a smarter fleet booking calendar?</h2>
            <p class="text-xl text-gray-600 mb-10">Join 300+ rental operators who have replaced spreadsheets with a
                booking system that actually works at scale.</p>
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