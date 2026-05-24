<?php
require_once __DIR__ . '/../includes/tenant_init.php';

// Tenant is already loaded by tenant_init.php
$tenant_id = getTenantId();
$tenant = getTenant();

$pdo = getDB();

// Get vehicle ID from URL
$vehicle_id = $_GET['vehicle_id'] ?? $_GET['id'] ?? null;

if (!$vehicle_id) {
    die('Vehicle not found');
}

// Get website content
$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$content = $stmt->fetch();

if (!$content) {
    $content = ['company_name' => $tenant['name']];
}

// Get vehicle details
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND tenant_id = ?");
$stmt->execute([$vehicle_id, $tenant_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die('Vehicle not found');
}

// Get similar vehicles
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE tenant_id = ? AND id != ? AND availability = 1 LIMIT 3");
$stmt->execute([$tenant_id, $vehicle_id]);
$similar_vehicles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> | <?= htmlspecialchars($content['company_name'] ?? $tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50">

    <!-- Universal Tenant Header (Includes Branding, Navigation & Styles) -->
    <?php include __DIR__ . '/includes/tenant_header.php'; ?>

    <!-- Back Button -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <a href="/templates/fleet.php" class="inline-flex items-center text-blue-600 hover:text-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Fleet
            </a>
        </div>
    </div>

    <!-- Vehicle Details -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12">
                <!-- Vehicle Image -->
                <div>
                    <div class="bg-white rounded-lg overflow-hidden shadow-sm">
                        <?php if ($vehicle['images']): ?>
                            <img src="<?= htmlspecialchars($vehicle['images']) ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" class="w-full h-96 object-cover">
                        <?php else: ?>
                            <div class="w-full h-96 bg-gray-200 flex items-center justify-center">
                                <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h1>
                            <p class="text-gray-600"><?= htmlspecialchars($vehicle['year']) ?></p>
                        </div>
                        <?php if ($vehicle['availability']): ?>
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">Available</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <div class="text-4xl font-bold text-blue-600 mb-2">£<?= number_format($vehicle['price_per_day']) ?> <span class="text-lg text-gray-600 font-normal">per day</span></div>
                        <p class="text-gray-600">Special rates available for weekly and monthly rentals</p>
                    </div>

                    <!-- Vehicle Specifications -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Vehicle Specifications</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700"><?= htmlspecialchars($vehicle['transmission']) ?></span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700"><?= htmlspecialchars($vehicle['fuel_type']) ?></span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700"><?= htmlspecialchars($vehicle['seats']) ?> Seats</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700"><?= htmlspecialchars(ucfirst($vehicle['category'])) ?></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200">
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-1">Horsepower</p>
                                <p class="text-lg font-semibold text-gray-900">N/A HP</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-1">0-60 mph</p>
                                <p class="text-lg font-semibold text-gray-900">N/As</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-1">Top Speed</p>
                                <p class="text-lg font-semibold text-gray-900">N/A mph</p>
                            </div>
                        </div>
                    </div>

                    <!-- Book Button -->
                    <a href="/templates/checkout.php?vehicle_id=<?= $vehicle_id ?>" class="w-full block text-center px-6 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-lg mb-6">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Book This Vehicle
                    </a>

                    <!-- Special Offers & Packages -->
                    <?php
                    $packages = !empty($vehicle['pricing_packages']) ? json_decode($vehicle['pricing_packages'], true) : [];
                    if (!empty($packages)):
                    ?>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>
                            Special Offers
                        </h3>
                        <div class="space-y-3">
                            <?php foreach ($packages as $pkg): 
                                $desc = "";
                                $dayNames = [1=>'Monday', 2=>'Tuesday', 3=>'Wednesday', 4=>'Thursday', 5=>'Friday', 6=>'Saturday', 7=>'Sunday'];
                                if ($pkg['type'] === 'fixed_price') {
                                    $p_start = intval($pkg['start_day'] ?? 0);
                                    $p_end = intval($pkg['end_day'] ?? 0);
                                    if ($p_start && $p_end) {
                                        $desc = "Book <strong>" . $dayNames[$p_start] . "</strong> to <strong>" . $dayNames[$p_end] . "</strong> for only <strong>£" . number_format($pkg['fixed_price']) . "</strong> total.";
                                    } else {
                                        $desc = "Flat rate of <strong>£" . number_format($pkg['fixed_price']) . "</strong> for " . intval($pkg['min_days']) . "+ days.";
                                    }
                                } elseif ($pkg['type'] === 'discount_target_day') {
                                    $amt = ($pkg['discount_type'] ?? 'fixed') === 'percentage' ? $pkg['discount_amount'] . '%' : '£' . $pkg['discount_amount'];
                                    $desc = "Get <strong>{$amt} off</strong> on your <strong>" . intval($pkg['target_day'] ?? 0) . "th day</strong>.";
                                } elseif ($pkg['type'] === 'free_target_day') {
                                    $desc = "Your <strong>" . intval($pkg['target_day'] ?? 0) . "th day is FREE</strong> on bookings of " . intval($pkg['min_days'] ?? 0) . "+ days.";
                                }
                            ?>
                                <div class="bg-white p-3 rounded-md border border-gray-100 shadow-sm">
                                    <div class="text-sm font-semibold text-gray-900 mb-1"><?= htmlspecialchars($pkg['name'] ?: 'Package Deal') ?></div>
                                    <p class="text-xs text-gray-600 leading-relaxed"><?= $desc ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Rental Includes -->
                    <div class="bg-blue-50 rounded-lg p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Rental Includes:</h3>
                        <ul class="space-y-2 text-sm text-blue-900">
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Unlimited mileage</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>24/7 roadside assistance</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Basic insurance coverage</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Free cancellation up to 24 hours before pickup</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Similar Vehicles -->
    <?php if (!empty($similar_vehicles)): ?>
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Similar Vehicles</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($similar_vehicles as $similar): ?>
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition">
                        <div class="h-48 bg-gray-200 overflow-hidden">
                            <?php if ($similar['images']): ?>
                                <img src="<?= htmlspecialchars($similar['images']) ?>" alt="<?= htmlspecialchars($similar['brand'] . ' ' . $similar['model']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($similar['brand'] . ' ' . $similar['model']) ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($similar['year']) ?></p>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-2xl font-bold text-blue-600">£<?= number_format($similar['price_per_day']) ?></span>
                                    <span class="text-gray-600 text-sm">/day</span>
                                </div>
                                <a href="/templates/vehicle-details.php?id=<?= $similar['id'] ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Universal Tenant Footer -->
    <?php include __DIR__ . '/includes/tenant_footer.php'; ?>
</body>
</html>
