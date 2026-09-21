<?php
/**
 * SMM Panel - Core Helper Functions
 */

require_once __DIR__ . '/../db.php';

/**
 * HTML Escaping (XSS Prevention)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or get CSRF Token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render Hidden CSRF Input Field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF Token
 */
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Security Error: Invalid or missing CSRF token. Please refresh and try again.');
        }
    }
}

/**
 * Get Setting from Database
 */
function get_setting(string $key, ?string $default = null): ?string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $rows = DB::fetchAll("SELECT setting_key, setting_value FROM settings");
        $settingsCache = [];
        foreach ($rows as $row) {
            $settingsCache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settingsCache[$key] ?? $default;
}

/**
 * Update or Insert Setting
 */
function set_setting(string $key, ?string $value): void {
    DB::query(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

/**
 * Set Flash Message
 */
function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Retrieve and Clear Flash Message
 */
function flash_get(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Safe Redirect
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * JSON Response Output
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Format Date Safely
 */
function format_date(?string $datetime, string $format = 'M d, Y h:i A'): string {
    if (!$datetime) return 'N/A';
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return e($datetime);
    }
}

/**
 * Badge Class Generator for Order Status
 */
function get_status_badge(string $status): string {
    $statusMap = [
        'pending'     => ['class' => 'badge-pending', 'label' => 'Pending'],
        'processing'  => ['class' => 'badge-processing', 'label' => 'Processing'],
        'in_progress' => ['class' => 'badge-inprogress', 'label' => 'In Progress'],
        'completed'   => ['class' => 'badge-completed', 'label' => 'Completed'],
        'partial'     => ['class' => 'badge-partial', 'label' => 'Partial'],
        'canceled'    => ['class' => 'badge-canceled', 'label' => 'Canceled'],
        'open'        => ['class' => 'badge-pending', 'label' => 'Open'],
        'answered'    => ['class' => 'badge-completed', 'label' => 'Answered'],
        'customer_reply' => ['class' => 'badge-inprogress', 'label' => 'User Reply'],
        'closed'      => ['class' => 'badge-canceled', 'label' => 'Closed'],
        'active'      => ['class' => 'badge-completed', 'label' => 'Active'],
        'suspended'   => ['class' => 'badge-canceled', 'label' => 'Suspended'],
    ];

    $info = $statusMap[strtolower($status)] ?? ['class' => 'badge-default', 'label' => ucfirst($status)];
    return '<span class="badge ' . $info['class'] . '">' . e($info['label']) . '</span>';
}
