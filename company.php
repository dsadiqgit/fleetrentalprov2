<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">


    <!-- ═══════════════════════════════════════════
         PRIMARY SEO TAGS
         Target: "Fleet Rental Pro" / "Car Rental Software" / "Car Rental Website"
    ═══════════════════════════════════════════ -->
    <title>Company | Fleet Rental Pro - Advanced Car Rental Software & Website Solutions</title>
    <meta name="description"
        content="Fleet Rental Pro is the leading car rental software and website platform for modern operators. Automate your bookings, manage your fleet, and grow your rental business with an all-in-one digital operating system.">
    <meta name="keywords"
        content="Fleet Rental Pro, car rental software, car rental website, fleet management system, rental booking engine, digital car rental platform, automation for car rental, car rental SEO, fleet software UK, rent a car software solutions">
    <link rel="canonical" href="https://<?= $_SERVER['HTTP_HOST']?>/company">

    <!-- ═══════════════════════════════════════════
         OPEN GRAPH
    ═══════════════════════════════════════════ -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Fleet Rental Pro | The Future of Car Rental Software">
    <meta property="og:description"
        content="Discover the world's most advanced car rental software and website platform. Built for performance, security, and effortless fleet growth.">
    <meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST']?>/company">
    <meta property="og:image" content="https://<?= $_SERVER['HTTP_HOST']?>/assets/images/og-about.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?= htmlspecialchars(SITE_NAME)?>">
    <meta property="og:locale" content="en_GB">

    <!-- ═══════════════════════════════════════════
         TWITTER / X CARD
    ═══════════════════════════════════════════ -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Fleet Rental Pro | Car Rental Software & Digital Solutions">
    <meta name="twitter:description"
        content="Empowering the car rental industry with automation and high-performace websites. Join the 300+ fleets scaling with Fleet Rental Pro.">
    <meta name="twitter:image" content="https://<?= $_SERVER['HTTP_HOST']?>/assets/images/og-about.jpg">

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA  Organization
    ═══════════════════════════════════════════ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Fleet Rental Pro",
      "alternateName": "FleetRentalPro",
      "url": "https://<?= $_SERVER['HTTP_HOST']?>",
      "logo": "https://<?= $_SERVER['HTTP_HOST']?>/assets/images/fleet-logo-black-small.png",
      "description": "Premium all-in-one car rental software and website platform for independent fleet operators.",
      "founders": [
        {
          "@type": "Person",
          "name": "The Fleet Rental Pro Team"
        }
      ],
      "foundingDate": "2020",
      "knowsAbout": ["Car Rental Software", "Fleet Management", "ID Verification", "Digital Transformation"],
      "sameAs": [
        "https://twitter.com/fleetrentalpro",
        "https://linkedin.com/company/fleetrentalpro"
      ]
    }
    </script>

    <!-- ═══════════════════════════════════════════
         STRUCTURED DATA  AboutPage
    ═══════════════════════════════════════════ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "AboutPage",
      "mainEntity": {
        "@type": "Organization",
        "name": "Fleet Rental Pro",
        "description": "Fleet Rental Pro provides advanced car rental software and website solutions designed to automate the entire rental lifecycle from booking to vehicle condition reports."
      }
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
          "name": "Company",
          "item": "https://<?= $_SERVER['HTTP_HOST']?>/company"
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

        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.15), transparent),
                radial-gradient(circle at bottom left, rgba(124, 58, 237, 0.1), transparent);
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-[#fafafa]" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ═══════════════════════════════════════════
         BREADCRUMB
    ═══════════════════════════════════════════ -->
    <nav aria-label="Breadcrumb" class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="/" class="hover:text-blue-600 transition">Home</a></li>
                <li aria-hidden="true"><span class="mx-1">/</span></li>
                <li class="text-gray-900 font-medium" aria-current="page">Company</li>
            </ol>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════ -->
    <div class="relative bg-[#0b1120] overflow-hidden pt-24 pb-32">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1449965072345-662358363a79?auto=format&fit=crop&q=80&w=2000"
                alt="Modern car rental office and automated software" class="w-full h-full object-cover opacity-30"
                width="2000" height="1333" loading="eager" fetchpriority="high">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/80 to-transparent"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <div
                    class="inline-flex items-center space-x-2 bg-white/10 rounded-full px-4 py-1.5 mb-6 border border-white/20">
                    <span class="flex h-2 w-2 rounded-full bg-blue-400"></span>
                    <span class="text-sm font-medium text-blue-100">The Gold Standard in Fleet Tech</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight tracking-tight">
                    The World's Most Advanced <span class="text-blue-500">Car Rental Software.</span>
                </h1>
                <p class="text-xl text-gray-300 mb-10 leading-relaxed">
                    Fleet Rental Pro provides a complete digital operating system for car rental companies. From
                    high-conversion websites to automated identity verification, we help you scale your fleet without
                    scaling your workload.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="/auth/signup.php"
                        class="inline-flex justify-center items-center px-8 py-3.5 border border-transparent text-base font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition shadow-lg shadow-blue-600/20">
                        Get Started Free
                    </a>
                    <a href="/contact.php"
                        class="inline-flex justify-center items-center px-8 py-3.5 border border-white/20 text-base font-semibold rounded-lg text-white hover:bg-white/10 transition backdrop-blur-sm">
                        Speak to an Expert
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         TRUST ROW
    ═══════════════════════════════════════════ -->
    <div class="border-b border-gray-100 bg-white shadow-sm overflow-hidden whitespace-nowrap">
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div
                class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center justify-items-center opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                <div class="text-xl font-bold text-gray-400">TRUSTED BY 300+ FLEETS</div>
                <div class="text-xl font-bold text-gray-400 tracking-widest text-center">ELITE CARS</div>
                <div class="text-xl font-bold text-gray-400 tracking-widest text-center">LUX RENTALS</div>
                <div class="text-xl font-bold text-gray-400 tracking-widest text-center">DRIVENOW</div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         OUR MISSION  Split Text/Image
    ═══════════════════════════════════════════ -->
    <div class="py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="relative">
                    <div class="absolute -left-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-3xl"></div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6 leading-tight">
                        Built to solve the complex <span class="gradient-text">hurdles of car rental.</span>
                    </h2>
                    <p class="text-xl text-gray-600 leading-relaxed mb-8">
                        Independent rental companies often struggle with fragmented software, high fraud risks, and
                        outdated customer experiences. Fleet Rental Pro was built to consolidate every part of the
                        rental journey into one seamless, premium car rental website and software suite.
                    </p>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div
                                class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-900">Total Automation</h3>
                                <p class="text-gray-500">From automated ID checks to e-signatures, we eliminate manual
                                    paperwork so you can focus on growth.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div
                                class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                    </path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-900">Enterprise Security</h3>
                                <p class="text-gray-500">We utilize AI-powered document verification and biometric
                                    liveness detection to keep your fleet safe from fraud.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl"></div>
                    <div class="relative rounded-3xl overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=1000"
                            alt="Fleet of luxury cars" class="w-full h-full object-cover">
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-gray-900/60 to-transparent flex items-bottom p-8">
                            <p class="text-white font-medium text-lg mt-auto">Scaling over 300 fleet operators globally.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         INTERACTIVE TABS  The Evolution of Fleet Rental Pro
    ═══════════════════════════════════════════ -->
    <div class="bg-[#fafafa] py-24" id="interactive-tabs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Why operators choose Fleet Rental Pro</h2>
            <p class="text-center text-gray-600 mb-16 max-w-2xl mx-auto">Providing more than just a car rental website -
                we provide the tools to run your entire empire.</p>
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-24 items-center">

                <div
                    class="relative w-full h-[450px] rounded-[2rem] overflow-hidden shadow-2xl transition-all duration-700 ease-in-out tab-visual-bg bg-gray-900">
                    <div class="absolute inset-0 tab-visual-img transition-opacity duration-700 opacity-100"
                        style="background-image: url('https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=800'); background-size: cover; background-position: center;">
                        <div class="absolute inset-0 bg-gray-900/60 mix-blend-multiply"></div>
                    </div>
                    <div class="relative z-10 p-10 flex flex-col justify-end h-full text-white">
                        <div
                            class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-md border border-white/20 tab-visual-icon transition-transform duration-500 hover:scale-110">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold mb-3 tab-visual-title tracking-tight text-white">Scale
                            Effortlessly</h3>
                        <p class="text-gray-200 text-lg leading-relaxed tab-visual-desc">Our architecture is built for
                            rapid expansion, handling thousands of simultaneous bookings across multiple cities.</p>
                    </div>
                </div>

                <div class="flex flex-col space-y-2 relative">
                    <div class="absolute left-6 top-10 bottom-10 w-0.5 bg-gray-200" aria-hidden="true"></div>

                    <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-white hover:shadow-lg group tab-trigger active bg-white shadow-md"
                        data-idx="0"
                        data-image="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=800">
                        <div
                            class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-blue-600 scale-125">
                        </div>
                        <div class="ml-6 flex-1">
                            <h3
                                class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title text-blue-600">
                                Enterprise Reliability</h3>
                            <p class="text-gray-500 leading-relaxed tab-text">99.9% uptime and military-grade security
                                ensure your car rental website never misses a booking.</p>
                        </div>
                    </div>

                    <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-white hover:shadow-lg group tab-trigger"
                        data-idx="1"
                        data-image="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=800">
                        <div
                            class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-gray-200">
                        </div>
                        <div class="ml-6 flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">
                                Conversion Optimized</h3>
                            <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">Every pixel
                                is designed to turn visitors into renters, with lightning-fast load times and seamless
                                checkout.</p>
                        </div>
                    </div>

                    <div class="relative flex items-start p-6 cursor-pointer rounded-2xl transition-all duration-300 hover:bg-white hover:shadow-lg group tab-trigger"
                        data-idx="2"
                        data-image="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=800">
                        <div
                            class="relative z-10 flex items-center justify-center w-6 h-6 rounded-full border-[3px] shadow-sm transition-colors duration-300 mt-0.5 tab-dot bg-white border-gray-200">
                        </div>
                        <div class="ml-6 flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 transition-colors duration-300 tab-title">
                                Data-Driven Growth</h3>
                            <p class="text-gray-500 leading-relaxed tab-text transition-colors duration-300">Deep
                                analytics and reporting dashboard provide the insights needed to optimise your fleet
                                utilization.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         TESTIMONIALS
    ═══════════════════════════════════════════ -->
    <div class="bg-white py-24 border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">What our partners say</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-gray-50 p-8 rounded-3xl border border-gray-100">
                    <div class="flex text-yellow-400 mb-4">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                            </path>
                        </svg>
                        <?php
endfor; ?>
                    </div>
                    <p class="text-gray-700 italic mb-6">"Switching to Fleet Rental Pro was the best decision for our
                        luxury fleet. The car rental software automates everything, and our new car rental website has
                        seen a 40% increase in direct bookings."</p>
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center font-bold text-blue-600 mr-3">
                            JD</div>
                        <div>
                            <div class="text-sm font-bold text-gray-900">James D.</div>
                            <div class="text-xs text-gray-500">CEO, Elite Luxury Rentals</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-8 rounded-3xl border border-gray-100">
                    <div class="flex text-yellow-400 mb-4">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                            </path>
                        </svg>
                        <?php
endfor; ?>
                    </div>
                    <p class="text-gray-700 italic mb-6">"The integrated ID verification alone paid for the software in
                        months. We finally have a professional car rental website that competes with the global brands
                        but stays uniquely ours."</p>
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center font-bold text-purple-600 mr-3">
                            AM</div>
                        <div>
                            <div class="text-sm font-bold text-gray-900">Amina M.</div>
                            <div class="text-xs text-gray-500">Operations Manager, Orbit Fleets</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         CORE STATS
    ═══════════════════════════════════════════ -->
    <div class="bg-gray-900 py-24 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-1/3 h-1/3 bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Driving Results with Innovation</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">Providing a high-conversion car rental website and rock-solid
                    fleet backend for the future of travel.</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center p-8 bg-white/5 rounded-3xl border border-white/10 backdrop-blur-sm">
                    <div class="text-5xl font-bold text-white mb-2">99.9%</div>
                    <div class="text-blue-400 font-semibold text-sm tracking-wider uppercase">System Uptime</div>
                </div>
                <div class="text-center p-8 bg-white/5 rounded-3xl border border-white/10 backdrop-blur-sm">
                    <div class="text-5xl font-bold text-white mb-2">300+</div>
                    <div class="text-blue-400 font-semibold text-sm tracking-wider uppercase">Fleets Powered</div>
                </div>
                <div class="text-center p-8 bg-white/5 rounded-3xl border border-white/10 backdrop-blur-sm">
                    <div class="text-5xl font-bold text-white mb-2">100k+</div>
                    <div class="text-blue-400 font-semibold text-sm tracking-wider uppercase">Bookings Managed</div>
                </div>
                <div class="text-center p-8 bg-white/5 rounded-3xl border border-white/10 backdrop-blur-sm">
                    <div class="text-5xl font-bold text-white mb-2">&lt;5s</div>
                    <div class="text-blue-400 font-semibold text-sm tracking-wider uppercase">Page Load Time</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         CTA  The final push
    ═══════════════════════════════════════════ -->
    <div class="py-24 text-center bg-white relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 tracking-tight">Ready to modernize your <span
                    class="gradient-text">car rental website?</span></h2>
            <p class="text-xl text-gray-600 mb-10 leading-relaxed">
                Join the operators who are reducing their overhead and increasing their revenue with the most advanced
                car rental software on the market.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="/auth/signup.php"
                    class="px-10 py-4 bg-blue-600 text-white font-bold rounded-xl shadow-xl shadow-blue-500/20 hover:bg-blue-700 transition-all hover:scale-105">
                    Start Your Free Trial
                </a>
                <a href="/contact.php"
                    class="px-10 py-4 bg-white text-gray-900 border border-gray-200 font-bold rounded-xl hover:bg-gray-50 transition-all">
                    Contact Sales Team
                </a>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         FAQ
    ═══════════════════════════════════════════ -->
    <section class="bg-[#fafafa] py-24 border-t border-gray-100">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <p class="text-blue-600 font-bold uppercase tracking-widest text-sm mb-4">Common Questions</p>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Learn more about Fleet Rental Pro.</h2>
                <p class="text-gray-600 leading-relaxed">Everything you need to know about our car rental software and
                    how it helps your business grow.</p>
            </div>

            <div class="space-y-4" id="faqAccordion">
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <button onclick="toggleFaq(0)"
                        class="w-full px-8 py-6 text-left flex justify-between items-center group">
                        <span class="text-lg font-bold text-gray-900">What makes Fleet Rental Pro different?</span>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-transform duration-300 faq-icon"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                        </svg>
                    </button>
                    <div class="hidden px-8 pb-6 faq-content">
                        <p class="text-gray-600 leading-relaxed">Unlike basic booking engines, Fleet Rental Pro is a
                            full-stack digital operating system. We provide the car rental website, the fleet management
                            dashboard, and the security tools (like ID verification) all in one platform.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <button onclick="toggleFaq(1)"
                        class="w-full px-8 py-6 text-left flex justify-between items-center group">
                        <span class="text-lg font-bold text-gray-900">Is the car rental website mobile-friendly?</span>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-transform duration-300 faq-icon"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                        </svg>
                    </button>
                    <div class="hidden px-8 pb-6 faq-content">
                        <p class="text-gray-600 leading-relaxed">Yes, 100%. Our car rental website templates are built
                            with a mobile-first philosophy, ensuring that your customers can book easily from any
                            device, anywhere.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <button onclick="toggleFaq(2)"
                        class="w-full px-8 py-6 text-left flex justify-between items-center group">
                        <span class="text-lg font-bold text-gray-900">How difficult is it to migrate my fleet?</span>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-transform duration-300 faq-icon"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                        </svg>
                    </button>
                    <div class="hidden px-8 pb-6 faq-content">
                        <p class="text-gray-600 leading-relaxed">Our car rental software is designed for easy migration.
                            You can import your vehicle data via CSV and launch your new car rental website in as little
                            as 24 hours.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        function toggleFaq(index) {
            const contents = document.querySelectorAll('.faq-content');
            const icons = document.querySelectorAll('.faq-icon');

            contents.forEach((content, i) => {
                if (i === index) {
                    const isHidden = content.classList.toggle('hidden');
                    icons[i].style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
                } else {
                    content.classList.add('hidden');
                    icons[i].style.transform = 'rotate(0deg)';
                }
            });
        }

        // Tab Switching Logic
        document.addEventListener('DOMContentLoaded', () => {
            const triggers = document.querySelectorAll('.tab-trigger');
            const visualImg = document.querySelector('.tab-visual-img');
            const visualTitle = document.querySelector('.tab-visual-title');
            const visualDesc = document.querySelector('.tab-visual-desc');

            function activateTab(index) {
                triggers.forEach((t, i) => {
                    const dot = t.querySelector('.tab-dot');
                    const title = t.querySelector('.tab-title');
                    if (i === index) {
                        t.classList.add('active', 'bg-white', 'shadow-md');
                        t.classList.remove('hover:bg-white');
                        dot.classList.replace('border-gray-200', 'border-blue-600');
                        dot.classList.add('scale-125');
                        title.classList.add('text-blue-600');

                        visualImg.style.opacity = '0';
                        setTimeout(() => {
                            visualImg.style.backgroundImage = `url('${t.dataset.image}')`;
                            visualImg.style.opacity = '1';
                            visualTitle.textContent = title.textContent;
                            visualDesc.textContent = t.querySelector('.tab-text').textContent;
                        }, 350);
                    } else {
                        t.classList.remove('active', 'bg-white', 'shadow-md');
                        t.classList.add('hover:bg-white');
                        dot.classList.replace('border-blue-600', 'border-gray-200');
                        dot.classList.remove('scale-125');
                        title.classList.remove('text-blue-600');
                    }
                });
            }

            triggers.forEach((trigger, idx) => {
                trigger.addEventListener('click', () => activateTab(idx));
            });
        });
    </script>
</body>

</html>