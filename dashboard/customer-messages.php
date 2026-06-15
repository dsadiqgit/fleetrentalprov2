<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

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

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
$stmt->execute([$user_id, $tenant_id]);
$user = $stmt->fetch();

// Fetch tenant settings for contact email
$stmt_t = $pdo->prepare("SELECT company_email, company_phone FROM tenant_settings WHERE tenant_id = ?");
$stmt_t->execute([$tenant_id]);
$tenant_contact = $stmt_t->fetch();
$tenant_email = $tenant_contact['company_email'] ?? 'support@fleetrentalpro.com';
$tenant_phone = $tenant_contact['company_phone'] ?? 'N/A';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize($_POST['subject'] ?? '');
    $message_body = sanitize($_POST['message'] ?? '');

    if (empty($subject) || empty($message_body)) {
        $error_msg = 'Both subject and message are required.';
    } else {
        // Construct clean HTML email
        $sender_name = htmlspecialchars($user['full_name'] ?? 'Customer');
        $sender_email = htmlspecialchars($user['email']);
        $sender_phone = htmlspecialchars($user['phone'] ?? 'N/A');

        $html_content = "
            <h2>New Message from Customer</h2>
            <p><strong>From:</strong> {$sender_name} ({$sender_email})</p>
            <p><strong>Phone:</strong> {$sender_phone}</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Message:</strong></p>
            <p style='white-space: pre-wrap; font-size: 14px; line-height: 1.6; color: #333;'>{$message_body}</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #999;'>Sent automatically via your Customer Portal on " . SITE_NAME . "</p>
        ";

        // Send actual email to tenant host
        $sent = sendEmail($tenant_email, "[Customer Enquiry] " . $subject, $html_content, $sender_name);

        if ($sent) {
            $success_msg = "Your message has been sent successfully to " . htmlspecialchars($tenant['name']) . "! They will reply to you shortly.";
        } else {
            $error_msg = "Failed to send the email. Please try again later or call " . htmlspecialchars($tenant_phone) . ".";
        }
    }
}

$primaryColor = $tenant['primary_color'] ?? '#3B82F6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Host - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
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
            <?php if (!empty($tenant['logo'])): ?>
                <img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="Logo" class="h-6 w-auto object-contain">
            <?php else: ?>
                <span class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($tenant['name']) ?></span>
            <?php endif; ?>
        </div>
        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
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
            <!-- Header -->
            <div class="max-w-4xl mb-12">
                <h1 class="text-4xl font-semibold text-gray-900 tracking-tight">Send Message</h1>
                <p class="text-gray-500 mt-2 text-lg font-medium">Have any questions? Email the rental provider directly from your customer portal.</p>
            </div>

            <!-- Notifications -->
            <?php if ($success_msg): ?>
                <div class="max-w-2xl mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-medium flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="max-w-2xl mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm font-medium flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <!-- Message Form Card -->
            <div class="max-w-2xl bg-white border border-gray-100 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Email Host</h2>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Recipient: <?= htmlspecialchars($tenant['name']) ?></p>
                    </div>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Subject Line</label>
                        <input type="text" name="subject" placeholder="Enquiry about my booking" required
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Your Message</label>
                        <textarea name="message" rows="6" placeholder="Write your query or message here..." required
                                  class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 bg-gray-900 hover:bg-black text-white text-sm font-semibold rounded-xl transition-all shadow-sm mt-4 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send Email
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle script
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (btn) btn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
            if (overlay) overlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        });
    </script>
</body>
</html>
