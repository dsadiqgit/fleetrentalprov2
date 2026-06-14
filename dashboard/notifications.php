<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');
if ($_SESSION['role'] === 'super_admin') redirect('/admin/super-admin.php');
if ($_SESSION['role'] === 'customer') redirect('/dashboard/customer.php');
if (!$_SESSION['tenant_id']) die('Error: No tenant associated with this account.');

$pdo = getDB();
$tenant_id = $_SESSION['tenant_id'];

$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();
if (!$tenant) die('Tenant not found.');

// ── Create table ──────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS email_templates (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT NOT NULL,
    template_key VARCHAR(50) NOT NULL,
    subject      VARCHAR(500) NOT NULL,
    body         LONGTEXT NOT NULL,
    enabled      TINYINT(1) DEFAULT 1,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenant_template (tenant_id, template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Default template definitions ─────────────────────────────────────────────
function notif_wrap(string $content, string $title_bar = '{{company_name}}'): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
         . '<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Arial,sans-serif;background:#f5f5f5;">'
         . '<div style="max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">'
         . '<div style="padding:24px 32px;background:#111;color:#fff;"><p style="margin:0;font-size:18px;font-weight:700;">' . $title_bar . '</p></div>'
         . '<div style="padding:32px;">' . $content . '</div>'
         . '<div style="padding:18px 32px;border-top:1px solid #f0f0f0;background:#fafafa;text-align:center;font-size:12px;color:#999;">'
         . '&copy; {{company_name}}. All rights reserved. &mdash; This is an automated message, please do not reply.'
         . '</div></div></body></html>';
}

function notif_table(array $rows): string {
    $html = '<table style="width:100%;border-collapse:collapse;margin:20px 0;">';
    foreach ($rows as [$label, $val]) {
        $html .= '<tr>'
               . '<td style="padding:11px 0;border-bottom:1px solid #f0f0f0;color:#666;font-size:14px;">' . $label . '</td>'
               . '<td style="padding:11px 0;border-bottom:1px solid #f0f0f0;font-weight:600;text-align:right;font-size:14px;">' . $val . '</td>'
               . '</tr>';
    }
    return $html . '</table>';
}

function notif_btn(string $url, string $label): string {
    return '<div style="margin:24px 0;"><a href="' . $url . '" style="display:inline-block;padding:13px 30px;background:#111;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">' . $label . '</a></div>';
}

function notif_alert(string $text, string $bg = '#fffbeb', string $border = '#f59e0b', string $color = '#92400e'): string {
    return '<div style="margin:20px 0;padding:14px 16px;background:' . $bg . ';border-left:4px solid ' . $border . ';border-radius:4px;">'
         . '<p style="margin:0;font-size:13px;color:' . $color . ';">' . $text . '</p></div>';
}

