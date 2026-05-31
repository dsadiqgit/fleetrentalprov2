<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

// Redirect super admin to their dashboard
if ($_SESSION['role'] === 'super_admin') {
    redirect('/admin/super-admin.php');
}

// Check if user has a tenant
if (!$_SESSION['tenant_id']) {
    die('Error: No tenant associated with this account.');
}

$pdo = getDB();

// Get tenant information
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

if (!$tenant) {
    die('Error: Tenant not found.');
}

// Calculate trial days remaining
$trial_days_remaining = 0;
$trial_percentage = 0;

// Use trial_ends_at if set, otherwise calculate 30 days from created_at
$trial_start = new DateTime($tenant['created_at']);
$trial_end = $tenant['trial_ends_at'] ? new DateTime($tenant['trial_ends_at']) : (clone $trial_start)->modify('+30 days');
$now = new DateTime();

if ($now < $trial_end) {
    $interval = $now->diff($trial_end);
    $trial_days_remaining = $interval->days;
    // Cap at 30 days
    if ($trial_days_remaining > 30)
        $trial_days_remaining = 30;
    $trial_percentage = ($trial_days_remaining / 30) * 100;
}

// Get tenant settings
$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$settings = $stmt->fetch();

// Update schema for tenant_settings
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN logo_url VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_publishable_key VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_secret_key VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_test_mode TINYINT(1) DEFAULT 0");
}
catch (PDOException $e) { /* Column might exist */
}
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_live_publishable_key VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_live_secret_key VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_test_publishable_key VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_test_secret_key VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}

// Update schema for users to support team member signature
try {
    @$pdo->exec("ALTER TABLE users ADD COLUMN signature_data LONGTEXT NULL");
}
catch (PDOException $e) { /* Column might exist */
}

