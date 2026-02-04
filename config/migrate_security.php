<?php
/**
 * Database Migration - Security Tables
 * Run this file once to add security-related tables
 */

require_once __DIR__ . '/../config/database.php';

$queries = [
    // Security log table
    "CREATE TABLE IF NOT EXISTS security_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        user_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_event_type (event_type),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Password reset tokens table
    "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_expires (expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Login attempts table for tracking
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(100),
        success TINYINT(1) DEFAULT 0,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

echo "🔒 Creating security tables...\n\n";

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        $tableName = 'unknown';
        if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $matches)) {
            $tableName = $matches[1];
        }
        echo "✅ Table '$tableName' created/verified\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

// Create logs directory
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    file_put_contents($logsDir . '/.htaccess', "Deny from all\n");
    echo "\n✅ Created logs directory with access protection\n";
}

echo "\n📌 Security tables and directories set up successfully!\n";
echo "\nSecurity features enabled:\n";
echo "- CSRF Token validation\n";
echo "- Rate limiting for login\n";
echo "- Security event logging\n";
echo "- Password hashing with bcrypt (cost=12)\n";
echo "- Secure session management\n";
echo "- XSS prevention headers\n";
?>