function getDefaultEmailTemplates(): array {
    // ─ Booking Confirmation ────────────────────────────────────────────────
    $bc_content = '<p style="color:#666;font-size:14px;margin:0 0 6px;">Hello {{customer_name}},</p>'
                . '<h1 style="margin:0 0 24px;font-size:22px;font-weight:700;color:#111;">Your booking is confirmed ✓</h1>'
                . notif_table([
                    ['Booking Reference', '#{{booking_ref}}'],
                    ['Vehicle', '{{vehicle_name}}'],
                    ['Pickup Date', '{{pickup_date}}'],
                    ['Return Date', '{{return_date}}'],
                    ['Total Amount', '<span style="font-size:16px;">{{currency}}{{total_price}}</span>'],
                    ['Security Deposit', '{{currency}}{{deposit}}'],
                  ])
                . notif_alert('<strong>Action Required:</strong> Please log in to review and sign your rental agreement before your pickup date.')
                . notif_btn('{{login_url}}', 'Login to Your Account')
                . '<p style="font-size:13px;color:#888;margin:16px 0 0;">Your deposit will be refunded after the vehicle is returned in good condition.</p>';

    // ─ Contract Welcome ────────────────────────────────────────────────────
    $cw_content = '<p style="color:#666;font-size:14px;margin:0 0 6px;">Hello {{customer_name}},</p>'
                . '<h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111;">Please sign your rental contract</h1>'
                . '<p style="font-size:14px;color:#555;margin:0 0 20px;">Thank you for booking with <strong>{{company_name}}</strong>. Before you collect your vehicle, please complete the steps below.</p>'
                . '<div style="margin:20px 0;">'
                . '<div style="display:flex;align-items:flex-start;margin-bottom:14px;"><div style="min-width:28px;height:28px;background:#111;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;margin-right:12px;">1</div><div><strong style="font-size:14px;">Verify Your ID</strong><br><span style="font-size:13px;color:#666;">Quick identity check for your security</span></div></div>'
                . '<div style="display:flex;align-items:flex-start;margin-bottom:14px;"><div style="min-width:28px;height:28px;background:#111;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;margin-right:12px;">2</div><div><strong style="font-size:14px;">Review Your Contract</strong><br><span style="font-size:13px;color:#666;">Read through the rental terms and conditions</span></div></div>'
                . '<div style="display:flex;align-items:flex-start;"><div style="min-width:28px;height:28px;background:#111;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;margin-right:12px;">3</div><div><strong style="font-size:14px;">Sign Digitally</strong><br><span style="font-size:13px;color:#666;">Confirm your booking with an electronic signature</span></div></div>'
                . '</div>'
                . notif_btn('{{contract_url}}', 'Review &amp; Sign Contract')
                . '<p style="font-size:12px;color:#999;word-break:break-all;">If the button doesn\'t work, paste this link: {{contract_url}}</p>';

    // ─ Account Created ─────────────────────────────────────────────────────
    $ac_content = '<p style="color:#666;font-size:14px;margin:0 0 6px;">Hello {{customer_name}},</p>'
                . '<h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111;">Your account has been created</h1>'
                . '<p style="font-size:14px;color:#555;margin:0 0 20px;">Your booking <strong>#{{booking_ref}}</strong> has been confirmed. An account has been created for you to manage your bookings online.</p>'
                . '<div style="background:#f9f9f9;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin:20px 0;">'
                . '<p style="margin:0 0 12px;font-weight:700;font-size:15px;color:#111;">Your Login Details</p>'
                . '<p style="margin:0 0 8px;font-size:14px;"><strong>Email:</strong> {{customer_email}}</p>'
                . '<p style="margin:0;font-size:14px;"><strong>Password:</strong> {{password}}</p>'
                . '</div>'
                . notif_alert('🔒 <strong>For your security</strong>, we recommend changing your password after your first login.', '#fefce8', '#fbbf24', '#92400e')
                . notif_btn('{{login_url}}', 'Login to Your Account');

    // ─ Booking Cancellation ────────────────────────────────────────────────
    $cancel_content = '<p style="color:#666;font-size:14px;margin:0 0 6px;">Hello {{customer_name}},</p>'
                    . '<h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111;">Your booking has been cancelled</h1>'
                    . notif_table([
                        ['Booking Reference', '#{{booking_ref}}'],
                        ['Vehicle', '{{vehicle_name}}'],
                        ['Pickup Date', '{{pickup_date}}'],
                        ['Return Date', '{{return_date}}'],
                      ])
                    . notif_alert('If you did not request this cancellation or have any questions, please contact us at <strong>{{company_email}}</strong>.', '#fef2f2', '#f87171', '#991b1b')
                    . '<p style="font-size:14px;color:#555;">We hope to see you again soon. Visit our website to make a new booking.</p>'
                    . '<p style="font-size:14px;color:#555;margin-top:24px;">The <strong>{{company_name}}</strong> Team</p>';

    // ─ Booking Reminder ────────────────────────────────────────────────────
    $remind_content = '<p style="color:#666;font-size:14px;margin:0 0 6px;">Hello {{customer_name}},</p>'
                    . '<h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111;">Reminder: Your rental pickup is tomorrow</h1>'
                    . '<p style="font-size:14px;color:#555;margin:0 0 20px;">This is a friendly reminder that your rental with <strong>{{company_name}}</strong> starts tomorrow. Please make sure everything is ready.</p>'
                    . notif_table([
                        ['Vehicle', '{{vehicle_name}}'],
                        ['Pickup Date', '{{pickup_date}}'],
                        ['Return Date', '{{return_date}}'],
                      ])
                    . '<div style="margin:20px 0;padding:16px;background:#f0fdf4;border-left:4px solid #22c55e;border-radius:4px;">'
                    . '<p style="margin:0 0 6px;font-weight:700;font-size:14px;color:#14532d;">What to bring</p>'
                    . '<ul style="margin:0;padding:0 0 0 18px;font-size:13px;color:#166534;">'
                    . '<li>Valid driving licence</li><li>Passport or government-issued ID</li><li>Payment card for deposit</li>'
                    . '</ul></div>'
                    . notif_btn('{{login_url}}', 'View Booking Details')
                    . '<p style="font-size:14px;color:#555;">See you tomorrow! — The <strong>{{company_name}}</strong> Team</p>';

    return [
        'booking_confirmation' => [
            'name'        => 'Booking Confirmation',
            'description' => 'Sent automatically when a new booking is created.',
            'icon_path'   => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'badge_bg'    => '#dcfce7', 'badge_color' => '#166534',
            'variables'   => ['customer_name','booking_ref','vehicle_name','pickup_date','return_date','total_price','deposit','currency','company_name','login_url'],
            'subject'     => 'Your Booking is Confirmed — #{{booking_ref}} | {{company_name}}',
            'body'        => notif_wrap($bc_content),
        ],
        'contract_welcome' => [
            'name'        => 'Contract Signing',
            'description' => 'Sent to the customer to review and sign the rental contract.',
            'icon_path'   => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'badge_bg'    => '#dbeafe', 'badge_color' => '#1e40af',
            'variables'   => ['customer_name','vehicle_name','pickup_date','contract_url','company_name'],
            'subject'     => 'Please Sign Your Rental Contract — {{company_name}}',
            'body'        => notif_wrap($cw_content),
        ],
        'account_created' => [
            'name'        => 'Account Created',
            'description' => 'Sent with login credentials when a customer account is created.',
            'icon_path'   => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'badge_bg'    => '#fef3c7', 'badge_color' => '#92400e',
            'variables'   => ['customer_name','customer_email','password','booking_ref','company_name','login_url'],
            'subject'     => 'Your Account Details — {{company_name}}',
            'body'        => notif_wrap($ac_content),
        ],
        'booking_cancellation' => [
            'name'        => 'Booking Cancellation',
            'description' => 'Sent when a booking is cancelled.',
            'icon_path'   => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'badge_bg'    => '#fee2e2', 'badge_color' => '#991b1b',
            'variables'   => ['customer_name','booking_ref','vehicle_name','pickup_date','return_date','company_name','company_email'],
            'subject'     => 'Booking Cancellation — #{{booking_ref}} | {{company_name}}',
            'body'        => notif_wrap($cancel_content),
        ],
        'booking_reminder' => [
            'name'        => 'Pickup Reminder',
            'description' => 'Sent 24 hours before the pickup date.',
            'icon_path'   => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'badge_bg'    => '#f3e8ff', 'badge_color' => '#6b21a8',
            'variables'   => ['customer_name','vehicle_name','pickup_date','return_date','company_name','login_url'],
            'subject'     => 'Reminder: Your Rental Pickup is Tomorrow — {{company_name}}',
            'body'        => notif_wrap($remind_content),
        ],
    ];
}