$error = '';
$success = '';
$active_tab = $_GET['tab'] ?? 'general';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_general':
                $currency = sanitize($_POST['currency'] ?? 'GBP');
                $distance_unit = sanitize($_POST['distance_unit'] ?? 'Miles');
                $week_start = sanitize($_POST['week_start'] ?? 'Monday');
                $require_license_verification = isset($_POST['require_license_verification']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare("UPDATE tenant_settings SET currency = ?, distance_unit = ?, week_start_day = ?, require_license_verification = ? WHERE tenant_id = ?");
                    $stmt->execute([$currency, $distance_unit, $week_start, $require_license_verification, $_SESSION['tenant_id']]);
                    $success = 'Settings updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update settings.';
                }
                break;
                
            case 'update_booking':
                $min_notice = intval($_POST['min_notice'] ?? 0);
                $notice_unit = sanitize($_POST['notice_unit'] ?? 'Hours');
                $buffer_time = intval($_POST['buffer_time'] ?? 0);
                $max_advance = intval($_POST['max_advance'] ?? 30);
                
                try {
                    $stmt = $pdo->prepare("UPDATE tenant_settings SET min_booking_notice = ?, booking_notice_unit = ?, buffer_time_hours = ?, max_booking_advance_days = ? WHERE tenant_id = ?");
                    $stmt->execute([$min_notice, $notice_unit, $buffer_time, $max_advance, $_SESSION['tenant_id']]);
                    $success = 'Booking settings updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update booking settings.';
                }
                break;
                
            case 'update_main_info':
                $company_name = sanitize($_POST['company_name'] ?? '');
                $company_address = sanitize($_POST['company_address'] ?? '');
                $phone = sanitize($_POST['phone'] ?? '');
                $pickup_location = sanitize($_POST['pickup_location'] ?? '');
                $dropoff_location = sanitize($_POST['dropoff_location'] ?? '');

                try {
                    // Handle logo upload
                    $logo_path = $tenant['logo'] ?? null;
                    $logo_uploaded = false;

                    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = __DIR__ . '/../uploads/logos/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $file_ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                        if (in_array($file_ext, $allowed_exts)) {
                            $new_filename = 'logo_' . $_SESSION['tenant_id'] . '_' . time() . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;

                            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $upload_path)) {
                                $logo_path = '/uploads/logos/' . $new_filename;
                                $logo_uploaded = true;

                                // Delete old logo if exists
                                if ($tenant['logo'] && file_exists(__DIR__ . '/..' . $tenant['logo'])) {
                                    unlink(__DIR__ . '/..' . $tenant['logo']);
                                }
                            }
                            else {
                                $error = 'Failed to move uploaded file. Check directory permissions.';
                            }
                        }
                        else {
                            $error = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
                        }
                    }
                    elseif (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
                            UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                        ];
                        $error = 'Upload error: ' . ($upload_errors[$_FILES['company_logo']['error']] ?? 'Unknown error');
                    }

                    if (!$error) {
                        // Validate and format location lists
                        $pickup_location = implode('; ', array_filter(array_map('trim', preg_split('/[;\r\n]+/', $pickup_location))));
                        $dropoff_location = implode('; ', array_filter(array_map('trim', preg_split('/[;\r\n]+/', $dropoff_location))));

                        $stmt = $pdo->prepare("UPDATE tenants SET name = ?, logo = ? WHERE id = ?");
                        $stmt->execute([$company_name, $logo_path, $_SESSION['tenant_id']]);

                        $stmt = $pdo->prepare("UPDATE tenant_settings SET company_address = ?, company_phone = ?, pickup_location = ?, dropoff_location = ? WHERE tenant_id = ?");
                        $stmt->execute([$company_address, $phone, $pickup_location, $dropoff_location, $_SESSION['tenant_id']]);

                        $success = 'Company information updated successfully!';
                        if ($logo_uploaded) {
                            $success .= ' Logo uploaded successfully.';
                        }
                        // Refresh tenant data
                        $stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
                        $stmt->execute([$_SESSION['tenant_id']]);
                        $tenant = $stmt->fetch();
                    }
                }
                catch (Exception $e) {
                    $error = 'Failed to update information: ' . $e->getMessage();
                }
                break;

            case 'update_stripe_keys':
                $test_mode = isset($_POST['stripe_test_mode']) ? 1 : 0;
                $test_pub = sanitize($_POST['stripe_test_publishable_key'] ?? '');
                $test_sec = sanitize($_POST['stripe_test_secret_key'] ?? '');
                $live_pub = sanitize($_POST['stripe_live_publishable_key'] ?? '');
                $live_sec = sanitize($_POST['stripe_live_secret_key'] ?? '');

                // Set the active keys based on current mode
                $active_pub = $test_mode ? $test_pub : $live_pub;
                $active_sec = $test_mode ? $test_sec : $live_sec;

                try {
                    $stmt = $pdo->prepare("UPDATE tenant_settings SET stripe_publishable_key = ?, stripe_secret_key = ?, stripe_test_mode = ?, stripe_live_publishable_key = ?, stripe_live_secret_key = ?, stripe_test_publishable_key = ?, stripe_test_secret_key = ? WHERE tenant_id = ?");
                    $stmt->execute([$active_pub, $active_sec, $test_mode, $live_pub, $live_sec, $test_pub, $test_sec, $_SESSION['tenant_id']]);
                    $success = 'Stripe configuration updated successfully!';
                }
                catch (Exception $e) {
                    $error = 'Failed to update Stripe keys.';
                }
                break;

            case 'remove_stripe':
                try {
                    $stmt = $pdo->prepare("UPDATE tenant_settings SET stripe_publishable_key = '', stripe_secret_key = '', stripe_live_publishable_key = '', stripe_live_secret_key = '', stripe_test_publishable_key = '', stripe_test_secret_key = '', stripe_test_mode = 0 WHERE tenant_id = ?");
                    $stmt->execute([$_SESSION['tenant_id']]);
                    $success = 'Stripe integration removed successfully.';
                }
                catch (Exception $e) {
                    $error = 'Failed to remove Stripe integration.';
                }
                break;

            case 'update_email':
                $new_email = sanitize($_POST['new_email'] ?? '');
                if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE tenant_settings SET company_email = ? WHERE tenant_id = ?");
                        $stmt->execute([$new_email, $_SESSION['tenant_id']]);
                        $success = 'Email updated successfully!';
                    }
                    catch (Exception $e) {
                        $error = 'Failed to update email.';
                    }
                }
                else {
                    $error = 'Invalid email address.';
                }
                break;

            case 'save_witness_signature':
                $sig_data = $_POST['signature_data'] ?? '';
                if ($sig_data) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET signature_data = ? WHERE id = ?");
                        $stmt->execute([$sig_data, $_SESSION['user_id']]);
                        $success = 'Witness signature saved successfully!';
                    }
                    catch (Exception $e) {
                        $error = 'Failed to save witness signature.';
                    }
                }
                else {
                    $error = 'No signature data provided.';
                }
                break;

            case 'invite_team_member':
                $email = sanitize($_POST['invite_email'] ?? '');
                $role = sanitize($_POST['invite_role'] ?? 'staff');

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Check team size limit (Users + Pending Invites)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE tenant_id = ?");
                    $stmt->execute([$_SESSION['tenant_id']]);
                    $active_count = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM team_invitations WHERE tenant_id = ? AND status = 'pending'");
                    $stmt->execute([$_SESSION['tenant_id']]);
                    $pending_count = $stmt->fetchColumn();

                    if (($active_count + $pending_count) >= 3) {
                        $error = 'Team limit reached. You can only have up to 3 team members (including pending invitations).';
                        break;
                    }

                    // Check if user already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'A user with this email already exists.';
                        break;
                    }

                    // Generate token
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

                    try {
                        $stmt = $pdo->prepare("INSERT INTO team_invitations (tenant_id, email, role, token, invited_by, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['tenant_id'], $email, $role, $token, $_SESSION['user_id'], $expires_at]);

                        // Send email
                        require_once __DIR__ . '/../includes/email.php';
                        if (sendTeamInvitationEmail($email, $tenant, $_SESSION['full_name'] ?? 'Admin', $token)) {
                            $success = 'Invitation sent successfully to ' . htmlspecialchars($email);
                        }
                        else {
                            $success = 'Invitation saved, but email sending failed. You can share the link manually if needed.';
                        }
                    }
                    catch (Exception $e) {
                        $error = 'Failed to create invitation: ' . $e->getMessage();
                    }
                }
                else {
                    $error = 'Invalid email address.';
                }
                break;

            case 'cancel_invitation':
                $id = (int)($_POST['invitation_id'] ?? 0);

                try {
                    $stmt = $pdo->prepare("DELETE FROM team_invitations WHERE id = ? AND tenant_id = ? AND status = 'pending'");
                    $stmt->execute([$id, $_SESSION['tenant_id']]);
                    if ($stmt->rowCount() > 0) {
                        $success = 'Invitation cancelled successfully.';
                    }
                }
                catch (Exception $e) {
                    $error = 'Failed to cancel invitation: ' . $e->getMessage();
                }
                break;

            case 'update_team_member':
                $member_id = (int)($_POST['member_id'] ?? 0);
                $new_role = sanitize($_POST['edit_role'] ?? 'staff');
                $new_password = $_POST['edit_password'] ?? '';

                try {
                    // Start transaction
                    $pdo->beginTransaction();

                    // Update role
                    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$new_role, $member_id, $_SESSION['tenant_id']]);

                    // Update password if provided
                    if (!empty($new_password)) {
                        if (strlen($new_password) < 8) {
                            throw new Exception('Password must be at least 8 characters long');
                        }
                        $hashed_password = hashPassword($new_password);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND tenant_id = ?");
                        $stmt->execute([$hashed_password, $member_id, $_SESSION['tenant_id']]);
                    }

                    $pdo->commit();
                    $success = 'Team member updated successfully.';
                }
                catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to update member: ' . $e->getMessage();
                }
                break;

            case 'delete_team_member':
                $member_id = (int)($_POST['member_id'] ?? 0);

                // Prevent self-deletion
                if ($member_id == $_SESSION['user_id']) {
                    $error = 'You cannot delete your own account.';
                    break;
                }

                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND tenant_id = ? AND role != 'super_admin'");
                    $stmt->execute([$member_id, $_SESSION['tenant_id']]);
                    if ($stmt->rowCount() > 0) {
                        $success = 'Team member removed successfully.';
                    }
                }
                catch (Exception $e) {
                    $error = 'Failed to delete member: ' . $e->getMessage();
                }
                break;

            case 'delete_account':
                try {
                    $pdo->beginTransaction();
                    $tenant_id = $_SESSION['tenant_id'];

                    // Delete related data (manually for safety)
                    $pdo->prepare("DELETE FROM bookings WHERE tenant_id = ?")->execute([$tenant_id]);
                    $pdo->prepare("DELETE FROM vehicles WHERE tenant_id = ?")->execute([$tenant_id]);
                    $pdo->prepare("DELETE FROM contract_templates WHERE tenant_id = ?")->execute([$tenant_id]);
                    $pdo->prepare("DELETE FROM tenant_settings WHERE tenant_id = ?")->execute([$tenant_id]);
                    $pdo->prepare("DELETE FROM users WHERE tenant_id = ?")->execute([$tenant_id]);
                    $pdo->prepare("DELETE FROM tenants WHERE id = ?")->execute([$tenant_id]);

                    $pdo->commit();

                    // Clear session and redirect to login
                    session_unset();
                    session_destroy();

                    // Redirect to login with a message
                    echo "<script>window.location.href = '/auth/login.php?deleted=1';</script>";
                    exit;
                }
                catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to delete account: ' . $e->getMessage();
                }
                break;
            case 'update_custom_domain':
                $custom_domain = strtolower(trim(sanitize($_POST['custom_domain'] ?? '')));

                if (empty($custom_domain)) {
                    // Remove domain
                    try {
                        $stmt = $pdo->prepare("UPDATE tenants SET custom_domain = NULL, custom_domain_status = 'pending' WHERE id = ?");
                        $stmt->execute([$_SESSION['tenant_id']]);
                        $success = 'Custom domain removed successfully.';
                    }
                    catch (Exception $e) {
                        $error = 'Failed to remove custom domain.';
                    }
                    break;
                }

                // Basic validation
                if (!preg_match('/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}$/i', $custom_domain)) {
                    $error = 'Invalid domain format.';
                    break;
                }

                try {
                    // Check if domain is taken
                    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE custom_domain = ? AND id != ?");
                    $stmt->execute([$custom_domain, $_SESSION['tenant_id']]);
                    if ($stmt->fetch()) {
                        $error = 'This domain is already in use by another tenant.';
                        break;
                    }

                    $stmt = $pdo->prepare("UPDATE tenants SET custom_domain = ?, custom_domain_status = 'pending' WHERE id = ?");
                    $stmt->execute([$custom_domain, $_SESSION['tenant_id']]);
                    $success = 'Custom domain saved! Please configure your DNS settings.';
                }
                catch (Exception $e) {
                    $error = 'Failed to update custom domain: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get team members
$stmt = $pdo->prepare("SELECT * FROM users WHERE tenant_id = ? AND role IN ('admin', 'staff') ORDER BY created_at DESC");
$stmt->execute([$_SESSION['tenant_id']]);
$team_members = $stmt->fetchAll();

// Get pending invitations
$stmt = $pdo->prepare("SELECT * FROM team_invitations WHERE tenant_id = ? AND status = 'pending' AND (expires_at > NOW() OR expires_at IS NULL) ORDER BY created_at DESC");
$stmt->execute([$_SESSION['tenant_id']]);
$pending_invites = $stmt->fetchAll();

// Refresh settings after update
$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$settings = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?= htmlspecialchars($tenant['name'])?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <script src="/app/custom-select.js" defer></script>
    <style>
        .sidebar-item {
            transition: all 0.2s;
        }
        .sidebar-item:hover {
            background-color: #f3f4f6;
        }
        .sidebar-item.active {
            background-color: #eff6ff;
            color: #3b82f5;
        }
        .sidebar-item.active svg {
            color: #3b82f5;
        }
        @keyframes scale-in-center {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .scale-in-center {
            animation: scale-in-center 0.2s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
        }
        .tab-button {
            transition: all 0.2s;
        }
        .tab-button.active {
            color: #111827;
            border-bottom: 2px solid #111827;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-3 z-40 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900">Dashboard</h1>
        </div>
        <div class="flex items-center gap-3">
            <button class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
            <button class="p-1 hover:bg-gray-100 rounded-lg transition-colors relative">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </button>
            <button class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-semibold">
                FL
            </button>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-30 hidden transition-all duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static top-14 lg:top-0 bottom-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 lg:flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto pt-14 lg:pt-0">
        <!-- Desktop Top Bar -->
        <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="text-sm text-gray-500 mb-1">
                        <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Settings</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage your business and account settings</p>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl">

                <!-- Tabs -->
                <div class="relative shrink-0 mb-6 sm:mb-8">
                    <div class="flex h-9 w-fit max-w-full items-center rounded-full border border-[rgba(120,120,128,0.05)] bg-[rgba(120,120,128,0.05)] p-1 select-none overflow-x-auto no-scrollbar">
                        <div class="flex items-center">
                            <a href="?tab=general" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'general' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black'?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M21.4707 19V5C21.4707 3 20.4707 2 18.4707 2H14.4707C12.4707 2 11.4707 3 11.4707 5V19C11.4707 21 12.4707 22 14.4707 22H18.4707C20.4707 22 21.4707 21 21.4707 19Z"></path>
                                    <path d="M11.4707 6H16.4707"></path>
                                    <path d="M11.4707 18H15.4707"></path>
                                    <path d="M11.4707 13.9502L16.4707 14.0002"></path>
                                    <path d="M11.4707 10H14.4707"></path>
                                    <path d="M5.4893 2C3.8593 2 2.5293 3.33 2.5293 4.95V17.91C2.5293 18.36 2.7193 19.04 2.9493 19.43L3.7693 20.79C4.7093 22.36 6.2593 22.36 7.1993 20.79L8.0193 19.43C8.2493 19.04 8.4393 18.36 8.4393 17.91V4.95C8.4393 3.33 7.1093 2 5.4893 2Z"></path>
                                    <path d="M8.4393 7H2.5293"></path>
                                </svg>
                                General
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'general' || $active_tab === 'booking' ? 'opacity-0' : 'bg-[#e6e6e6]'?>"></div>
                            <a href="?tab=booking" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'booking' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black'?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Booking
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'booking' || $active_tab === 'main' ? 'opacity-0' : 'bg-[#e6e6e6]'?>"></div>
                            <a href="?tab=main" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'main' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black'?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Company
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'main' || $active_tab === 'team' ? 'opacity-0' : 'bg-[#e6e6e6]'?>"></div>
                            <a href="?tab=team" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'team' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black'?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Team
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'team' || $active_tab === 'payments' ? 'opacity-0' : 'bg-[#e6e6e6]'?>"></div>
                            <a href="?tab=payments" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'payments' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black'?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                    <line x1="2" x2="22" y1="10" y2="10"></line>
                                </svg>
                                Payments
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'payments' || $active_tab === 'domain' ? 'opacity-0' : 'bg-[#e6e6e6]'?>"></div>
                            <a href="?tab=domain" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'domain' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black'?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                                Domain
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'domain' || $active_tab === 'danger' ? 'opacity-0' : 'bg-[#e6e6e6]'?>"></div>
                            <a href="?tab=danger" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'danger' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black'?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Danger
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($success)?>
                </div>
                <?php
endif; ?>

                <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($error)?>
                </div>
                <?php
endif; ?>

                <!-- Tab Content -->
                <?php if ($active_tab === 'general'): ?>
                <!-- General Settings Tab -->
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="update_general">
                    
                    <!-- Driver License Verification -->
                    <div>
                        <div class="flex items-start space-x-2 mb-3">
                            <h3 class="text-base font-semibold text-gray-900">Driver licence verification feature</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4">
                            <div class="flex items-center space-x-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_license_verification" value="1" class="sr-only peer" <?= ($settings['require_license_verification'] ?? 0) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                </label>
                                <span class="text-sm text-gray-700">When turned on your customers will need to verify their driver licence before they can make a booking</span>
                            </div>
                        </div>
                    </div>

                    <!-- Currency -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Currency</h3>
                        <p class="text-sm text-gray-600 mb-4">You can change it until your first booking/payment.</p>
                        <div class="flex items-center space-x-3">
                            <select name="currency" class="custom-select">
                                <option value="GBP" <?= ($settings['currency'] ?? 'GBP') === 'GBP' ? 'selected' : '' ?>>GBP</option>
                                <option value="USD" <?= ($settings['currency'] ?? 'GBP') === 'USD' ? 'selected' : '' ?>>USD</option>
                                <option value="EUR" <?= ($settings['currency'] ?? 'GBP') === 'EUR' ? 'selected' : '' ?>>EUR</option>
                            </select>
                            <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Save currency
                            </button>
                        </div>
                    </div>

                    <!-- Distance Unit -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Distance unit</h3>
                        <p class="text-sm text-gray-600 mb-4">You can change this setting until your first booking/payment.</p>
                        <div class="flex items-center space-x-3">
                            <div class="toggle-select flex border border-gray-300 rounded-lg overflow-hidden bg-white">
                                <button type="button" class="px-4 py-2 text-sm font-medium transition-colors <?= ($settings['distance_unit'] ?? 'Miles') === 'Kilometres' ? 'bg-gray-100 text-gray-900 border-r border-gray-300' : 'text-gray-500 hover:text-gray-700' ?>" onclick="setDistanceUnit('Kilometres', this)">
                                    Kilometres
                                </button>
                                <button type="button" class="px-4 py-2 text-sm font-medium transition-colors <?= ($settings['distance_unit'] ?? 'Miles') === 'Miles' ? 'bg-gray-100 text-gray-900 border-l border-gray-300' : 'text-gray-500 hover:text-gray-700' ?>" onclick="setDistanceUnit('Miles', this)">
                                    Miles
                                </button>
                            </div>
                            <input type="hidden" name="distance_unit" id="distance_unit_input" value="<?= htmlspecialchars($settings['distance_unit'] ?? 'Miles') ?>">
                            <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Save
                            </button>
                        </div>
                    </div>

                    <!-- Start Week On -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-4">Start the week on</h3>
                        <div class="flex items-center space-x-3">
                            <select name="week_start" class="custom-select">
                                <option value="Monday" <?= ($settings['week_start_day'] ?? 'Monday') === 'Monday' ? 'selected' : '' ?>>Monday</option>
                                <option value="Sunday" <?= ($settings['week_start_day'] ?? 'Monday') === 'Sunday' ? 'selected' : '' ?>>Sunday</option>
                            </select>
                            <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Save
                            </button>
                        </div>
                    </div>
                </form>

                <?php elseif ($active_tab === 'booking'): ?>
                <!-- Booking Settings Tab -->
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="update_booking">
                    
                    <!-- Manual Approval -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Require manual approval</h3>
                        <p class="text-sm text-gray-600 mb-4">When enabled, you'll have 48h to approve bookings made online.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center space-x-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">Manual approval is currently turned off</span>
                            </div>
                            <button type="button" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Turn on manual approval
                            </button>
                        </div>
                    </div>

                    <!-- Minimum Notice -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Minimum notice before a booking</h3>
                        <p class="text-sm text-gray-600 mb-4">Minimum time required between the booking request and pickup.</p>
                        <div class="flex items-center space-x-3">
                            <input type="number" name="min_notice" value="<?= htmlspecialchars($settings['min_booking_notice'] ?? '48') ?>" placeholder="e.g. 48" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="flex bg-gray-100 rounded-lg">
                                <button type="button" onclick="setNoticeUnit('Hours')" class="px-4 py-2 text-sm font-medium rounded-l-lg hover:bg-gray-200">Hours</button>
                                <button type="button" onclick="setNoticeUnit('Days')" class="px-4 py-2 text-sm font-medium rounded-r-lg hover:bg-gray-200">Days</button>
                            </div>
                            <input type="hidden" name="notice_unit" id="notice_unit" value="<?= htmlspecialchars($settings['booking_notice_unit'] ?? 'Hours') ?>">
                        </div>
                    </div>

                    <!-- Buffer Time -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Buffer time between bookings (hours)</h3>
                        <p class="text-sm text-gray-600 mb-4">Minimum time you need between two rentals for cleaning, inspection, or preparation.</p>
                        <input type="number" name="buffer_time" value="<?= htmlspecialchars($settings['buffer_time_hours'] ?? '6') ?>" placeholder="e.g. 6" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Maximum Booking Window -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Maximum booking window (days in advance)</h3>
                        <p class="text-sm text-gray-600 mb-4">How far ahead clients can book a car</p>
                        <input type="number" name="max_advance" value="<?= htmlspecialchars($settings['max_booking_advance_days'] ?? '30') ?>" placeholder="e.g. 30" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Save Button -->
                    <div class="pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium">
                            Save changes
                        </button>
                    </div>
                </form>

                <?php elseif ($active_tab === 'main'): ?>
                <!-- Main Information Tab -->
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="update_main_info">
                    
                    <!-- Logo Upload -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Company Logo</label>
                        <p class="text-sm text-gray-600 mb-6">This logo appears on your public site, and on your e-sign contracts.</p>
                        
                        <div class="flex items-center gap-6">
                            <div class="relative group">
                                <?php if (!empty($tenant['logo'])): ?>
                                <img id="logoPreview" src="<?= htmlspecialchars($tenant['logo'])?>" alt="Current Logo" class="h-20 w-20 object-contain border border-gray-200 rounded-xl p-2 bg-white shadow-sm transition-all group-hover:border-blue-300">
                                <?php
    else: ?>
                                <div id="logoPreview" class="h-20 w-20 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400 border border-dashed border-gray-300">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <?php
    endif; ?>
                            </div>

                            <div class="space-y-2">
                                <button type="button" onclick="document.getElementById('logoInput').click()" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-semibold shadow-sm transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    Update logo
                                </button>
                                <p class="text-xs text-gray-500" id="logoFileName">PNG, JPG, or SVG. Max 2MB.</p>
                                <input type="file" id="logoInput" name="company_logo" class="hidden" accept="image/*" onchange="updateFileName(this)">
                            </div>
                        </div>
                    </div>

                    <!-- Company Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Company name</label>
                        <input type="text" name="company_name" value="<?= htmlspecialchars($tenant['name'])?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Company Address -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Company address</label>
                        <input type="text" name="company_address" value="<?= htmlspecialchars($settings['company_address'] ?? '')?>" placeholder="Start typing..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Pickup & Drop-off Locations -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Default Pickup Location(s)</label>
                            <input type="text" name="pickup_location" value="<?= htmlspecialchars($settings['pickup_location'] ?? '')?>" placeholder="e.g. Heathrow Terminal 2; Manchester Central Complex: Windmill St, Manchester, M2 3GX" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-[10px] text-gray-400 mt-1">Separate multiple locations using a semicolon (;). Do not use commas to separate locations, as addresses may contain commas.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Default Drop-off Location(s)</label>
                            <input type="text" name="dropoff_location" value="<?= htmlspecialchars($settings['dropoff_location'] ?? '')?>" placeholder="e.g. Heathrow Terminal 2; Manchester Central Complex: Windmill St, Manchester, M2 3GX" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-[10px] text-gray-400 mt-1">Separate multiple locations using a semicolon (;). Do not use commas to separate locations, as addresses may contain commas.</p>
                        </div>
                    </div>

                    <!-- Website -->
                    <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-6 mb-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-1">Your Website</label>
                                <?php
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $site_url = $protocol . "://" . $tenant['subdomain'] . "." . ROOT_DOMAIN . (ROOT_DOMAIN === 'localhost' ? ":" . PORT : "");
?>
                                <a href="<?= $site_url?>" target="_blank" class="text-blue-600 font-medium hover:underline flex items-center gap-1.5">
                                    <?= str_replace(['http://', 'https://'], '', $site_url)?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                            <a href="?tab=domain" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-semibold shadow-sm transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                Connect your own domain
                            </a>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Phone number</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '')?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Main Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Main email</label>
                        <p class="text-sm text-gray-600 mb-2">Current email : <?= htmlspecialchars($settings['company_email'] ?? $_SESSION['user_email'] ?? '')?></p>
                        <p class="text-xs text-gray-500 mb-4">Email notifications regarding business activities will be sent to this email address</p>
                        <button type="button" onclick="document.getElementById('email-modal').classList.remove('hidden')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                            Update email address
                        </button>
                    </div>

                    <!-- Save Button -->
                    <div class="pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium">
                            Save changes
                        </button>
                    </div>
                </form>

                <?php
elseif ($active_tab === 'team'): ?>
                <!-- Team Tab -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Team Members</h3>
                        <?php
    $total_team = count($team_members) + count($pending_invites);
    if ($total_team < 3):
?>
                        <button onclick="document.getElementById('invite-modal').classList.remove('hidden')" class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm font-semibold flex items-center gap-2 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Invite Member
                        </button>
                        <?php
    else: ?>
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-xs font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Maximum 3 members reached
                        </div>
                        <?php
    endif; ?>
                    </div>

                    <!-- Members List -->
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($team_members as $member): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs uppercase">
                                                <?= substr($member['full_name'] ?? $member['email'] ?? 'U', 0, 1)?>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($member['full_name'] ?? 'No Name')?></span>
                                                <span class="text-[10px] text-gray-400 uppercase tracking-wide">Joined <?= date('M Y', strtotime($member['created_at']))?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $member['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'?>">
                                            <?= ucfirst($member['role'])?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($member['email'])?></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button onclick="showEditUserModal(<?= htmlspecialchars(json_encode($member))?>)" class="p-2 text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <?php if ($member['id'] != $_SESSION['user_id']): ?>
                                            <button onclick="showDeleteUserModal(<?= $member['id']?>, '<?= htmlspecialchars($member['full_name'] ?? $member['email'])?>')" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                            <?php
        endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
    endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($pending_invites)): ?>
                    <div>
                        <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Pending Invitations</h4>
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Sent</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($pending_invites as $invite): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($invite['email'])?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <?= ucfirst($invite['role'])?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?= date('M j, Y', strtotime($invite['created_at']))?></td>
                                        <td class="px-6 py-4 text-right">
                                            <button type="button" 
                                                    onclick="showCancelInviteModal(<?= $invite['id']?>, '<?= htmlspecialchars($invite['email'])?>')" 
                                                    class="text-xs font-bold text-red-600 hover:text-red-800 uppercase tracking-wider transition-colors">
                                                Cancel
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
        endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
    endif; ?>

                    <!-- Logged-in Team Member Signature Section -->
                    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm mt-6">
                        <h4 class="text-sm font-bold text-gray-900 mb-1">Your Witness Signature</h4>
                        <p class="text-xs text-gray-500 mb-4">Draw your signature below to save it as your witness signature. This signature will automatically appear on rental contracts.</p>
                        
                        <?php
                        // Fetch logged in user's saved signature
                        $stmt_sig = $pdo->prepare("SELECT signature_data FROM users WHERE id = ?");
                        $stmt_sig->execute([$_SESSION['user_id']]);
                        $my_sig = $stmt_sig->fetchColumn();
                        ?>

                        <form method="POST" id="witnessSigForm" class="space-y-4">
                            <input type="hidden" name="action" value="save_witness_signature">
                            <input type="hidden" name="signature_data" id="witnessSignatureData" value="">
                            
                            <div class="relative w-full max-w-md border border-gray-200 rounded-xl overflow-hidden bg-gray-50" style="height: 150px;">
                                <canvas id="witnessSigCanvas" class="w-full h-full cursor-crosshair relative z-10" style="touch-action: none;"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none text-gray-300 font-semibold text-xs uppercase tracking-wider" id="canvasPlaceholder">
                                    <?php if ($my_sig): ?>
                                        <img src="<?= htmlspecialchars($my_sig) ?>" alt="My Signature" id="witnessSigImage" class="max-h-28 pointer-events-none relative z-0">
                                    <?php else: ?>
                                        Draw signature here
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flex gap-3">
                                <button type="button" onclick="clearWitnessSignature()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-xs font-semibold">Clear</button>
                                <button type="button" onclick="saveWitnessSignature()" class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-xs font-semibold">Save Signature</button>
                            </div>
                        </form>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const canvas = document.getElementById('witnessSigCanvas');
                            if (!canvas) return;
                            
                            const ctx = canvas.getContext('2d');
                            const placeholder = document.getElementById('canvasPlaceholder');
                            let isDrawing = false;
                            let hasDrawn = false;
                            
                            // Adjust canvas coordinate resolution on resize
                            function resizeCanvas() {
                                const rect = canvas.getBoundingClientRect();
                                canvas.width = rect.width;
                                canvas.height = rect.height;
                                ctx.strokeStyle = '#000000';
                                ctx.lineWidth = 2.5;
                                ctx.lineCap = 'round';
                                ctx.lineJoin = 'round';
                            }
                            
                            resizeCanvas();
                            window.addEventListener('resize', resizeCanvas);
                            
                            function getPos(e) {
                                const rect = canvas.getBoundingClientRect();
                                if (e.touches && e.touches.length > 0) {
                                    return {
                                        x: e.touches[0].clientX - rect.left,
                                        y: e.touches[0].clientY - rect.top
                                    };
                                }
                                return {
                                    x: e.clientX - rect.left,
                                    y: e.clientY - rect.top
                                };
                            }
                            
                            function startDraw(e) {
                                isDrawing = true;
                                const pos = getPos(e);
                                ctx.beginPath();
                                ctx.moveTo(pos.x, pos.y);
                                
                                // Hide placeholder
                                const sigImg = document.getElementById('witnessSigImage');
                                if (sigImg) sigImg.remove();
                                placeholder.innerHTML = '';
                            }
                            
                            function draw(e) {
                                if (!isDrawing) return;
                                e.preventDefault();
                                const pos = getPos(e);
                                ctx.lineTo(pos.x, pos.y);
                                ctx.stroke();
                                hasDrawn = true;
                            }
                            
                            function stopDraw() {
                                isDrawing = false;
                            }
                            
                            // Mouse events
                            canvas.addEventListener('mousedown', startDraw);
                            canvas.addEventListener('mousemove', draw);
                            canvas.addEventListener('mouseup', stopDraw);
                            canvas.addEventListener('mouseleave', stopDraw);
                            
                            // Touch events
                            canvas.addEventListener('touchstart', startDraw);
                            canvas.addEventListener('touchmove', draw);
                            canvas.addEventListener('touchend', stopDraw);
                            
                            window.clearWitnessSignature = function() {
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                                placeholder.innerHTML = 'Draw signature here';
                                hasDrawn = false;
                                document.getElementById('witnessSignatureData').value = '';
                            };
                            
                            window.saveWitnessSignature = function() {
                                if (!hasDrawn) {
                                    alert('Please draw a signature before saving.');
                                    return;
                                }
                                const dataUrl = canvas.toDataURL('image/png');
                                document.getElementById('witnessSignatureData').value = dataUrl;
                                document.getElementById('witnessSigForm').submit();
                            };
                        });
                    </script>
                </div>

                <!-- Invite Modal -->
                <div id="invite-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-md w-full p-8 shadow-2xl scale-in-center">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-extrabold text-gray-900 tracking-tight">Invite Team Member</h3>
                            <button onclick="document.getElementById('invite-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="action" value="invite_team_member">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                                <input type="email" name="invite_email" required placeholder="name@example.com" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Role</label>
                                <select name="invite_role" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 8px;">
                                    <option value="staff">Staff (Limited access)</option>
                                    <option value="admin">Administrator (Full access)</option>
                                </select>
                            </div>
                            <div class="pt-4 flex gap-3">
                                <button type="button" onclick="document.getElementById('invite-modal').classList.add('hidden')" id="inviteCancelBtn" class="flex-1 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                                <button type="submit" id="inviteSubmitBtn" class="flex-[2] px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold shadow-lg shadow-blue-100 transition-all flex items-center justify-center gap-2">
                                    <span id="inviteSubmitText">Send Invitation</span>
                                    <svg id="inviteLoader" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div id="edit-user-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-md w-full p-8 shadow-2xl scale-in-center overflow-hidden relative">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-extrabold text-gray-900 tracking-tight">Edit Member</h3>
                            <button onclick="document.getElementById('edit-user-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="action" value="update_team_member">
                            <input type="hidden" name="member_id" id="editUserId">
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                                <input type="text" id="editUserName" disabled class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-500 cursor-not-allowed text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Role</label>
                                <select name="edit_role" id="editUserRole" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 8px;">
                                    <option value="staff">Staff (Limited access)</option>
                                    <option value="admin">Administrator (Full access)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Reset Password (Optional)</label>
                                <input type="password" name="edit_password" placeholder="Leave blank to keep current" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                <p class="mt-1.5 text-[10px] text-gray-400">If filled, this will instantly change the user's password.</p>
                            </div>

                            <div class="pt-4 flex gap-3">
                                <button type="button" onclick="document.getElementById('edit-user-modal').classList.add('hidden')" class="flex-1 py-3 text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors">Cancel</button>
                                <button type="submit" class="flex-[2] py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold shadow-lg shadow-blue-100 transition-all">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete User Modal -->
                <div id="delete-user-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-sm w-full p-8 shadow-2xl scale-in-center overflow-hidden relative">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </div>
                        
                        <h3 class="text-xl font-extrabold text-gray-900 tracking-tight text-center mb-2">Remove Member?</h3>
                        <p class="text-gray-500 text-center text-sm mb-8">Are you sure you want to remove <strong id="deleteUserNameDisplay" class="text-gray-900"></strong> from your team? They will lose all access to the dashboard.</p>
                        
                        <form method="POST" class="flex gap-3">
                            <input type="hidden" name="action" value="delete_team_member">
                            <input type="hidden" name="member_id" id="deleteUserId" value="">
                            
                            <button type="button" onclick="document.getElementById('delete-user-modal').classList.add('hidden')" class="flex-1 py-3 text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors">Cancel</button>
                            <button type="submit" class="flex-1 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 font-bold shadow-lg shadow-red-100 transition-all">Delete User</button>
                        </form>
                    </div>
                </div>

                <!-- Cancel Invite Confirmation Modal -->
                <div id="cancel-invite-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-sm w-full p-8 shadow-2xl scale-in-center overflow-hidden relative">
                        <!-- Danger Icon -->
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        
                        <h3 class="text-xl font-extrabold text-gray-900 tracking-tight text-center mb-2">Cancel Invitation?</h3>
                        <p class="text-gray-500 text-center text-sm mb-8">Are you sure you want to cancel the invitation for <strong id="cancelInviteEmailDisplay" class="text-gray-900"></strong>? This link will no longer work.</p>
                        
                        <form method="POST" class="flex gap-3">
                            <input type="hidden" name="action" value="cancel_invitation">
                            <input type="hidden" name="invitation_id" id="cancelInvitationId" value="">
                            
                            <button type="button" onclick="document.getElementById('cancel-invite-modal').classList.add('hidden')" class="flex-1 py-3 text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors">Keep it</button>
                            <button type="submit" class="flex-1 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 font-bold shadow-lg shadow-red-100 transition-all">Cancel Invite</button>
                        </form>
                    </div>
                </div>

                <?php
