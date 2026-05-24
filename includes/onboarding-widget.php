<?php
// Onboarding checklist data (self-contained)
$ob_pdo = getDB();
$ob_tenant_id = $_SESSION['tenant_id'];

// Check vehicles
$ob_stmt = $ob_pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE tenant_id = ?");
$ob_stmt->execute([$ob_tenant_id]);
$ob_has_vehicle = $ob_stmt->fetchColumn() > 0;

// Check settings
$ob_stmt = $ob_pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$ob_stmt->execute([$ob_tenant_id]);
$ob_settings = $ob_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$ob_has_company = !empty($ob_settings['company_address']) || !empty($ob_settings['company_phone']) || !empty($ob_settings['pickup_location']);

// Check contracts
$ob_has_contract = false;
try {
    $ob_stmt = $ob_pdo->prepare("SELECT COUNT(*) FROM contract_templates WHERE tenant_id = ?");
    $ob_stmt->execute([$ob_tenant_id]);
    $ob_has_contract = $ob_stmt->fetchColumn() > 0;
} catch (PDOException $e) {}

$ob_steps_done  = array_sum([$ob_has_vehicle ? 1 : 0, $ob_has_company ? 1 : 0, $ob_has_contract ? 1 : 0]);
$ob_total_steps = 3; // "View vehicles" is a shortcut, not a task
$ob_pct         = $ob_total_steps > 0 ? round(($ob_steps_done / $ob_total_steps) * 100) : 0;
// Note: Some pages use full_name, user_name
$ob_session_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'there';
$ob_first_name  = explode(' ', trim($ob_session_name))[0];

if ($ob_steps_done < $ob_total_steps): 
?>
<!-- Onboarding Checklist Widget -->
<div id="onboardingWidget" class="fixed bottom-6 right-6 z-[200] w-[340px] hidden">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
        <div class="flex items-start justify-between p-5 pb-3">
            <div>
                <h3 class="text-base font-semibold text-gray-900 flex items-center gap-1.5">
                    Hello <?= htmlspecialchars($ob_first_name) ?>! <span>🎉</span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Complete these steps to make the best out of your account</p>
            </div>
            <button onclick="dismissOnboarding()" class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-700 transition-colors rounded-lg hover:bg-gray-100 ml-2 -mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 pb-3">
            <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all duration-700" style="width:<?= $ob_pct ?>%"></div>
            </div>
            <p class="text-[11px] text-green-600 font-medium mt-1"><?= $ob_pct ?>% completed</p>
        </div>
        <div class="divide-y divide-gray-50 pb-2">

            <?php
            $ob_steps = [
                [
                    'done'  => $ob_has_vehicle,
                    'title' => 'Add a vehicle',
                    'sub'   => 'Add a vehicle your customers can book',
                    'href'  => '/dashboard/vehicles.php',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM3 9l1.5-6h15L21 9m-18 0h18m-18 0l-1 4h20l-1-4"/>',
                ],
                [
                    'done'  => $ob_has_company,
                    'title' => 'Add company details',
                    'sub'   => 'Set your address, phone and pickup location',
                    'href'  => '/dashboard/dealership.php',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                ],
                [
                    'done'  => $ob_has_contract,
                    'title' => 'Create a contract',
                    'sub'   => 'Design a rental contract template for your customers',
                    'href'  => '/dashboard/contract-designer.php',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                ],
            ];
            foreach ($ob_steps as $step): ?>
            <div class="flex items-center gap-3 px-5 py-3 <?= $step['done'] ? 'opacity-40' : '' ?>">
                <div class="w-9 h-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0">
                    <?php if ($step['done']): ?>
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <?php else: ?>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $step['icon'] ?></svg>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 leading-tight <?= $step['done'] ? 'line-through text-gray-400' : '' ?>"><?= htmlspecialchars($step['title']) ?></p>
                    <p class="text-xs text-gray-400 leading-snug"><?= htmlspecialchars($step['sub']) ?></p>
                </div>
                <?php if (!$step['done']): ?>
                <a href="<?= $step['href'] ?>" class="flex-shrink-0 px-3.5 py-1.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all whitespace-nowrap">Start</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <!-- View your vehicles – always visible shortcut -->
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 leading-tight">View your vehicles</p>
                    <p class="text-xs text-gray-400 leading-snug">See and manage all your fleet</p>
                </div>
                <a href="/dashboard/vehicles.php" class="flex-shrink-0 px-3.5 py-1.5 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-all whitespace-nowrap shadow-sm">View</a>
            </div>

        </div>
    </div>
</div>

<script>
(function() {
    var key = 'ob_dismissed_<?= (int)$_SESSION['tenant_id'] ?>';
    
    <?php if (!empty($_SESSION['ob_reset'])): ?>
    localStorage.removeItem(key);
    <?php unset($_SESSION['ob_reset']); ?>
    <?php endif; ?>

    if (!localStorage.getItem(key)) {
        var w = document.getElementById('onboardingWidget');
        if (w) {
            w.classList.remove('hidden');
            w.style.transform = 'translateY(16px)';
            w.style.opacity = '0';
            w.style.transition = 'transform 0.4s cubic-bezier(.22,1,.36,1), opacity 0.35s ease';
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    w.style.transform = 'translateY(0)';
                    w.style.opacity = '1';
                });
            });
        }
    }
})();

function dismissOnboarding() {
    localStorage.setItem('ob_dismissed_<?= (int)$_SESSION['tenant_id'] ?>', '1');
    var w = document.getElementById('onboardingWidget');
    if (w) {
        w.style.transition = 'transform 0.3s ease, opacity 0.25s ease';
        w.style.transform = 'translateY(16px)';
        w.style.opacity = '0';
        setTimeout(function(){ w.classList.add('hidden'); }, 300);
    }
}
</script>
<?php endif; ?>
