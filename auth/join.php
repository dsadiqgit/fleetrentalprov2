<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = '';
$invitation = null;

if (empty($token)) {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Verify token - simplified for debugging
$stmt = $pdo->prepare("SELECT * FROM team_invitations WHERE token = ? AND status = 'pending'");
$stmt->execute([$token]);
$invitation = $stmt->fetch();

if ($invitation) {
    // Get tenant name separately
    $stmtT = $pdo->prepare("SELECT name FROM tenants WHERE id = ?");
    $stmtT->execute([$invitation['tenant_id']]);
    $t = $stmtT->fetch();
    if ($t) {
        $invitation['tenant_name'] = $t['name'];
    }
}

if (!$invitation) {
    $error = 'This invitation link is invalid or has expired.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $invitation) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create user
            $hashed_password = hashPassword($password);
            $stmt = $pdo->prepare("INSERT INTO users (tenant_id, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $invitation['tenant_id'],
                $invitation['email'],
                $hashed_password,
                $full_name,
                $invitation['role']
            ]);
            
            // Update invitation status
            $stmt = $pdo->prepare("UPDATE team_invitations SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$invitation['id']]);
            
            $pdo->commit();
            
            // Auto login
            $stmtUser = $pdo->prepare("SELECT id, tenant_id, role, email, full_name FROM users WHERE id = ?");
            $stmtUser->execute([$pdo->lastInsertId()]);
            $user = $stmtUser->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['tenant_id'] = $user['tenant_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                
                $success = 'Account created successfully! Welcome to the team.';
                header('refresh:2;url=/dashboard/');
            } else {
                $error = 'Account created, but failed to log in automatically. Please log in manually.';
                header('refresh:3;url=/auth/login.php');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create account: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Team - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-luxury {
            background-image: url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-luxury min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Logo -->
            <div class="flex items-center justify-center mb-8">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-black rounded flex items-center justify-center text-white font-bold text-xl">⚡</div>
                    <span class="text-2xl font-bold text-gray-900">Fleet Rental Pro</span>
                </div>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-2">Join the Team</h2>
            <?php if ($invitation): ?>
            <p class="text-gray-500 text-center mb-8">You've been invited to join <strong><?= htmlspecialchars($invitation['tenant_name']) ?></strong></p>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6 text-sm">
                <?= $error ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
                <?= $success ?>
                <p class="mt-2">Redirecting to login page...</p>
            </div>
            <?php endif; ?>
            
            <?php if ($invitation && !$success): ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Email Address</label>
                    <input type="text" value="<?= htmlspecialchars($invitation['email']) ?>" disabled
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed text-sm">
                </div>

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-900 mb-2">Full Name</label>
                    <input id="full_name" name="full_name" type="text" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition text-sm" placeholder="Enter your full name" value="<?= $_POST['full_name'] ?? '' ?>">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-900 mb-2">Create Password</label>
                    <input id="password" name="password" type="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition text-sm" placeholder="At least 8 characters">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-900 mb-2">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition text-sm" placeholder="Repeat password">
                </div>

                <button type="submit"
                        class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition shadow-lg mt-2">
                    Accept & Create Account
                </button>
            </form>
            <?php endif; ?>

            <?php if (!$invitation && !$success): ?>
            <div class="text-center py-4">
                <a href="/auth/login.php" class="text-sm font-bold text-gray-900 hover:text-gray-700 underline">Back to Login</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
