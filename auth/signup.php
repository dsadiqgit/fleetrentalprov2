<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

// Block tenant signup on subdomains
if (IS_SUBDOMAIN) {
    header('Location: /');
    exit;
}

$error = '';
$success = false;

// Handle Stripe Session for Guest Signups
$plan_slug = sanitize($_GET['plan'] ?? 'trial');
$session_id = sanitize($_GET['session_id'] ?? '');
$is_paid = isset($_GET['paid']) && $_GET['paid'] === 'true';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $first_name = sanitize($_POST['first_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $company_name = sanitize($_POST['company_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $fleet_size = sanitize($_POST['fleet_size'] ?? '');
    $google_signup = isset($_POST['google_signup']) && $_POST['google_signup'] === 'true';

    // Carry over plan from form/context
    $final_plan = sanitize($_POST['plan'] ?? $plan_slug);
    
    // For Google signup, get user data from session
    if ($google_signup && isset($_SESSION['google_user_data'])) {
        $google_user = $_SESSION['google_user_data'];
        $email = $google_user['email'];
        $first_name = $google_user['given_name'] ?? '';
        $last_name = $google_user['family_name'] ?? '';
        $google_id = $google_user['id'] ?? '';
        unset($_SESSION['google_user_data']);
    }

    // Generate subdomain
    $subdomain = strtolower($company_name);
    $subdomain = preg_replace('/\s+/', '-', $subdomain);
    $subdomain = preg_replace('/[^a-z0-9-]+/', '', $subdomain);
    $subdomain = preg_replace('/-+/', '-', $subdomain);
    $subdomain = trim($subdomain, '-');

    $full_name = trim($first_name . ' ' . $last_name);

    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($first_name) || empty($last_name) || empty($company_name)) {
        $error = 'Please fill in all required fields';
    }
    elseif (!$google_signup && (empty($password) || $password !== $confirm_password)) {
        $error = $password !== $confirm_password ? 'Passwords do not match' : 'Password is required';
    }
    elseif (!$google_signup && (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password))) {
        $error = 'Password must be at least 8 characters long and contain at least one letter, one number, and one special character';
    }
    else {
        $pdo = getDB();

        // 1. Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists';
        }
        else {
            // 3. Check if subdomain exists
            $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ?");
            $stmt->execute([$subdomain]);
            if ($stmt->fetch()) {
                $error = 'Company name already taken. Please choose a different name.';
            }
            else {
                // 4. Create tenant & user
                try {
                    $pdo->beginTransaction();

                    // 2. Create tenant
                    $stmt = $pdo->prepare("
                        INSERT INTO tenants (name, subdomain, plan, fleet_size, phone, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$company_name, $subdomain, $final_plan, $fleet_size, $phone]);
                    $tenant_id = $pdo->lastInsertId();

                    // 3. Create admin user for this tenant
                    $hashed_password = $google_signup ? null : password_hash($password, PASSWORD_DEFAULT);
                    $google_id = $google_signup ? ($google_id ?? null) : null;
                    $stmt = $pdo->prepare("
                        INSERT INTO users (tenant_id, email, password, full_name, google_id, role, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, 'admin', NOW(), NOW())
                    ");
                    $stmt->execute([$tenant_id, $email, $hashed_password, $full_name, $google_id]);
                    $user_id = $pdo->lastInsertId();

                    // Create tenant settings
                    $stmt = $pdo->prepare("INSERT INTO tenant_settings (tenant_id) VALUES (?)");
                    $stmt->execute([$tenant_id]);

                    $pdo->commit();
                    $success = true;

                    // Send Welcome Email
                    sendWelcomeEmail($email, $first_name);

                    // Auto-login the user
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['tenant_id'] = $tenant_id;
                    $_SESSION['role'] = 'admin';
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['ob_reset'] = true;

                    // Redirect to dashboard
                    header('Location: /dashboard/');
                    exit;

                }
                catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to create account: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create An Account |
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <script src="/app/custom-select.js" defer></script>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-white h-screen overflow-hidden font-light">
    <div class="flex h-full">
        <!-- Left Side: Signup Form (Light Mode) -->
        <div class="w-full lg:w-[45%] xl:w-[35%] flex flex-col justify-center p-6 sm:p-10 lg:p-16 bg-white z-10">
            <div class="max-w-md w-full mx-auto">
                <div class="mb-8">
                    <div class="mb-8">
                        <a href="/"><img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro"
                                class="h-14 w-auto -ml-3"></a>
                    </div>
                    <h1 class="text-3xl font-light text-slate-900 tracking-tight mb-2">Create An Account</h1>
                    <p class="text-slate-400 font-light tracking-tight text-base">Get set up in minutes with Fleet
                        Rental Pro.
                    </p>
                </div>

                <?php if ($success): ?>
                <div class="text-center bg-slate-50 border border-slate-100 p-8 rounded-2xl animate-fadeIn">
                    <div
                        class="w-16 h-16 bg-green-500 rounded-xl flex items-center justify-center text-white mx-auto mb-4 shadow-lg shadow-green-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-light text-slate-900 mb-2">Account Created!</h2>
                    <p class="text-slate-500 font-light mb-6 text-sm">Your rental company is being provisioned...</p>
                    <a href="/auth/login.php"
                        class="inline-block w-full bg-slate-900 text-white py-3 rounded-xl font-light tracking-widest shadow-xl hover:bg-black transition-all uppercase text-sm">Go
                        to login</a>
                </div>
                <?php
else: ?>

                <?php if ($is_paid): ?>
                <div class="bg-blue-600 text-white p-4 rounded-xl mb-6 flex items-center gap-3 shadow-md">
                    <div class="bg-white/20 p-1.5 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2.5"></path>
                        </svg></div>
                    <div>
                        <div class="text-[9px] font-light uppercase tracking-widest opacity-70">Payment Verified</div>
                        <div class="text-xs font-light">
                            <?= strtoupper($plan_slug)?> Plan Active
                        </div>
                    </div>
                </div>
                <?php
    endif; ?>

                <?php if ($error): ?>
                <div
                    class="bg-red-50 text-red-600 p-4 rounded-xl text-xs font-light mb-6 border border-red-50 flex items-center gap-3 animate-shake">
                    <div
                        class="bg-red-600 text-white w-5 h-5 rounded flex items-center justify-center flex-shrink-0 font-light">
                        !</div>
                    <?= htmlspecialchars($error)?>
                </div>
                <?php
    endif; ?>

                <!-- Google Sign-In Option -->
                <div class="mb-6">
                    <button type="button" onclick="showGoogleSignupModal()" 
                            class="w-full flex items-center justify-center gap-3 py-3 border border-slate-100 rounded-xl font-light text-slate-700 hover:bg-slate-50 transition-all active:scale-[0.98] group text-sm">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                        </svg>
                        Sign up with Google
                    </button>

                    <div class="relative flex items-center py-2">
                        <div class="flex-grow border-t border-slate-50"></div>
                        <span class="flex-shrink mx-4 text-[9px] font-light uppercase tracking-[0.2em] text-slate-300">or
                            sign up with email</span>
                        <div class="flex-grow border-t border-slate-50"></div>
                    </div>
                </div>

                <form method="POST" class="space-y-3" id="signupForm">
                    <input type="hidden" name="plan" value="<?= $plan_slug?>">

                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" name="first_name" required placeholder="First name"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 px-4 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                        <input type="text" name="last_name" required placeholder="Last name"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 px-4 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" id="company_name" name="company_name" required placeholder="Rental Company"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 px-4 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm"
                            oninput="updateSubdomainPreview()">
                        <input type="email" name="email" required placeholder="Work email"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 px-4 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <input type="tel" name="phone" placeholder="Phone number"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 px-4 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                        <select name="fleet_size" required
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 px-4 text-slate-900 font-light outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                            <option value="" disabled selected>Fleet size</option>
                            <option value="1-5">1-5 vehicles</option>
                            <option value="5-10">5-10 vehicles</option>
                            <option value="10-20">10-20 vehicles</option>
                            <option value="20+">20+ vehicles</option>
                        </select>
                    </div>

                    <div class="relative group">
                        <input type="password" name="password" id="password" required placeholder="Set Password"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 pl-4 pr-12 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                        <button type="button" onclick="togglePassword('password')"
                            class="absolute h-[47px] inset-y-0 right-0 pr-4 flex items-center text-slate-300 hover:text-slate-900 transition-colors">
                            <svg id="password-eye-open-icon" class="h-4 w-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M2.036 12.322a1.012 1.012 0 010-.644C3.67 8.5 7.652 5 12 5c4.348 0 8.332 3.5 9.964 6.678.118.23.118.492 0 .722C20.332 15.5 16.348 19 12 19c-4.348 0-8.332-3.5-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="password-eye-closed-icon" class="h-4 w-4 hidden" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <!-- Password strength tracking -->
                    <div id="pwd-strength-container"
                        class="space-y-1.5 mt-2 mb-3 hidden opacity-0 transition-opacity duration-300">
                        <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden flex gap-1">
                            <div id="pwd-bar-1" class="h-full flex-1 bg-slate-200 transition-all duration-300"></div>
                            <div id="pwd-bar-2" class="h-full flex-1 bg-slate-200 transition-all duration-300"></div>
                            <div id="pwd-bar-3" class="h-full flex-1 bg-slate-200 transition-all duration-300"></div>
                            <div id="pwd-bar-4" class="h-full flex-1 bg-slate-200 transition-all duration-300"></div>
                        </div>
                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-slate-400">
                            <span id="pwd-req-min" class="flex items-center gap-1 transition-colors"><svg
                                    class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7" />
                                </svg>8+ chars</span>
                            <span id="pwd-req-letter" class="flex items-center gap-1 transition-colors"><svg
                                    class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7" />
                                </svg>1 letter</span>
                            <span id="pwd-req-num" class="flex items-center gap-1 transition-colors"><svg
                                    class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7" />
                                </svg>1 number</span>
                            <span id="pwd-req-special" class="flex items-center gap-1 transition-colors"><svg
                                    class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7" />
                                </svg>1 special</span>
                        </div>
                    </div>

                    <div class="relative group mt-3">
                        <input type="password" name="confirm_password" id="confirm_password" required
                            placeholder="Confirm Password"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 pl-4 pr-12 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                        <button type="button" onclick="togglePassword('confirm_password')"
                            class="absolute h-[47px] inset-y-0 right-0 pr-4 flex items-center text-slate-300 hover:text-slate-900 transition-colors">
                            <svg id="confirm_password-eye-open-icon" class="h-4 w-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M2.036 12.322a1.012 1.012 0 010-.644C3.67 8.5 7.652 5 12 5c4.348 0 8.332 3.5 9.964 6.678.118.23.118.492 0 .722C20.332 15.5 16.348 19 12 19c-4.348 0-8.332-3.5-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="confirm_password-eye-closed-icon" class="h-4 w-4 hidden" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <script>
                        function togglePassword(id) {
                            const input = document.getElementById(id);
                            const openIcon = document.getElementById(id + '-eye-open-icon');
                            const closedIcon = document.getElementById(id + '-eye-closed-icon');

                            if (input.type === 'password') {
                                input.type = 'text';
                                openIcon.classList.add('hidden');
                                closedIcon.classList.remove('hidden');
                            } else {
                                input.type = 'password';
                                openIcon.classList.remove('hidden');
                                closedIcon.classList.add('hidden');
                            }
                        }

                        document.getElementById('password').addEventListener('input', function (e) {
                            const val = e.target.value;
                            const container = document.getElementById('pwd-strength-container');

                            // Toogle visibility
                            if (val.length > 0) {
                                container.classList.remove('hidden');
                                // Small delay to allow display to apply before opacity transition
                                setTimeout(() => container.classList.remove('opacity-0'), 10);
                            } else {
                                container.classList.add('opacity-0');
                                setTimeout(() => {
                                    if (e.target.value.length === 0) container.classList.add('hidden');
                                }, 300);
                            }

                            const hasMin = val.length >= 8;
                            const hasLetter = /[A-Za-z]/.test(val);
                            const hasNum = /[0-9]/.test(val);
                            const hasSpecial = /[^A-Za-z0-9]/.test(val);

                            // Update text colors
                            document.getElementById('pwd-req-min').className = hasMin ? 'flex items-center gap-1 transition-colors text-green-500' : 'flex items-center gap-1 transition-colors text-slate-400';
                            document.getElementById('pwd-req-letter').className = hasLetter ? 'flex items-center gap-1 transition-colors text-green-500' : 'flex items-center gap-1 transition-colors text-slate-400';
                            document.getElementById('pwd-req-num').className = hasNum ? 'flex items-center gap-1 transition-colors text-green-500' : 'flex items-center gap-1 transition-colors text-slate-400';
                            document.getElementById('pwd-req-special').className = hasSpecial ? 'flex items-center gap-1 transition-colors text-green-500' : 'flex items-center gap-1 transition-colors text-slate-400';

                            // Calculate score (0-4)
                            const score = (hasMin ? 1 : 0) + (hasLetter ? 1 : 0) + (hasNum ? 1 : 0) + (hasSpecial ? 1 : 0);

                            // Update bars
                            const bars = [
                                document.getElementById('pwd-bar-1'),
                                document.getElementById('pwd-bar-2'),
                                document.getElementById('pwd-bar-3'),
                                document.getElementById('pwd-bar-4')
                            ];

                            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
                            const activeColor = score > 0 ? colors[score - 1] : 'bg-slate-200';

                            bars.forEach((bar, index) => {
                                // Reset classes
                                bar.className = 'h-full flex-1 transition-all duration-300 ' + (index < score ? activeColor : 'bg-slate-200');
                            });
                        });
                    </script>

                    <div class="pt-2 px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <div class="flex flex-col min-w-0">
                            <span class="text-[9px] uppercase tracking-widest text-slate-400 font-medium">Your booking
                                website will be</span>
                            <span id="subdomain-text" class="text-xs font-medium text-slate-700 truncate">prestige.
                                <?= ROOT_DOMAIN?>
                            </span>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-slate-900 text-white py-3 rounded-xl font-light tracking-widest shadow-lg shadow-slate-200 hover:bg-black transition-all active:scale-[0.98] text-sm mt-2 uppercase">
                        Create Your Account
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-slate-50 text-center">
                    <p class="text-[12px] text-slate-400 font-light tracking-tight">
                        Employee with account? <a href="/auth/login.php"
                            class="text-slate-900 hover:underline underline-offset-4 decoration-1">Sign in here</a>
                    </p>
                </div>
                <?php
endif; ?>

                <div
                    class="mt-20 pt-8 border-t border-slate-100 text-[12px] text-slate-400 flex justify-between items-center font-light">
                    <span>&copy;
                        <?= date('Y')?> Fleet Rental Pro
                    </span>
                    <div class="flex gap-6">
                        <a href="/terms.php" target="_blank" rel="noopener noreferrer"
                            class="hover:text-slate-900 transition-colors uppercase tracking-widest font-normal">Terms</a>
                        <a href="/privacy.php" target="_blank" rel="noopener noreferrer"
                            class="hover:text-slate-900 transition-colors uppercase tracking-widest font-normal">Privacy</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Luxury Car Image -->
        <div class="hidden lg:block lg:flex-1 relative overflow-hidden bg-slate-100 border-l border-slate-50">
            <img src="/assets/images/luxury_cars.png" class="absolute inset-0 w-full h-full object-cover opacity-100"
                alt="Luxury Cars">
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-white/10"></div>
        </div>
    </div>

    <script>
        function updateSubdomainPreview() {
            const companyName = document.getElementById('company_name').value;
            const subdomainText = document.getElementById('subdomain-text');

            let subdomain = companyName.toLowerCase().replace(/[^a-z0-9]+/g, '');

            if (subdomain === '') {
                subdomain = 'prestige';
                subdomainText.classList.remove('text-slate-900');
                subdomainText.classList.add('text-slate-400');
            } else {
                subdomainText.classList.remove('text-slate-400');
                subdomainText.classList.add('text-slate-900');
            }
            subdomainText.textContent = subdomain + '.<?= ROOT_DOMAIN?>';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const companyInput = document.getElementById('company_name');
            if (companyInput && companyInput.value) {
                updateSubdomainPreview();
            }
        });
    </script>

    <!-- Google Signup Modal -->
    <div id="googleSignupModal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Complete Your Registration</h3>
                    <button onclick="closeGoogleSignupModal()" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <form id="googleSignupForm" class="p-6 space-y-4">
                <div class="space-y-2">
                    <p class="text-sm text-gray-600">Please provide a few more details to complete your registration:</p>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                        <input type="text" name="company_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" name="phone" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fleet Size</label>
                        <select name="fleet_size" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select fleet size</option>
                            <option value="1-5">1-5 vehicles</option>
                            <option value="5-10">5-10 vehicles</option>
                            <option value="10-20">10-20 vehicles</option>
                            <option value="20+">20+ vehicles</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeGoogleSignupModal()" 
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        Complete Registration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Existing updateSubdomainPreview function
        function updateSubdomainPreview() {
            const companyName = document.getElementById('company_name').value;
            const subdomainText = document.getElementById('subdomain-text');

            let subdomain = companyName.toLowerCase().replace(/[^a-z0-9]+/g, '');

            if (subdomain === '') {
                subdomainText.textContent = 'prestige.localhost';
            } else {
                subdomainText.textContent = subdomain + '.localhost';
            }
        }

        // Google signup modal functions
        let googleUserData = null;

        function showGoogleSignupModal() {
            document.getElementById('googleSignupModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Redirect to Google OAuth
            window.location.href = '/auth/google-oauth.php?signup=true';
        }

        function closeGoogleSignupModal() {
            document.getElementById('googleSignupModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Handle Google signup form submission
        document.getElementById('googleSignupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!googleUserData) {
                alert('Google authentication required. Please try again.');
                return;
            }

            const formData = new FormData(this);
            formData.append('google_signup', 'true');
            formData.append('email', googleUserData.email);
            formData.append('first_name', googleUserData.given_name);
            formData.append('last_name', googleUserData.family_name);
            formData.append('plan', '<?= $plan_slug ?>');

            fetch('/auth/signup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Check if response contains error message
                if (html.includes('bg-red-50')) {
                    // Reload page to show errors
                    window.location.reload();
                } else {
                    // Success - redirect to dashboard
                    window.location.href = '/dashboard/';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Registration failed. Please try again.');
            });
        });

        // Check if returning from Google OAuth with signup intent
        <?php if (isset($_GET['signup']) && $_GET['signup'] === 'true'): ?>
        // Auto-open modal if returning from Google OAuth
        window.addEventListener('load', function() {
            // Check if we have Google user data in session
            fetch('/auth/check-google-session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.google_user) {
                        googleUserData = data.google_user;
                        // Auto-open modal immediately
                        showGoogleSignupModal();
                    }
                });
        });
        <?php endif; ?>
        
            </script>
</body>

</html>