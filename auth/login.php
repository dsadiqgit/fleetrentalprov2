<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth-helper.php';
require_once __DIR__ . '/../includes/security-helper.php';

// Rate limit login attempts: 5 per minute
if (!check_rate_limit('login_attempt', 5, 60, false)) {
    $error = 'Too many login attempts. Please try again later.';
}


// Handle local login submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_local'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['password'] && password_verify($password, $user['password'])) {
        // Enforce tenant-specific login
        if (IS_SUBDOMAIN) {
            if ((int)$user['tenant_id'] !== (int)CURRENT_TENANT_ID) {
                $error = 'This account does not belong to this website. Please login at your assigned portal.';
            }
        } elseif ($user['role'] === 'customer') {
            // Customers should not be logging in via the main platform domain
            $error = 'Customer accounts must log in through their specific rental company portal.';
        }

        if (!$error) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['ob_reset'] = true;

            if ($user['role'] === 'customer') {
                header('Location: /dashboard/customer.php');
            }
            else {
                header('Location: /dashboard/');
            }
            exit;
        }
    }
    else if ($user && !$user['password']) {
        $error = 'This account uses Google Sign-In. Please use the "Sign in with Google" button.';
    }
    else {
        $error = 'Invalid email or password.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login |
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-white h-screen overflow-hidden font-light">
    <div class="flex h-full">
        <!-- Left Side: Login Form (Light Mode) -->
        <div class="w-full lg:w-[45%] xl:w-[35%] flex flex-col justify-center p-6 sm:p-10 lg:p-16 bg-white z-10">
            <div class="max-w-md w-full mx-auto">
                <div class="mb-8">
                    <div class="mb-8">
                        <a href="/"><img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro"
                                class="h-14 w-auto -ml-3"></a>
                    </div>
                    <h1 class="text-3xl font-light text-slate-900 tracking-tight mb-2">Secure Portal</h1>
                    <p class="text-slate-400 font-light tracking-tight text-base">Access your elite fleet intelligence.
                    </p>
                </div>

                <!-- Social Sign In -->
                <div class="space-y-3 mb-8">
                    <a href="/auth/google-oauth.php"
                        class="w-full flex items-center justify-center gap-3 py-3 border border-slate-100 rounded-xl font-light text-slate-700 hover:bg-slate-50 transition-all active:scale-[0.98] group text-sm">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
                            <path
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                fill="#4285F4" />
                            <path
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                fill="#34A853" />
                            <path
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                fill="#FBBC05" />
                            <path
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                fill="#EA4335" />
                        </svg>
                        Sign in with Google
                    </a>

                    <div class="relative flex items-center py-2">
                        <div class="flex-grow border-t border-slate-50"></div>
                        <span
                            class="flex-shrink mx-4 text-[9px] font-light uppercase tracking-[0.2em] text-slate-300">or
                            use credentials</span>
                        <div class="flex-grow border-t border-slate-50"></div>
                    </div>
                </div>

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

                <form method="POST" class="space-y-3">
                    <div class="group">
                        <input type="email" name="email" required placeholder="Email Address"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 px-4 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                    </div>

                    <div class="group relative">
                        <input type="password" name="password" id="password" required placeholder="Password"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 pl-4 pr-12 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                        <button type="button" onclick="togglePassword('password')"
                            class="absolute h-[47px] inset-y-0 right-0 pr-4 flex items-center text-slate-300 hover:text-slate-900 transition-colors">
                            <svg id="eye-open-icon" class="h-4 w-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M2.036 12.322a1.012 1.012 0 010-.644C3.67 8.5 7.652 5 12 5c4.348 0 8.332 3.5 9.964 6.678.118.23.118.492 0 .722C20.332 15.5 16.348 19 12 19c-4.348 0-8.332-3.5-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="eye-closed-icon" class="h-4 w-4 hidden" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                        <div class="flex justify-end mt-1">
                            <a href="/auth/forgot-password.php"
                                class="text-[10px] font-light text-slate-300 hover:text-slate-900 transition-colors uppercase tracking-widest">Forgot
                                your password</a>
                        </div>
                    </div>

                    <button type="submit" name="login_local"
                        class="w-full bg-slate-900 text-white py-3 rounded-xl font-light tracking-widest shadow-lg shadow-slate-200 hover:bg-black transition-all active:scale-[0.98] text-sm mt-4 uppercase">
                        Sign In
                    </button>
                </form>

                <script>
                    function togglePassword(id) {
                        const input = document.getElementById(id);
                        const openIcon = document.getElementById('eye-open-icon');
                        const closedIcon = document.getElementById('eye-closed-icon');

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
                </script>
                <hr class="text-black">
                <div class="mt-8 pt-6 border-t border-slate-50 text-center">
                    <p class="text-[12px] text-slate-400 font-light tracking-tight">
                        New rental partner? <a href="/auth/signup.php"
                            class="text-slate-900 hover:underline underline-offset-4 decoration-1 font-normal">Sign
                            Up</a>
                    </p>
                </div>

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
            <img src="/assets/images/luxury_cars.png"
                class="absolute inset-0 w-full h-full object-cover opacity-100 transition-transform duration-[10000ms] hover:scale-110"
                alt="Luxury Cars">
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-white/10"></div>
        </div>
    </div>
</body>

</html>