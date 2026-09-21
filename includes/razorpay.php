<?php
/**
 * SMM Panel - Real Razorpay Payment Gateway Integration (INR Only)
 * Production-ready server-side order creation & HMAC-SHA256 signature verification in INR
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

class RazorpayGateway {
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;
    private bool $isEnabled;

    public function __construct() {
        $this->keyId = (string)get_setting('razorpay_key_id', '');
        $this->keySecret = (string)get_setting('razorpay_key_secret', '');
        $this->webhookSecret = (string)get_setting('razorpay_webhook_secret', '');
        $this->isEnabled = (bool)get_setting('razorpay_enabled', '0');
    }

    /**
     * Check if Razorpay is fully configured and ready
     */
    public function isConfigured(): bool {
        return !empty($this->keyId) && !empty($this->keySecret) && $this->isEnabled;
    }

    public function getKeyId(): string {
        return $this->keyId;
    }

    /**
     * Create Order on Razorpay API (POST https://api.razorpay.com/v1/orders)
     * Strictly INR Only - amount sent in paise (1 INR = 100 paise)
     */
    public function createOrder(int $userId, float $amountInr): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Razorpay payment gateway is not active or awaiting API credentials in Admin Settings.'
            ];
        }

        // Validate amount in INR
        $amountInr = round($amountInr, 2);
        if ($amountInr < 10.0) {
            return [
                'success' => false,
                'message' => 'Minimum deposit amount is ₹10.00 INR.'
            ];
        }

        // Amount in smallest currency sub-unit: Paise for INR
        $amountInPaise = (int)round($amountInr * 100);

        $receipt = 'rcpt_' . $userId . '_' . time();
        $payload = [
            'amount'          => $amountInPaise,
            'currency'        => 'INR',
            'receipt'         => $receipt,
            'payment_capture' => 1,
            'notes'           => [
                'user_id' => (string)$userId,
                'channel' => 'Rose SMM Panel Wallet Topup'
            ]
        ];

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_USERPWD        => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: Rose-SMM-Panel/1.0'
            ],
            CURLOPT_TIMEOUT        => 30,
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

        // Save pending payment record in database for idempotency & auditing in INR
        DB::query(
            "INSERT INTO payments (user_id, payment_method, order_id, amount, currency, converted_amount, status)
             VALUES (?, 'razorpay', ?, ?, 'INR', ?, 'pending')",
            [$userId, $razorpayOrderId, $amountInr, $amountInr]
        );

        return [
            'success'    => true,
            'order_id'   => $razorpayOrderId,
            'amount'     => $amountInPaise,
            'currency'   => 'INR',
            'key_id'     => $this->keyId,
            'amount_inr' => $amountInr
        ];
    }

    /**
     * Verify Server-Side Payment Signature and Credit Wallet in INR
     */
    public function verifyPayment(int $userId, string $razorpayOrderId, string $razorpayPaymentId, string $signature): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Payment gateway is not configured.'];
        }

        if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($signature)) {
            return ['success' => false, 'message' => 'Missing payment verification credentials.'];
        }

        // 1. Verify HMAC SHA256 Signature
        $generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);
        if (!hash_equals($generatedSignature, $signature)) {
            error_log("Razorpay Signature Mismatch for Order: {$razorpayOrderId}");
            return ['success' => false, 'message' => 'Payment verification failed: Invalid signature.'];
        }

        // 2. Fetch Payment Record with DB Transaction & Row Locking
        try {
            DB::beginTransaction();

            $payment = DB::fetch(
                "SELECT * FROM payments WHERE order_id = ? AND user_id = ? FOR UPDATE",
                [$razorpayOrderId, $userId]
            );

            if (!$payment) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Payment order record not found.'];
            }

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

            // 4. Lock user balance row and credit funds in INR
            $user = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
            if (!$user) {
                DB::rollBack();
                return ['success' => false, 'message' => 'User account not found.'];
            }

            $amountInr = (float)$payment['amount'];
            $balanceBefore = (float)$user['balance'];
            $balanceAfter = round($balanceBefore + $amountInr, 4);

            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$balanceAfter, $userId]);

            // 5. Add Ledger Transaction Record in INR
            DB::query(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, reference_id, description)
                 VALUES (?, ?, ?, ?, 'deposit', ?, ?)",
                [$userId, $amountInr, $balanceBefore, $balanceAfter, $razorpayPaymentId, "Deposit via Razorpay (Order: {$razorpayOrderId})"]
            );

            DB::commit();

            return [
                'success'     => true,
                'message'     => 'Payment verified successfully! ₹' . number_format($amountInr, 2) . ' credited to your wallet.',
                'new_balance' => $balanceAfter
            ];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Payment verification transaction failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'An internal database error occurred while crediting wallet.'];
        }
    }

    /**
     * Webhook Handler for automated asynchronous payment capture verification
     */
    public function handleWebhook(string $rawPayload, string $signature): array {
        if (!empty($this->webhookSecret)) {
            $expectedSignature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
            if (!hash_equals($expectedSignature, $signature)) {
                return ['success' => false, 'message' => 'Invalid webhook signature'];
            }
        }

        $event = json_decode($rawPayload, true);
        if (!$event || empty($event['event'])) {
            return ['success' => false, 'message' => 'Invalid event payload'];
        }

        if (in_array($event['event'], ['payment.captured', 'order.paid'])) {
            $paymentObj = $event['payload']['payment']['entity'] ?? [];
            $orderId = $paymentObj['order_id'] ?? '';
            $paymentId = $paymentObj['id'] ?? '';

            if (!empty($orderId)) {
                $payment = DB::fetch("SELECT * FROM payments WHERE order_id = ? LIMIT 1", [$orderId]);
                if ($payment && $payment['status'] === 'pending') {
                    $userId = (int)$payment['user_id'];
                    $amountInr = (float)$payment['amount'];

                    DB::beginTransaction();
                    try {
                        DB::query("UPDATE payments SET payment_id = ?, status = 'completed', updated_at = NOW() WHERE id = ?", [$paymentId, $payment['id']]);
                        $user = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
                        if ($user) {
                            $balanceBefore = (float)$user['balance'];
                            $balanceAfter = round($balanceBefore + $amountInr, 4);
                            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$balanceAfter, $userId]);
                            DB::query(
                                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, reference_id, description)
                                 VALUES (?, ?, ?, ?, 'deposit', ?, ?)",
                                [$userId, $amountInr, $balanceBefore, $balanceAfter, $paymentId, "Webhook Deposit via Razorpay (Order: {$orderId})"]
                            );
                        }
                        DB::commit();
                        return ['success' => true, 'message' => 'Webhook processed successfully'];
                    } catch (Exception $e) {
                        DB::rollBack();
                        return ['success' => false, 'message' => $e->getMessage()];
                    }
                }
            }
        }

        return ['success' => true, 'message' => 'Event ignored'];
    }
}
