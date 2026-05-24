<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$valid_token = false;

if (empty($token)) {
    $error = 'Invalid or missing reset token';
} else {
    // Verify token
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = FALSE AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset_request = $stmt->fetch();
    
    if ($reset_request) {
        $valid_token = true;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($password) || strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } else {
                // Update user password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $reset_request['email']]);
                
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
                $stmt->execute([$token]);
                
                $success = 'Your password has been reset successfully. You can now login with your new password.';
                $valid_token = false; // Hide form after successful reset
            }
        }
    } else {
        $error = 'This password reset link is invalid or has expired. Please request a new one.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-white h-screen overflow-hidden font-light">
    <div class="flex h-full">
        <!-- Left Side: Reset Form -->
        <div class="w-full lg:w-[45%] xl:w-[35%] flex flex-col justify-center p-8 sm:p-12 lg:p-20 bg-white z-10 overflow-y-auto">
            <div class="max-w-md w-full mx-auto">
                <div class="mb-12">
                    <div class="mb-10 text-left">
                        <img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro" class="h-12 w-auto -ml-3">
                    </div>
                    <h1 class="text-4xl font-light text-slate-900 tracking-tight mb-4">Reset Password</h1>
                    <p class="text-slate-500 font-light tracking-tight text-lg italic">Authenticate your new security credentials.</p>
                </div>

                <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl text-xs font-light mb-8 border border-red-50 flex items-center gap-3 animate-shake">
                    <div class="bg-red-600 text-white w-5 h-5 rounded flex items-center justify-center flex-shrink-0 font-light">!</div>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="bg-slate-900 text-white p-6 rounded-2xl text-sm font-light mb-8 shadow-xl shadow-slate-200">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="bg-white text-slate-900 w-5 h-5 rounded flex items-center justify-center font-bold text-[10px]">✓</div>
                        Access Restored
                    </div>
                    <p class="opacity-80 font-light text-xs"><?= htmlspecialchars($success) ?></p>
                    <a href="/auth/login.php" class="inline-block mt-4 text-white underline underline-offset-4 decoration-1 hover:text-slate-300 transition-colors uppercase tracking-[0.2em] text-[10px]">Back to Login</a>
                </div>
                <?php endif; ?>

                <?php if ($valid_token && !$success): ?>
                <form method="POST" class="space-y-4">
                    <div class="group relative">
                        <label class="block text-[11px] font-light uppercase tracking-[0.2em] text-slate-400 mb-2 group-focus-within:text-slate-900 transition-all">New Password</label>
                        <input type="password" name="password" id="password" required minlength="8" placeholder="Enter new password"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 pl-4 pr-12 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                        <button type="button" onclick="togglePassword('password', 'eye-open-p', 'eye-closed-p')" class="absolute bottom-3 right-0 pr-4 flex items-center text-slate-300 hover:text-slate-900 transition-colors">
                            <svg id="eye-open-p" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.644C3.67 8.5 7.652 5 12 5c4.348 0 8.332 3.5 9.964 6.678.118.23.118.492 0 .722C20.332 15.5 16.348 19 12 19c-4.348 0-8.332-3.5-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="eye-closed-p" class="h-4 w-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <div class="group relative">
                        <label class="block text-[11px] font-light uppercase tracking-[0.2em] text-slate-400 mb-2 group-focus-within:text-slate-900 transition-all">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="8" placeholder="Verify new password"
                            class="w-full bg-slate-50 border border-slate-100 rounded-lg py-3 pl-4 pr-12 text-slate-900 font-light placeholder-slate-400 outline-none focus:border-slate-900 focus:bg-white transition-all text-sm">
                        <button type="button" onclick="togglePassword('confirm_password', 'eye-open-c', 'eye-closed-c')" class="absolute bottom-3 right-0 pr-4 flex items-center text-slate-300 hover:text-slate-900 transition-colors">
                            <svg id="eye-open-c" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.644C3.67 8.5 7.652 5 12 5c4.478 0 8.332 3.5 9.964 6.678.118.23.118.492 0 .722C20.332 15.5 16.348 19 12 19c-4.348 0-8.332-3.5-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="eye-closed-c" class="h-4 w-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <button type="submit" 
                        class="w-full bg-slate-900 text-white py-3 rounded-xl font-light tracking-widest shadow-lg shadow-slate-200 hover:bg-black transition-all active:scale-[0.98] text-sm mt-4 uppercase">
                        Confirm New Security Key
                    </button>
                </form>
                <?php endif; ?>

                <?php if (!$valid_token && !$success): ?>
                <div class="text-center">
                    <a href="/auth/forgot-password.php" class="w-full bg-slate-50 text-slate-900 py-3 rounded-xl font-light tracking-widest hover:bg-slate-100 transition-all text-xs uppercase inline-block">Request New Reset Link</a>
                </div>
                <?php endif; ?>

                <div class="mt-20 pt-8 border-t border-slate-100 text-[12px] text-slate-400 flex justify-between items-center font-light">
                    <span>&copy; <?= date('Y') ?> Fleet Rental Pro</span>
                    <div class="flex gap-6">
                        <a href="/terms.php" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 transition-colors uppercase tracking-widest font-normal">Terms</a>
                        <a href="/privacy.php" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 transition-colors uppercase tracking-widest font-normal">Privacy</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Luxury Car Image -->
        <div class="hidden lg:block lg:flex-1 relative overflow-hidden bg-slate-100">
            <img src="/assets/images/luxury_cars.png" 
                 class="absolute inset-0 w-full h-full object-cover opacity-100 transition-transform duration-[10000ms] hover:scale-110" 
                 alt="Luxury Cars">
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-white/10"></div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, openIconId, closedIconId) {
            const input = document.getElementById(inputId);
            const openIcon = document.getElementById(openIconId);
            const closedIcon = document.getElementById(closedIconId);
            
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
</body>
</html>
