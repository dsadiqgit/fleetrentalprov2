<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Documentation - <?= SITE_NAME?></title>
    <meta name="description" content="Step-by-step guides on how to use the Fleet Rental Pro dashboard. Learn how to add vehicles, customise your website, design contracts, and manage settings.">
    <?php include __DIR__ . '/includes/head-content.php'; ?>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
    <style>
        html { scroll-behavior: smooth; }
        .doc-screenshot {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            margin: 2rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }
        .doc-screenshot img {
            width: 100%;
            display: block;
        }
        .doc-screenshot figcaption {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
        }
        .step-number {
            display: flex; align-items: center; justify-content: center;
            width: 2.5rem; height: 2.5rem;
            background-color: #2563eb; color: white;
            border-radius: 9999px; font-weight: 800; flex-shrink: 0;
        }
        .step-number-dark {
            background-color: #0f172a;
        }
        .doc-card { transition: all 0.2s ease; }
        .doc-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .code-block {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 1.25rem 1.5rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.85rem; color: #1e293b; margin: 1.5rem 0;
            line-height: 1.8;
        }
        .code-block .tag { color: #2563eb; font-weight: 700; }
        .code-block .desc { color: #64748b; }
        .callout-info {
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 16px;
            padding: 1.5rem; margin: 2rem 0;
        }
        .callout-info h4 { color: #1e40af; font-weight: 700; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .callout-info p { color: #1e40af; opacity: 0.8; margin: 0; font-size: 0.9rem; line-height: 1.6; }
        .callout-warning {
            background: #fefce8; border: 1px solid #fde68a; border-radius: 16px;
            padding: 1.5rem; margin: 2rem 0;
        }
        .callout-warning h4 { color: #92400e; font-weight: 700; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .callout-warning p { color: #92400e; opacity: 0.8; margin: 0; font-size: 0.9rem; line-height: 1.6; }
        .sidebar-nav a.active-doc { background-color: #eff6ff; color: #2563eb; font-weight: 600; }
    </style>
</head>

<body class="bg-white text-slate-900" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col lg:flex-row gap-12">

        <!-- Desktop Sidebar Navigation -->
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <nav class="sticky top-28 space-y-1">
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Getting Started</p>
                <a href="#overview" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Dashboard Overview</a>
                <a href="#sidebar-nav" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Navigating the Sidebar</a>

                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-8 mb-3">Fleet Management</p>
                <a href="#create-vehicle" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Creating a Vehicle</a>
                <a href="#edit-vehicle" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Editing a Vehicle</a>
                <a href="#vehicle-pricing" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Setting Up Pricing</a>

                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-8 mb-3">Website & Branding</p>
                <a href="#website-management" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Website Management</a>
                <a href="#website-editor" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Website Editor</a>

                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-8 mb-3">Legal & Compliance</p>
                <a href="#e-signing" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">E-Signing</a>
                <a href="#contract-designer" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Contract Designer</a>

                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-8 mb-3">Configuration</p>
                <a href="#settings" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Settings</a>
                <a href="#booking-settings" class="block px-3 py-2 text-sm text-slate-600 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition-colors">Booking Settings</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 max-w-4xl">

            <!-- Hero Header -->
            <header class="mb-16">
                <div class="flex items-center gap-2 text-blue-600 font-bold text-xs uppercase tracking-widest mb-4">
                    <span>v1.2.0</span>
                    <span class="text-slate-300">•</span>
                    <span>Resources</span>
                </div>
                <h1 class="text-5xl font-extrabold text-slate-900 tracking-tight leading-tight mb-6">Dashboard Documentation</h1>
                <p class="text-xl text-slate-500 leading-relaxed max-w-2xl">
                    Master the Fleet Rental Pro platform with our step-by-step visual guides. Each section includes screenshots so you can follow along with confidence.
                </p>

                <!-- Quick Access Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-10">
                    <a href="#create-vehicle" class="doc-card p-5 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                        <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center shadow-sm border border-slate-100">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div><p class="font-bold text-slate-900 text-sm">Add a Vehicle</p><p class="text-xs text-slate-500">List your fleet in minutes</p></div>
                    </a>
                    <a href="#website-editor" class="doc-card p-5 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                        <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center shadow-sm border border-slate-100">
                            <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9h18"/></svg>
                        </div>
                        <div><p class="font-bold text-slate-900 text-sm">Edit Website</p><p class="text-xs text-slate-500">Customise your storefront</p></div>
                    </a>
                    <a href="#contract-designer" class="doc-card p-5 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                        <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center shadow-sm border border-slate-100">
                            <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div><p class="font-bold text-slate-900 text-sm">Design Contracts</p><p class="text-xs text-slate-500">Automate legal workflows</p></div>
                    </a>
                </div>
            </header>

            <!-- ==================== SECTION: Dashboard Overview ==================== -->
            <section id="overview" class="prose-custom pt-12 border-t border-slate-100">
                <h2>Dashboard Overview</h2>
                <p>When you first log in, you'll land on the <strong>Calendar</strong> page. This is your command centre: a single view of your entire rental operation showing live statistics, recent activity, and a booking calendar.</p>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/dashboard-overview.png" alt="Fleet Rental Pro Dashboard Overview" loading="lazy">
                    <figcaption>The main dashboard showing stats cards, recent activity feed, and the booking calendar.</figcaption>
                </figure>

                <p>At the top, you'll see three key metrics:</p>
                <ul>
                    <li><strong>Total Vehicles</strong>: The number of cars currently listed in your fleet.</li>
                    <li><strong>Active Bookings</strong>: Bookings that are either confirmed or currently in progress.</li>
                    <li><strong>Registered Customers</strong>: The total number of customers who have signed up through your website.</li>
                </ul>

                <p>Below the stats, the <strong>Recent Activity</strong> feed shows you real-time contract signings. You can dismiss this panel using the <strong>×</strong> button if you prefer a cleaner view.</p>
            </section>

            <!-- ==================== SECTION: Sidebar Navigation ==================== -->
            <section id="sidebar-nav" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>Navigating the Sidebar</h2>
                <p>The left sidebar is your primary navigation tool. It contains all the key sections of your dashboard:</p>

                <div class="overflow-x-auto my-8">
                    <table class="table-custom">
                        <thead>
                            <tr><th>Menu Item</th><th>What It Does</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>Calendar</strong></td><td>View and manage bookings in month, week, or day view.</td></tr>
                            <tr><td><strong>Vehicles</strong></td><td>Add, edit, and manage your fleet inventory.</td></tr>
                            <tr><td><strong>Customers</strong></td><td>View all registered customers and their booking history.</td></tr>
                            <tr><td><strong>Bookings</strong></td><td>Manage all bookings, update statuses, and process refunds.</td></tr>
                            <tr><td><strong>Media Library</strong></td><td>Upload and organise images for vehicles and your website.</td></tr>
                            <tr><td><strong>E-signing</strong></td><td>Toggle e-signatures and manage contract templates.</td></tr>
                            <tr><td><strong>Website</strong></td><td>Select templates and open the visual website editor.</td></tr>
                            <tr><td><strong>Dealership</strong></td><td>Configure your business information (name, address, logo).</td></tr>
                            <tr><td><strong>Settings</strong></td><td>Manage currency, distance units, booking rules, and payments.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="callout-info">
                    <h4>💡 Quick Tip: "View Live Website"</h4>
                    <p>The blue <strong>View Live Website</strong> button in the sidebar opens your public-facing rental website in a new tab, so you can always check what your customers are seeing.</p>
                </div>
            </section>

            <!-- ==================== SECTION: Creating a Vehicle ==================== -->
            <section id="create-vehicle" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>How to Create a Vehicle</h2>
                <p>Listing your vehicles is the first step to getting bookings. Follow these steps carefully to ensure your listing is complete and professional.</p>

                <div class="space-y-10 my-10">
                    <div class="flex gap-6">
                        <div class="step-number">1</div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">Navigate to the Vehicles Page</h4>
                            <p class="text-slate-500">Click <strong>Vehicles</strong> in the sidebar. You'll see your current fleet displayed as cards. If you have no vehicles yet, you'll see an empty state encouraging you to add your first one.</p>
                        </div>
                    </div>
                </div>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/vehicles-list.png" alt="Vehicles list page showing fleet cards" loading="lazy">
                    <figcaption>The Vehicles page displays your fleet as cards. Notice the "+ Add Vehicle" button in the top right corner, and the Active/Inactive toggle on each card.</figcaption>
                </figure>

                <div class="space-y-10 my-10">
                    <div class="flex gap-6">
                        <div class="step-number">2</div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">Click "+ Add Vehicle"</h4>
                            <p class="text-slate-500">Click the black <strong>+ Add Vehicle</strong> button in the top right corner. This will open the vehicle creation form with three tabs: <strong>Basic Information</strong>, <strong>Rental Settings</strong>, and <strong>Pricing</strong>.</p>
                        </div>
                    </div>
                </div>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/add-vehicle-form.png" alt="Add vehicle form showing basic information fields" loading="lazy">
                    <figcaption>The "Basic Information" tab of the new vehicle form. All fields marked with * are required.</figcaption>
                </figure>

                <div class="space-y-10 my-10">
                    <div class="flex gap-6">
                        <div class="step-number">3</div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">Fill In the Vehicle Details</h4>
                            <p class="text-slate-500">Complete the following fields on the <strong>Basic Information</strong> tab:</p>
                            <ul class="text-slate-500 mt-3 space-y-2">
                                <li><strong>Make</strong> — Select from the dropdown (e.g., BMW, Audi, Mercedes) or choose "Other" to type a custom make.</li>
                                <li><strong>Model</strong> — Once you select a make, choose the model from the second dropdown.</li>
                                <li><strong>Licence Plate</strong> — Enter the vehicle's registration (e.g., AB12 CDE).</li>
                                <li><strong>Year</strong> — Select the model year.</li>
                                <li><strong>Vehicle Type</strong> — Choose Sedan, SUV, Coupe, Truck, or Van.</li>
                                <li><strong>Transmission</strong> — Automatic, Manual, or Semi Auto.</li>
                                <li><strong>Fuel Type</strong> — Petrol, Diesel, Electric, or Hybrid.</li>
                                <li><strong>Seats & Doors</strong> — Set the passenger capacity and door count.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex gap-6">
                        <div class="step-number">4</div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">Add Vehicle Features</h4>
                            <p class="text-slate-500">Scroll down to the <strong>Vehicle Features</strong> field. Type a feature like "Bluetooth" or "Heated Seats" and press <kbd class="px-1.5 py-0.5 bg-slate-100 rounded text-xs font-mono">Enter</kbd> or <kbd class="px-1.5 py-0.5 bg-slate-100 rounded text-xs font-mono">,</kbd> to add it as a tag. These will display on your public website listing.</p>
                        </div>
                    </div>

                    <div class="flex gap-6">
                        <div class="step-number">5</div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">Upload Vehicle Images</h4>
                            <p class="text-slate-500">Scroll down to the <strong>Vehicle Images</strong> section. You have two options:</p>
                            <ul class="text-slate-500 mt-3 space-y-2">
                                <li><strong>Upload New:</strong> Click the upload area to select photos from your computer (PNG, JPG, up to 5MB each).</li>
                                <li><strong>Browse Gallery:</strong> Click "Browse Gallery" to select images you've already uploaded to your Media Library.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex gap-6">
                        <div class="step-number">6</div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">Set Pricing & Save</h4>
                            <p class="text-slate-500">Switch to the <strong>Pricing</strong> tab to set your daily rate. You can also configure day-of-week pricing (e.g., higher rates on weekends). Once everything is filled in, click the <strong>Save</strong> button at the bottom of the form.</p>
                        </div>
                    </div>
                </div>

                <div class="callout-warning">
                    <h4>⚠️ Important: High-Quality Images</h4>
                    <p>Vehicles with professional, high-resolution photos receive up to <strong>40% more bookings</strong>. Upload at least 3 photos showing the exterior, interior, and dashboard.</p>
                </div>
            </section>

            <!-- ==================== SECTION: Edit Vehicle ==================== -->
            <section id="edit-vehicle" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>Editing a Vehicle</h2>
                <p>To edit an existing vehicle, go to the <strong>Vehicles</strong> page, find the car you want to update, and click the <strong>three-dot menu</strong> (⋮) on the vehicle card. Select <strong>"Edit Vehicle"</strong> from the dropdown.</p>
                <p>This will open the same form as the creation flow, but pre-filled with all the vehicle's current data. Make your changes and click <strong>Save</strong>.</p>

                <p>From the same three-dot menu you can also:</p>
                <ul>
                    <li><strong>Mark as Featured</strong> — Featured vehicles are highlighted with a gold star badge and displayed prominently on your website.</li>
                    <li><strong>Delete Vehicle</strong> — Permanently remove the vehicle from your fleet (this cannot be undone).</li>
                </ul>

                <p>You can also use the <strong>Active/Inactive toggle</strong> on each vehicle card to quickly take a car offline without deleting it.</p>
            </section>

            <!-- ==================== SECTION: Website Management ==================== -->
            <section id="website-management" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>Website Management</h2>
                <p>Navigate to <strong>Website</strong> in the sidebar. This opens the <strong>Website Management</strong> page where you can select your website template and access the visual editor.</p>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/website-management.png" alt="Website management page with template selection" loading="lazy">
                    <figcaption>The Website Management page. Your active template is highlighted with a blue border and "Active" badge. Click "Edit Website" to customise.</figcaption>
                </figure>

                <p>From this page you can:</p>
                <ul>
                    <li><strong>Preview Website</strong> — Opens your public site in a new tab to see it as your customers do.</li>
                    <li><strong>Edit Website</strong> — Opens the no-code visual editor (see below).</li>
                    <li><strong>Apply Template</strong> — Select and apply a different website template.</li>
                </ul>
            </section>

            <!-- ==================== SECTION: Website Editor ==================== -->
            <section id="website-editor" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>Website Editor (No-Code Builder)</h2>
                <p>The Website Editor is a live, visual editor. You see your website exactly as your customers do, with the ability to click on any element to edit it directly.</p>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/website-editor.png" alt="Visual website editor showing live preview" loading="lazy">
                    <figcaption>The Website Builder in action. Hover over any text to see the blue dashed outline, then click to edit it directly.</figcaption>
                </figure>

                <h3>How to Edit Text</h3>
                <div class="space-y-8 my-8">
                    <div class="flex gap-5">
                        <div class="step-number">1</div>
                        <div class="text-slate-500"><strong class="text-slate-900">Hover</strong> over any text block. You'll see a blue dashed outline and a tooltip saying "Click to edit".</div>
                    </div>
                    <div class="flex gap-5">
                        <div class="step-number">2</div>
                        <div class="text-slate-500"><strong class="text-slate-900">Click</strong> the text. It becomes editable — just start typing your new content.</div>
                    </div>
                    <div class="flex gap-5">
                        <div class="step-number">3</div>
                        <div class="text-slate-500"><strong class="text-slate-900">Click away</strong> from the text. Your changes are saved automatically. A green "Saved" indicator will briefly appear in the bottom right corner.</div>
                    </div>
                </div>

                <h3>Toolbar Controls</h3>
                <p>The builder toolbar at the top provides additional controls:</p>
                <ul>
                    <li><strong>☰ Sections</strong> — Reorder, show, or hide website sections (Hero, Fleet, About, Testimonials, Contact).</li>
                    <li><strong>⚙ Settings</strong> — Opens a panel to change brand colours, fonts, logo, and header style.</li>
                    <li><strong>👁 Preview Website</strong> — Opens your live site in a new tab.</li>
                </ul>

                <div class="callout-info">
                    <h4>💡 Editing the Hero Image</h4>
                    <p>To change the large background image on your hero section, open the <strong>Settings</strong> panel from the toolbar and update the "Hero Image" field. You can paste a URL or upload from your Media Library.</p>
                </div>
            </section>

            <!-- ==================== SECTION: E-Signing ==================== -->
            <section id="e-signing" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>E-Signing</h2>
                <p>The E-Signing feature allows your customers to sign rental contracts digitally. Navigate to <strong>E-signing</strong> in the sidebar.</p>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/esigning-dashboard.png" alt="E-signing dashboard with toggle and templates" loading="lazy">
                    <figcaption>The E-signing page. Toggle the feature on/off at the top. Below, you'll see your contract templates with Edit, Preview, and Delete options.</figcaption>
                </figure>

                <h3>Enabling E-Signatures</h3>
                <p>At the top of the page, there's a toggle switch labelled <strong>"E-Sign feature"</strong>. When turned on, your customers will be required to sign rental contracts electronically before their booking is finalised.</p>

                <h3>Managing Templates</h3>
                <p>Below the toggle, you'll see your <strong>Default contract template</strong>. Each template has three actions:</p>
                <ul>
                    <li><strong>Edit</strong> — Open the template in a code editor to modify the contract text.</li>
                    <li><strong>Preview</strong> — See what the contract looks like as a PDF.</li>
                    <li><strong>Visual Designer</strong> — Open the drag-and-drop contract builder (see below).</li>
                </ul>
            </section>

            <!-- ==================== SECTION: Contract Designer ==================== -->
            <section id="contract-designer" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>Contract Designer</h2>
                <p>The Visual Contract Designer is a powerful tool that lets you build professional rental agreements using a drag-and-drop interface.</p>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/contract-designer.png" alt="Visual contract designer with drag-and-drop sections" loading="lazy">
                    <figcaption>The Contract Designer. The left panel has settings and section blocks. The right panel shows a live preview of the contract.</figcaption>
                </figure>

                <h3>Left Panel — Design Controls</h3>
                <ul>
                    <li><strong>Template Name</strong> — Give your contract a descriptive name (e.g., "Vehicle Rental Contract").</li>
                    <li><strong>Brand Colour</strong> — Set the accent colour used in section headers.</li>
                    <li><strong>Company Logo</strong> — Upload your business logo to appear in the contract header.</li>
                    <li><strong>Contact Information</strong> — Add your website, social media, and phone number.</li>
                </ul>

                <h3>Add Sections</h3>
                <p>Below the controls, you'll see section buttons like <strong>+ Agreement</strong>, <strong>+ Leased Vehicle</strong>, <strong>+ Payment Terms</strong>, etc. Click any section to add it to your contract preview on the right.</p>

                <h3>Dynamic Tags (Smart Tags)</h3>
                <p>The contract uses <strong>dynamic tags</strong> that are automatically replaced with real booking data when the contract is generated. Here are the key tags:</p>

                <div class="code-block">
                    <span class="tag">{{tenant_name}}</span> <span class="desc">→ Your business name</span><br>
                    <span class="tag">{{renter_full_name}}</span> <span class="desc">→ Customer's full name</span><br>
                    <span class="tag">{{vehicle_name}}</span> <span class="desc">→ Vehicle make and model</span><br>
                    <span class="tag">{{vehicle_registration}}</span> <span class="desc">→ Vehicle's license plate</span><br>
                    <span class="tag">{{booking_total_price}}</span> <span class="desc">→ Total rental cost</span><br>
                    <span class="tag">{{included_distance}}</span> <span class="desc">→ Included mileage limit</span><br>
                    <span class="tag">{{excess_distance_fee}}</span> <span class="desc">→ Fee per extra mile</span><br>
                    <span class="tag">{{current_datetime}}</span> <span class="desc">→ Date the contract was generated</span>
                </div>
            </section>

            <!-- ==================== SECTION: Settings ==================== -->
            <section id="settings" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>Settings — General</h2>
                <p>Navigate to <strong>Settings</strong> in the sidebar. The settings page has four tabs: <strong>General</strong>, <strong>Booking</strong>, <strong>Payment</strong>, and <strong>Cancellation</strong>.</p>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/settings-general.png" alt="General settings page" loading="lazy">
                    <figcaption>The General settings tab. Configure driver licence verification, currency, distance units, and week start day.</figcaption>
                </figure>

                <h3>General Tab Options</h3>
                <ul>
                    <li><strong>Driver Licence Verification</strong> — Toggle this on to require customers to verify their driving licence before they can book a vehicle.</li>
                    <li><strong>Currency</strong> — Set your operating currency (GBP, USD, EUR, etc.). This can only be changed before your first booking.</li>
                    <li><strong>Distance Unit</strong> — Choose between Kilometres and Miles.</li>
                    <li><strong>Start the Week On</strong> — Set whether your calendar week starts on Monday or Sunday.</li>
                </ul>
            </section>

            <!-- ==================== SECTION: Booking Settings ==================== -->
            <section id="booking-settings" class="prose-custom pt-12 mt-12 border-t border-slate-100">
                <h2>Settings — Booking</h2>
                <p>Switch to the <strong>Booking</strong> tab to configure how bookings are handled.</p>

                <figure class="doc-screenshot">
                    <img src="/public/images/docs/settings-booking.png" alt="Booking settings page" loading="lazy">
                    <figcaption>The Booking settings tab. Control approval workflows, notice periods, and buffer times.</figcaption>
                </figure>

                <h3>Booking Tab Options</h3>
                <ul>
                    <li><strong>Manual Approval</strong> — When enabled, you'll have 48 hours to approve or reject each booking request before it's automatically confirmed. Useful if you want to vet customers first.</li>
                    <li><strong>Minimum Notice Before a Booking</strong> — Set the minimum time between when a booking is made and the pickup date (e.g., 24 hours). This gives you time to prepare the vehicle.</li>
                    <li><strong>Buffer Time Between Bookings</strong> — Set the minimum gap between consecutive rentals (e.g., 2 hours for cleaning and inspection).</li>
                    <li><strong>Maximum Booking Window</strong> — How far in advance a customer can book (e.g., 30 days).</li>
                </ul>

                <div class="callout-info">
                    <h4>💡 Recommended Setup for New Operators</h4>
                    <p>If you're just starting out, we recommend turning <strong>Manual Approval ON</strong>, setting a <strong>24-hour minimum notice</strong>, and a <strong>2-hour buffer</strong> between bookings. This gives you full control while you learn the system.</p>
                </div>
            </section>

            <!-- ==================== CTA Section ==================== -->
            <div class="mt-24 bg-gradient-to-br from-slate-900 to-slate-800 rounded-[40px] p-12 text-center relative overflow-hidden shadow-2xl">
                <div class="absolute -right-20 -top-20 w-[400px] h-[400px] bg-blue-500/10 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="relative z-10">
                    <span class="inline-block px-4 py-1 bg-white/10 rounded-full text-[10px] font-bold text-blue-300 uppercase tracking-widest mb-6">Need More Help?</span>
                    <h2 class="text-4xl font-extrabold text-white mb-6 tracking-tight">Our Support Team is Online</h2>
                    <p class="text-slate-400 text-lg mb-10 max-w-xl mx-auto">Can't find what you're looking for? Chat with one of our fleet experts and get your questions answered in real-time.</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="/contact.php" class="px-8 py-4 bg-white text-slate-900 rounded-full font-bold hover:bg-slate-50 transition transform hover:scale-105">Contact Support</a>
                        <a href="#overview" class="px-8 py-4 bg-transparent border border-white/20 text-white rounded-full font-bold hover:bg-white/5 transition">Back to Top ↑</a>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>
