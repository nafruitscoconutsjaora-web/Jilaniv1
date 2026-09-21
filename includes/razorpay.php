<?php
/**
 * SMM Panel - Real Razorpay Payment Gateway Integration
 * INR Only - Production-ready server-side order creation, HMAC-SHA256 signature verification & Webhook
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/currency.php';

class RazorpayGateway {
    private string $keyId = '';
    private string $keySecret = '';
    private string $webhookSecret = '';
    private bool $isEnabled = false;
    private float $minAmount = 10.0;
    private float $maxAmount = 50000.0;
    private ?array $gatewayRecord = null;

    public function __construct() {
        // Load dynamically from payment_gateways table
        try {
            $gw = DB::fetch("SELECT * FROM payment_gateways WHERE code = 'razorpay' LIMIT 1");
            if ($gw) {
                $this->gatewayRecord = $gw;
                $this->isEnabled = (bool)$gw['is_enabled'];
                $this->minAmount = (float)($gw['min_amount'] ?? 10.0);
                $this->maxAmount = (float)($gw['max_amount'] ?? 50000.0);

                if (!empty($gw['config'])) {
                    $config = json_decode($gw['config'], true);
                    if (is_array($config)) {
                        $this->keyId = trim($config['key_id'] ?? '');
                        $this->keySecret = trim($config['key_secret'] ?? '');
                        $this->webhookSecret = trim($config['webhook_secret'] ?? '');
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to load gateway config from DB: " . $e->getMessage());
        }

        // Fallback to legacy settings table if config is empty
        if (empty($this->keyId)) {
            $this->keyId = (string)get_setting('razorpay_key_id', '');
        }
        if (empty($this->keySecret)) {
            $this->keySecret = (string)get_setting('razorpay_key_secret', '');
        }
        if (empty($this->webhookSecret)) {
            $this->webhookSecret = (string)get_setting('razorpay_webhook_secret', '');
        }
    }

    /**
     * Check if Razorpay is fully configured and enabled by admin
     */
    public function isConfigured(): bool {
        return !empty($this->keyId) && !empty($this->keySecret) && $this->isEnabled;
    }

    public function isEnabled(): bool {
        return $this->isEnabled;
    }

    public function getKeyId(): string {
        return $this->keyId;
    }

    public function getMinAmount(): float {
        return $this->minAmount;
    }

    public function getMaxAmount(): float {
        return $this->maxAmount;
    }

    /**
     * Create Order on Razorpay API strictly in INR (POST https://api.razorpay.com/v1/orders)
     * Amount sent is in Paise (1 INR = 100 Paise)
     */
    public function createOrder(int $userId, float $amountInr): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Razorpay payment gateway is currently disabled or awaiting API credentials in Payment Gateway Manager.'
            ];
        }

        if ($amountInr < $this->minAmount) {
            return [
                'success' => false,
                'message' => 'Minimum deposit amount is ₹' . number_format($this->minAmount, 2) . ' INR.'
            ];
        }

        if ($amountInr > $this->maxAmount) {
            return [
                'success' => false,
                'message' => 'Maximum deposit amount is ₹' . number_format($this->maxAmount, 2) . ' INR.'
            ];
        }

        // Razorpay expects amount in smallest currency sub-unit: Paise (e.g. ₹100 = 10000 paise)
        $amountInPaise = (int)round($amountInr * 100);

        // Convert INR amount to base currency (USD) for ledger balance calculation
        $inrCurrency = Currency::get('INR');
        $inrRate = (float)($inrCurrency['rate'] ?? 90.0);
        $amountBaseUsd = Currency::convertToUsd($amountInr, $inrRate);

        $receipt = 'rcpt_' . $userId . '_' . time();
        $payload = [
            'amount' => $amountInPaise,
            'currency' => 'INR', // Strictly INR only
            'receipt' => $receipt,
            'payment_capture' => 1
        ];

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_USERPWD => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Rose-SMM-Panel/1.0'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log("Razorpay cURL Error: " . $curlError);
            return [
                'success' => false,
                'message' => 'Network error connecting to payment gateway: ' . $curlError
            ];
        }

        $data = json_decode($response, true);
        if ($httpCode !== 200 || empty($data['id'])) {
            $errorDesc = $data['error']['description'] ?? 'Failed to initialize payment with gateway.';
            return [
                'success' => false,
                'message' => 'Razorpay Error: ' . $errorDesc
            ];
        }

        $razorpayOrderId = $data['id'];

        // Save pending payment record in database for idempotency & auditing
        DB::query(
            "INSERT INTO payments (user_id, payment_method, order_id, amount, currency, converted_amount, status)
             VALUES (?, 'razorpay', ?, ?, 'INR', ?, 'pending')",
            [$userId, $razorpayOrderId, $amountInr, $amountBaseUsd]
        );

        return [
            'success' => true,
            'order_id' => $razorpayOrderId,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'key_id' => $this->keyId,
            'amount_inr' => $amountInr,
            'converted_base_amount' => $amountBaseUsd
        ];
    }

    /**
     * Verify Server-Side Payment Signature and Credit Wallet
     */
    public function verifyPayment(int $userId, string $razorpayOrderId, string $razorpayPaymentId, string $signature): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Payment gateway is not configured or disabled.'];
        }

        // 1. Verify HMAC SHA256 Signature
        $generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);
        if (!hash_equals($generatedSignature, $signature)) {
            error_log("Razorpay Signature Mismatch for Order: {$razorpayOrderId}");
            return ['success' => false, 'message' => 'Payment verification failed: Invalid signature.'];
        }

        return $this->creditWalletForOrder($razorpayOrderId, $razorpayPaymentId, $signature, $userId);
    }

    /**
     * Verify and Process Webhook Notification
     */
    public function verifyWebhook(string $rawPayload, string $webhookSignature): array {
        if (empty($this->webhookSecret)) {
            return ['success' => false, 'message' => 'Webhook secret is not configured.'];
        }

        $expectedSignature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
        if (!hash_equals($expectedSignature, $webhookSignature)) {
            error_log("Razorpay Webhook Signature Mismatch");
            return ['success' => false, 'message' => 'Invalid webhook signature.'];
        }

        $data = json_decode($rawPayload, true);
        if (!$data || empty($data['event'])) {
            return ['success' => false, 'message' => 'Invalid webhook payload.'];
        }

        // Handle payment.captured or order.paid
        $event = $data['event'];
        if ($event === 'payment.captured' || $event === 'order.paid') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? [];
            $orderId = $paymentEntity['order_id'] ?? '';
            $paymentId = $paymentEntity['id'] ?? '';

            if (!empty($orderId) && !empty($paymentId)) {
                return $this->creditWalletForOrder($orderId, $paymentId, 'webhook_verified');
            }
        }

        return ['success' => true, 'message' => 'Event noted: ' . $event];
    }

    /**
     * Centralized Atomic Wallet Crediting with Row Locking (Idempotency Guaranteed)
     */
    private function creditWalletForOrder(string $razorpayOrderId, string $razorpayPaymentId, string $signature, ?int $expectedUserId = null): array {
        try {
            DB::beginTransaction();

            $sql = "SELECT * FROM payments WHERE order_id = ? FOR UPDATE";
            $payment = DB::fetch($sql, [$razorpayOrderId]);

            if (!$payment) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Payment order record not found.'];
            }

            if ($expectedUserId !== null && (int)$payment['user_id'] !== $expectedUserId) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Payment user mismatch.'];
            }

            $userId = (int)$payment['user_id'];

            // Prevent duplicate crediting
            if ($payment['status'] === 'completed') {
                DB::rollBack();
                return ['success' => true, 'message' => 'Payment was already verified and credited previously.'];
            }

            // 3. Update payment record
            DB::query(
                "UPDATE payments SET payment_id = ?, signature = ?, status = 'completed', updated_at = NOW() WHERE id = ?",
                [$razorpayPaymentId, $signature, $payment['id']]
            );

            // 4. Lock user balance row and credit funds
            $user = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
            if (!$user) {
                DB::rollBack();
                return ['success' => false, 'message' => 'User account not found.'];
            }

            $amountBase = (float)$payment['converted_amount'];
            $balanceBefore = (float)$user['balance'];
            $balanceAfter = round($balanceBefore + $amountBase, 4);

            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$balanceAfter, $userId]);

            // 5. Add Ledger Transaction Record
            DB::query(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, reference_id, description)
                 VALUES (?, ?, ?, ?, 'deposit', ?, ?)",
                [
                    $userId,
                    $amountBase,
                    $balanceBefore,
                    $balanceAfter,
                    $razorpayPaymentId,
                    "Deposit via Razorpay ₹" . number_format((float)$payment['amount'], 2) . " INR (Order: {$razorpayOrderId})"
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment verified successfully! Your wallet has been credited.',
                'new_balance' => $balanceAfter
            ];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Payment verification transaction failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'An internal database error occurred while crediting wallet.'];
        }
    }
}
