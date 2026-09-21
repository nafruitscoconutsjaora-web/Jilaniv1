<?php
/**
 * SMM Panel - Real Razorpay Payment Gateway Integration
 * Production-ready server-side order creation & HMAC-SHA256 signature verification
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
     */
    public function createOrder(int $userId, float $amountUsd, string $currencyCode = 'INR', float $exchangeRate = 1.0): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Razorpay payment gateway is not configured yet. Please enter valid Razorpay credentials in Admin Settings.'
            ];
        }

        // Razorpay supports currencies like INR, USD, EUR, etc.
        // Convert amount to target currency
        $convertedAmount = round($amountUsd * $exchangeRate, 2);
        // Razorpay expects amount in smallest currency sub-unit (e.g. paise for INR, cents for USD)
        $amountInSubunits = (int)round($convertedAmount * 100);

        if ($amountInSubunits < 100) { // Minimum 1.00
            return [
                'success' => false,
                'message' => 'Minimum deposit amount is $1.00.'
            ];
        }

        $receipt = 'rcpt_' . $userId . '_' . time();
        $payload = [
            'amount' => $amountInSubunits,
            'currency' => strtoupper($currencyCode),
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
             VALUES (?, 'razorpay', ?, ?, ?, ?, 'pending')",
            [$userId, $razorpayOrderId, $amountUsd, $currencyCode, $convertedAmount]
        );

        return [
            'success' => true,
            'order_id' => $razorpayOrderId,
            'amount' => $amountInSubunits,
            'currency' => strtoupper($currencyCode),
            'key_id' => $this->keyId,
            'converted_amount' => $convertedAmount
        ];
    }

    /**
     * Verify Server-Side Payment Signature and Credit Wallet
     */
    public function verifyPayment(int $userId, string $razorpayOrderId, string $razorpayPaymentId, string $signature): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Payment gateway is not configured.'];
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

            // 4. Lock user balance row and credit funds
            $user = DB::fetch("SELECT id, balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
            if (!$user) {
                DB::rollBack();
                return ['success' => false, 'message' => 'User account not found.'];
            }

            $amountUsd = (float)$payment['amount'];
            $balanceBefore = (float)$user['balance'];
            $balanceAfter = round($balanceBefore + $amountUsd, 4);

            DB::query("UPDATE users SET balance = ? WHERE id = ?", [$balanceAfter, $userId]);

            // 5. Add Ledger Transaction Record
            DB::query(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, reference_id, description)
                 VALUES (?, ?, ?, ?, 'deposit', ?, ?)",
                [$userId, $amountUsd, $balanceBefore, $balanceAfter, $razorpayPaymentId, "Deposit via Razorpay (Order: {$razorpayOrderId})"]
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