elseif ($active_tab === 'payments'): ?>
                <!-- Payments Tab -->
                <div class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Stripe Connect</h3>
                                <p class="text-sm text-gray-500">Connect your Stripe account to accept payments directly.</p>
                            </div>
                        </div>

                        <?php
    // Determine which keys to show based on current mode
    $is_test = ($settings['stripe_test_mode'] ?? 0) ? true : false;
    // Read test keys from dedicated columns (fallback to main columns for migration)
    $test_pub = $settings['stripe_test_publishable_key'] ?? '';
    $test_sec = $settings['stripe_test_secret_key'] ?? '';
    if (empty($test_pub) && $is_test)
        $test_pub = $settings['stripe_publishable_key'] ?? '';
    if (empty($test_sec) && $is_test)
        $test_sec = $settings['stripe_secret_key'] ?? '';
    // Read live keys from dedicated columns (fallback to main columns for migration)
    $live_pub = $settings['stripe_live_publishable_key'] ?? '';
    $live_sec = $settings['stripe_live_secret_key'] ?? '';
    if (empty($live_pub) && !$is_test)
        $live_pub = $settings['stripe_publishable_key'] ?? '';
    if (empty($live_sec) && !$is_test)
        $live_sec = $settings['stripe_secret_key'] ?? '';
