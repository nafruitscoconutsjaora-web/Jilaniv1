<?php
/**
 * Rose SMM Panel - Front Controller
 * Plain PHP routing with zero frameworks
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/currency.php';
require_once __DIR__ . '/includes/smm_provider.php';
require_once __DIR__ . '/includes/razorpay.php';
require_once __DIR__ . '/includes/icons.php';

// Prevent blank white screens on uncaught exceptions
set_exception_handler(function (\Throwable $e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    if (PHP_SAPI !== 'cli') {
        if (!headers_sent()) {
            http_response_code(500);
        }
        $isAdmin = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== false;
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Error</title><link rel="stylesheet" href="/themes/classic/assets/css/style.css"></head><body style="padding:40px;background:#fff1f2;font-family:system-ui,-apple-system,sans-serif;"><div style="max-width:600px;margin:40px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);border:1px solid #fecdd3;"><h3 style="color:#e11d48;margin-top:0;">System Notice</h3><p style="color:#4c0519;font-size:0.9375rem;">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p><div style="margin-top:20px;"><a href="javascript:history.back()" style="color:#e11d48;font-weight:600;margin-right:15px;">&larr; Go Back</a>' . ($isAdmin ? '<a href="/admin/provider-services" style="color:#e11d48;font-weight:600;">Return to Provider Services</a>' : '<a href="/" style="color:#e11d48;font-weight:600;">Home</a>') . '</div></div></body></html>';
        exit;
    }
});

// Parse Request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Clean trailing slash (except root)
if (strlen($requestUri) > 1 && substr($requestUri, -1) === '/') {
    $requestUri = rtrim($requestUri, '/');
}

// Serve static assets if called directly under PHP dev server
$filePath = __DIR__ . $requestUri;
if ($requestUri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf'
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        readfile($filePath);
        exit;
    }
}

// Maintenance Mode Gate
$maintenanceMode = get_setting('maintenance_mode', '0');
if ($maintenanceMode === '1' && !Auth::isAdmin() && !in_array($requestUri, ['/login', '/logout'])) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8"><title>Maintenance - Rose SMM Panel</title>
      <link rel="stylesheet" href="/themes/classic/assets/css/style.css">
    </head>
    <body style="display:flex;align-items:center;justify-content:center;height:100vh;text-align:center;">
      <div class="card" style="max-width:500px;margin:1rem;">
        <h2 style="color:var(--primary-rose);margin-bottom:0.5rem;">Scheduled Maintenance</h2>
        <p style="color:var(--text-muted);margin-bottom:1.5rem;">Our systems are currently undergoing planned performance updates. We will be back online shortly.</p>
        <a href="/login" class="btn btn-secondary btn-sm">Administrator Access</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================================================
// ROUTE DISPATCHER
// ==========================================================================

switch ($requestUri) {

    // ------------------------------------------------------------------------
    // API & Cron Endpoints
    // ------------------------------------------------------------------------
    case '/api':
        require __DIR__ . '/api.php';
        exit;

    case '/cron.php':
        require __DIR__ . '/cron.php';
        exit;

    // ------------------------------------------------------------------------
    // Public Landing Page & Services
    // ------------------------------------------------------------------------
    case '':
    case '/':
        require __DIR__ . '/themes/classic/views/landing/home.php';
        exit;

    case '/services':
        require __DIR__ . '/themes/classic/views/landing/services.php';
        exit;

    // ------------------------------------------------------------------------
    // Authentication Routes
    // ------------------------------------------------------------------------
    case '/login':
        if (Auth::check()) {
            redirect(Auth::isAdmin() ? '/admin' : '/dashboard');
        }

        if ($requestMethod === 'POST') {
            verify_csrf();
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $result = Auth::login($username, $password);
            if ($result['success']) {
                flash_set('success', 'Welcome back, ' . htmlspecialchars($username) . '!');
                redirect($result['role'] === 'admin' ? '/admin' : '/dashboard');
            } else {
                flash_set('error', $result['message']);
                redirect('/login');
            }
        }

        require __DIR__ . '/themes/classic/views/landing/login.php';
        exit;

    case '/register':
        if (Auth::check()) {
            redirect('/dashboard');
        }

        if ($requestMethod === 'POST') {
            verify_csrf();
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $currency = trim($_POST['currency'] ?? 'USD');

            $res = Auth::register($username, $email, $password, $currency);
            if ($res['success']) {
                flash_set('success', 'Your account has been created successfully! Welcome to Rose SMM.');
                redirect('/dashboard');
            } else {
                flash_set('error', $res['message']);
                redirect('/register');
            }
        }

        require __DIR__ . '/themes/classic/views/landing/register.php';
        exit;

    case '/forgot-password':
        if ($requestMethod === 'POST') {
            verify_csrf();
            flash_set('info', 'If an account exists with that email address, password reset instructions have been dispatched.');
            redirect('/login');
        }
        require __DIR__ . '/themes/classic/views/landing/forgot_password.php';
        exit;

    case '/logout':
        Auth::logout();
        flash_set('info', 'You have been successfully signed out.');
        redirect('/');
        exit;

    // ------------------------------------------------------------------------
    // User Panel Routes (Requires Login)
    // ------------------------------------------------------------------------
    case '/dashboard':
        require __DIR__ . '/themes/classic/views/user/dashboard.php';
        exit;

    case '/new-order':
        require __DIR__ . '/themes/classic/views/user/new_order.php';
        exit;

    case '/order/create':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $user = Auth::user();
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $link = trim($_POST['link'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($serviceId <= 0 || empty($link) || $quantity <= 0) {
            flash_set('error', 'Please fill in all order fields correctly.');
            redirect('/new-order');
        }

        $service = DB::fetch("SELECT * FROM services WHERE id = ? AND status = 'active' LIMIT 1", [$serviceId]);
        if (!$service) {
            flash_set('error', 'The requested service is not available.');
            redirect('/new-order');
        }

        if ($quantity < (int)$service['min_quantity'] || $quantity > (int)$service['max_quantity']) {
            flash_set('error', "Quantity must be between {$service['min_quantity']} and {$service['max_quantity']}.");
            redirect('/new-order');
        }

        $ratePer1000 = (float)$service['rate'];
        $costUsd = round(($ratePer1000 / 1000) * $quantity, 4);

        try {
            DB::beginTransaction();

            $userRow = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
            $currentBalance = (float)$userRow['balance'];

            if ($currentBalance < $costUsd) {
                DB::rollBack();
                flash_set('error', 'Insufficient balance. Please add funds to your wallet before placing this order.');
                redirect('/add-funds');
            }

            $balanceAfter = round($currentBalance - $costUsd, 4);
            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$balanceAfter, $userId]);

            // Save order snapshot
            $userCurrency = Currency::getUserCurrency($user);
            $snapshot = Currency::createPricingSnapshot($ratePer1000, $quantity, $userCurrency);

            DB::query(
                "INSERT INTO orders (user_id, service_id, provider_id, link, quantity, charge, charge_currency, currency_rate, user_charge, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [
                    $userId,
                    $service['id'],
                    $service['provider_id'] ?: null,
                    $link,
                    $quantity,
                    $costUsd,
                    $snapshot['charge_currency'],
                    $snapshot['currency_rate'],
                    $snapshot['user_charge']
                ]
            );

            $orderId = (int)DB::lastInsertId();

            // Transaction Ledger
            DB::query(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, reference_id, description)
                 VALUES (?, ?, ?, ?, 'order', ?, ?)",
                [$userId, $costUsd, $currentBalance, $balanceAfter, (string)$orderId, "Order #{$orderId} for {$service['name']}"]
            );

            // Attempt automated dispatch to provider
            if (!empty($service['provider_id']) && !empty($service['provider_service_id'])) {
                $providerObj = SMMProvider::find((int)$service['provider_id']);
                if ($providerObj) {
                    $provRes = $providerObj->placeOrder($service['provider_service_id'], $link, $quantity);
                    if ($provRes['success']) {
                        DB::query(
                            "UPDATE orders SET provider_order_id = ?, status = 'processing' WHERE id = ?",
                            [$provRes['provider_order_id'], $orderId]
                        );
                    }
                }
            }

            DB::commit();

            flash_set('success', "Order #{$orderId} placed successfully!");
            redirect('/orders');
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Order creation error: " . $e->getMessage());
            flash_set('error', 'A database error occurred while creating your order.');
            redirect('/new-order');
        }
        exit;

    case '/orders':
        require __DIR__ . '/themes/classic/views/user/orders.php';
        exit;

    case '/user/services':
        require __DIR__ . '/themes/classic/views/user/services.php';
        exit;

    case '/add-funds':
        require __DIR__ . '/themes/classic/views/user/add_funds.php';
        exit;

    case '/payment/razorpay/initiate':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $user = Auth::user();
        $userCurrency = Currency::getUserCurrency($user);
        $amountUsd = (float)($_POST['amount'] ?? 0);

        if ($amountUsd < 1.0) {
            json_response(['success' => false, 'message' => 'Minimum deposit is $1.00.'], 400);
        }

        $gateway = new RazorpayGateway();
        $orderRes = $gateway->createOrder($userId, $amountUsd, $userCurrency['code'], (float)$userCurrency['rate']);
        json_response($orderRes);
        exit;

    case '/payment/razorpay/verify':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $orderId = trim($_POST['razorpay_order_id'] ?? '');
        $paymentId = trim($_POST['razorpay_payment_id'] ?? '');
        $signature = trim($_POST['razorpay_signature'] ?? '');

        $gateway = new RazorpayGateway();
        $verifyRes = $gateway->verifyPayment($userId, $orderId, $paymentId, $signature);
        if ($verifyRes['success']) {
            flash_set('success', 'Payment verified! Your wallet has been credited.');
        }
        json_response($verifyRes);
        exit;

    case '/tickets':
        require __DIR__ . '/themes/classic/views/user/tickets.php';
        exit;

    case '/tickets/create':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $subject = trim($_POST['subject'] ?? '');
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';
        $message = trim($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            flash_set('error', 'Subject and message are required.');
            redirect('/tickets');
        }

        DB::beginTransaction();
        try {
            DB::query(
                "INSERT INTO tickets (user_id, subject, priority, status) VALUES (?, ?, ?, 'open')",
                [$userId, $subject, $priority]
            );
            $ticketId = (int)DB::lastInsertId();

            DB::query(
                "INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)",
                [$ticketId, $userId, $message]
            );

            DB::commit();
            flash_set('success', "Ticket #{$ticketId} opened successfully.");
            redirect('/tickets/view?id=' . $ticketId);
        } catch (Exception $e) {
            DB::rollBack();
            flash_set('error', 'Error creating support ticket.');
            redirect('/tickets');
        }
        exit;

    case '/tickets/view':
        require __DIR__ . '/themes/classic/views/user/ticket_view.php';
        exit;

    case '/tickets/reply':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        $ticket = DB::fetch("SELECT id FROM tickets WHERE id = ? AND user_id = ? LIMIT 1", [$ticketId, $userId]);
        if (!$ticket || empty($message)) {
            flash_set('error', 'Invalid ticket or empty reply message.');
            redirect('/tickets');
        }

        DB::query("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)", [$ticketId, $userId, $message]);
        DB::query("UPDATE tickets SET status = 'customer_reply', updated_at = NOW() WHERE id = ?", [$ticketId]);

        flash_set('success', 'Your reply has been sent to our staff.');
        redirect('/tickets/view?id=' . $ticketId);
        exit;

    case '/profile':
        require __DIR__ . '/themes/classic/views/user/profile.php';
        exit;

    case '/user/currency':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $newCurrency = trim($_POST['currency'] ?? 'USD');

        $exists = DB::fetch("SELECT code FROM currencies WHERE code = ? AND status = 'active' LIMIT 1", [$newCurrency]);
        if ($exists) {
            DB::query("UPDATE users SET currency_code = ? WHERE id = ?", [$newCurrency, $userId]);
            flash_set('success', "Display currency updated to {$newCurrency}.");
        }

        $referrer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        redirect($referrer);
        exit;

    case '/profile/password':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $user = Auth::user();
        $currentPass = trim($_POST['current_password'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');

        if (!password_verify($currentPass, $user['password'])) {
            flash_set('error', 'Current password was incorrect.');
            redirect('/profile');
        }

        if (strlen($newPass) < 6) {
            flash_set('error', 'New password must be at least 6 characters.');
            redirect('/profile');
        }

        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        DB::query("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);

        flash_set('success', 'Your password has been changed successfully.');
        redirect('/profile');
        exit;

    case '/profile/api-key':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $newKey = 'smm_usr_' . bin2hex(random_bytes(16));
        DB::query("UPDATE users SET api_key = ? WHERE id = ?", [$newKey, $userId]);

        flash_set('success', 'Your API Key has been regenerated.');
        redirect('/profile');
        exit;

    case '/api-docs':
        require __DIR__ . '/themes/classic/views/user/api_docs.php';
        exit;

    // ------------------------------------------------------------------------
    // Admin Panel Routes (Requires Admin)
    // ------------------------------------------------------------------------
    case '/admin':
    case '/admin/dashboard':
        require __DIR__ . '/themes/classic/views/admin/dashboard.php';
        exit;

    case '/admin/users':
        require __DIR__ . '/themes/classic/views/admin/users.php';
        exit;

    case '/admin/users/balance':
        Auth::requireAdmin();
        verify_csrf();

        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $actionType = $_POST['action_type'] ?? 'add';
        $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Admin Manual Adjustment');

        if ($targetUserId <= 0 || $amount <= 0) {
            flash_set('error', 'Invalid user or adjustment amount.');
            redirect('/admin/users');
        }

        try {
            DB::beginTransaction();

            $targetUser = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$targetUserId]);
            if (!$targetUser) {
                DB::rollBack();
                flash_set('error', 'Target user not found.');
                redirect('/admin/users');
            }

            $currentBal = (float)$targetUser['balance'];
            $newBal = $actionType === 'add' ? round($currentBal + $amount, 4) : round(max(0, $currentBal - $amount), 4);

            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$newBal, $targetUserId]);

            DB::query(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$targetUserId, $amount, $currentBal, $newBal, $actionType === 'add' ? 'deposit' : 'manual_deduct', "Admin Adjustment: " . $reason]
            );

            DB::commit();
            flash_set('success', "Balance updated successfully for User #{$targetUserId}.");
            redirect('/admin/users');
        } catch (Exception $e) {
            DB::rollBack();
            flash_set('error', 'Error updating balance: ' . $e->getMessage());
            redirect('/admin/users');
        }
        exit;

    case '/admin/users/status':
        Auth::requireAdmin();
        verify_csrf();

        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newStatus = in_array($_POST['new_status'] ?? '', ['active', 'suspended']) ? $_POST['new_status'] : 'active';

        DB::query("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'", [$newStatus, $targetUserId]);
        flash_set('success', "User status set to {$newStatus}.");
        redirect('/admin/users');
        exit;

    case '/admin/orders':
        require __DIR__ . '/themes/classic/views/admin/orders.php';
        exit;

    case '/admin/orders/status':
        Auth::requireAdmin();
        verify_csrf();

        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'pending';

        if (in_array($newStatus, ['pending', 'processing', 'in_progress', 'completed', 'partial', 'canceled'])) {
            DB::query("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $orderId]);
            flash_set('success', "Order #{$orderId} status set to {$newStatus}.");
        }
        redirect('/admin/orders');
        exit;

    case '/admin/orders/refund':
        Auth::requireAdmin();
        verify_csrf();

        $orderId = (int)($_POST['order_id'] ?? 0);

        try {
            DB::beginTransaction();

            $order = DB::fetch("SELECT * FROM orders WHERE id = ? FOR UPDATE", [$orderId]);
            if (!$order || $order['status'] === 'canceled') {
                DB::rollBack();
                flash_set('error', 'Order not found or already canceled/refunded.');
                redirect('/admin/orders');
            }

            $targetUser = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$order['user_id']]);
            $refundAmount = (float)$order['charge'];
            $balBefore = (float)$targetUser['balance'];
            $balAfter = round($balBefore + $refundAmount, 4);

            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$balAfter, $targetUser['id']]);
            DB::query("UPDATE orders SET status = 'canceled', updated_at = NOW() WHERE id = ?", [$orderId]);

            DB::query(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, reference_id, description)
                 VALUES (?, ?, ?, ?, 'refund', ?, ?)",
                [$targetUser['id'], $refundAmount, $balBefore, $balAfter, (string)$orderId, "Full refund for canceled Order #{$orderId}"]
            );

            DB::commit();
            flash_set('success', "Order #{$orderId} refunded successfully ($" . number_format($refundAmount, 4) . " USD).");
            redirect('/admin/orders');
        } catch (Exception $e) {
            DB::rollBack();
            flash_set('error', 'Refund failed: ' . $e->getMessage());
            redirect('/admin/orders');
        }
        exit;

    case '/admin/orders/resend':
        Auth::requireAdmin();
        verify_csrf();

        $orderId = (int)($_POST['order_id'] ?? 0);
        $order = DB::fetch("SELECT o.*, s.provider_service_id FROM orders o JOIN services s ON o.service_id = s.id WHERE o.id = ?", [$orderId]);

        if ($order && $order['provider_id'] && $order['provider_service_id']) {
            $provider = SMMProvider::find((int)$order['provider_id']);
            if ($provider) {
                $res = $provider->placeOrder($order['provider_service_id'], $order['link'], (int)$order['quantity']);
                if ($res['success']) {
                    DB::query(
                        "UPDATE orders SET provider_order_id = ?, status = 'processing', updated_at = NOW() WHERE id = ?",
                        [$res['provider_order_id'], $orderId]
                    );
                    flash_set('success', "Order #{$orderId} dispatched to provider! Provider ID: #" . $res['provider_order_id']);
                } else {
                    flash_set('error', "Provider dispatch failed: " . ($res['error'] ?? 'Unknown error'));
                }
            }
        }
        redirect('/admin/orders');
        exit;

    case '/admin/categories':
        require __DIR__ . '/themes/classic/views/admin/categories.php';
        exit;

    case '/admin/categories/create':
        Auth::requireAdmin();
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (!empty($name)) {
            DB::query("INSERT INTO categories (name, sort_order, status) VALUES (?, ?, ?)", [$name, $sortOrder, $status]);
            flash_set('success', "Category '{$name}' created.");
        }
        redirect('/admin/categories');
        exit;

    case '/admin/categories/toggle':
        Auth::requireAdmin();
        verify_csrf();

        $catId = (int)($_POST['category_id'] ?? 0);
        $cat = DB::fetch("SELECT status FROM categories WHERE id = ?", [$catId]);
        if ($cat) {
            $newStatus = $cat['status'] === 'active' ? 'inactive' : 'active';
            DB::query("UPDATE categories SET status = ? WHERE id = ?", [$newStatus, $catId]);
            flash_set('success', "Category status updated.");
        }
        redirect('/admin/categories');
        exit;

    case '/admin/categories/delete':
        Auth::requireAdmin();
        verify_csrf();

        $catId = (int)($_POST['category_id'] ?? 0);
        $svcCount = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM services WHERE category_id = ?", [$catId])['cnt'] ?? 0);
        if ($svcCount > 0) {
            flash_set('error', "Cannot delete category with active services.");
        } else {
            DB::query("DELETE FROM categories WHERE id = ?", [$catId]);
            flash_set('success', "Category deleted.");
        }
        redirect('/admin/categories');
        exit;

    case '/admin/services':
        require __DIR__ . '/themes/classic/views/admin/services.php';
        exit;

    case '/admin/services/create':
        Auth::requireAdmin();
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 0);
        $rate = (float)($_POST['rate'] ?? 0);
        $min = (int)($_POST['min_quantity'] ?? 10);
        $max = (int)($_POST['max_quantity'] ?? 10000);
        $provId = !empty($_POST['provider_id']) ? (int)$_POST['provider_id'] : null;
        $provSrvId = trim($_POST['provider_service_id'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!empty($name) && $catId > 0 && $rate > 0) {
            DB::query(
                "INSERT INTO services (category_id, provider_id, provider_service_id, name, type, rate, min_quantity, max_quantity, description, status)
                 VALUES (?, ?, ?, ?, 'default', ?, ?, ?, ?, 'active')",
                [$catId, $provId, $provSrvId ?: null, $name, $rate, $min, $max, $desc]
            );
            flash_set('success', "Service created successfully.");
        } else {
            flash_set('error', "Please fill in all required service fields.");
        }
        redirect('/admin/services');
        exit;

    case '/admin/services/toggle':
        Auth::requireAdmin();
        verify_csrf();

        $srvId = (int)($_POST['service_id'] ?? 0);
        $srv = DB::fetch("SELECT status FROM services WHERE id = ?", [$srvId]);
        if ($srv) {
            $newStatus = $srv['status'] === 'active' ? 'inactive' : 'active';
            DB::query("UPDATE services SET status = ? WHERE id = ?", [$newStatus, $srvId]);
            flash_set('success', "Service status updated.");
        }
        redirect('/admin/services');
        exit;

    case '/admin/services/delete':
        Auth::requireAdmin();
        verify_csrf();

        $srvId = (int)($_POST['service_id'] ?? 0);
        DB::query("DELETE FROM services WHERE id = ?", [$srvId]);
        flash_set('success', "Service deleted.");
        redirect('/admin/services');
        exit;

    case '/admin/providers':
        require __DIR__ . '/themes/classic/views/admin/providers.php';
        exit;

    case '/admin/providers/create':
        Auth::requireAdmin();
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['api_url'] ?? '');
        $key = trim($_POST['api_key'] ?? '');
        $currency = trim($_POST['currency'] ?? 'USD');

        if (!empty($name) && !empty($url) && !empty($key)) {
            DB::query("INSERT INTO providers (name, api_url, api_key, currency, status) VALUES (?, ?, ?, ?, 'active')", [$name, $url, $key, $currency]);
            flash_set('success', "Provider connected.");
        }
        redirect('/admin/providers');
        exit;

    case '/admin/providers/sync':
        Auth::requireAdmin();
        verify_csrf();

        $provId = (int)($_POST['provider_id'] ?? 0);
        $provider = SMMProvider::find($provId);
        if ($provider) {
            $res = $provider->getBalance();
            if ($res['success']) {
                flash_set('success', "Provider balance synced successfully: " . $res['currency'] . ' ' . number_format($res['balance'], 2));
            } else {
                flash_set('error', "Sync error: " . ($res['error'] ?? 'Failed to reach API'));
            }
        }
        redirect('/admin/providers');
        exit;

    case '/admin/providers/delete':
        Auth::requireAdmin();
        verify_csrf();

        $provId = (int)($_POST['provider_id'] ?? 0);
        DB::query("DELETE FROM providers WHERE id = ?", [$provId]);
        flash_set('success', "Provider removed.");
        redirect('/admin/providers');
        exit;

    case '/admin/provider-services':
        Auth::requireAdmin();
        require __DIR__ . '/themes/classic/views/admin/provider_services.php';
        exit;

    case '/admin/provider-services/import':
        Auth::requireAdmin();
        verify_csrf();

        $provId = (int)($_POST['provider_id'] ?? 0);

        try {
            // Validate Provider
            $provider = null;
            if ($provId > 0) {
                $provider = DB::fetch("SELECT id, name FROM providers WHERE id = ? LIMIT 1", [$provId]);
            }
            if (!$provider) {
                flash_set('error', 'Invalid or missing provider. Please select a valid active provider.');
                redirect('/admin/provider-services');
                exit;
            }

            // Validate Category
            $catId = (int)($_POST['category_id'] ?? 0);
            $category = null;
            if ($catId > 0) {
                $category = DB::fetch("SELECT id, name FROM categories WHERE id = ? LIMIT 1", [$catId]);
            }
            if (!$category) {
                $defaultCat = DB::fetch("SELECT id, name FROM categories ORDER BY sort_order ASC, id ASC LIMIT 1");
                if ($defaultCat) {
                    $catId = (int)$defaultCat['id'];
                    $category = $defaultCat;
                } else {
                    flash_set('error', 'Cannot import service: No categories exist. Please create a category first in Categories settings.');
                    redirect('/admin/provider-services?provider_id=' . $provId);
                    exit;
                }
            }

            // Sanitize & Validate Service Fields
            $provSrvId = trim((string)($_POST['provider_service_id'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            if ($name === '') {
                $name = $provSrvId !== '' ? "Provider Service #{$provSrvId}" : "Imported Service";
            }

            $rawOriginalRate = str_replace(['$', ',', ' '], '', (string)($_POST['original_rate'] ?? '0'));
            $originalRate = is_numeric($rawOriginalRate) ? max(0.0, (float)$rawOriginalRate) : 0.0;

            $marginPercent = max(0.0, (float)($_POST['margin_percent'] ?? 30.0));
            $min = max(1, (int)($_POST['min_quantity'] ?? 10));
            $max = max($min, (int)($_POST['max_quantity'] ?? 10000));
            $type = trim((string)($_POST['type'] ?? 'Default'));
            if ($type === '') {
                $type = 'Default';
            }

            $finalRate = round($originalRate * (1 + ($marginPercent / 100)), 4);

            // Execute insert with schema column fallback handling
            try {
                DB::query(
                    "INSERT INTO services (category_id, provider_id, provider_service_id, name, type, rate, original_rate, min_quantity, max_quantity, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                    [$catId, $provId, $provSrvId, $name, $type, $finalRate, $originalRate, $min, $max]
                );
            } catch (\PDOException $pdoEx) {
                if (strpos($pdoEx->getMessage(), "Unknown column 'original_rate'") !== false) {
                    try {
                        DB::query("ALTER TABLE services ADD COLUMN `original_rate` DECIMAL(15, 4) NULL DEFAULT NULL AFTER `rate`");
                        DB::query(
                            "INSERT INTO services (category_id, provider_id, provider_service_id, name, type, rate, original_rate, min_quantity, max_quantity, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                            [$catId, $provId, $provSrvId, $name, $type, $finalRate, $originalRate, $min, $max]
                        );
                    } catch (\Throwable $eFallback) {
                        DB::query(
                            "INSERT INTO services (category_id, provider_id, provider_service_id, name, type, rate, min_quantity, max_quantity, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                            [$catId, $provId, $provSrvId, $name, $type, $finalRate, $min, $max]
                        );
                    }
                } else {
                    throw $pdoEx;
                }
            }

            flash_set('success', "Service '{$name}' imported with {$marginPercent}% margin at $" . number_format($finalRate, 4) . "/1K.");
            redirect('/admin/provider-services?provider_id=' . $provId);
            exit;
        } catch (\Throwable $e) {
            error_log("Provider service import error: " . $e->getMessage());
            flash_set('error', "Import error: " . $e->getMessage());
            $redirectUrl = '/admin/provider-services' . ($provId > 0 ? '?provider_id=' . $provId : '');
            redirect($redirectUrl);
            exit;
        }

    case '/admin/payments':
        require __DIR__ . '/themes/classic/views/admin/payments.php';
        exit;

    case '/admin/currencies':
        require __DIR__ . '/themes/classic/views/admin/currencies.php';
        exit;

    case '/admin/currencies/create':
        Auth::requireAdmin();
        verify_csrf();

        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $symbol = trim($_POST['symbol'] ?? '$');
        $rate = (float)($_POST['rate'] ?? 1.0);

        if (!empty($code) && !empty($name) && $rate > 0) {
            DB::query(
                "INSERT INTO currencies (code, name, symbol, rate, status) VALUES (?, ?, ?, ?, 'active')
                 ON DUPLICATE KEY UPDATE name = VALUES(name), symbol = VALUES(symbol), rate = VALUES(rate)",
                [$code, $name, $symbol, $rate]
            );
            flash_set('success', "Currency {$code} saved.");
        }
        redirect('/admin/currencies');
        exit;

    case '/admin/currencies/update':
        Auth::requireAdmin();
        verify_csrf();

        $code = strtoupper(trim($_POST['code'] ?? ''));
        $rate = (float)($_POST['rate'] ?? 1.0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $isDefault = !empty($_POST['set_default']);

        if ($isDefault) {
            DB::query("UPDATE currencies SET is_default = 0");
            DB::query("UPDATE currencies SET is_default = 1, status = 'active', rate = ? WHERE code = ?", [$rate, $code]);
            flash_set('success', "{$code} set as default currency.");
        } else {
            DB::query("UPDATE currencies SET rate = ?, status = ? WHERE code = ?", [$rate, $status, $code]);
            flash_set('success', "{$code} updated.");
        }
        redirect('/admin/currencies');
        exit;

    case '/admin/tickets':
        require __DIR__ . '/themes/classic/views/admin/tickets.php';
        exit;

    case '/admin/tickets/view':
        require __DIR__ . '/themes/classic/views/admin/ticket_view.php';
        exit;

    case '/admin/tickets/reply':
        Auth::requireAdmin();
        verify_csrf();

        $adminId = Auth::id();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($ticketId > 0 && !empty($message)) {
            DB::query("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)", [$ticketId, $adminId, $message]);
            DB::query("UPDATE tickets SET status = 'answered', updated_at = NOW() WHERE id = ?", [$ticketId]);
            flash_set('success', "Reply posted to Ticket #{$ticketId}.");
            redirect('/admin/tickets/view?id=' . $ticketId);
        }
        redirect('/admin/tickets');
        exit;

    case '/admin/tickets/status':
        Auth::requireAdmin();
        verify_csrf();

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['open', 'answered', 'customer_reply', 'closed']) ? $_POST['status'] : 'open';

        DB::query("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $ticketId]);
        flash_set('success', "Ticket status updated to {$status}.");
        redirect('/admin/tickets/view?id=' . $ticketId);
        exit;

    case '/admin/finance':
        require __DIR__ . '/themes/classic/views/admin/finance.php';
        exit;

    case '/admin/settings':
        require __DIR__ . '/themes/classic/views/admin/settings.php';
        exit;

    case '/admin/settings/save':
        Auth::requireAdmin();
        verify_csrf();

        $section = $_POST['section'] ?? 'general';

        if ($section === 'general') {
            set_setting('site_name', trim($_POST['site_name'] ?? 'Rose SMM Panel'));
            set_setting('site_tagline', trim($_POST['site_tagline'] ?? ''));
            set_setting('support_email', trim($_POST['support_email'] ?? ''));
            set_setting('signup_bonus', (string)max(0, (float)($_POST['signup_bonus'] ?? 0)));
            set_setting('maintenance_mode', (string)($_POST['maintenance_mode'] ?? '0'));
            flash_set('success', 'General settings saved.');
        } elseif ($section === 'razorpay') {
            set_setting('razorpay_enabled', (string)($_POST['razorpay_enabled'] ?? '0'));
            set_setting('razorpay_key_id', trim($_POST['razorpay_key_id'] ?? ''));
            set_setting('razorpay_key_secret', trim($_POST['razorpay_key_secret'] ?? ''));
            set_setting('razorpay_webhook_secret', trim($_POST['razorpay_webhook_secret'] ?? ''));
            flash_set('success', 'Razorpay credentials saved.');
        } elseif ($section === 'hero_banner') {
            $bannerId = (int)($_POST['banner_id'] ?? 1);
            $heading = trim($_POST['heading'] ?? 'Grow Your Social Media');
            $subheading = trim($_POST['subheading'] ?? 'Fast • Secure • Reliable');
            $description = trim($_POST['description'] ?? '');
            $ctaText = trim($_POST['cta_text'] ?? 'Explore Services');
            $ctaLink = trim($_POST['cta_link'] ?? '/new-order');
            $imageUrl = trim($_POST['image_url'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($heading === '') {
                $heading = 'Grow Your Social Media';
            }
            if ($ctaText === '') {
                $ctaText = 'Explore Services';
            }
            if ($ctaLink === '') {
                $ctaLink = '/new-order';
            }

            $existing = DB::fetch("SELECT id FROM hero_banners WHERE id = ? LIMIT 1", [$bannerId]);
            if ($existing) {
                DB::query(
                    "UPDATE hero_banners SET heading = ?, subheading = ?, description = ?, cta_text = ?, cta_link = ?, image_url = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                    [$heading, $subheading, $description, $ctaText, $ctaLink, $imageUrl, $isActive, $bannerId]
                );
            } else {
                DB::query(
                    "INSERT INTO hero_banners (id, heading, subheading, description, cta_text, cta_link, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
                    [$bannerId, $heading, $subheading, $description, $ctaText, $ctaLink, $imageUrl, $isActive]
                );
            }
            flash_set('success', 'User dashboard hero banner settings updated successfully.');
        }

        redirect('/admin/settings');
        exit;

    case '/admin/banners/add':
        Auth::requireAdmin();
        verify_csrf();

        $heading = trim($_POST['heading'] ?? 'Promotional Banner');
        $ctaLink = trim($_POST['cta_link'] ?? '/new-order');
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imageUrl = trim($_POST['image_url'] ?? '');

        if ($ctaLink === '') {
            $ctaLink = '/new-order';
        }

        // Process File Upload with Strict Validation if provided
        if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['banner_file'];
            $maxBytes = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxBytes) {
                flash_set('error', 'Image file is too large. Maximum allowed size is 5MB.');
                redirect('/admin/settings');
                exit;
            }

            $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedExts, true)) {
                flash_set('error', 'Invalid image format. Allowed formats: PNG, JPG, JPEG, WEBP, SVG, GIF.');
                redirect('/admin/settings');
                exit;
            }

            // Validate MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMimes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/gif', 'text/xml', 'text/plain'];
            if (!in_array($mime, $allowedMimes, true)) {
                flash_set('error', 'Invalid image MIME type detected.');
                redirect('/admin/settings');
                exit;
            }

            $uploadDir = __DIR__ . '/uploads/banners';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = 'banner_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $destination = $uploadDir . '/' . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $imageUrl = '/uploads/banners/' . $newFileName;
            } else {
                flash_set('error', 'Failed to save uploaded banner image.');
                redirect('/admin/settings');
                exit;
            }
        }

        if (empty($imageUrl)) {
            flash_set('error', 'Please upload a banner image or provide a valid image URL.');
            redirect('/admin/settings');
            exit;
        }

        DB::query(
            "INSERT INTO hero_banners (heading, subheading, description, cta_text, cta_link, image_url, sort_order, is_active) VALUES (?, '', '', 'Explore Services', ?, ?, ?, ?)",
            [$heading, $ctaLink, $imageUrl, $sortOrder, $isActive]
        );

        flash_set('success', 'New banner added to the user dashboard slider successfully.');
        redirect('/admin/settings');
        exit;

    case '/admin/banners/toggle':
        Auth::requireAdmin();
        verify_csrf();

        $bannerId = (int)($_POST['banner_id'] ?? 0);
        $banner = DB::fetch("SELECT id, is_active FROM hero_banners WHERE id = ?", [$bannerId]);
        if ($banner) {
            $newStatus = $banner['is_active'] ? 0 : 1;
            DB::query("UPDATE hero_banners SET is_active = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $bannerId]);
            flash_set('success', 'Banner status updated.');
        }
        redirect('/admin/settings');
        exit;

    case '/admin/banners/update':
        Auth::requireAdmin();
        verify_csrf();

        $bannerId = (int)($_POST['banner_id'] ?? 0);
        $heading = trim($_POST['heading'] ?? '');
        $ctaLink = trim($_POST['cta_link'] ?? '/new-order');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($bannerId > 0) {
            DB::query(
                "UPDATE hero_banners SET heading = ?, cta_link = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                [$heading, $ctaLink, $sortOrder, $isActive, $bannerId]
            );
            flash_set('success', 'Banner updated successfully.');
        }
        redirect('/admin/settings');
        exit;

    case '/admin/banners/delete':
        Auth::requireAdmin();
        verify_csrf();

        $bannerId = (int)($_POST['banner_id'] ?? 0);
        $banner = DB::fetch("SELECT * FROM hero_banners WHERE id = ?", [$bannerId]);
        if ($banner) {
            // Delete uploaded file if it was a custom upload and exists
            $img = $banner['image_url'];
            if ($img && strpos($img, '/uploads/banners/banner_') === 0) {
                $filePath = __DIR__ . $img;
                if (file_exists($filePath) && is_file($filePath) && !in_array(basename($img), ['banner_social_growth.svg', 'banner_instagram_viral.svg', 'banner_monetization_boost.svg'], true)) {
                    @unlink($filePath);
                }
            }
            DB::query("DELETE FROM hero_banners WHERE id = ?", [$bannerId]);
            flash_set('success', 'Banner deleted successfully.');
        }
        redirect('/admin/settings');
        exit;

    default:
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8"><title>404 - Not Found</title>
          <link rel="stylesheet" href="/themes/classic/assets/css/style.css">
        </head>
        <body style="display:flex;align-items:center;justify-content:center;height:100vh;text-align:center;">
          <div class="card" style="max-width:440px;margin:1rem;">
            <h1 style="font-size:3rem;color:var(--primary-rose);margin-bottom:0.25rem;">404</h1>
            <h3 style="margin-bottom:0.5rem;">Page Not Found</h3>
            <p style="color:var(--text-muted);margin-bottom:1.5rem;">The requested page or endpoint does not exist.</p>
            <a href="/" class="btn btn-primary btn-sm">&larr; Back to Home</a>
          </div>
        </body>
        </html>
        <?php
        exit;
}
