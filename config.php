<?php
// ============================================================
// MASTER SWITCHES — both false now, both true on launch day
// ============================================================
define('SUBSCRIPTION_ENABLED', false);
define('LOGIN_REQUIRED',        false);
define('FREE_TRIAL_DAYS',       7);

// ============================================================
// APP SETTINGS
// ============================================================
define('APP_NAME',    'Meesho Image Generator');
define('APP_URL',     'https://kjpixel.com/apps/meesho-image-generator');
define('APP_VERSION', '1.0.0');

// ============================================================
// DATABASE CREDENTIALS
// ============================================================
define('DB_HOST',    'localhost');
define('DB_NAME',    'u323182147_meesho_tool');
define('DB_USER',    'u323182147_meesho_user');
define('DB_PASS',    'Chennabasappa@123');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// IMAGE PROCESSING CONSTANTS
// ============================================================
define('CANVAS_SIZE',   1000);
define('PRODUCT_SIZE',  860);
define('BORDER_SIZE',   70);
define('JPEG_QUALITY',  90);
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// ============================================================
// PATHS
// ============================================================
define('ROOT_PATH',    __DIR__);
define('UPLOADS_PATH', __DIR__ . '/uploads/');
define('OUTPUTS_PATH', __DIR__ . '/outputs/');
define('STICKERS_PATH',__DIR__ . '/stickers/');

// ============================================================
// DOWNLOAD LIMITS
// ============================================================
define('FREE_DOWNLOAD_LIMIT', 3);
define('RATE_LIMIT_UPLOADS',  5);
define('RATE_LIMIT_WINDOW',   60);

// ============================================================
// API KEYS — empty now, fill on launch day
// ============================================================
define('RAZORPAY_KEY_ID',      '');
define('RAZORPAY_KEY_SECRET',  '');
define('GOOGLE_CLIENT_ID',     '');
define('GOOGLE_CLIENT_SECRET', '');
define('FAST2SMS_API_KEY',     '');

// ============================================================
// SECURITY
// ============================================================
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ============================================================
// SESSION
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure',   1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// ============================================================
// DATABASE
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Service temporarily unavailable.']));
        }
    }
    return $pdo;
}

// ============================================================
// SECURITY HELPERS
// ============================================================
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid request token.']));
    }
}

function isRateLimited(string $ip): bool {
    $key = 'rl_' . md5($ip);
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start' => $now];
    }
    if ($now - $_SESSION[$key]['start'] > RATE_LIMIT_WINDOW) {
        $_SESSION[$key] = ['count' => 0, 'start' => $now];
    }
    $_SESSION[$key]['count']++;
    return $_SESSION[$key]['count'] > RATE_LIMIT_UPLOADS;
}

function generateDownloadToken(string $filename): string {
    $expires = time() + 3600;
    $payload = $filename . '|' . $expires . '|' . session_id();
    $sig     = hash_hmac('sha256', $payload, DB_PASS . APP_VERSION);
    return base64_encode($payload . '|' . $sig);
}

function verifyDownloadToken(string $token): string|false {
    $decoded = base64_decode($token);
    $parts   = explode('|', $decoded);
    if (count($parts) !== 4) return false;
    [$filename, $expires, $sessid, $sig] = $parts;
    if (time() > (int)$expires) return false;
    $payload  = $filename . '|' . $expires . '|' . $sessid;
    $expected = hash_hmac('sha256', $payload, DB_PASS . APP_VERSION);
    return hash_equals($expected, $sig) ? $filename : false;
}

// ============================================================
// SUBSCRIPTION GATE
// ============================================================
function isPro(): bool {
    if (!SUBSCRIPTION_ENABLED) return true;
    return false;
}