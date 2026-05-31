<!-- Footer -->
<footer class="bg-gray-900 text-white py-12">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8">
            <div>
                <img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro" class="h-10 mb-4 brightness-0 invert">
                <p class="text-gray-400 text-sm">The complete car rental management platform for modern businesses.</p>
            </div>
            <div>
                <h4 class="font-bold mb-4">Product</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/features/id-verification.php" class="text-gray-400 hover:text-white">ID Verification</a></li>
                    <li><a href="/features/booking-calendar.php" class="text-gray-400 hover:text-white">Booking Calendar</a></li>
                    <li><a href="/features/vehicle-management.php" class="text-gray-400 hover:text-white">Vehicle Management</a></li>
                    <li><a href="/features/stripe-integration.php" class="text-gray-400 hover:text-white">Stripe Integration</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/#about" class="text-gray-400 hover:text-white">About</a></li>
                    <li><a href="/pricing.php" class="text-gray-400 hover:text-white">Pricing</a></li>
                    <li><a href="/#faq" class="text-gray-400 hover:text-white">FAQs</a></li>
                    <li><a href="/contact.php" class="text-gray-400 hover:text-white">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/privacy.php" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                    <li><a href="/terms.php" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Privacy Banner -->
<?php include __DIR__ . '/privacy-banner.php'; ?>