?>
                        <form method="POST" class="space-y-6" id="stripe-form">
                            <input type="hidden" name="action" value="update_stripe_keys">
                            
                            <!-- Test Mode Toggle -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">Test Mode</h4>
                                    <p class="text-xs text-gray-500">Use this to test payments with fake cards</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="stripe_test_mode" id="stripe_test_mode_toggle" class="sr-only peer" <?= $is_test ? 'checked' : ''?> onchange="toggleStripeMode(this)">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Mode indicator badge -->
                            <div id="stripe-mode-badge" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-bold <?= $is_test ? 'bg-amber-50 border border-amber-200 text-amber-700' : 'bg-green-50 border border-green-200 text-green-700'?>">
                                <span id="stripe-mode-dot" class="w-2 h-2 rounded-full <?= $is_test ? 'bg-amber-500' : 'bg-green-500'?>"></span>
                                <span id="stripe-mode-label"><?= $is_test ? 'Test Mode — Using test keys (no real charges)' : 'Live Mode — Using live keys (real charges)'?></span>
                            </div>

                            <!-- Test Keys (shown when test mode is ON) -->
                            <div id="stripe-test-fields" class="space-y-4" style="<?= $is_test ? '' : 'display:none'?>">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Test Publishable Key</label>
                                    <input type="text" name="stripe_test_publishable_key" 
                                        value="<?= htmlspecialchars($test_pub)?>" 
                                        placeholder="pk_test_..." 
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                    <p class="mt-1.5 text-[10px] text-gray-400">Starts with <span class="text-amber-600 font-semibold">pk_test_</span></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Test Secret Key</label>
                                    <input type="password" name="stripe_test_secret_key" 
                                        value="<?= htmlspecialchars($test_sec)?>" 
                                        placeholder="sk_test_..." 
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                    <p class="mt-1.5 text-[10px] text-gray-400">Starts with <span class="text-amber-600 font-semibold">sk_test_</span></p>
                                </div>
                            </div>

                            <!-- Live Keys (shown when test mode is OFF) -->
                            <div id="stripe-live-fields" class="space-y-4" style="<?= $is_test ? 'display:none' : ''?>">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Live Publishable Key</label>
                                    <input type="text" name="stripe_live_publishable_key" 
                                        value="<?= htmlspecialchars($live_pub)?>" 
                                        placeholder="pk_live_..." 
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                    <p class="mt-1.5 text-[10px] text-gray-400">Starts with <span class="text-green-600 font-semibold">pk_live_</span></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Live Secret Key</label>
                                    <input type="password" name="stripe_live_secret_key" 
                                        value="<?= htmlspecialchars($live_sec)?>" 
                                        placeholder="sk_live_..." 
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                    <p class="mt-1.5 text-[10px] text-gray-400">Starts with <span class="text-green-600 font-semibold">sk_live_</span></p>
                                </div>
                            </div>

                            <div class="pt-4 flex items-center justify-between gap-4">
                                <button type="submit" class="px-8 py-3 bg-black text-white rounded-xl hover:bg-gray-800 font-bold transition-all shadow-lg hover:shadow-xl">
                                    <?=(!empty($settings['stripe_publishable_key']) && !empty($settings['stripe_secret_key'])) ? 'Update Configuration' : 'Set up Stripe Connect'?>
                                </button>
                                
                                <?php if (!empty($settings['stripe_publishable_key']) || !empty($settings['stripe_secret_key']) || !empty($live_pub) || !empty($live_sec)): ?>
                                <button type="button" onclick="showConfirmation('Remove Stripe?', 'Are you sure you want to remove your Stripe integration? This will prevent you from accepting card payments online.', () => { const f = document.createElement('form'); f.method='POST'; const a=document.createElement('input'); a.type='hidden'; a.name='action'; a.value='remove_stripe'; f.appendChild(a); document.body.appendChild(f); f.submit(); }, 'Remove Integration', 'bg-red-600')" class="px-6 py-3 border border-red-200 text-red-600 rounded-xl hover:bg-red-50 font-bold transition-all">
                                    Remove Connection
                                </button>
                                <?php
    endif; ?>

                                <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-sm text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1">
                                    Get your API keys
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                        </form>

                        <script>
                        function toggleStripeMode(checkbox) {
                            const isTest = checkbox.checked;
                            const testFields = document.getElementById('stripe-test-fields');
                            const liveFields = document.getElementById('stripe-live-fields');
                            const badge = document.getElementById('stripe-mode-badge');
                            const dot = document.getElementById('stripe-mode-dot');
                            const label = document.getElementById('stripe-mode-label');

                            if (isTest) {
                                testFields.style.display = '';
                                liveFields.style.display = 'none';
                                badge.className = 'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-bold bg-amber-50 border border-amber-200 text-amber-700';
                                dot.className = 'w-2 h-2 rounded-full bg-amber-500';
                                label.textContent = 'Test Mode — Using test keys (no real charges)';
                            } else {
                                testFields.style.display = 'none';
                                liveFields.style.display = '';
                                badge.className = 'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-bold bg-green-50 border border-green-200 text-green-700';
                                dot.className = 'w-2 h-2 rounded-full bg-green-500';
                                label.textContent = 'Live Mode — Using live keys (real charges)';
                            }
                        }
                        </script>
                    </div>

                    <div class="bg-blue-50/50 rounded-2xl p-6 border border-blue-100 italic">
                        <p class="text-sm text-gray-600">
                            <strong>Testing Pro-tip:</strong> When Test Mode is ON, you can use Stripe's test card numbers (like 4242 4242 4242 4242) to simulate successful or failed bookings without real money.
                        </p>
                    </div>
                </div>

                <?php
