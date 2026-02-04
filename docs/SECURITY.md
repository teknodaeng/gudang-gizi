# 🔒 Panduan Keamanan - Gudang Gizi

## Fitur Keamanan yang Diimplementasikan

### 1. Password Hashing (Bcrypt)

Semua password di-hash menggunakan `password_hash()` dengan algoritma Bcrypt dan cost factor 12.

```php
// Contoh penggunaan
$hashedPassword = hashPassword('password_plain');
$isValid = verifyPassword('password_plain', $hashedPassword);
```

**Fitur:**

- Secure hashing dengan salt otomatis
- Automatic rehashing jika password lama menggunakan cost rendah
- Timing-safe comparison

### 2. CSRF Token Protection

Semua form yang melakukan POST request dilindungi dengan CSRF token.

```php
// Dalam form HTML
<form method="POST">
    <?= csrfField() ?>
    <!-- atau -->
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
</form>

// Validasi di backend
if (!validateCsrfToken($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}

// Atau gunakan helper
verifyCsrf('/redirect-url', 'Error message');
```

**Fitur:**

- Token berubah setiap 1 jam
- Menggunakan `random_bytes()` untuk generasi aman
- Timing-safe comparison dengan `hash_equals()`

**Form yang sudah dilindungi:**

- ✅ `/modules/auth/login.php` - Form login
- ✅ `/modules/stok_masuk/create.php` - Form stok masuk
- ✅ `/modules/stok_keluar/create.php` - Form stok keluar
- ✅ `/modules/produksi/create.php` - Form produksi
- ✅ `/modules/produksi/detail.php` - Form selesai/batalkan produksi
- ✅ `/modules/master/bahan_form.php` - Form tambah/edit bahan
- ✅ `/modules/master/kategori.php` - Form kategori
- ✅ `/modules/master/supplier.php` - Form supplier
- ✅ `/modules/master/users.php` - Form users
- ✅ `/modules/menu/index.php` - Form tambah/edit/hapus menu
- ✅ `/modules/menu/resep.php` - Form resep (add/edit/delete bahan)
- ✅ `/modules/penerima/index.php` - Form penerima (add/edit/toggle/delete)
- ✅ `/modules/penerima/calculator.php` - Form kalkulator

### 3. Prepared Statements

Semua query database menggunakan prepared statements untuk mencegah SQL Injection.

```php
// AMAN - Menggunakan prepared statement
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId], 'i');

// JANGAN LAKUKAN INI - Vulnerable
$user = $conn->query("SELECT * FROM users WHERE id = " . $_GET['id']);
```

**Fungsi yang tersedia:**

- `query($sql, $params, $types)` - Execute query
- `fetchOne($sql, $params, $types)` - Get single row
- `fetchAll($sql, $params, $types)` - Get all rows
- `insertGetId($sql, $params, $types)` - Insert dan return ID

### 4. Rate Limiting

Login dilindungi dengan rate limiting untuk mencegah brute force attack.

```php
// 5 percobaan per 5 menit
if (!checkRateLimit('login_' . $ip, 5, 300)) {
    die('Terlalu banyak percobaan');
}
```

### 5. Session Security

- Session ID di-regenerate setiap 30 menit
- Cookie dengan flag `httponly` dan `secure`
- Strict mode untuk mencegah session fixation

### 6. HTTP Security Headers

Setiap response menyertakan security headers:

```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; ...
```

### 7. Input Sanitization

Semua input user di-sanitize untuk mencegah XSS.

```php
$cleanInput = sanitizeInput($_POST['data']);
$safeOutput = escapeHtml($data);
```

### 8. Security Event Logging

Semua event keamanan dicatat di database dan file log.

```php
logSecurityEvent('login_failed', 'Failed login for: ' . $username, $userId);
```

**Log disimpan di:**

- Database: tabel `security_log`
- File: `/logs/security.log`

---

## Checklist Keamanan untuk Developer

### Saat membuat form baru:

- [ ] Tambahkan `<?= csrfField() ?>` di dalam form
- [ ] Tambahkan validasi CSRF di handler: `verifyCsrf()`
- [ ] Gunakan `sanitize()` atau `sanitizeInput()` untuk semua input
- [ ] Gunakan prepared statements untuk query database

### Saat menampilkan data:

- [ ] Gunakan `htmlspecialchars()` atau `escapeHtml()` untuk output
- [ ] Jangan tampilkan error database ke user

### Saat upload file:

- [ ] Validasi tipe file dengan whitelist
- [ ] Batasi ukuran file
- [ ] Generate nama file unik
- [ ] Simpan di luar web root jika memungkinkan

### Password:

- [ ] Gunakan `hashPassword()` untuk menyimpan
- [ ] Gunakan `verifyPassword()` untuk verifikasi
- [ ] Jangan simpan password plain text
- [ ] Minimal 8 karakter dengan kombinasi

---

## Konfigurasi Keamanan Tambahan

### File .htaccess (Apache)

```apache
# Lindungi file sensitif
<FilesMatch "\.(php|ini|log|sql)$">
    Deny from all
</FilesMatch>

# Except index.php dan file PHP publik
<FilesMatch "^(index|login)\.php$">
    Allow from all
</FilesMatch>
```

### PHP Configuration (php.ini)

```ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
expose_php = Off
display_errors = Off
log_errors = On
```

---

## Reporting Security Issues

Jika menemukan celah keamanan, harap laporkan ke:

- Email: security@gudanggizi.local
- Jangan publikasikan sebelum diperbaiki

---

_Dokumen ini dibuat: <?= date('Y-m-d') ?>_
_Gudang Gizi - Sistem Manajemen Stok_
