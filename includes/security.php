<?php
/**
 * Security Helper Functions
 * Gudang Gizi - Sistem Manajemen Stok
 * 
 * Features:
 * - CSRF Token Generation & Validation
 * - Password Hashing & Verification
 * - Input Sanitization
 * - Rate Limiting
 * - XSS Prevention
 */

/**
 * Generate CSRF Token and store in session
 * @return string The CSRF token
 */
function generateCsrfToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Generate token if not exists or expired (1 hour)
    if (
        !isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > 3600
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF Token HTML input field
 * @return string HTML hidden input field
 */
function csrfField()
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF Token
 * @param string $token Token from form submission
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Use timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check CSRF and redirect if invalid
 * @param string $redirectUrl URL to redirect on failure
 * @param string $message Error message
 */
function verifyCsrf($redirectUrl = null, $message = 'Invalid security token. Please try again.')
{
    if (!validateCsrfToken()) {
        if ($redirectUrl) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            http_response_code(403);
            die(json_encode(['error' => $message]));
        }
    }
}

/**
 * Hash password using bcrypt (PHP's password_hash)
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Check if password hash needs rehashing
 * @param string $hash Current hash
 * @return bool True if needs rehashing
 */
function needsRehash($hash)
{
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Sanitize input string - prevent XSS
 * @param string $input Input string
 * @param bool $allowHtml Allow some HTML tags
 * @return string Sanitized string
 */
function sanitizeInput($input, $allowHtml = false)
{
    if ($input === null) {
        return '';
    }

    // Remove null bytes
    $input = str_replace(chr(0), '', $input);

    if ($allowHtml) {
        // Allow only safe tags
        $input = strip_tags($input, '<p><br><strong><em><ul><ol><li><a>');
    } else {
        $input = strip_tags($input);
    }

    // Convert special characters to HTML entities
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize for output (already sanitized data)
 * @param string $input Input string
 * @return string Safe for HTML output
 */
function escapeHtml($input)
{
    if ($input === null) {
        return '';
    }
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Simple rate limiting
 * @param string $key Unique identifier (e.g., IP + action)
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $windowSeconds Time window in seconds
 * @return bool True if within limit, false if exceeded
 */
function checkRateLimit($key, $maxAttempts = 5, $windowSeconds = 300)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $rateLimitKey = 'rate_limit_' . md5($key);
    $now = time();

    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [
            'attempts' => 0,
            'window_start' => $now
        ];
    }

    $data = &$_SESSION[$rateLimitKey];

    // Reset if window expired
    if (($now - $data['window_start']) > $windowSeconds) {
        $data['attempts'] = 0;
        $data['window_start'] = $now;
    }

    $data['attempts']++;

    return $data['attempts'] <= $maxAttempts;
}

/**
 * Get remaining rate limit attempts
 * @param string $key Unique identifier
 * @param int $maxAttempts Maximum attempts allowed
 * @return int Remaining attempts
 */
function getRateLimitRemaining($key, $maxAttempts = 5)
{
    $rateLimitKey = 'rate_limit_' . md5($key);

    if (!isset($_SESSION[$rateLimitKey])) {
        return $maxAttempts;
    }

    return max(0, $maxAttempts - $_SESSION[$rateLimitKey]['attempts']);
}

/**
 * Validate and sanitize integer input
 * @param mixed $input Input value
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return int|null Sanitized integer or null if invalid
 */
function sanitizeInt($input, $min = null, $max = null)
{
    $value = filter_var($input, FILTER_VALIDATE_INT);

    if ($value === false) {
        return null;
    }

    if ($min !== null && $value < $min) {
        return null;
    }

    if ($max !== null && $value > $max) {
        return null;
    }

    return $value;
}

/**
 * Validate and sanitize float input
 * @param mixed $input Input value
 * @return float|null Sanitized float or null if invalid
 */
function sanitizeFloat($input)
{
    $value = filter_var($input, FILTER_VALIDATE_FLOAT);
    return $value !== false ? $value : null;
}

/**
 * Validate email address
 * @param string $email Email address
 * @return string|null Sanitized email or null if invalid
 */
function sanitizeEmail($email)
{
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    return $email !== false ? $email : null;
}

/**
 * Validate URL
 * @param string $url URL to validate
 * @return string|null Sanitized URL or null if invalid
 */
function sanitizeUrl($url)
{
    $url = filter_var($url, FILTER_VALIDATE_URL);
    return $url !== false ? $url : null;
}

/**
 * Generate secure random string
 * @param int $length Length of string
 * @return string Random string
 */
function generateSecureToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate filename to prevent directory traversal
 * @param string $filename Filename to validate
 * @return bool True if safe
 */
function isSafeFilename($filename)
{
    // Remove any path components
    $basename = basename($filename);

    // Check for directory traversal
    if (preg_match('/\.\./', $filename)) {
        return false;
    }

    // Check for null bytes
    if (strpos($filename, chr(0)) !== false) {
        return false;
    }

    // Only allow alphanumeric, dash, underscore, and dot
    return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $basename);
}

/**
 * Set secure headers
 */
function setSecureHeaders()
{
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS filter
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob:;");
}

/**
 * Log security event
 * @param string $event Event type
 * @param string $details Event details
 * @param int|null $userId User ID if applicable
 */
function logSecurityEvent($event, $details = '', $userId = null)
{
    global $conn;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    // Log to database if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'security_log'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO security_log (event_type, details, ip_address, user_agent, user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('ssssi', $event, $details, $ip, $userAgent, $userId);
        $stmt->execute();
    }

    // Also log to file
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logMessage = date('Y-m-d H:i:s') . " | $event | $ip | User:$userId | $details\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
?>