elseif ($active_tab === 'domain'): ?>
                <!-- Domain Management Tab -->
                <div class="space-y-8">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Custom Domain</h2>
                        <p class="text-sm text-gray-600 mb-6">Point your own domain to your rental site for a professional look.</p>

                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_custom_domain">
                            
                            <div class="max-w-md">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Enter your domain</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" id="domainInput" name="custom_domain" value="<?= htmlspecialchars($tenant['custom_domain'] ?? '')?>" placeholder="rent.example.com" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                        <div id="domainStatusIcon" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                                            <!-- Icons will be injected by JS -->
                                        </div>
                                    </div>
                                    <button type="button" onclick="checkDomainAvailability()" id="checkDomainBtn" class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-all">
                                        Check Availability
                                    </button>
                                </div>
                                <p id="domainStatusMsg" class="mt-2 text-xs"></p>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium transition-all shadow-lg">
                                    Save Domain
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <div class="flex items-center gap-2 mb-4">
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Already own a domain?</h3>
                        </div>
                        
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 uppercase tracking-tight">DNS Configuration</h3>
                                    <p class="text-xs text-gray-500">Add these records to your domain's DNS settings</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em]">Target Host</span>
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">CNAME</span>
                                    </div>
                                    <code class="text-sm font-mono text-gray-800 break-all"><?= htmlspecialchars($tenant['custom_domain'] ?: 'yourdomain.com')?></code>
                                    <div class="mt-2 text-xs text-gray-400">Points to: <span class="font-medium text-gray-600">proxy.fleetrentalpro.com</span></div>
                                </div>

                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em]">Verification</span>
                                        <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-[10px] font-bold">TXT</span>
                                    </div>
                                    <code class="text-sm font-mono text-gray-800 break-all">fleet-rental-pro-site-verification=<?= substr(md5($tenant['id']), 0, 16)?></code>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center gap-2 p-3 bg-amber-50 border border-amber-100 rounded-lg text-amber-700 text-xs text-center justify-center">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>DNS changes can take up to 48 hours to propagate.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    async function checkDomainAvailability() {
                        const input = document.getElementById('domainInput');
                        const domain = input.value.trim();
                        const btn = document.getElementById('checkDomainBtn');
                        const msg = document.getElementById('domainStatusMsg');
                        const icon = document.getElementById('domainStatusIcon');

                        if (!domain) return;

                        btn.disabled = true;
                        btn.innerHTML = '<svg class="animate-spin h-4 w-4 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                        
                        try {
                            const res = await fetch(`../api/check-domain.php?domain=${domain}`);
                            const data = await res.json();

                            if (data.available) {
                                if (data.registered === true) {
                                    msg.className = 'mt-2 text-xs text-green-600 flex flex-col gap-2';
                                    msg.innerHTML = `
                                        <div class="flex items-center gap-1.5 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            ${data.message}
                                        </div>
                                        <button type="submit" class="w-fit px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded text-[10px] font-bold uppercase tracking-wider hover:bg-green-100 transition-all">
                                            Connect This Domain
                                        </button>
                                    `;
                                    icon.innerHTML = '<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                                } else {
                                    msg.className = 'mt-2 text-xs text-amber-600 flex flex-col gap-3';
                                    msg.innerHTML = `
                                        <div class="flex items-center gap-1.5 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                            ${data.message}
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" onclick="initiateDomainPurchase('${domain}', event)" class="px-3 py-1.5 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 transition-all font-bold flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                                Buy via Fleet Rental Pro ($14.99/yr)
                                            </button>
                                        </div>
                                    `;
                                    icon.innerHTML = '<svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
                                }
                            } else {
                                msg.className = 'mt-2 text-xs text-red-600 flex items-center gap-1.5';
                                msg.innerHTML = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>' + (data.message || data.error);
                                icon.innerHTML = '<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
                            }
                            
                            icon.classList.remove('hidden');
                        } catch (err) {
                            msg.innerText = 'Error checking domain availability.';
                            msg.className = 'mt-2 text-xs text-red-600';
                        } finally {
                            btn.disabled = false;
                            btn.innerText = 'Check Availability';
                        }
                    }

                    async function initiateDomainPurchase(domain, event) {
                        try {
                            const btn = event.currentTarget;
                            const originalContent = btn.innerHTML;
                            btn.disabled = true;
                            btn.innerHTML = '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';

                            const res = await fetch('../api/initiate-domain-purchase.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ domain })
                            });

                            const data = await res.json();
                            if (data.url) {
                                window.location.href = data.url;
                            } else {
                                alert(data.error || 'Failed to initiate purchase');
                                btn.disabled = false;
                                btn.innerHTML = originalContent;
                            }
                        } catch (err) {
                            alert('Connection error. Please try again.');
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                        }
                    }
                </script>

                <?php
