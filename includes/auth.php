<?php
/**
 * SMM Panel - Authentication & Authorization Manager
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private static ?array $currentUser = null;

    /**
     * Check if user is logged in
     */
    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Alias for check()
     */
    public static function isLoggedIn(): bool {
        return self::check();
    }

    /**
     * Get Logged-in User ID
     */
    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get Current Authenticated User Data
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }

        if (self::$currentUser === null) {
            $user = DB::fetch(
                "SELECT u.*, c.symbol as currency_symbol, c.rate as currency_rate, c.name as currency_name
                 FROM users u
                 LEFT JOIN currencies c ON u.currency_code = c.code
                 WHERE u.id = ? LIMIT 1",
                [$_SESSION['user_id']]
            );

            if ($user && $user['status'] === 'active') {
                self::$currentUser = $user;
            } else {
                self::logout();
                return null;
            }
        }

        return self::$currentUser;
    }

    /**
     * Check if Current User is Admin
     */
    public static function isAdmin(): bool {
        $user = self::user();
        return $user && ($user['role'] === 'admin');
    }

    /**
     * Enforce Authenticated User Session
     */
    public static function requireLogin(): void {
        if (!self::check()) {
            flash_set('error', 'Please log in to continue.');
            redirect('/login');
        }
    }

    /**
     * Enforce Authorized Administrator Session
     */
    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            flash_set('error', 'Access denied. Administrator privileges required.');
            redirect('/dashboard');
        }
    }

    /**
     * Rate Limiter for Login Attempts
     */
    public static function isRateLimited(string $ip, string $username, int $maxAttempts = 5, int $decaySeconds = 900): bool {
        $decayTime = date('Y-m-d H:i:s', time() - $decaySeconds);
        $attempts = DB::fetch(
            "SELECT COUNT(*) as cnt FROM login_attempts 
             WHERE (ip_address = ? OR username = ?) AND attempted_at > ?",
            [$ip, $username, $decayTime]
        );
        return ($attempts['cnt'] ?? 0) >= $maxAttempts;
    }

    /**
     * Record Failed Login Attempt
     */
    public static function recordFailedAttempt(string $ip, string $username): void {
        DB::query(
            "INSERT INTO login_attempts (ip_address, username, attempted_at) VALUES (?, ?, NOW())",
            [$ip, $username]
        );
    }

    /**
     * Clear Failed Attempts on Successful Login
     */
    public static function clearAttempts(string $ip, string $username): void {
        DB::query(
            "DELETE FROM login_attempts WHERE ip_address = ? OR username = ?",
            [$ip, $username]
        );
    }

    /**
     * Authenticate User
     */
    public static function login(string $username, string $password): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (self::isRateLimited($ip, $username)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please wait 15 minutes before trying again.'
            ];
        }

        $user = DB::fetch(
            "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            self::recordFailedAttempt($ip, $username);
            return [
                'success' => false,
                'message' => 'Invalid username/email or password.'
            ];
        }

        if ($user['status'] === 'suspended') {
            return [
                'success' => false,
                'message' => 'Your account has been suspended. Please contact support.'
            ];
        }

        // Clean up attempts
        self::clearAttempts($ip, $username);

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_username'] = $user['username'];

        self::$currentUser = null;

        return [
            'success' => true,
            'role' => $user['role']
        ];
    }

    /**
     * Register New User Account
     */
    public static function register(string $username, string $email, string $password, string $currency = 'USD'): array {
        $username = trim($username);
        $email = trim(strtolower($email));

        if (strlen($username) < 3 || strlen($username) > 30 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'message' => 'Username must be 3-30 characters and alphanumeric.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please provide a valid email address.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        // Check if currency is valid
        $curr = DB::fetch("SELECT code FROM currencies WHERE code = ? AND status = 'active'", [$currency]);
        if (!$curr) {
            $currency = 'USD';
        }

        // Check duplicates
        $existing = DB::fetch("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1", [$username, $email]);
        if ($existing) {
            return ['success' => false, 'message' => 'A user with that username or email already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $apiKey = 'smm_usr_' . bin2hex(random_bytes(16));
        $bonus = (float)(get_setting('signup_bonus', '0'));

        try {
            DB::beginTransaction();

            DB::query(
                "INSERT INTO users (username, email, password, role, balance, currency_code, api_key, status) 
                 VALUES (?, ?, ?, 'user', ?, ?, ?, 'active')",
                [$username, $email, $passwordHash, $bonus, $currency, $apiKey]
            );

            $userId = (int)DB::lastInsertId();

            if ($bonus > 0) {
                DB::query(
                    "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description)
                     VALUES (?, ?, 0, ?, 'deposit', 'Welcome Signup Bonus')",
                    [$userId, $bonus, $bonus]
                );
            }

            DB::commit();

            // Auto login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_role'] = 'user';
            $_SESSION['user_username'] = $username;

            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Terminate User Session
     */
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$currentUser = null;
    }
}
