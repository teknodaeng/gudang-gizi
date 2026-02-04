<?php
/**
 * Login Page
 * Gudang Gizi - Sistem Manajemen Stok
 * 
 * Security Features:
 * - CSRF Token Validation
 * - Rate Limiting (5 attempts per 5 minutes)
 * - Password Hash Verification
 * - Automatic Password Rehashing
 */

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: /gudang-gizi/index.php');
    exit;
}

$error = '';
$csrfToken = generateCsrfToken();

// Rate limiting key based on IP
$rateLimitKey = 'login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
        logSecurityEvent('csrf_failed', 'Login form CSRF validation failed');
    }
    // Check rate limit
    elseif (!checkRateLimit($rateLimitKey, 5, 300)) { // 5 attempts per 5 minutes
        $remaining = 300 - (time() - $_SESSION['rate_limit_' . md5($rateLimitKey)]['window_start']);
        $error = "Terlalu banyak percobaan login. Silakan tunggu " . ceil($remaining / 60) . " menit.";
        logSecurityEvent('rate_limit', 'Login rate limit exceeded');
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi!';
        } else {
            $user = fetchOne("SELECT * FROM users WHERE username = ? AND is_active = 1", [$username], 's');

            if ($user && password_verify($password, $user['password'])) {
                // Check if password needs rehashing
                if (needsRehash($user['password'])) {
                    $newHash = hashPassword($password);
                    query("UPDATE users SET password = ? WHERE id = ?", [$newHash, $user['id']], 'si');
                }

                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Update last login
                query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']], 'i');

                // Set session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role' => $user['role'],
                    'email' => $user['email']
                ];
                $_SESSION['session_created'] = time();

                // Log successful login
                logActivity($user['id'], 'login', 'User logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                logSecurityEvent('login_success', 'User logged in', $user['id']);

                header('Location: /gudang-gizi/index.php');
                exit;
            } else {
                $error = 'Username atau password salah!';
                logSecurityEvent('login_failed', "Failed login attempt for username: $username");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gudang Gizi</title>
    <meta name="description" content="Login ke Sistem Manajemen Stok Gudang Gizi">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .glow {
            box-shadow: 0 0 60px rgba(34, 197, 94, 0.3);
        }

        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3);
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <!-- Background Decorations -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-20 w-72 h-72 bg-primary-500/10 rounded-full blur-3xl float-animation"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl float-animation"
            style="animation-delay: -3s;"></div>
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-primary-500/5 rounded-full blur-3xl">
        </div>
    </div>

    <div class="relative z-10 w-full max-w-md">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 shadow-2xl glow mb-4">
                <i class="fas fa-warehouse text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Gudang Gizi</h1>
            <p class="text-gray-400">Sistem Manajemen Stok & Inventory</p>
            <p class="text-sm text-gray-500 mt-1">Dapur Makan Gizi Gratis</p>
        </div>

        <!-- Login Card -->
        <div class="glass rounded-2xl p-8 shadow-2xl">
            <h2 class="text-xl font-semibold text-white text-center mb-6">Masuk ke Akun Anda</h2>

            <?php if ($error): ?>
                <div
                    class="bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        <i class="fas fa-user mr-2 text-primary-400"></i>Username
                    </label>
                    <input type="text" name="username" required
                        class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:border-primary-500 input-glow transition-all"
                        placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        <i class="fas fa-lock mr-2 text-primary-400"></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:border-primary-500 input-glow transition-all"
                            placeholder="Masukkan password">
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-primary-500 focus:ring-primary-500/30">
                        <span class="text-sm text-gray-400">Ingat saya</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg hover:shadow-primary-500/30">
                    <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-700">
                <p class="text-sm text-gray-400 text-center mb-4">Demo Accounts:</p>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div class="bg-slate-800/50 rounded-lg p-2 text-center">
                        <div class="text-primary-400 font-medium">Owner</div>
                        <div class="text-gray-500">owner / owner123</div>
                    </div>
                    <div class="bg-slate-800/50 rounded-lg p-2 text-center">
                        <div class="text-blue-400 font-medium">Admin</div>
                        <div class="text-gray-500">admin / admin123</div>
                    </div>
                    <div class="bg-slate-800/50 rounded-lg p-2 text-center">
                        <div class="text-orange-400 font-medium">Gudang</div>
                        <div class="text-gray-500">gudang / gudang123</div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-gray-500 text-sm mt-6">
            &copy;
            <?= date('Y') ?> Gudang Gizi - Dapur Makan Gizi Gratis
        </p>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>