elseif ($active_tab === 'danger'): ?>
                <!-- Danger Zone Tab -->
                <div class="bg-white border border-red-200 rounded-lg p-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Delete Account</h3>
                                <p class="text-sm text-gray-600 mt-1">Once you delete your account, there is no going back. Please be certain.</p>
                            </div>
                        </div>
                        <button onclick="document.getElementById('delete-account-modal').classList.remove('hidden')" class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium whitespace-nowrap transition-all">
                            Delete account
                        </button>
                    </div>
                </div>

                <!-- Delete Account Modal -->
                <div id="delete-account-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[100] flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-sm w-full p-8 shadow-2xl scale-in-center overflow-hidden relative">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        
                        <h3 class="text-xl font-extrabold text-gray-900 tracking-tight text-center mb-2">Delete Account?</h3>
                        <p class="text-gray-500 text-center text-sm mb-8">Are you sure? All your data will be <strong>lost forever</strong> and cannot be recovered in any way.</p>
                        
                        <form method="POST" class="flex flex-col gap-3">
                            <input type="hidden" name="action" value="delete_account">
                            <button type="submit" class="w-full py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 font-bold shadow-lg shadow-red-100 transition-all">Yes, Delete Permanently</button>
                            <button type="button" onclick="document.getElementById('delete-account-modal').classList.add('hidden')" class="w-full py-3 text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors text-center">Cancel</button>
                        </form>
                    </div>
                </div>
                <?php
