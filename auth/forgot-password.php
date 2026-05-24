<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$pdo = getDB();
$error = '';
$success = '';

// Create password_resets table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        INDEX idx_token (token),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
catch (Exception $e) {
// Table might already exist
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    }
    else {
        // Check if user exists within the current tenant context
        $query = "SELECT id, full_name, tenant_id FROM users WHERE email = ?";
        $params = [$email];
        
        if (IS_SUBDOMAIN) {
            $query .= " AND tenant_id = ?";
            $params[] = CURRENT_TENANT_ID;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $user = $stmt->fetch();

        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires_at]);

            // Send reset email
            if (sendPasswordResetEmail($email, $user['full_name'], $token)) {
                $success = 'Password reset instructions have been sent to your email address.';
            }
            else {
                $error = 'Failed to send reset email. Please try again later.';
            }
        }
        else {
            // Don't reveal if email exists or not for security
            $success = 'If an account exists with this email, you will receive password reset instructions.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery |
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-white h-screen overflow-hidden font-light">
    <div class="flex h-full">
        <!-- Left Side: Recovery Form (Light Mode) -->
        <div
            class="w-full lg:w-[45%] xl:w-[35%] flex flex-col justify-center p-8 sm:p-12 lg:p-20 bg-white z-10 overflow-y-auto">
            <div class="max-w-md w-full mx-auto">
                <div class="mb-12">
                    <div class="mb-10">
                        <a href="/"><img src="/assets/images/fleet-logo-black.svg" alt="Fleet Rental Pro"
                                class="h-20 w-auto -ml-4"></a>
                    </div>
                    <h1 class="text-4xl font-light text-slate-900 tracking-tight mb-4">Account Recovery</h1>
                    <p class="text-slate-500 font-light tracking-tight text-lg">Retrieve access to your elite fleet
                        operations system.</p>
                </div>

                <?php if ($error): ?>
                <div
                    class="bg-red-50 text-red-600 p-5 rounded-2xl text-xs font-light mb-8 border border-red-50 flex items-center gap-4 animate-shake">
                    <div
                        class="bg-red-600 text-white w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 font-light">
                        !</div>
                    <?= htmlspecialchars($error)?>
                </div>
                <?php
endif; ?>

                <?php if ($success): ?>
                <div
                    class="bg-green-50 text-green-700 p-6 rounded-2xl text-sm font-light mb-8 border border-green-50 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <div
                            class="bg-green-600 text-white w-5 h-5 rounded-md flex items-center justify-center font-light">
                            ✓</div>
                        Request Received
                    </div>
                    <p class="font-light opacity-80 text-xs tracking-tight">
                        <?= htmlspecialchars($success)?>
                    </p>
                </div>
                <?php
endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="group">
                        <label
                            class="block text-[11px] font-light uppercase tracking-[0.2em] text-slate-400 mb-2 group-focus-within:text-slate-900 transition-colors">Registered
                            Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-300 group-focus-within:text-slate-900 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        stroke-width="2.5"></path>
                                </svg>
                            </div>
                            <input type="email" name="email" required placeholder="name@company.com"
                                class="w-full bg-slate-50 border border-slate-50 rounded-2xl py-3 pl-12 pr-4 text-slate-900 font-light placeholder-slate-300 outline-none focus:border-slate-900 focus:bg-white transition-all shadow-sm"
                                value="<?= htmlspecialchars($_POST['email'] ?? '')?>">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-slate-900 text-white py-3 rounded-2xl font-light tracking-widest shadow-2xl shadow-slate-200 hover:bg-black transition-all active:scale-[0.98] text-sm mt-4 uppercase">
                        Send Recovery Link
                    </button>

                    <div class="text-center mt-10">
                        <a href="/auth/login.php"
                            class="text-xs font-light text-slate-400 hover:text-slate-900 uppercase tracking-widest transition-colors flex items-center justify-center gap-2 group">
                            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="3"></path>
                            </svg>
                            Back to Secure Login
                        </a>
                    </div>
                </form>

                <div
                    class="mt-20 pt-10 border-t border-slate-50 text-[10px] text-slate-300 font-black uppercase tracking-[0.25em] flex justify-between items-center">
                    <span>&copy;
                        <?= date('Y')?> Fleet Rental Pro
                    </span>
                    <div class="flex gap-6">
                        <a href="/terms.php" target="_blank" rel="noopener noreferrer"
                            class="hover:text-slate-900 transition-colors">Terms</a>
                        <a href="/privacy.php" target="_blank" rel="noopener noreferrer"
                            class="hover:text-slate-900 transition-colors">Privacy</a>
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
</body>

</html>