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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?> - <?= htmlspecialchars($content['company_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <?php if (!empty($tenant['logo_url']) || !empty($tenant['logo'])): ?>
                        <img src="<?= htmlspecialchars($tenant['logo_url'] ?: $tenant['logo']) ?>" alt="<?= htmlspecialchars($content['company_name']) ?>" class="h-8 w-auto">
                    <?php else: ?>
                        <div class="w-8 h-8 bg-gray-300 rounded"></div>
                    <?php endif; ?>
                    <span class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($content['company_name']) ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Back Button -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <a href="/templates/vehicle-details.php?id=<?= $vehicle_id ?>" class="inline-flex items-center text-blue-600 hover:text-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Vehicle Details
            </a>
        </div>
    </div>

    <!-- Booking Form -->
    <section class="py-12">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Booking Form -->
                <div class="md:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                        <h1 class="text-2xl font-bold text-gray-900 mb-8">Book Your Vehicle</h1>
                        
                        <form id="bookingForm">
                            <!-- Personal Information -->
                            <div class="mb-8">
                                <div class="flex items-center mb-4">
                                    <svg class="w-5 h-5 text-gray-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <h2 class="text-lg font-semibold text-gray-900">Personal Information</h2>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                        <input type="text" name="full_name" placeholder="John Doe" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                        <input type="email" name="email" placeholder="john@example.com" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                        <input type="tel" name="phone" placeholder="Enter up to 12 digits" required maxlength="12" pattern="[0-9]{1,12}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Driver License Number *</label>
                                        <input type="text" name="license" placeholder="ABCD123456789" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <!-- Rental Period -->
                            <div class="mb-8">
                                <div class="flex items-center mb-4">
                                    <svg class="w-5 h-5 text-gray-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <h2 class="text-lg font-semibold text-gray-900">Rental Period</h2>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pickup Date *</label>
                                        <input type="date" id="pickupDate" name="pickup_date" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Return Date *</label>
                                        <input type="date" id="returnDate" name="return_date" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Notes -->
                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes (Optional)</label>
                                <textarea name="notes" rows="4" placeholder="Any special requests or requirements..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="w-full px-6 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-lg">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Submit Booking Request
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-4">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Booking Summary</h2>
                        
                        <!-- Vehicle Image -->
                        <div class="mb-4 rounded-lg overflow-hidden">
                            <?php
                            $image_url = null;
                            if ($vehicle['images']) {
                                $decoded = json_decode($vehicle['images'], true);
                                $image_url = is_array($decoded) && !empty($decoded) ? $decoded[0] : $vehicle['images'];
                            }
                            // Use placeholder if no image
                            if (empty($image_url)) {
                                $image_url = '/assets/images/placeholder-img.webp';
                            }
?>
                            <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" class="w-full h-48 object-cover">
                        </div>

                        <!-- Vehicle Details -->
                        <h3 class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($vehicle['year']) ?></p>

                        <!-- Pricing -->
                        <div class="border-t border-gray-200 pt-4 mb-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Daily Rate:</span>
                                <span class="font-semibold text-gray-900">£<?= number_format($vehicle['price_per_day']) ?>/day</span>
                            </div>
                            <div class="flex justify-between mb-4">
                                <span class="text-gray-600">Number of Days:</span>
                                <span class="font-semibold text-gray-900" id="numDays">0 days</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-4">
                                <span>Total:</span>
                                <span class="text-blue-600" id="totalPrice">£0.00</span>
                            </div>
                        </div>

                        <!-- What's Included -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-900 mb-2 text-sm">What's Included:</h4>
                            <ul class="space-y-1 text-xs text-blue-900">
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>Basic insurance coverage</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>24/7 roadside assistance</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>Unlimited mileage</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>Free cancellation (24h notice)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-12">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex items-center justify-center space-x-2 mb-4">
                    <div class="w-8 h-8 bg-white rounded"></div>
                    <span class="text-lg font-semibold"><?= htmlspecialchars($content['company_name']) ?></span>
                </div>
                <p class="text-gray-400 text-sm">&copy; <?= date('Y') ?> <?= htmlspecialchars($content['company_name']) ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        const dailyRate = <?= $vehicle['price_per_day'] ?>;
        const pickupDate = document.getElementById('pickupDate');
        const returnDate = document.getElementById('returnDate');
        const numDaysEl = document.getElementById('numDays');
        const totalPriceEl = document.getElementById('totalPrice');

        // Custom error modal function
        function showErrorModal(message) {
            const existingModal = document.getElementById('customErrorModal');
            if (existingModal) {
                existingModal.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'customErrorModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Error</h3>
                    <p class="text-gray-600 text-center mb-6">${message}</p>
                    <button onclick="document.getElementById('customErrorModal').remove()" class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors">
                        OK
                    </button>
                </div>
            `;
            document.body.appendChild(modal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Set minimum dates to today
        const today = new Date().toISOString().split('T')[0];
        pickupDate.min = today;
        returnDate.min = today;

        function calculateTotal() {
            if (pickupDate.value && returnDate.value) {
                const pickup = new Date(pickupDate.value);
                const returnD = new Date(returnDate.value);
                const diffTime = Math.abs(returnD - pickup);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 0) {
                    numDaysEl.textContent = diffDays + ' day' + (diffDays !== 1 ? 's' : '');
                    const total = dailyRate * diffDays;
                    totalPriceEl.textContent = '£' + total.toFixed(2);
                } else {
                    numDaysEl.textContent = '0 days';
                    totalPriceEl.textContent = '£0.00';
                }
            }
        }

        pickupDate.addEventListener('change', function() {
            returnDate.min = this.value;
            calculateTotal();
        });

        returnDate.addEventListener('change', function() {
            // Validate return date is not before pickup date
            if (pickupDate.value && returnDate.value) {
                const pickup = new Date(pickupDate.value);
                const returnD = new Date(returnDate.value);
                
                if (returnD < pickup) {
                    showErrorModal('Return date cannot be before pickup date.');
                    returnDate.value = pickupDate.value;
                    calculateTotal();
                    return;
                }
            }
            calculateTotal();
        });

        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate dates before submission
            if (pickupDate.value && returnDate.value) {
                const pickup = new Date(pickupDate.value);
                const returnD = new Date(returnDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                // Check minimum booking notice FIRST
                const minNotice = <?= isset($settings['min_booking_notice']) ? (int)$settings['min_booking_notice'] : 48 ?>;
                const noticeUnit = <?= json_encode($settings['booking_notice_unit'] ?? 'hours') ?>;
                
                if (minNotice > 0) {
                    let minAllowed = new Date(today);
                    if (noticeUnit === 'hours') {
                        minAllowed.setHours(minAllowed.getHours() + minNotice);
                    } else {
                        minAllowed.setDate(minAllowed.getDate() + minNotice);
                    }
                    
                    if (pickup < minAllowed) {
                        const unitLabel = noticeUnit === 'days' ? 'days' : 'hours';
                        showErrorModal(`Bookings must be made at least ${minNotice} ${unitLabel} in advance. Please select a later pickup date.`);
                        return;
                    }
                }
                
                // Check pickup date is not in the past
                if (pickup < today) {
                    showErrorModal('Pickup date cannot be in the past.');
                    return;
                }
                
                // Check return date is not before pickup date
                if (returnD < pickup) {
                    showErrorModal('Return date cannot be before pickup date.');
                    return;
                }
            }
            
            showSuccessModal();
        });
        
        function showSuccessModal() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Booking Submitted!</h3>
                    <p class="text-gray-600 text-center mb-6">The rental company will contact you shortly to confirm your reservation.</p>
                    <button onclick="window.location.href='/templates/fleet.php'" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        Back to Fleet
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
        }
    </script>
</body>
</html>
