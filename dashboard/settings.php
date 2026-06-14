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
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN company_website VARCHAR(255) DEFAULT ''");
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
try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN stripe_customer_id VARCHAR(255) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN opening_time VARCHAR(10) DEFAULT '08:00'");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN closing_time VARCHAR(10) DEFAULT '18:00'");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN min_booking_notice INT DEFAULT 0");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN booking_notice_unit ENUM('hours', 'days') DEFAULT 'hours'");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN buffer_time_hours INT DEFAULT 0");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN buffer_time_unit ENUM('hours', 'days') DEFAULT 'hours'");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN max_booking_advance_days INT DEFAULT 30");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN whatsapp_number VARCHAR(50) DEFAULT ''");
}
catch (PDOException $e) { /* Column might exist */
}

try {
    @$pdo->exec("ALTER TABLE tenant_settings ADD COLUMN whatsapp_enabled TINYINT(1) DEFAULT 0");
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

// Preserve submitted values on error
$submitted_pickup_locations = null;
$submitted_dropoff_locations = null;

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
                $notice_unit = sanitize($_POST['notice_unit'] ?? 'hours');
                $buffer_time = intval($_POST['buffer_time'] ?? 0);
                $buffer_time_unit = sanitize($_POST['buffer_time_unit'] ?? 'hours');
                $max_advance = intval($_POST['max_advance'] ?? 30);
                
                $pickup_locs = $_POST['pickup_locations'] ?? [];
                $dropoff_locs = $_POST['dropoff_locations'] ?? [];
                $opening_time = sanitize($_POST['opening_time'] ?? '08:00');
                $closing_time = sanitize($_POST['closing_time'] ?? '18:00');

                // Filter out empty locations and sanitize
                $pickup_locs = array_filter(array_map('sanitize', $pickup_locs));
                $dropoff_locs = array_filter(array_map('sanitize', $dropoff_locs));

                // Validate that at least one pickup and return location is provided
                if (empty($pickup_locs)) {
                    $error = 'Please add at least one Default Pickup Location.';
                    $submitted_pickup_locations = $_POST['pickup_locations'] ?? [];
                    $submitted_dropoff_locations = $_POST['dropoff_locations'] ?? [];
                } elseif (empty($dropoff_locs)) {
                    $error = 'Please add at least one Default Return Location.';
                    $submitted_pickup_locations = $_POST['pickup_locations'] ?? [];
                    $submitted_dropoff_locations = $_POST['dropoff_locations'] ?? [];
                } else {
                    // Join with '; '
                    $pickup_location = implode('; ', $pickup_locs);
                    $dropoff_location = implode('; ', $dropoff_locs);

                    try {
                        $stmt = $pdo->prepare("UPDATE tenant_settings SET min_booking_notice = ?, booking_notice_unit = ?, buffer_time_hours = ?, buffer_time_unit = ?, max_booking_advance_days = ?, pickup_location = ?, dropoff_location = ?, opening_time = ?, closing_time = ? WHERE tenant_id = ?");
                        $stmt->execute([$min_notice, $notice_unit, $buffer_time, $buffer_time_unit, $max_advance, $pickup_location, $dropoff_location, $opening_time, $closing_time, $_SESSION['tenant_id']]);
                        $success = 'Booking settings updated successfully!';
                    } catch (Exception $e) {
                        $error = 'Failed to update booking settings: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'update_main_info':
                $company_name = sanitize($_POST['company_name'] ?? '');
                $company_address = sanitize($_POST['company_address'] ?? '');
                $phone = sanitize($_POST['phone'] ?? '');
                $whatsapp_number = sanitize($_POST['whatsapp_number'] ?? '');
                $whatsapp_enabled = isset($_POST['whatsapp_enabled']) ? 1 : 0;
                $company_email = sanitize($_POST['company_email'] ?? '');
                $company_website = sanitize($_POST['company_website'] ?? '');

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
                        $stmt = $pdo->prepare("UPDATE tenants SET name = ?, logo = ? WHERE id = ?");
                        $stmt->execute([$company_name, $logo_path, $_SESSION['tenant_id']]);

                        $stmt = $pdo->prepare("UPDATE tenant_settings SET company_address = ?, company_phone = ?, whatsapp_number = ?, whatsapp_enabled = ?, company_email = ?, company_website = ? WHERE tenant_id = ?");
                        $stmt->execute([$company_address, $phone, $whatsapp_number, $whatsapp_enabled, $company_email, $company_website, $_SESSION['tenant_id']]);

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
                $new_email = sanitize($_POST['edit_email'] ?? '');
                $new_password = $_POST['edit_password'] ?? '';

                try {
                    // Start transaction
                    $pdo->beginTransaction();

                    // Update email if provided and different
                    if (!empty($new_email)) {
                        // Validate email format
                        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception('Invalid email address');
                        }
                        
                        // Check if email is already taken by another user
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND tenant_id = ?");
                        $stmt->execute([$new_email, $member_id, $_SESSION['tenant_id']]);
                        if ($stmt->fetch()) {
                            throw new Exception('Email address is already in use by another team member');
                        }
                        
                        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ? AND tenant_id = ?");
                        $stmt->execute([$new_email, $member_id, $_SESSION['tenant_id']]);
                    }

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
                    <?php
                    $tabLabels = [
                        'general' => 'General',
                        'booking' => 'Booking',
                        'main' => 'Company',
                        'team' => 'Team',
                        'payments' => 'Payments',
                        'domain' => 'Domain',
                        'billing' => 'Billing',
                    ];
                    $tabLabel = $tabLabels[$active_tab] ?? 'Settings';
                    ?>
                    <nav class="text-sm text-gray-500 mb-1">
                        <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                        <span class="mx-2">/</span>
                        <a href="/dashboard/settings.php" class="hover:text-gray-700">Settings</a>
                        <?php if ($active_tab !== 'general'): ?>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900"><?= htmlspecialchars($tabLabel) ?></span>
                        <?php endif; ?>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($tabLabel) ?></h1>
                    <p class="text-sm text-gray-600 mt-1">Manage your business and account settings</p>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl">

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
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showErrorModal('<?= htmlspecialchars($error)?>');
                    });
                </script>
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
                            <div class="flex bg-gray-100 rounded-lg p-1" id="noticeUnitToggle">
                                <button type="button" onclick="setNoticeUnit('hours')" id="btn-hours" class="px-4 py-2 text-sm font-medium rounded-lg transition-all">Hours</button>
                                <button type="button" onclick="setNoticeUnit('days')" id="btn-days" class="px-4 py-2 text-sm font-medium rounded-lg transition-all">Days</button>
                            </div>
                            <?php $currentNoticeUnit = strtolower($settings['booking_notice_unit'] ?? 'hours'); ?>
                            <input type="hidden" name="notice_unit" id="notice_unit" value="<?= htmlspecialchars($currentNoticeUnit) ?>">
                        </div>
                    </div>

                    <!-- Buffer Time -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Buffer time between bookings</h3>
                        <p class="text-sm text-gray-600 mb-4">Minimum time you need between two rentals for cleaning, inspection, or preparation.</p>
                        <div class="flex items-center space-x-3">
                            <input type="number" name="buffer_time" value="<?= htmlspecialchars($settings['buffer_time_hours'] ?? '6') ?>" placeholder="e.g. 6" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="flex bg-gray-100 rounded-lg p-1" id="bufferUnitToggle">
                                <button type="button" onclick="setBufferUnit('hours')" id="buffer-btn-hours" class="px-4 py-2 text-sm font-medium rounded-lg transition-all">Hours</button>
                                <button type="button" onclick="setBufferUnit('days')" id="buffer-btn-days" class="px-4 py-2 text-sm font-medium rounded-lg transition-all">Days</button>
                            </div>
                            <?php $currentBufferUnit = strtolower($settings['buffer_time_unit'] ?? 'hours'); ?>
                            <input type="hidden" name="buffer_time_unit" id="buffer_time_unit" value="<?= htmlspecialchars($currentBufferUnit) ?>">
                        </div>
                    </div>

                    <!-- Maximum Booking Window -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Maximum booking window (days in advance)</h3>
                        <p class="text-sm text-gray-600 mb-4">How far ahead clients can book a car</p>
                        <input type="number" name="max_advance" value="<?= htmlspecialchars($settings['max_booking_advance_days'] ?? '30') ?>" placeholder="e.g. 30" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Business Hours -->
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Business Hours / Booking Times</h3>
                        <p class="text-sm text-gray-600 mb-4">Set your opening and closing times. Bookings outside of these hours will be automatically disabled/restricted on checkout.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-md">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Opening Time</label>
                                <input type="time" name="opening_time" value="<?= htmlspecialchars($settings['opening_time'] ?? '08:00') ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Closing Time</label>
                                <input type="time" name="closing_time" value="<?= htmlspecialchars($settings['closing_time'] ?? '18:00') ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Pickup & Drop-off Locations -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t border-gray-200">
                        <!-- Pickup Locations -->
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Default Pickup Location(s)</h3>
                            <p class="text-sm text-gray-600 mb-4">Manage your authorized pickup locations for rental bookings.</p>
                            <div id="pickup-locations-container" class="space-y-3">
                                <?php
                                if ($submitted_pickup_locations !== null) {
                                    $pickup_arr = $submitted_pickup_locations;
                                } else {
                                    $pickup_arr = array_filter(array_map('trim', explode(';', $settings['pickup_location'] ?? '')));
                                }
                                if (empty($pickup_arr)) {
                                    $pickup_arr = [''];
                                }
                                foreach ($pickup_arr as $index => $loc):
                                ?>
                                <div class="flex items-center gap-2 location-row">
                                    <input type="text" name="pickup_locations[]" value="<?= htmlspecialchars($loc) ?>" placeholder="e.g. Heathrow Airport Terminal 2" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <button type="button" onclick="removeLocationRow(this)" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addLocationRow('pickup-locations-container', 'pickup_locations[]')" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add pickup location
                            </button>
                        </div>

                        <!-- Drop-off Locations -->
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Default Return Location(s)</h3>
                            <p class="text-sm text-gray-600 mb-4">Manage your authorized return locations for rental bookings.</p>
                            <div id="dropoff-locations-container" class="space-y-3">
                                <?php
                                if ($submitted_dropoff_locations !== null) {
                                    $dropoff_arr = $submitted_dropoff_locations;
                                } else {
                                    $dropoff_arr = array_filter(array_map('trim', explode(';', $settings['dropoff_location'] ?? '')));
                                }
                                if (empty($dropoff_arr)) {
                                    $dropoff_arr = [''];
                                }
                                foreach ($dropoff_arr as $index => $loc):
                                ?>
                                <div class="flex items-center gap-2 location-row">
                                    <input type="text" name="dropoff_locations[]" value="<?= htmlspecialchars($loc) ?>" placeholder="e.g. Heathrow Airport Terminal 2" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <button type="button" onclick="removeLocationRow(this)" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addLocationRow('dropoff-locations-container', 'dropoff_locations[]')" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add return location
                            </button>
                        </div>
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

                    <!-- Website -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Company website</label>
                        <input type="url" name="company_website" value="<?= htmlspecialchars($settings['company_website'] ?? '')?>" placeholder="https://yourcompany.com" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Website Preview -->
                    <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-6">
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

                    <!-- Company Number & WhatsApp Number -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Company number</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '')?>" maxlength="12" pattern="[0-9]{1,12}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter up to 12 digits">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">WhatsApp number</label>
                            <input type="tel" name="whatsapp_number" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '')?>" maxlength="12" pattern="[0-9]{1,12}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter WhatsApp number">
                        </div>
                    </div>

                    <!-- WhatsApp Toggle -->
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-xl px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Enable WhatsApp chat button</p>
                            <p class="text-xs text-gray-500 mt-0.5">Show the floating WhatsApp button on your public website.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="whatsapp_enabled" value="1" <?= !empty($settings['whatsapp_enabled']) ? 'checked' : ''?> class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- Company Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Company email</label>
                        <input type="email" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? '')?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter company email">
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
                                    document.getElementById('sigRequiredModal').classList.remove('hidden');
                                    return;
                                }
                                const dataUrl = canvas.toDataURL('image/png');
                                document.getElementById('witnessSignatureData').value = dataUrl;
                                document.getElementById('witnessSigForm').submit();
                            };
                        });
                    </script>
                </div>

                <!-- Signature Required Modal -->
                <div id="sigRequiredModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl text-center">
                        <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Signature Required</h3>
                        <p class="text-sm text-gray-500 mb-5">Please draw a signature on the canvas before saving.</p>
                        <button onclick="document.getElementById('sigRequiredModal').classList.add('hidden')" class="w-full px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">Got it</button>
                    </div>
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
                                <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                                <input type="email" name="edit_email" id="editUserEmail" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
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
                <div class="space-y-8">
                    <!-- Stripe Integration Card -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                        <div class="flex items-start justify-between mb-8">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900">Stripe Integration</h3>
                                    <p class="text-sm text-gray-500 mt-1">Accept payments securely with Stripe</p>
                                </div>
                            </div>
                            <div id="stripe-status-badge" class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold <?= (!empty($settings['stripe_publishable_key']) && !empty($settings['stripe_secret_key'])) ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-500'?>">
                                <span class="w-2 h-2 rounded-full <?= (!empty($settings['stripe_publishable_key']) && !empty($settings['stripe_secret_key'])) ? 'bg-blue-600' : 'bg-gray-400'?>"></span>
                                <?= (!empty($settings['stripe_publishable_key']) && !empty($settings['stripe_secret_key'])) ? 'Connected' : 'Not Connected'?>
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
                            
                            <!-- Mode Selection -->
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-900 mb-1">Environment Mode</label>
                                        <p class="text-xs text-gray-500" id="mode-description"><?= $is_test ? 'Test Mode - For testing only' : 'Live Mode - Real transactions' ?></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="stripe_test_mode" value="1" class="sr-only peer" <?= $is_test ? 'checked' : ''?> onchange="toggleStripeMode(this.checked)">
                                        <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700" id="mode-label"><?= $is_test ? 'Test' : 'Live' ?></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Test Keys Section -->
                            <div id="stripe-test-fields" class="space-y-4" style="<?= $is_test ? '' : 'display:none'?>">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    <h4 class="text-sm font-bold text-gray-900">Test API Keys</h4>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Testing Environment</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Publishable Key</label>
                                        <div class="relative">
                                            <input type="text" name="stripe_test_publishable_key" 
                                                value="<?= htmlspecialchars($test_pub)?>" 
                                                placeholder="pk_test_..." 
                                                class="stripe-key-input w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-mono">pk_test_</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Secret Key</label>
                                        <div class="relative">
                                            <input type="password" name="stripe_test_secret_key" 
                                                value="<?= htmlspecialchars($test_sec)?>" 
                                                placeholder="sk_test_..." 
                                                class="stripe-key-input w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-mono">sk_test_</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Keys Section -->
                            <div id="stripe-live-fields" class="space-y-4" style="<?= $is_test ? 'display:none' : ''?>">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    <h4 class="text-sm font-bold text-gray-900">Live API Keys</h4>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Production Environment</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Publishable Key</label>
                                        <div class="relative">
                                            <input type="text" name="stripe_live_publishable_key" 
                                                value="<?= htmlspecialchars($live_pub)?>" 
                                                placeholder="pk_live_..." 
                                                class="stripe-key-input w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-mono">pk_live_</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Secret Key</label>
                                        <div class="relative">
                                            <input type="password" name="stripe_live_secret_key" 
                                                value="<?= htmlspecialchars($live_sec)?>" 
                                                placeholder="sk_live_..." 
                                                class="stripe-key-input w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50/50 text-sm font-mono">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-mono">sk_live_</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="pt-6 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <?=(!empty($settings['stripe_publishable_key']) && !empty($settings['stripe_secret_key'])) ? 'Update Configuration' : 'Connect Stripe'?>
                                    </button>
                                    
                                    <?php if (!empty($settings['stripe_publishable_key']) || !empty($settings['stripe_secret_key']) || !empty($live_pub) || !empty($live_sec)): ?>
                                    <button type="button" onclick="showConfirmation('Remove Stripe?', 'Are you sure you want to remove your Stripe integration? This will prevent you from accepting card payments online.', () => { const f = document.createElement('form'); f.method='POST'; const a=document.createElement('input'); a.type='hidden'; a.name='action'; a.value='remove_stripe'; f.appendChild(a); document.body.appendChild(f); f.submit(); }, 'Remove Integration', 'bg-red-600')" class="px-4 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-semibold transition-all flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Disconnect
                                    </button>
                                    <?php
    endif; ?>
                                </div>

                                <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-sm text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1.5 group">
                                    Get API keys from Stripe
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            </div>
                        </form>

                        <script>
                        function toggleStripeMode(isTest) {
                            const testFields = document.getElementById('stripe-test-fields');
                            const liveFields = document.getElementById('stripe-live-fields');
                            const statusBadge = document.getElementById('stripe-status-badge');
                            const modeDescription = document.getElementById('mode-description');
                            const modeLabel = document.getElementById('mode-label');

                            if (isTest) {
                                testFields.style.display = '';
                                liveFields.style.display = 'none';
                                modeDescription.textContent = 'Test Mode - For testing only';
                                modeLabel.textContent = 'Test';
                            } else {
                                testFields.style.display = 'none';
                                liveFields.style.display = '';
                                modeDescription.textContent = 'Live Mode - Real transactions';
                                modeLabel.textContent = 'Live';
                            }
                        }

                        // Hide placeholder indicators when user types
                        document.querySelectorAll('.stripe-key-input').forEach(input => {
                            const placeholder = input.nextElementSibling;
                            
                            function updatePlaceholder() {
                                if (input.value.length > 0) {
                                    placeholder.style.display = 'none';
                                } else {
                                    placeholder.style.display = 'block';
                                }
                            }
                            
                            input.addEventListener('input', updatePlaceholder);
                            input.addEventListener('focus', updatePlaceholder);
                            input.addEventListener('blur', updatePlaceholder);
                            
                            // Initial check
                            updatePlaceholder();
                        });
                        </script>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1">Testing with Stripe</h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    When in <span class="font-semibold text-blue-600">Test Mode</span>, use Stripe's test card number <code class="px-2 py-0.5 bg-white rounded text-xs font-mono text-blue-700">4242 4242 4242 4242</code> to simulate successful payments without real money. This allows you to test your booking flow before going live.
                                </p>
                            </div>
                        </div>
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
elseif ($active_tab === 'billing'): ?>
                <?php
                // Fetch Stripe billing data
                $stripe_customer_id = $settings['stripe_customer_id'] ?? '';
                $stripe_payment_methods = [];
                $stripe_invoices = [];
                $stripe_subscription = null;
                $stripe_error = null;

                if (!empty($stripe_customer_id) && !empty($settings['stripe_secret_key'])) {
                    try {
                        \Stripe\Stripe::setApiKey($settings['stripe_secret_key']);
                        
                        // Fetch payment methods
                        $payment_methods = \Stripe\PaymentMethod::all([
                            'customer' => $stripe_customer_id,
                            'type' => 'card',
                        ]);
                        $stripe_payment_methods = $payment_methods->data;
                        
                        // Fetch invoices
                        $invoices = \Stripe\Invoice::all([
                            'customer' => $stripe_customer_id,
                            'limit' => 10,
                        ]);
                        $stripe_invoices = $invoices->data;
                        
                        // Fetch subscription
                        $subscriptions = \Stripe\Subscription::all([
                            'customer' => $stripe_customer_id,
                            'limit' => 1,
                            'status' => 'active',
                        ]);
                        if (!empty($subscriptions->data)) {
                            $stripe_subscription = $subscriptions->data[0];
                        }
                    } catch (Exception $e) {
                        $stripe_error = $e->getMessage();
                    }
                }
                ?>
                <!-- Billing & Invoice Tab -->
                <div class="space-y-8">
                    <!-- Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Current Plan Summary Card -->
                        <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-base font-bold text-gray-900">Current Plan Summary</h3>
                                <a href="/pricing.php#pricingCard" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-all inline-block text-center">
                                    Upgrade
                                </a>
                            </div>
                            
                            <?php if ($stripe_subscription): ?>
                                <?php
                                $plan_name = 'Basic Plan';
                                $billing_cycle = 'Monthly';
                                $plan_cost = '0';
                                
                                if (!empty($stripe_subscription->items->data)) {
                                    $plan_item = $stripe_subscription->items->data[0];
                                    $plan_cost = number_format($plan_item->price->unit_amount / 100, 2);
                                    $billing_cycle = ucfirst($plan_item->price->recurring->interval);
                                    $plan_name = ucfirst($plan_item->price->nickname ?? 'Plan');
                                }
                                ?>
                                <div class="grid grid-cols-3 gap-4 mb-6">
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Plan Name</p>
                                        <p class="text-base font-bold text-gray-900"><?= htmlspecialchars($plan_name) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Billing Cycle</p>
                                        <p class="text-base font-bold text-gray-900"><?= htmlspecialchars($billing_cycle) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Plan Cost</p>
                                        <p class="text-base font-bold text-gray-900"><?= htmlspecialchars($settings['currency'] ?? 'GBP') ?><?= htmlspecialchars($plan_cost) ?></p>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</p>
                                        <p class="text-xs font-semibold text-gray-700"><?= ucfirst($stripe_subscription->status ?? 'Unknown') ?></p>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-green-600 h-2.5 rounded-full" style="width: 100%;"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <p class="text-sm text-gray-500">No active subscription found.</p>
                                    <p class="text-xs text-gray-400 mt-2">Add Stripe customer ID to view subscription details.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Payment Method Card -->
                        <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm flex flex-col">
                            <h3 class="text-base font-bold text-gray-900 mb-6">Payment Method</h3>
                            
                            <?php if ($stripe_error): ?>
                                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                                    Error loading payment methods: <?= htmlspecialchars($stripe_error) ?>
                                </div>
                            <?php elseif (empty($stripe_payment_methods)): ?>
                                <div class="border border-gray-150 rounded-2xl p-5 flex items-center justify-center flex-1">
                                    <p class="text-sm text-gray-500">No payment methods found. Add Stripe keys to enable billing.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($stripe_payment_methods as $pm): ?>
                                    <?php if ($pm->type === 'card'): ?>
                                        <div class="border border-gray-150 rounded-2xl p-5 flex items-center justify-between flex-1 mb-3 last:mb-0">
                                            <div class="flex items-start gap-4">
                                                <div class="w-12 h-8 bg-gray-50 border border-gray-100 rounded-md flex items-center justify-center p-1.5 shrink-0">
                                                    <?php
                                                    $card_brand = strtolower($pm->card->brand ?? 'card');
                                                    $card_colors = [
                                                        'visa' => '#1A1F71',
                                                        'mastercard' => '#EB001B',
                                                        'amex' => '#006FCF',
                                                        'discover' => '#FF6000',
                                                    ];
                                                    $color = $card_colors[$card_brand] ?? '#666666';
                                                    ?>
                                                    <div class="w-8 h-5 rounded" style="background: <?= $color ?>;"></div>
                                                </div>
                                                <div class="space-y-0.5">
                                                    <h4 class="text-sm font-bold text-gray-900"><?= ucfirst($pm->card->brand ?? 'Card') ?></h4>
                                                    <p class="text-xs font-semibold text-gray-600">•••• •••• •••• <?= $pm->card->last4 ?? '****' ?></p>
                                                    <p class="text-[10px] text-gray-400 font-medium">Expiry on <?= $pm->card->exp_month ?>/<?= $pm->card->exp_year ?></p>
                                                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-semibold mt-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <?= htmlspecialchars($settings['company_email'] ?? 'No email on file') ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button type="button" class="px-4 py-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-bold shadow-sm transition-all">
                                                Change
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Invoices Section -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-950">Invoice</h3>
                            <p class="text-xs text-gray-500 mt-1">Effortlessly handle your billing and invoices right here.</p>
                        </div>

                        <!-- Invoices Table -->
                        <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-gray-100 bg-gray-50/50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                            <th class="py-4 px-6">
                                                <div class="flex items-center gap-1 cursor-pointer hover:text-gray-600">
                                                    Invoice ID
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th class="py-4 px-6">
                                                <div class="flex items-center gap-1 cursor-pointer hover:text-gray-600">
                                                    Billing Date
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th class="py-4 px-6">
                                                <div class="flex items-center gap-1 cursor-pointer hover:text-gray-600">
                                                    Plan
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th class="py-4 px-6">
                                                <div class="flex items-center gap-1 cursor-pointer hover:text-gray-600">
                                                    Amount
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th class="py-4 px-6">
                                                <div class="flex items-center gap-1 cursor-pointer hover:text-gray-600">
                                                    Status
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th class="py-4 px-6 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 text-sm font-semibold text-gray-800">
                                        <?php if ($stripe_error): ?>
                                            <tr>
                                                <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                                    Error loading invoices: <?= htmlspecialchars($stripe_error) ?>
                                                </td>
                                            </tr>
                                        <?php elseif (empty($stripe_invoices)): ?>
                                            <tr>
                                                <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                                    No invoices found. Add Stripe customer ID to enable billing.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($stripe_invoices as $invoice): ?>
                                                <tr class="hover:bg-gray-50/50 transition">
                                                    <td class="py-4.5 px-6 font-bold text-gray-900">#<?= htmlspecialchars(substr($invoice->id ?? '', -8)) ?></td>
                                                    <td class="py-4.5 px-6 text-gray-500 font-medium"><?= date('d M Y', $invoice->created ?? time()) ?></td>
                                                    <td class="py-4.5 px-6"><?= htmlspecialchars($invoice->description ?? 'Fleet Rental') ?></td>
                                                    <td class="py-4.5 px-6 font-bold"><?= htmlspecialchars($settings['currency'] ?? 'GBP') ?><?= number_format(($invoice->total ?? 0) / 100, 2) ?></td>
                                                    <td class="py-4.5 px-6">
                                                        <?php
                                                        $status = $invoice->status ?? 'unknown';
                                                        $status_colors = [
                                                            'paid' => 'text-green-700 bg-green-50 border-green-100/50',
                                                            'open' => 'text-blue-700 bg-blue-50 border-blue-100/50',
                                                            'void' => 'text-gray-700 bg-gray-50 border-gray-100/50',
                                                            'uncollectible' => 'text-red-700 bg-red-50 border-red-100/50',
                                                        ];
                                                        $status_class = $status_colors[$status] ?? 'text-gray-700 bg-gray-50 border-gray-100/50';
                                                        ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $status_class ?>">
                                                            <?= ucfirst($status) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4.5 px-6 text-right relative">
                                                        <div class="relative inline-block">
                                                            <button onclick="toggleInvoiceMenu(this)" class="text-gray-400 hover:text-gray-600 transition p-1">
                                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                                </svg>
                                                            </button>
                                                            <div class="invoice-dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-10">
                                                                <a href="<?= $invoice->hosted_invoice_url ?? '#' ?>" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                    </svg>
                                                                    View Invoice
                                                                </a>
                                                                <a href="<?= $invoice->invoice_pdf ?? '#' ?>" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                                    </svg>
                                                                    Download Invoice
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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

    <!-- Error Modal -->
    <div id="error-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-8 shadow-2xl scale-in-center overflow-hidden relative">
            <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-extrabold text-gray-900 tracking-tight text-center mb-2">Error</h3>
            <p id="error-modal-message" class="text-gray-500 text-center text-sm mb-8"></p>
            <button type="button" onclick="document.getElementById('error-modal').classList.add('hidden')" class="w-full py-3 bg-gray-900 text-white rounded-xl hover:bg-gray-800 font-bold shadow-lg transition-all">OK</button>
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

        // Error Modal Function
        function showErrorModal(message) {
            document.getElementById('error-modal-message').textContent = message;
            document.getElementById('error-modal').classList.remove('hidden');
        }

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
            document.getElementById('editUserEmail').value = user.email;
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
            const hoursBtn = document.getElementById('btn-hours');
            const daysBtn = document.getElementById('btn-days');
            if (unit === 'hours') {
                hoursBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
                hoursBtn.classList.remove('text-gray-500');
                daysBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
                daysBtn.classList.add('text-gray-500');
            } else {
                daysBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
                daysBtn.classList.remove('text-gray-500');
                hoursBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
                hoursBtn.classList.add('text-gray-500');
            }
        }

        // Initialize toggle state on page load
        (function() {
            const currentUnit = document.getElementById('notice_unit').value || 'hours';
            setNoticeUnit(currentUnit);

            const currentBuffer = document.getElementById('buffer_time_unit').value || 'hours';
            setBufferUnit(currentBuffer);
        })();

        // Buffer unit toggle
        function setBufferUnit(unit) {
            document.getElementById('buffer_time_unit').value = unit;
            const hoursBtn = document.getElementById('buffer-btn-hours');
            const daysBtn = document.getElementById('buffer-btn-days');
            if (unit === 'hours') {
                hoursBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
                hoursBtn.classList.remove('text-gray-500');
                daysBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
                daysBtn.classList.add('text-gray-500');
            } else {
                daysBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
                daysBtn.classList.remove('text-gray-500');
                hoursBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
                hoursBtn.classList.add('text-gray-500');
            }
        }

        // Dynamic location row logic
        function addLocationRow(containerId, inputName) {
            const container = document.getElementById(containerId);
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 location-row';
            div.innerHTML = `
                <input type="text" name="${inputName}" placeholder="e.g. Heathrow Airport Terminal 2" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="button" onclick="removeLocationRow(this)" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
        }

        function removeLocationRow(button) {
            const row = button.closest('.location-row');
            const container = row.parentNode;
            if (container.querySelectorAll('.location-row').length > 1) {
                row.remove();
            } else {
                row.querySelector('input').value = '';
            }
        }

        // Invoice dropdown menu toggle
        function toggleInvoiceMenu(button) {
            const menu = button.nextElementSibling;
            const allMenus = document.querySelectorAll('.invoice-dropdown-menu');
            
            // Close all other menus
            allMenus.forEach(m => {
                if (m !== menu) {
                    m.classList.add('hidden');
                }
            });
            
            // Toggle current menu
            menu.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative.inline-block')) {
                document.querySelectorAll('.invoice-dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // View invoice function
        function viewInvoice(element) {
            const row = element.closest('tr');
            const invoiceId = row.querySelector('td').textContent;
            alert('Viewing invoice: ' + invoiceId);
            // TODO: Implement actual invoice viewing logic
        }

        // Download invoice function
        function downloadInvoice(element) {
            const row = element.closest('tr');
            const invoiceId = row.querySelector('td').textContent;
            alert('Downloading invoice: ' + invoiceId);
            // TODO: Implement actual invoice download logic
        }

        // Invoice table sorting
        let invoiceSortDirection = {};
        
        function sortInvoiceTable(columnIndex) {
            const table = document.querySelector('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Toggle sort direction
            invoiceSortDirection[columnIndex] = !invoiceSortDirection[columnIndex];
            const direction = invoiceSortDirection[columnIndex] ? 1 : -1;
            
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();
                
                // Handle numeric sorting for amount
                if (columnIndex === 3) {
                    const aNum = parseFloat(aText.replace(/[^0-9.-]/g, ''));
                    const bNum = parseFloat(bText.replace(/[^0-9.-]/g, ''));
                    return (aNum - bNum) * direction;
                }
                
                // Handle date sorting
                if (columnIndex === 1) {
                    const aDate = new Date(aText);
                    const bDate = new Date(bText);
                    return (aDate - bDate) * direction;
                }
                
                // Default text sorting
                return aText.localeCompare(bText) * direction;
            });
            
            // Re-append rows in sorted order
            rows.forEach(row => tbody.appendChild(row));
            
            // Update sort icons
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                const icon = header.querySelector('svg');
                if (icon && index === columnIndex) {
                    icon.style.transform = invoiceSortDirection[columnIndex] ? 'rotate(180deg)' : 'rotate(0deg)';
                    icon.style.transition = 'transform 0.2s';
                }
            });
        }

        // Add click handlers to sortable headers
        document.addEventListener('DOMContentLoaded', function() {
            const sortableHeaders = document.querySelectorAll('th');
            sortableHeaders.forEach((header, index) => {
                if (index < 5) { // First 5 columns are sortable
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => sortInvoiceTable(index));
                }
            });
        });
    </script>
    
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>
</html>
