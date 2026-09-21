<?php
/**
 * Rose SMM Panel - Automated Cron Sync
 * Runs via CLI or Webhook with secure key to synchronize orders with external providers
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/smm_provider.php';

$cronKey = get_setting('cron_key', 'cron_smm_secure_key_2026');
$providedKey = $_GET['key'] ?? ($argv[1] ?? '');

// Ensure CLI or authenticated web request
if (php_sapi_name() !== 'cli' && (!hash_equals($cronKey, $providedKey))) {
    http_response_code(403);
    die("Access Denied: Invalid cron secret key.\n");
}

echo "[" . date('Y-m-d H:i:s') . "] Starting automated order status sync...\n";

// Find orders needing sync
$orders = DB::fetchAll(
    "SELECT o.*, p.api_url, p.api_key, p.currency as prov_currency 
     FROM orders o 
     JOIN providers p ON o.provider_id = p.id 
     WHERE o.provider_order_id IS NOT NULL 
       AND o.status IN ('pending', 'processing', 'in_progress') 
     ORDER BY o.id ASC LIMIT 50"
);

$syncedCount = 0;

foreach ($orders as $ord) {
    $providerObj = SMMProvider::find((int)$ord['provider_id']);
    if (!$providerObj) continue;

    $res = $providerObj->getOrderStatus((string)$ord['provider_order_id']);
    if ($res['success']) {
        $newStatus = $res['status'];
        $startCount = $res['start_count'];
        $remains = $res['remains'];

        DB::query(
            "UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $startCount, $remains, $ord['id']]
        );

        echo "Order #{$ord['id']} updated: status={$newStatus}, remains={$remains}\n";
        $syncedCount++;
    } else {
        echo "Order #{$ord['id']} check error: " . ($res['error'] ?? 'Unknown error') . "\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Sync finished. Processed: {$syncedCount} orders.\n";
