<?php
/**
 * Rose SMM Panel - Standard SMM API v2
 * Fully compliant programmatic API for external clients & scripts
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/currency.php';
require_once __DIR__ . '/includes/smm_provider.php';

header('Content-Type: application/json; charset=utf-8');

$apiKey = trim($_POST['key'] ?? '');
$action = trim($_POST['action'] ?? '');

if (empty($apiKey)) {
    json_response(['error' => 'API key is missing'], 401);
}

// Authenticate user via API key
$user = DB::fetch("SELECT * FROM users WHERE api_key = ? AND status = 'active' LIMIT 1", [$apiKey]);
if (!$user) {
    json_response(['error' => 'Invalid or inactive API key'], 401);
}

$userId = (int)$user['id'];
$userCurrency = Currency::getUserCurrency($user);

switch ($action) {
    case 'balance':
        json_response([
            'balance'  => number_format((float)$user['balance'], 4, '.', ''),
            'currency' => 'USD'
        ]);
        break;

    case 'services':
        $services = DB::fetchAll(
            "SELECT s.id as service, s.name, c.name as category, s.rate, s.min_quantity as min, s.max_quantity as max, s.type, s.description 
             FROM services s 
             JOIN categories c ON s.category_id = c.id 
             WHERE s.status = 'active' 
             ORDER BY c.sort_order ASC, s.id ASC"
        );
        json_response($services);
        break;

    case 'add':
        $serviceId = (int)($_POST['service'] ?? 0);
        $link = trim($_POST['link'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($serviceId <= 0 || empty($link) || $quantity <= 0) {
            json_response(['error' => 'Missing or invalid parameters: service, link, and quantity are required'], 400);
        }

        $service = DB::fetch("SELECT * FROM services WHERE id = ? AND status = 'active' LIMIT 1", [$serviceId]);
        if (!$service) {
            json_response(['error' => 'Service not found or currently inactive'], 404);
        }

        if ($quantity < (int)$service['min_quantity']) {
            json_response(['error' => 'Quantity is less than minimum allowed: ' . $service['min_quantity']], 400);
        }

        if ($quantity > (int)$service['max_quantity']) {
            json_response(['error' => 'Quantity exceeds maximum allowed: ' . $service['max_quantity']], 400);
        }

        $costUsd = round(((float)$service['rate'] / 1000) * $quantity, 4);

        try {
            DB::beginTransaction();

            // Lock user row
            $userRow = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
            $currentBalance = (float)$userRow['balance'];

            if ($currentBalance < $costUsd) {
                DB::rollBack();
                json_response(['error' => 'Not enough funds on your balance'], 400);
            }

            $balanceAfter = round($currentBalance - $costUsd, 4);
            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$balanceAfter, $userId]);

            // Save order snapshot
            $snapshot = Currency::createPricingSnapshot((float)$service['rate'], $quantity, $userCurrency);

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

            // Record transaction ledger
            DB::query(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, reference_id, description) 
                 VALUES (?, ?, ?, ?, 'order', ?, ?)",
                [$userId, $costUsd, $currentBalance, $balanceAfter, (string)$orderId, "API Order #{$orderId} for {$service['name']}"]
            );

            // If service has connected provider, attempt real dispatch
            $providerOrderId = null;
            if (!empty($service['provider_id']) && !empty($service['provider_service_id'])) {
                $providerObj = SMMProvider::find((int)$service['provider_id']);
                if ($providerObj) {
                    $provRes = $providerObj->placeOrder($service['provider_service_id'], $link, $quantity);
                    if ($provRes['success']) {
                        $providerOrderId = $provRes['provider_order_id'];
                        DB::query(
                            "UPDATE orders SET provider_order_id = ?, status = 'processing' WHERE id = ?",
                            [$providerOrderId, $orderId]
                        );
                    }
                }
            }

            DB::commit();

            json_response(['order' => $orderId]);
        } catch (Exception $e) {
            DB::rollBack();
            json_response(['error' => 'Internal server error processing order: ' . $e->getMessage()], 500);
        }
        break;

    case 'status':
        $orderId = (int)($_POST['order'] ?? 0);
        if ($orderId <= 0) {
            json_response(['error' => 'Missing order ID'], 400);
        }

        $order = DB::fetch("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1", [$orderId, $userId]);
        if (!$order) {
            json_response(['error' => 'Incorrect order ID'], 404);
        }

        json_response([
            'charge'      => number_format((float)$order['charge'], 4, '.', ''),
            'start_count' => (string)$order['start_count'],
            'status'      => $order['status'] === 'in_progress' ? 'In progress' : ucfirst($order['status']),
            'remains'     => (string)$order['remains'],
            'currency'    => 'USD'
        ]);
        break;

    default:
        json_response(['error' => 'Invalid action: ' . $action], 400);
        break;
}