$default_templates = getDefaultEmailTemplates();

// ── AJAX handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = trim($_POST['action'] ?? '');

    if ($action === 'save') {
        $key     = trim($_POST['template_key'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        $enabled = ($_POST['enabled'] ?? '0') === '1' ? 1 : 0;

        if (!array_key_exists($key, $default_templates) || !$subject || !$body) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO email_templates (tenant_id, template_key, subject, body, enabled)
                               VALUES (?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE subject=VALUES(subject), body=VALUES(body),
                               enabled=VALUES(enabled), updated_at=CURRENT_TIMESTAMP");
        $stmt->execute([$tenant_id, $key, $subject, $body, $enabled]);
        echo json_encode(['success' => true, 'message' => 'Template saved successfully.']);
        exit;
    }

    if ($action === 'send_test') {
        require_once __DIR__ . '/../includes/email.php';
        $key        = trim($_POST['template_key'] ?? '');
        $test_email = trim($_POST['test_email'] ?? '');
        $subject    = trim($_POST['subject'] ?? '');
        $body       = trim($_POST['body'] ?? '');

        if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }
        if (!$subject || !$body) {
            echo json_encode(['success' => false, 'message' => 'Subject and body cannot be empty.']);
            exit;
        }

        $sample = [
            '{{customer_name}}'  => 'John Smith',
            '{{customer_email}}' => $test_email,
            '{{booking_ref}}'    => '00123',
            '{{vehicle_name}}'   => 'BMW 5 Series',
            '{{pickup_date}}'    => 'Mon, Jul 14, 2025',
            '{{return_date}}'    => 'Mon, Jul 21, 2025',
            '{{total_price}}'    => '350.00',
            '{{deposit}}'        => '500.00',
            '{{currency}}'       => '£',
            '{{password}}'       => 'TempPass123!',
            '{{company_name}}'   => htmlspecialchars($tenant['name'] ?? 'Your Company'),
            '{{company_email}}'  => 'info@yourcompany.com',
            '{{login_url}}'      => SITE_URL . '/auth/login.php',
            '{{contract_url}}'   => SITE_URL . '/sign/sample',
        ];

        $rendered_subject = str_replace(array_keys($sample), array_values($sample), $subject);
        $rendered_body    = str_replace(array_keys($sample), array_values($sample), $body);

        $ok = sendEmail($test_email, '[TEST] ' . $rendered_subject, $rendered_body, $tenant['name'] ?? SITE_NAME);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Test email sent to ' . htmlspecialchars($test_email)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send. Check SMTP configuration.']);
        }
        exit;
    }

    if ($action === 'reset') {
        $key = trim($_POST['template_key'] ?? '');
        if (!array_key_exists($key, $default_templates)) {
            echo json_encode(['success' => false, 'message' => 'Invalid template.']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM email_templates WHERE tenant_id = ? AND template_key = ?");
        $stmt->execute([$tenant_id, $key]);
        echo json_encode([
            'success' => true,
            'subject' => $default_templates[$key]['subject'],
            'body'    => $default_templates[$key]['body'],
        ]);
        exit;
    }

    if ($action === 'toggle_enabled') {
        $key     = trim($_POST['template_key'] ?? '');
        $enabled = ($_POST['enabled'] ?? '0') === '1' ? 1 : 0;
        if (!array_key_exists($key, $default_templates)) {
            echo json_encode(['success' => false]);
            exit;
        }
        $def = $default_templates[$key];
        $stmt = $pdo->prepare("INSERT INTO email_templates (tenant_id, template_key, subject, body, enabled)
                               VALUES (?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE enabled=VALUES(enabled)");
        $stmt->execute([$tenant_id, $key, $def['subject'], $def['body'], $enabled]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ── Load saved templates ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM email_templates WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$saved = [];
foreach ($stmt->fetchAll() as $row) {
    $saved[$row['template_key']] = $row;
}

$templates = [];
foreach ($default_templates as $key => $def) {
    $templates[$key]              = $def;
    $templates[$key]['subject']   = $saved[$key]['subject']    ?? $def['subject'];
    $templates[$key]['body']      = $saved[$key]['body']       ?? $def['body'];
    $templates[$key]['enabled']   = isset($saved[$key])        ? (int)$saved[$key]['enabled'] : 1;
    $templates[$key]['is_custom'] = isset($saved[$key]);
    $templates[$key]['updated_at']= $saved[$key]['updated_at'] ?? null;
}

$active_tab = $_GET['tab'] ?? 'booking_confirmation';
if (!array_key_exists($active_tab, $templates)) $active_tab = 'booking_confirmation';
$active = $templates[$active_tab];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — <?= htmlspecialchars($tenant['name'] ?? 'Dashboard') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-item { transition: all .15s; }
        .sidebar-item:hover { background: #f3f4f6; }
        .sidebar-item.active { background: #eff6ff; color: #3b82f6; }
        .sidebar-item.active svg { color: #3b82f6; }
        #preview-iframe { border: none; width: 100%; height: 100%; }
        .var-chip { cursor: pointer; user-select: none; }
        .var-chip:hover { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Topbar -->
<header class="fixed top-0 left-0 right-0 h-14 bg-white border-b border-gray-200 z-50 flex items-center px-4 gap-4">
    <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg hover:bg-gray-100">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <a href="/dashboard/" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Dashboard
    </a>
    <span class="text-gray-300">/</span>
    <span class="font-semibold text-sm text-gray-900">Notifications</span>
</header>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black/50 z-30 hidden"></div>

<!-- Layout -->
<div class="flex pt-14 h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static top-14 bottom-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 lg:flex flex-shrink-0">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Page header -->
            <div class="flex items-start justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900">Notifications &amp; Emails</h1>
                    <p class="text-sm text-gray-500 mt-1">Customise the emails your customers receive at every stage of their booking.</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-500 bg-white border border-gray-200 rounded-xl px-4 py-2 shadow-sm">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= count(array_filter($templates, fn($t) => $t['enabled'])) ?> / <?= count($templates) ?> templates enabled
                </div>
            </div>

            <!-- Grid: Tab list + Editor -->
            <div class="grid lg:grid-cols-[280px_1fr] gap-6 items-start">

                <!-- ── Template list ──────────────────────────────────────── -->
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Email Templates</p>
                    </div>
                    <?php foreach ($templates as $key => $tpl): ?>
                    <a href="?tab=<?= $key ?>"
                       class="flex items-start gap-3 px-5 py-4 border-b border-gray-50 hover:bg-gray-50 transition group <?= $key === $active_tab ? 'bg-blue-50 border-l-4 border-l-blue-500' : '' ?>">
                        <div class="mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 <?= $key === $active_tab ? 'bg-blue-100' : 'bg-gray-100 group-hover:bg-gray-200' ?>">
                            <svg class="w-4 h-4 <?= $key === $active_tab ? 'text-blue-600' : 'text-gray-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($tpl['icon_path']) ?>"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold <?= $key === $active_tab ? 'text-blue-700' : 'text-gray-800' ?> truncate"><?= htmlspecialchars($tpl['name']) ?></p>
                            <p class="text-xs text-gray-400 truncate mt-0.5"><?= htmlspecialchars($tpl['description']) ?></p>
                        </div>
                        <?php if (!$tpl['enabled']): ?>
                        <span class="flex-shrink-0 text-xs bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full font-medium">Off</span>
                        <?php elseif ($tpl['is_custom']): ?>
                        <span class="flex-shrink-0 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Custom</span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- ── Editor + Preview ───────────────────────────────────── -->
                <div class="space-y-5">

                    <!-- Template header -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                                          style="background:<?= $active['badge_bg'] ?>;color:<?= $active['badge_color'] ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($active['icon_path']) ?>"/></svg>
                                        <?= htmlspecialchars($active['name']) ?>
                                    </span>
                                    <?php if ($active['is_custom']): ?>
                                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full font-semibold">Custom</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($active['description']) ?></p>
                                <?php if ($active['updated_at']): ?>
                                <p class="text-xs text-gray-400 mt-1">Last saved: <?= date('D j M Y, g:i a', strtotime($active['updated_at'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <!-- Enable toggle -->
                            <label class="flex items-center gap-2.5 cursor-pointer flex-shrink-0">
                                <span class="text-sm font-medium text-gray-700">Enabled</span>
                                <div class="relative">
                                    <input type="checkbox" id="enabled-toggle" class="sr-only peer" <?= $active['enabled'] ? 'checked' : '' ?>
                                           data-key="<?= htmlspecialchars($active_tab) ?>">
                                    <div class="w-10 h-5 bg-gray-200 peer-checked:bg-blue-500 rounded-full transition-colors"></div>
                                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Subject line -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Subject Line</label>
                        <input type="text" id="email-subject"
                               value="<?= htmlspecialchars($active['subject']) ?>"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                               placeholder="Enter email subject…">
                    </div>

                    <!-- Variables -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <p class="text-sm font-semibold text-gray-800 mb-3">Available Variables
                            <span class="text-xs font-normal text-gray-400 ml-1">— click to insert into body</span>
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($active['variables'] as $var): ?>
                            <button onclick="insertVariable('{{<?= $var ?>}}')"
                                    class="var-chip text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg font-mono transition">
                                &#123;&#123;<?= htmlspecialchars($var) ?>&#125;&#125;
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Editor / Preview split -->
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                        <!-- Tab bar -->
                        <div class="flex items-center gap-0 border-b border-gray-200 px-6">
                            <button onclick="switchEditorTab('editor')" id="editor-tab-btn"
                                    class="editor-tab-btn text-sm font-semibold px-4 py-3.5 border-b-2 border-blue-500 text-blue-600 transition">
                                HTML Editor
                            </button>
                            <button onclick="switchEditorTab('preview')" id="preview-tab-btn"
                                    class="editor-tab-btn text-sm font-semibold px-4 py-3.5 border-b-2 border-transparent text-gray-500 hover:text-gray-800 transition">
                                Preview
                            </button>
                            <div class="ml-auto flex items-center gap-2 py-2">
                                <span class="text-xs text-gray-400" id="char-count">0 chars</span>
                            </div>
                        </div>

                        <!-- Editor pane -->
                        <div id="editor-pane" class="p-4">
                            <textarea id="email-body" rows="22"
                                      class="w-full border border-gray-200 rounded-xl px-4 py-3 text-xs font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 resize-y"
                                      placeholder="Paste or write your HTML email body here…"
                                      oninput="updatePreview(); updateCharCount()"><?= htmlspecialchars($active['body']) ?></textarea>
                        </div>

                        <!-- Preview pane -->
                        <div id="preview-pane" class="hidden" style="height: 540px;">
                            <iframe id="preview-iframe" title="Email Preview"></iframe>
                        </div>
                    </div>

                    <!-- Action bar -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <?php if ($active['is_custom']): ?>
                            <button onclick="resetTemplate()"
                                    class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reset to Default
                            </button>
                            <?php endif; ?>
                            <button onclick="openTestModal()"
                                    class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Send Test Email
                            </button>
                        </div>
                        <button onclick="saveTemplate()"
                                class="flex items-center justify-center gap-2 px-6 py-2.5 bg-gray-900 hover:bg-gray-700 text-white rounded-xl text-sm font-semibold transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Template
                        </button>
                    </div>
                </div><!-- /editor column -->
            </div><!-- /grid -->
        </div>
    </main>
</div>

<!-- ── Send Test Email Modal ──────────────────────────────────────────────── -->
<div id="test-modal" class="fixed inset-0 z-[999] flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeTestModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-gray-900 text-lg">Send Test Email</h3>
                <p class="text-sm text-gray-500 mt-0.5">We'll send a preview with sample data.</p>
            </div>
            <button onclick="closeTestModal()" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Recipient Email</label>
        <input type="email" id="test-email-input" placeholder="you@example.com"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 mb-4">
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 mb-5 text-xs text-blue-700">
            <strong>Sample data</strong> will be used for all variables (e.g. customer name = "John Smith", booking ref = "00123").
        </div>
        <div class="flex gap-3">
            <button onclick="closeTestModal()" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</button>
            <button onclick="sendTestEmail()" id="send-test-btn"
                    class="flex-1 py-2.5 bg-gray-900 hover:bg-gray-700 text-white rounded-xl text-sm font-bold transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Test
            </button>
        </div>
    </div>
</div>

<!-- ── Notification toast ─────────────────────────────────────────────────── -->
<div id="toast" class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 bg-white border border-gray-200 rounded-2xl px-5 py-3.5 shadow-xl translate-y-20 opacity-0 transition-all duration-300 max-w-sm">
    <div id="toast-icon" class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"></div>
    <p id="toast-msg" class="text-sm font-medium text-gray-800"></p>
</div>

<script>
const ACTIVE_KEY = <?= json_encode($active_tab) ?>;

// ── Preview update ────────────────────────────────────────────────────────────
function updatePreview() {
    const body = document.getElementById('email-body').value;
    const iframe = document.getElementById('preview-iframe');
    const doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open(); doc.write(body); doc.close();
}

function updateCharCount() {
    const len = document.getElementById('email-body').value.length;
    document.getElementById('char-count').textContent = len.toLocaleString() + ' chars';
}

// ── Editor tabs ───────────────────────────────────────────────────────────────
function switchEditorTab(tab) {
    const isEditor = (tab === 'editor');
    document.getElementById('editor-pane').classList.toggle('hidden', !isEditor);
    document.getElementById('preview-pane').classList.toggle('hidden', isEditor);
    if (!isEditor) updatePreview();

    document.querySelectorAll('.editor-tab-btn').forEach(btn => {
        btn.classList.remove('border-blue-500', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    const active = isEditor ? document.getElementById('editor-tab-btn') : document.getElementById('preview-tab-btn');
    active.classList.add('border-blue-500', 'text-blue-600');
    active.classList.remove('border-transparent', 'text-gray-500');
}

// ── Variable insertion ────────────────────────────────────────────────────────
function insertVariable(varText) {
    const ta = document.getElementById('email-body');
    const start = ta.selectionStart, end = ta.selectionEnd;
    ta.value = ta.value.slice(0, start) + varText + ta.value.slice(end);
    ta.selectionStart = ta.selectionEnd = start + varText.length;
    ta.focus();
    updateCharCount();
}

// ── Save template ─────────────────────────────────────────────────────────────
async function saveTemplate() {
    const subject = document.getElementById('email-subject').value.trim();
    const body    = document.getElementById('email-body').value.trim();
    const enabled = document.getElementById('enabled-toggle').checked ? '1' : '0';

    if (!subject || !body) { showToast('Subject and body are required.', 'error'); return; }

    const btn = document.querySelector('button[onclick="saveTemplate()"]');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Saving…';

    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('template_key', ACTIVE_KEY);
    fd.append('subject', subject);
    fd.append('body', body);
    fd.append('enabled', enabled);

    const res = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    btn.disabled = false;
    btn.innerHTML = orig;

    showToast(data.message, data.success ? 'success' : 'error');
    if (data.success) {
        // Show "Custom" badge without reload
        document.querySelectorAll('[data-key="' + ACTIVE_KEY + '"]').forEach(el => el.setAttribute('data-custom', '1'));
    }
}

// ── Reset to default ──────────────────────────────────────────────────────────
async function resetTemplate() {
    if (!confirm('Reset this template to the default? Your custom changes will be lost.')) return;
    const fd = new FormData();
    fd.append('action', 'reset');
    fd.append('template_key', ACTIVE_KEY);
    const res = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        document.getElementById('email-subject').value = data.subject;
        document.getElementById('email-body').value = data.body;
        updateCharCount();
        showToast('Template reset to default.', 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(data.message || 'Reset failed.', 'error');
    }
}

// ── Enable toggle ─────────────────────────────────────────────────────────────
document.getElementById('enabled-toggle').addEventListener('change', async function () {
    const fd = new FormData();
    fd.append('action', 'toggle_enabled');
    fd.append('template_key', this.dataset.key);
    fd.append('enabled', this.checked ? '1' : '0');
    const res = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.success ? (this.checked ? 'Template enabled.' : 'Template disabled.') : 'Error toggling template.', data.success ? 'success' : 'error');
});

// ── Test modal ────────────────────────────────────────────────────────────────
function openTestModal()  { document.getElementById('test-modal').classList.remove('hidden'); document.getElementById('test-email-input').focus(); }
function closeTestModal() { document.getElementById('test-modal').classList.add('hidden'); }

async function sendTestEmail() {
    const email   = document.getElementById('test-email-input').value.trim();
    const subject = document.getElementById('email-subject').value.trim();
    const body    = document.getElementById('email-body').value.trim();
    if (!email) { showToast('Please enter a recipient email.', 'error'); return; }

    const btn = document.getElementById('send-test-btn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Sending…';

    const fd = new FormData();
    fd.append('action', 'send_test');
    fd.append('template_key', ACTIVE_KEY);
    fd.append('test_email', email);
    fd.append('subject', subject);
    fd.append('body', body);

    const res = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Send Test';

    closeTestModal();
    showToast(data.message, data.success ? 'success' : 'error');
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    const icon  = document.getElementById('toast-icon');
    document.getElementById('toast-msg').textContent = msg;

    if (type === 'success') {
        icon.className = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-green-100';
        icon.innerHTML = '<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
    } else {
        icon.className = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-red-100';
        icon.innerHTML = '<svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>';
    }

    toast.classList.remove('translate-y-20', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3500);
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    updateCharCount();

    // Mobile sidebar toggle
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
