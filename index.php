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
        $amountInr = (float)($_POST['amount'] ?? 0);

        $gateway = new RazorpayGateway();
        if (!$gateway->isEnabled()) {
            json_response(['success' => false, 'message' => 'Razorpay payment gateway is currently disabled by administrator.'], 400);
        }

        if ($amountInr < $gateway->getMinAmount()) {
            json_response(['success' => false, 'message' => 'Minimum deposit is ₹' . number_format($gateway->getMinAmount(), 2) . ' INR.'], 400);
        }

        $orderRes = $gateway->createOrder($userId, $amountInr);
        json_response($orderRes, $orderRes['success'] ? 200 : 400);
        exit;

    case '/payment/razorpay/verify':
        Auth::requireLogin();
        verify_csrf();

        $userId = Auth::id();
        $orderId = trim($_POST['razorpay_order_id'] ?? '');
        $paymentId = trim($_POST['razorpay_payment_id'] ?? '');
        $signature = trim($_POST['razorpay_signature'] ?? '');

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            json_response(['success' => false, 'message' => 'Incomplete payment response received.'], 400);
        }

        $gateway = new RazorpayGateway();
        $verifyRes = $gateway->verifyPayment($userId, $orderId, $paymentId, $signature);
        if ($verifyRes['success']) {
            flash_set('success', 'Payment verified successfully! Your wallet balance has been credited.');
        }
        json_response($verifyRes, $verifyRes['success'] ? 200 : 400);
        exit;

    case '/payment/razorpay/webhook':
        $rawPayload = file_get_contents('php://input');
        $webhookSignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

        if (empty($rawPayload) || empty($webhookSignature)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing payload or signature.']);
            exit;
        }

        $gateway = new RazorpayGateway();
        $res = $gateway->verifyWebhook($rawPayload, $webhookSignature);
        http_response_code($res['success'] ? 200 : 400);
        header('Content-Type: application/json');
        echo json_encode($res);
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
        require __DIR__ . '/themes/classic/views/admin/provider_services.php';
        exit;

    case '/admin/provider-services/import':
        Auth::requireAdmin();
        verify_csrf();

        $provId = (int)($_POST['provider_id'] ?? 0);
        $provSrvId = trim($_POST['provider_service_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 0);
        $originalRate = (float)($_POST['original_rate'] ?? 0);
        $marginPercent = (float)($_POST['margin_percent'] ?? 30);
        $min = (int)($_POST['min_quantity'] ?? 10);
        $max = (int)($_POST['max_quantity'] ?? 10000);
        $type = trim($_POST['type'] ?? 'default');

        $finalRate = round($originalRate * (1 + ($marginPercent / 100)), 4);

        DB::query(
            "INSERT INTO services (category_id, provider_id, provider_service_id, name, type, rate, original_rate, min_quantity, max_quantity, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [$catId, $provId, $provSrvId, $name, $type, $finalRate, $originalRate, $min, $max]
        );

        flash_set('success', "Service '{$name}' imported with {$marginPercent}% margin at $" . number_format($finalRate, 4) . "/1K.");
        redirect('/admin/provider-services?provider_id=' . $provId);
        exit;

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

    case '/admin/payment-gateways':
        Auth::requireAdmin();
        require __DIR__ . '/themes/classic/views/admin/payment_gateways.php';
        exit;

    case '/admin/payment-gateways/update':
        Auth::requireAdmin();
        verify_csrf();

        $gwId = (int)($_POST['gateway_id'] ?? 0);
        $minAmount = max(1.0, (float)($_POST['min_amount'] ?? 10.0));
        $maxAmount = max($minAmount, (float)($_POST['max_amount'] ?? 50000.0));
        $instructions = trim($_POST['instructions'] ?? '');
        $isEnabled = (int)($_POST['is_enabled'] ?? 0);
        $configData = $_POST['config'] ?? [];

        $gw = DB::fetch("SELECT * FROM payment_gateways WHERE id = ?", [$gwId]);
        if (!$gw) {
            flash_set('danger', 'Payment gateway not found.');
            redirect('/admin/payment-gateways');
            exit;
        }

        $existingConfig = !empty($gw['config']) ? json_decode($gw['config'], true) : [];
        if (!is_array($existingConfig)) {
            $existingConfig = [];
        }

        // Merge incoming config
        foreach ($configData as $k => $v) {
            $existingConfig[$k] = trim($v);
        }

        DB::query(
            "UPDATE payment_gateways SET min_amount = ?, max_amount = ?, instructions = ?, is_enabled = ?, config = ?, updated_at = NOW() WHERE id = ?",
            [$minAmount, $maxAmount, $instructions, $isEnabled, json_encode($existingConfig), $gwId]
        );

        // Sync with legacy settings table for backward compatibility if razorpay
        if ($gw['code'] === 'razorpay') {
            set_setting('razorpay_enabled', (string)$isEnabled);
            if (isset($existingConfig['key_id'])) {
                set_setting('razorpay_key_id', $existingConfig['key_id']);
            }
            if (isset($existingConfig['key_secret'])) {
                set_setting('razorpay_key_secret', $existingConfig['key_secret']);
            }
            if (isset($existingConfig['webhook_secret'])) {
                set_setting('razorpay_webhook_secret', $existingConfig['webhook_secret']);
            }
        }

        flash_set('success', "Payment gateway '{$gw['name']}' updated successfully.");
        redirect('/admin/payment-gateways');
        exit;

    case '/admin/payment-gateways/toggle':
        Auth::requireAdmin();
        verify_csrf();

        $gwId = (int)($_POST['gateway_id'] ?? 0);
        $gw = DB::fetch("SELECT * FROM payment_gateways WHERE id = ?", [$gwId]);
        if ($gw) {
            $newStatus = $gw['is_enabled'] ? 0 : 1;
            DB::query("UPDATE payment_gateways SET is_enabled = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $gwId]);
            if ($gw['code'] === 'razorpay') {
                set_setting('razorpay_enabled', (string)$newStatus);
            }
            flash_set('success', "Gateway status updated.");
        }
        redirect('/admin/payment-gateways');
        exit;

    case '/admin/banners':
        Auth::requireAdmin();
        require __DIR__ . '/themes/classic/views/admin/banners.php';
        exit;

    case '/admin/banners/create':
        Auth::requireAdmin();
        verify_csrf();

        $heading = trim($_POST['heading'] ?? '');
        $subheading = trim($_POST['subheading'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $ctaText = trim($_POST['cta_text'] ?? 'Explore Services');
        $ctaLink = trim($_POST['cta_link'] ?? '/new-order');
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $isActive = (int)($_POST['is_active'] ?? 1);

        if (empty($heading) || empty($description)) {
            flash_set('danger', 'Heading and description are required.');
            redirect('/admin/banners');
            exit;
        }

        $imageUrl = '';
        if (!empty($_FILES['banner_image']['tmp_name']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['banner_image'];
            $maxSize = 4 * 1024 * 1024; // 4MB
            if ($file['size'] > $maxSize) {
                flash_set('danger', 'Uploaded image exceeds 4MB limit.');
                redirect('/admin/banners');
                exit;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowedMimes[$mimeType])) {
                flash_set('danger', 'Invalid file type. Only JPG, PNG, and WebP images are permitted.');
                redirect('/admin/banners');
                exit;
            }

            $ext = $allowedMimes[$mimeType];
            $filename = 'banner_' . bin2hex(random_bytes(10)) . '.' . $ext;
            $destDir = __DIR__ . '/uploads/banners';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $targetPath = $destDir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imageUrl = '/uploads/banners/' . $filename;
            }
        }

        DB::query(
            "INSERT INTO hero_banners (heading, subheading, description, cta_text, cta_link, image_url, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$heading, $subheading, $description, $ctaText, $ctaLink, $imageUrl, $sortOrder, $isActive]
        );

        flash_set('success', 'Hero banner created successfully.');
        redirect('/admin/banners');
        exit;

    case '/admin/banners/update':
        Auth::requireAdmin();
        verify_csrf();

        $bannerId = (int)($_POST['banner_id'] ?? 0);
        $banner = DB::fetch("SELECT * FROM hero_banners WHERE id = ?", [$bannerId]);
        if (!$banner) {
            flash_set('danger', 'Hero banner not found.');
            redirect('/admin/banners');
            exit;
        }

        $heading = trim($_POST['heading'] ?? '');
        $subheading = trim($_POST['subheading'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $ctaText = trim($_POST['cta_text'] ?? 'Explore Services');
        $ctaLink = trim($_POST['cta_link'] ?? '/new-order');
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $isActive = (int)($_POST['is_active'] ?? 1);

        $imageUrl = $banner['image_url'];
        if (!empty($_FILES['banner_image']['tmp_name']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['banner_image'];
            $maxSize = 4 * 1024 * 1024; // 4MB
            if ($file['size'] <= $maxSize) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (isset($allowedMimes[$mimeType])) {
                    $ext = $allowedMimes[$mimeType];
                    $filename = 'banner_' . bin2hex(random_bytes(10)) . '.' . $ext;
                    $destDir = __DIR__ . '/uploads/banners';
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    if (move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
                        $imageUrl = '/uploads/banners/' . $filename;
                    }
                }
            }
        }

        DB::query(
            "UPDATE hero_banners SET heading = ?, subheading = ?, description = ?, cta_text = ?, cta_link = ?, image_url = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
            [$heading, $subheading, $description, $ctaText, $ctaLink, $imageUrl, $sortOrder, $isActive, $bannerId]
        );

        flash_set('success', 'Hero banner updated successfully.');
        redirect('/admin/banners');
        exit;

    case '/admin/banners/toggle':
        Auth::requireAdmin();
        verify_csrf();

        $bannerId = (int)($_POST['banner_id'] ?? 0);
        DB::query("UPDATE hero_banners SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?", [$bannerId]);
        flash_set('success', 'Banner display status toggled.');
        redirect('/admin/banners');
        exit;

    case '/admin/banners/delete':
        Auth::requireAdmin();
        verify_csrf();

        $bannerId = (int)($_POST['banner_id'] ?? 0);
        $banner = DB::fetch("SELECT * FROM hero_banners WHERE id = ?", [$bannerId]);
        if ($banner) {
            if (!empty($banner['image_url']) && str_starts_with($banner['image_url'], '/uploads/banners/')) {
                $filePath = __DIR__ . $banner['image_url'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            DB::query("DELETE FROM hero_banners WHERE id = ?", [$bannerId]);
            flash_set('success', 'Hero banner deleted.');
        }
        redirect('/admin/banners');
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
