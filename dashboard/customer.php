<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

// Only customers can access this page
if ($_SESSION['role'] !== 'customer') {
    redirect('/dashboard/');
}

if (!$_SESSION['tenant_id']) {
    die('Error: No tenant associated with this account.');
}

$pdo = getDB();
$tenant_id = $_SESSION['tenant_id'];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Get tenant info
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();

if (!$tenant) {
    die('Error: Tenant not found.');
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
$stmt->execute([$user_id, $tenant_id]);
$user = $stmt->fetch();

// Get customer bookings with vehicle and contract info
$stmt = $pdo->prepare("
    SELECT b.*, 
           v.brand, v.model, v.year, v.category, v.images as vehicle_images,
           c.id as contract_id, c.contract_status, c.signed_at, c.signing_token, c.signed_pdf_path
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN contracts c ON c.booking_id = b.id AND c.tenant_id = b.tenant_id
    WHERE b.tenant_id = ? AND b.customer_email = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$tenant_id, $user_email]);
$bookings = $stmt->fetchAll();

$primaryColor = $tenant['primary_color'] ?? '#3B82F6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-3 z-40 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="p-2 hover:bg-gray-100 rounded-xl transition-colors text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <span class="text-lg font-bold text-gray-900"><?= htmlspecialchars($tenant['name']) ?></span>
        </div>
        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
            <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden transition-all duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40">
        <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden pt-14 lg:pt-0">
        <main class="flex-1 overflow-y-auto bg-gray-50/50 p-4 sm:p-6 lg:p-10">
            <!-- Welcome Header -->
            <div class="max-w-6xl mb-12">
                <h1 class="text-4xl font-black text-gray-900 tracking-tight">Bonjour, <?= htmlspecialchars(explode(' ', $user['full_name'] ?? 'Customer')[0]) ?>!</h1>
                <p class="text-gray-500 mt-2 text-lg font-medium">Here are your active fleet reservations.</p>
            </div>

            <!-- Stats Overview -->
            <?php
            $totalBookings = count($bookings);
            $activeBookings = count(array_filter($bookings, function($b) { return in_array($b['status'], ['confirmed', 'active']); }));
            $pendingContracts = count(array_filter($bookings, function($b) { return $b['contract_id'] && $b['contract_status'] !== 'signed'; }));
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 max-w-6xl">
                <div class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm flex items-center justify-between group hover:shadow-xl transition-all">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Total Orders</p>
                        <h3 class="text-3xl font-black text-gray-900"><?= $totalBookings ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 transition-transform group-hover:scale-110">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>

                <div class="bg-[#1a1f2b] p-8 rounded-[2rem] shadow-2xl flex items-center justify-between text-white group">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Active Rentals</p>
                        <h3 class="text-3xl font-black"><?= $activeBookings ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-blue-400 transition-transform group-hover:scale-110">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm flex items-center justify-between group hover:shadow-xl transition-all">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Pending Sign</p>
                        <h3 class="text-3xl font-black text-gray-900"><?= $pendingContracts ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 transition-transform group-hover:scale-110">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="max-w-6xl space-y-6">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">Recent Reservations</h2>
                </div>

                <?php if (empty($bookings)): ?>
                    <div class="bg-white rounded-[3rem] border border-gray-100 p-20 text-center shadow-sm">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">No bookings yet</h3>
                        <p class="text-gray-400 mt-4 max-w-sm mx-auto font-medium">Your rental history will appear here once you make your first reservation.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6 pb-20">
                        <?php foreach ($bookings as $booking): 
                            $vehicleName = trim(($booking['brand'] ?? '') . ' ' . ($booking['model'] ?? ''));
                            $bookingRef = 'REF-' . str_pad($booking['id'], 5, '0', STR_PAD_LEFT);
                            
                            $vehicleImage = null;
                            if (!empty($booking['vehicle_images'])) {
                                $decoded = json_decode($booking['vehicle_images'], true);
                                $vehicleImage = is_array($decoded) && !empty($decoded) ? $decoded[0] : "";
                            }
                            
                            $statusColors = [
                                'confirmed' => 'bg-blue-50 text-blue-700',
                                'active' => 'bg-green-50 text-green-700',
                                'completed' => 'bg-gray-100 text-gray-600',
                                'cancelled' => 'bg-red-50 text-red-600',
                                'pending' => 'bg-amber-50 text-amber-700',
                            ];
                            $statusClass = $statusColors[$booking['status']] ?? 'bg-gray-100 text-gray-600';
                        ?>
                        <div class="bg-white rounded-[2.5rem] border border-gray-100 p-8 shadow-sm hover:shadow-2xl transition-all group overflow-hidden relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/0 to-indigo-50/0 group-hover:from-blue-50/50 group-hover:to-indigo-50/50 transition-all duration-500 -z-10"></div>
                            
                            <div class="flex flex-col md:flex-row md:items-center gap-10">
                                <div class="w-full md:w-56 h-40 rounded-[2rem] overflow-hidden bg-gray-50 flex-shrink-0 relative">
                                    <?php if ($vehicleImage): ?>
                                        <img src="<?= htmlspecialchars($vehicleImage) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-200">
                                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute top-4 left-4">
                                        <span class="px-4 py-1.5 rounded-2xl text-[10px] font-black uppercase tracking-widest bg-white/90 backdrop-blur-md shadow-xl text-gray-900 border border-gray-100"><?= $bookingRef ?></span>
                                    </div>
                                </div>
        
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-4 mb-3">
                                        <span class="px-4 py-1.5 rounded-2xl text-[10px] font-black uppercase tracking-[0.1em] <?= $statusClass ?> shadow-sm shadow-current/5"><?= $booking['status'] ?></span>
                                        <div class="flex items-center gap-2 text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="text-xs font-black uppercase tracking-tight"><?= date('M j, Y', strtotime($booking['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <h3 class="text-3xl font-black text-gray-900 leading-tight mb-6"><?= htmlspecialchars($vehicleName ?: 'Vehicle Rental') ?></h3>
                                    <div class="flex flex-wrap items-center gap-y-4">
                                        <div class="flex items-center gap-8">
                                            <div>
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Pick-up</p>
                                                <p class="text-base font-black text-gray-900"><?= date('D, M j', strtotime($booking['pickup_date'])) ?></p>
                                            </div>
                                            <div class="w-12 h-0.5 bg-gray-100 rounded-full"></div>
                                            <div>
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Return</p>
                                                <p class="text-base font-black text-gray-900"><?= date('D, M j', strtotime($booking['return_date'])) ?></p>
                                            </div>
                                        </div>
                                        <div class="ml-auto flex items-center gap-3 bg-gray-50 px-6 py-3 rounded-[1.5rem] border border-gray-100">
                                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total cost</span>
                                            <span class="text-2xl font-black text-gray-900">£<?= number_format($booking['total_price'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
        
                                <div class="flex flex-row md:flex-col items-stretch gap-4 w-full md:w-56 pt-6 md:pt-0 border-t md:border-t-0 md:border-l border-gray-100 md:pl-10">
                                    <?php if ($booking['contract_id']): ?>
                                        <?php if ($booking['contract_status'] === 'signed'): ?>
                                            <button onclick="viewContract(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['signing_token']) ?>')" 
                                                    class="flex-1 flex items-center justify-center gap-2 px-8 py-5 text-xs font-black uppercase tracking-widest text-gray-700 bg-gray-50 border border-gray-200 rounded-[1.5rem] hover:bg-gray-100 transition-all">
                                                View Agreement
                                            </button>
                                            <a href="/api/download-contract.php?booking_id=<?= $booking['id'] ?>" target="_blank"
                                               class="flex-1 flex items-center justify-center gap-2 px-8 py-5 text-xs font-black uppercase tracking-widest text-white rounded-[1.5rem] shadow-2xl shadow-blue-200 transition-all active:scale-95" style="background: linear-gradient(135deg, <?= $primaryColor ?>, #1e40af)">
                                                PDF Copy
                                            </a>
                                        <?php else: ?>
                                            <a href="/templates/contract-sign.php?booking_id=<?= $booking['id'] ?>&token=<?= htmlspecialchars($booking['signing_token']) ?>" 
                                               class="w-full flex items-center justify-center gap-2 px-8 py-6 text-xs font-black uppercase tracking-[0.2em] text-white rounded-[1.5rem] shadow-2xl shadow-amber-200 transform hover:-translate-y-1 transition-all active:scale-95 animate-pulse" style="background: linear-gradient(135deg, #f59e0b, #d97706)">
                                                Sign Contract
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Mobile Menu Script -->
    <script>
        const btn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function toggleMenu() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        btn?.addEventListener('click', toggleMenu);
        overlay?.addEventListener('click', toggleMenu);
    </script>

    <!-- Contract Viewer Modal -->
    <div id="contractModal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Rental Contract</h3>
                    <p class="text-sm text-gray-500" id="contractModalSubtitle"></p>
                </div>
                <button onclick="closeContractModal()" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <!-- Modal Content -->
            <div class="flex-1 overflow-y-auto p-6" id="contractModalContent">
                <div class="text-center py-12 text-gray-400">
                    <svg class="animate-spin h-8 w-8 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading contract...
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div id="contractSignedInfo" class="text-sm text-gray-500"></div>
                <button onclick="closeContractModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <script>
    function viewContract(bookingId, token) {
        const modal = document.getElementById('contractModal');
        const content = document.getElementById('contractModalContent');
        const subtitle = document.getElementById('contractModalSubtitle');
        const signedInfo = document.getElementById('contractSignedInfo');
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        subtitle.textContent = 'Booking #' + String(bookingId).padStart(5, '0');
        content.innerHTML = '<div class="text-center py-12 text-gray-400"><svg class="animate-spin h-8 w-8 mx-auto mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Loading contract...</div>';
        
        fetch('/api/get-contract.php?booking_id=' + bookingId + '&token=' + token)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    if (data.contract.is_html) {
                        html = '<div class="prose prose-sm max-w-none">' + data.contract.content + '</div>';
                    } else {
                        let text = data.contract.content;
                        text = text.replace(/\n/g, '<br>');
                        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                        html = '<div class="prose prose-sm max-w-none">' + text + '</div>';
                    }
                    
                    // Show signature for signed contracts
                    if (data.contract.contract_status === 'signed' && data.contract.signature_image_url) {
                        html += `
                            <div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
                                <p style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Digital Signature</p>
                                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; text-align: center;">
                                    <img src="${data.contract.signature_image_url}" alt="Signature" style="max-width: 280px; max-height: 120px; margin: 0 auto; display: block;">
                                </div>
                                ${data.contract.signed_at ? '<p style="font-size: 12px; color: #9ca3af; margin-top: 8px;">Signed on ' + new Date(data.contract.signed_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + '</p>' : ''}
                            </div>
                        `;
                    }
                    
                    content.innerHTML = html;
                    
                    if (data.contract.signed_at) {
                        const d = new Date(data.contract.signed_at);
                        signedInfo.innerHTML = '<span class="inline-flex items-center gap-1 text-green-600 font-medium"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Signed on ' + d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + '</span>';
                    } else {
                        signedInfo.textContent = '';
                    }
                } else {
                    content.innerHTML = '<p class="text-red-500 text-center py-8">Failed to load contract: ' + (data.message || 'Unknown error') + '</p>';
                }
            })
            .catch(() => {
                content.innerHTML = '<p class="text-red-500 text-center py-8">Error loading contract. Please try again.</p>';
            });
    }
    
    function closeContractModal() {
        document.getElementById('contractModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // Close modal on backdrop click
    document.getElementById('contractModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeContractModal();
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeContractModal();
    });
    </script>
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>
</html>