endif; ?>
            </div>
        </main>
    </div>

    <!-- Email Update Modal -->
    <div id="email-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Email Address</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_email">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Email Address</label>
                    <input type="email" name="new_email" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('email-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800">
                        Update Email
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Support Chat Button -->
    <button class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 w-12 h-12 sm:w-14 sm:h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition z-30">
        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
    </button>

    <!-- Mobile Menu Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    <script>
        // Close modal when clicking outside
        document.getElementById('email-modal').addEventListener('click', (e) => {
            if (e.target.id === 'email-modal') {
                e.target.classList.add('hidden');
            }
        });

        // Update filename display when logo is selected
        function updateFileName(input) {
            const fileNameDisplay = document.getElementById('logoFileName');
            const preview = document.getElementById('logoPreview');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileNameDisplay.textContent = file.name;
                
                // Show preview if it's an image
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } else {
                            // If it was a dashed div, replace it with an img
                            const img = document.createElement('img');
                            img.id = 'logoPreview';
                            img.src = e.target.result;
                            img.className = 'h-20 w-20 object-contain border border-gray-200 rounded-xl p-2 bg-white shadow-sm transition-all group-hover:border-blue-300';
                            preview.parentNode.replaceChild(img, preview);
                        }
                    }
                    reader.readAsDataURL(file);
                }
            } else {
                fileNameDisplay.textContent = 'PNG, JPG, or SVG. Max 2MB.';
            }
        }
        // Handle invite form submission loading state
        const inviteForm = document.querySelector('#invite-modal form');
        const inviteSubmitBtn = document.getElementById('inviteSubmitBtn');
        const inviteCancelBtn = document.getElementById('inviteCancelBtn');
        const inviteSubmitText = document.getElementById('inviteSubmitText');
        const inviteLoader = document.getElementById('inviteLoader');

        if (inviteForm) {
            inviteForm.addEventListener('submit', function() {
                inviteSubmitBtn.disabled = true;
                inviteCancelBtn.classList.add('hidden');
                inviteSubmitBtn.classList.add('opacity-80', 'cursor-not-allowed');
                inviteSubmitText.textContent = 'Sending...';
                inviteLoader.classList.remove('hidden');
            });
        }

        // Invitation Modal Logic
        function showCancelInviteModal(id, email) {
            document.getElementById('cancelInvitationId').value = id;
            document.getElementById('cancelInviteEmailDisplay').textContent = email;
            document.getElementById('cancel-invite-modal').classList.remove('hidden');
        }

        // Edit User Modal Logic
        function showEditUserModal(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUserName').value = user.full_name || user.email;
            document.getElementById('editUserRole').value = user.role;
            document.getElementById('edit-user-modal').classList.remove('hidden');
        }

        // Delete User Modal Logic
        function showDeleteUserModal(id, name) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserNameDisplay').textContent = name;
            document.getElementById('delete-user-modal').classList.remove('hidden');
        }
    </script>
    
    <script>
        // Distance unit toggle
        function setDistanceUnit(unit, button) {
            // Update hidden input
            document.getElementById('distance_unit_input').value = unit;
            
            // Update active state classes
            const container = button.parentElement;
            const buttons = container.querySelectorAll('button');
            
            buttons.forEach(btn => {
                if (btn.innerText.trim() === unit) {
                    btn.className = 'px-4 py-2 text-sm font-medium transition-colors bg-gray-100 text-gray-900 border-' + (unit === 'Miles' ? 'l' : 'r') + ' border-gray-300';
                } else {
                    btn.className = 'px-4 py-2 text-sm font-medium transition-colors text-gray-500 hover:text-gray-700';
                }
            });
        }
        
        // Notice unit toggle
        function setNoticeUnit(unit) {
            document.getElementById('notice_unit').value = unit;
        }
    </script>
    
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>
</html>
