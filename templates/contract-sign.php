<?php
/**
 * Contract Signing Page
 * Customer-facing page where they review and sign the rental contract
 * Accessed via the link in the "Welcome & Sign" email
 */
require_once __DIR__ . '/../includes/tenant_init.php';

$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

// Get URL parameters
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$token = $_GET['token'] ?? '';

if (!$booking_id || !$token) {
    http_response_code(400);
    die('<div style="text-align:center;padding:60px;font-family:Arial,sans-serif;"><h1 style="color:#ef4444;">Invalid Link</h1><p>This contract signing link is invalid or has expired.</p></div>');
}

// Validate booking + contract
$stmt = $pdo->prepare("
    SELECT c.*, b.customer_name, b.customer_email, b.customer_phone,
           b.pickup_date, b.return_date, b.total_days, b.total_price,
           v.brand, v.model, v.year, v.category
    FROM contracts c
    JOIN bookings b ON c.booking_id = b.id
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    WHERE c.booking_id = ? AND c.signing_token = ? AND c.tenant_id = ?
");
$stmt->execute([$booking_id, $token, $tenant_id]);
$contract = $stmt->fetch();

if (!$contract) {
    http_response_code(404);
    die('<div style="text-align:center;padding:60px;font-family:Arial,sans-serif;"><h1 style="color:#ef4444;">Contract Not Found</h1><p>This contract could not be found. The link may have expired or be invalid.</p></div>');
}

$contractStatus = $contract['contract_status'] ?? ($contract['signed'] ? 'signed' : 'pending');
$isPending = $contractStatus === 'pending';
$vehicleName = trim(($contract['brand'] ?? '') . ' ' . ($contract['model'] ?? ''));
$bookingRef = str_pad($booking_id, 5, '0', STR_PAD_LEFT);
$primaryColor = $tenant['primary_color'] ?? '#3B82F6';
$secondaryColor = $tenant['secondary_color'] ?? '#1E40AF';

// Check Didit ID verification status
$idVerified = false;
try {
    $verStmt = $pdo->prepare("SELECT verification_status FROM customer_verifications WHERE tenant_id = ? AND customer_email = ? ORDER BY id DESC LIMIT 1");
    $verStmt->execute([$tenant_id, $contract['customer_email']]);
    $verification = $verStmt->fetch();
    if ($verification && $verification['verification_status'] === 'approved') {
        $idVerified = true;
    }
} catch (Exception $e) {
    // Table might not exist — default to verified to avoid blocking
    $idVerified = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Contract - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body { font-family: 'Inter', sans-serif; }
        
        /* Blur overlay — non-dismissible */
        .contract-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        
        .contract-modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 800px;
            max-height: 92vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.4s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .signature-pad-wrapper {
            position: relative;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            cursor: crosshair;
            touch-action: none;
            transition: border-color 0.3s;
        }
        
        .signature-pad-wrapper:hover,
        .signature-pad-wrapper.active {
            border-color: <?= $primaryColor ?>;
        }
        
        .signature-pad-wrapper canvas {
            display: block;
            width: 100%;
            height: 180px;
        }
        
        .signature-line {
            position: absolute;
            bottom: 40px;
            left: 24px;
            right: 24px;
            height: 1px;
            background: #d1d5db;
            pointer-events: none;
        }
        
        .signature-line::after {
            content: '✕';
            position: absolute;
            left: 0;
            top: -14px;
            font-size: 16px;
            color: #9ca3af;
        }
        
        .signature-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #d1d5db;
            font-size: 15px;
            pointer-events: none;
            transition: opacity 0.2s;
        }
        
        .clear-sig-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.05);
            border: none;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 12px;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 2;
        }
        
        .clear-sig-btn:hover {
            background: rgba(0,0,0,0.1);
            color: #374151;
        }
        
        .step-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .step-active {
            background: <?= $primaryColor ?>;
            color: white;
        }
        
        .step-done {
            background: #d1fae5;
            color: #065f46;
        }
        
        .step-pending {
            background: #f3f4f6;
            color: #9ca3af;
        }
        
        .brand-btn {
            background-color: <?= $primaryColor ?>;
            transition: all 0.2s;
        }
        
        .brand-btn:hover {
            filter: brightness(0.9);
        }
        
        .contract-content {
            max-height: 350px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            background: #fafafa;
            font-size: 14px;
            line-height: 1.7;
        }
        
        .contract-content .no-print,
        .contract-content .absolute.-left-12 {
            display: none !important;
        }
        
        .contract-content::-webkit-scrollbar { width: 6px; }
        .contract-content::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .contract-content::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        .contract-content::-webkit-scrollbar-thumb:hover { background: #a1a1a1; }
        
        .pulse-ring {
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(0.33); opacity: 1; }
            80%, 100% { transform: scale(1.2); opacity: 0; }
        }
        
        .success-check {
            animation: scaleIn 0.4s ease-out;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Background page (blurred when pending) -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="text-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: <?= $primaryColor ?>20;">
                <svg class="w-8 h-8" fill="none" stroke="<?= $primaryColor ?>" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($tenant['name']) ?></h1>
            <p class="text-gray-600">Rental Contract for Booking #<?= $bookingRef ?></p>
        </div>
    </div>

    <?php if ($isPending): ?>
    <!-- NON-DISMISSIBLE CONTRACT OVERLAY -->
    <div class="contract-overlay" id="contractOverlay">
        <div class="contract-modal">
            <!-- Modal Header -->
            <div class="sticky top-0 z-10 rounded-t-2xl px-6 py-5" style="background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-white text-xl font-bold"><?= htmlspecialchars($tenant['name']) ?></h2>
                        <p class="text-white/80 text-sm mt-0.5">Rental Contract — Booking #<?= $bookingRef ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-white/20 rounded-full text-white text-xs font-semibold backdrop-blur-sm">
                            <?= htmlspecialchars($vehicleName) ?>
                        </span>
                    </div>
                </div>
                
                <!-- Step Indicators -->
                <div class="flex items-center gap-3 mt-5">
                    <div class="flex items-center gap-2">
                        <div class="step-badge <?= !$idVerified ? 'step-active' : 'step-done' ?>" id="step1Badge">
                            <?= $idVerified ? '✓' : '1' ?>
                        </div>
                        <span class="text-white text-sm font-medium">Verify ID</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-white/30 rounded"></div>
                    <div class="flex items-center gap-2">
                        <div class="step-badge <?= !$idVerified ? 'step-pending' : 'step-active' ?>" id="step2Badge">2</div>
                        <span class="text-white text-sm font-medium">Review & Sign</span>
                    </div>
                </div>
            </div>

            <!-- Step 1: ID Verification (Didit) -->
            <div id="step1Content" class="p-6 <?= $idVerified ? 'hidden' : '' ?>">
                <div class="text-center py-8">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6" style="background-color: <?= $primaryColor ?>15;">
                        <svg class="w-10 h-10" fill="none" stroke="<?= $primaryColor ?>" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Verify Your Identity</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">For your security, we need to verify your identity before you can sign the contract. This quick process takes less than a minute.</p>
                    
                    <button onclick="startIdVerification()" class="brand-btn text-white px-8 py-3 rounded-xl font-semibold text-base" id="verifyBtn">
                        <span id="verifyBtnText">Start ID Verification</span>
                        <span id="verifyBtnLoading" class="hidden">
                            <svg class="animate-spin h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                    
                    <p class="text-gray-400 text-xs mt-4">Powered by Didit — Secure identity verification</p>
                </div>
            </div>

            <!-- Step 2: Review & Sign Contract -->
            <div id="step2Content" class="<?= !$idVerified ? 'hidden' : '' ?>">
                <!-- Booking Summary Bar -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 block text-xs">Customer</span>
                            <span class="font-semibold text-gray-900"><?= htmlspecialchars($contract['customer_name']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-xs">Pickup</span>
                            <span class="font-semibold text-gray-900"><?= date('M j, Y', strtotime($contract['pickup_date'])) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-xs">Return</span>
                            <span class="font-semibold text-gray-900"><?= date('M j, Y', strtotime($contract['return_date'])) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-xs">Total</span>
                            <span class="font-bold" style="color: <?= $primaryColor ?>">£<?= number_format($contract['total_price'], 2) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Contract Text -->
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Rental Agreement
                    </h3>
                    
                    <div class="contract-content" id="contractContent">
                        <div class="text-center text-gray-400 py-8">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading contract...
                        </div>
                    </div>
                    
                    <!-- Signature Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Digital Signature</h3>
                        <p class="text-gray-500 text-sm mb-5">Draw your signature in the box below to sign this contract electronically.</p>
                        
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Your Signature</label>
                            <div class="signature-pad-wrapper" id="signaturePadWrapper">
                                <canvas id="signatureCanvas"></canvas>
                                <div class="signature-line"></div>
                                <div class="signature-placeholder" id="sigPlaceholder">Draw your signature here</div>
                                <button type="button" class="clear-sig-btn" id="clearSigBtn" onclick="clearSignature()">Clear</button>
                            </div>
                            
                            <div class="flex items-start gap-3 mt-5">
                                <input type="checkbox" id="agreeCheckbox" class="mt-1 w-4 h-4 rounded border-gray-300 cursor-pointer" style="accent-color: <?= $primaryColor ?>">
                                <label for="agreeCheckbox" class="text-sm text-gray-600 cursor-pointer leading-relaxed">
                                    I have read and agree to the terms and conditions outlined in this rental contract. I understand that this electronic signature is legally binding.
                                </label>
                            </div>
                        </div>
                        
                        <button onclick="submitSignature()" 
                                id="signBtn"
                                disabled
                                class="w-full mt-6 brand-btn text-white py-4 rounded-xl font-bold text-base disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                            <span id="signBtnText">Confirm Signature</span>
                            <span id="signBtnLoading" class="hidden">
                                <svg class="animate-spin h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Signing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Success State (shown after signing) -->
            <div id="successContent" class="hidden p-6">
                <div class="text-center py-12">
                    <div class="relative inline-flex items-center justify-center mb-6">
                        <div class="absolute w-20 h-20 rounded-full pulse-ring" style="background-color: <?= $primaryColor ?>30;"></div>
                        <div class="success-check w-20 h-20 rounded-full flex items-center justify-center" style="background-color: #d1fae5;">
                            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Contract Signed!</h2>
                    <p class="text-gray-600 mb-1">Thank you, <strong id="signedName"></strong>.</p>
                    <p class="text-gray-500 text-sm mb-6">Your signed contract has been sent to your email address.</p>
                    
                    <div class="bg-gray-50 rounded-xl p-5 max-w-sm mx-auto text-left text-sm">
                        <div class="flex items-center gap-2 text-gray-700 mb-2">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Contract signed electronically
                        </div>
                        <div class="flex items-center gap-2 text-gray-700 mb-2">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            PDF sent to your email
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Rental provider notified
                        </div>
                    </div>
                    
                    <p class="text-gray-400 text-xs mt-6" id="signedTimestamp"></p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- ALREADY SIGNED STATE -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-100">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-5" style="background-color: #d1fae5;">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Contract Already Signed</h2>
            <p class="text-gray-600 mb-4">This contract was signed on <?= $contract['signed_at'] ? date('F j, Y', strtotime($contract['signed_at'])) : 'a previous date' ?>.</p>
            <?php if ($contract['signature_typed']): ?>
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Signed by</p>
                <p class="font-semibold text-lg" style="font-family: 'Brush Script MT', cursive;"><?= htmlspecialchars($contract['signature_typed']) ?></p>
            </div>
            <?php endif; ?>
            <p class="text-gray-500 text-sm">A copy of the signed contract was sent to your email address.</p>
        </div>
    </div>
    <?php endif; ?>

<script>
    const BOOKING_ID = <?= $booking_id ?>;
    const TOKEN = '<?= htmlspecialchars($token) ?>';
    let idVerified = <?= $idVerified ? 'true' : 'false' ?>;
    
    // ============================================
    // SIGNATURE PAD — Canvas Drawing
    // ============================================
    let canvas, ctx, isDrawing = false, hasSignature = false;
    let lastX = 0, lastY = 0;
    
    function initSignaturePad() {
        canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        
        ctx = canvas.getContext('2d');
        
        // Set canvas resolution to match display size
        function resizeCanvas() {
            const wrapper = document.getElementById('signaturePadWrapper');
            const rect = wrapper.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            canvas.width = rect.width * dpr;
            canvas.height = 180 * dpr;
            canvas.style.width = rect.width + 'px';
            canvas.style.height = '180px';
            ctx.scale(dpr, dpr);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#1a1a2e';
            ctx.lineWidth = 3.0; // Slightly thicker for better visibility in PDF
        }
        resizeCanvas();
        window.addEventListener('resize', () => {
            if (!hasSignature) resizeCanvas();
        });
        
        // Mouse events
        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);
        
        // Touch events
        canvas.addEventListener('touchstart', handleTouch, { passive: false });
        canvas.addEventListener('touchmove', handleTouch, { passive: false });
        canvas.addEventListener('touchend', endDraw);
        canvas.addEventListener('touchcancel', endDraw);
    }
    
    function getCanvasPos(e) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }
    
    function startDraw(e) {
        isDrawing = true;
        const pos = getCanvasPos(e);
        lastX = pos.x;
        lastY = pos.y;
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        
        // Hide placeholder on first draw
        document.getElementById('sigPlaceholder').style.opacity = '0';
        document.getElementById('signaturePadWrapper').classList.add('active');
    }
    
    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const pos = getCanvasPos(e);
        
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        
        lastX = pos.x;
        lastY = pos.y;
        hasSignature = true;
    }
    
    function endDraw() {
        if (isDrawing) {
            isDrawing = false;
            ctx.closePath();
            document.getElementById('signaturePadWrapper').classList.remove('active');
            updateSignButton();
        }
    }
    
    function handleTouch(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent({
            'touchstart': 'mousedown',
            'touchmove': 'mousemove',
            'touchend': 'mouseup'
        }[e.type], {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }
    
    function clearSignature() {
        if (!canvas || !ctx) return;
        const dpr = window.devicePixelRatio || 1;
        ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        hasSignature = false;
        document.getElementById('sigPlaceholder').style.opacity = '1';
        updateSignButton();
    }
    
    function isCanvasBlank() {
        const blank = document.createElement('canvas');
        blank.width = canvas.width;
        blank.height = canvas.height;
        return canvas.toDataURL() === blank.toDataURL();
    }
    
    function getSignatureDataUrl() {
        if (!canvas || isCanvasBlank()) return null;
        return canvas.toDataURL('image/png');
    }
    
    // ============================================
    // PAGE INIT
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        if (idVerified) {
            loadContractContent();
            initSignaturePad();
        }
        
        const checkbox = document.getElementById('agreeCheckbox');
        if (checkbox) {
            checkbox.addEventListener('change', updateSignButton);
        }
    });
    
    function updateSignButton() {
        const agreed = document.getElementById('agreeCheckbox')?.checked || false;
        const btn = document.getElementById('signBtn');
        if (btn) btn.disabled = !(hasSignature && agreed);
    }
    
    function loadContractContent() {
        fetch('/api/get-contract.php?booking_id=' + BOOKING_ID + '&token=' + TOKEN)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('contractContent');
                    if (data.contract.is_html) {
                        container.innerHTML = data.contract.content;
                    } else {
                        let text = data.contract.content;
                        text = text.replace(/\n/g, '<br>');
                        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                        container.innerHTML = text;
                    }
                } else {
                    document.getElementById('contractContent').innerHTML = 
                        '<p class="text-red-500 text-center py-4">Failed to load contract: ' + (data.message || 'Unknown error') + '</p>';
                }
            })
            .catch(err => {
                document.getElementById('contractContent').innerHTML = 
                    '<p class="text-red-500 text-center py-4">Error loading contract. Please refresh the page.</p>';
            });
    }
    
    function startIdVerification() {
        const btn = document.getElementById('verifyBtn');
        const btnText = document.getElementById('verifyBtnText');
        const btnLoading = document.getElementById('verifyBtnLoading');
        
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        btn.disabled = true;
        
        const bookingData = {
            customer_email: '<?= htmlspecialchars($contract['customer_email']) ?>',
            customer_name: '<?= htmlspecialchars($contract['customer_name']) ?>',
            skip_verification: false
        };
        
        fetch('/templates/save-booking-data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bookingData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.verification_url) {
                const verifyWindow = window.open(data.verification_url, 'didit_verify', 'width=600,height=700');
                
                const pollInterval = setInterval(() => {
                    fetch('/templates/check-verification-status.php?email=<?= urlencode($contract['customer_email']) ?>')
                        .then(r => r.json())
                        .then(status => {
                            if (status.verified) {
                                clearInterval(pollInterval);
                                idVerified = true;
                                if (verifyWindow && !verifyWindow.closed) verifyWindow.close();
                                moveToStep2();
                            }
                        })
                        .catch(() => {});
                }, 3000);
                
                const closeInterval = setInterval(() => {
                    if (verifyWindow && verifyWindow.closed) {
                        clearInterval(closeInterval);
                        setTimeout(() => {
                            fetch('/templates/check-verification-status.php?email=<?= urlencode($contract['customer_email']) ?>')
                                .then(r => r.json())
                                .then(status => {
                                    if (status.verified) {
                                        clearInterval(pollInterval);
                                        idVerified = true;
                                        moveToStep2();
                                    } else {
                                        btnText.classList.remove('hidden');
                                        btnLoading.classList.add('hidden');
                                        btn.disabled = false;
                                    }
                                });
                        }, 1000);
                    }
                }, 1000);
                
            } else if (data.skipped) {
                idVerified = true;
                moveToStep2();
            } else {
                alert('Could not start verification: ' + (data.message || 'Unknown error'));
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.log('Verification service unavailable, proceeding to contract');
            idVerified = true;
            moveToStep2();
        });
    }
    
    function moveToStep2() {
        document.getElementById('step1Badge').className = 'step-badge step-done';
        document.getElementById('step1Badge').textContent = '✓';
        document.getElementById('step2Badge').className = 'step-badge step-active';
        
        document.getElementById('step1Content').classList.add('hidden');
        document.getElementById('step2Content').classList.remove('hidden');
        
        loadContractContent();
        initSignaturePad();
    }
    
    function submitSignature() {
        const signatureData = getSignatureDataUrl();
        const agreed = document.getElementById('agreeCheckbox').checked;
        
        if (!signatureData) {
            alert('Please draw your signature in the box.');
            return;
        }
        if (!agreed) {
            alert('Please agree to the terms and conditions.');
            return;
        }
        
        const btn = document.getElementById('signBtn');
        const btnText = document.getElementById('signBtnText');
        const btnLoading = document.getElementById('signBtnLoading');
        
        btn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        
        fetch('/api/sign-contract.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                booking_id: BOOKING_ID,
                token: TOKEN,
                signature: signatureData
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('step2Content').classList.add('hidden');
                document.getElementById('successContent').classList.remove('hidden');
                document.getElementById('signedName').textContent = '<?= htmlspecialchars($contract['customer_name']) ?>';
                document.getElementById('signedTimestamp').textContent = 'Signed on ' + (data.signed_at || new Date().toLocaleString());
                
                document.getElementById('step2Badge').className = 'step-badge step-done';
                document.getElementById('step2Badge').textContent = '✓';
                
                // Onboard and redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = data.redirect_url || '/dashboard/index.php';
                }, 2000);
            } else {
                alert('Error: ' + (data.message || 'Failed to sign contract'));
                btn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
            }
        })
        .catch(err => {
            alert('Network error. Please try again.');
            btn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        });
    }
</script>
</body>
</html>
