<?php
/**
 * SMM Panel - Real SMM Provider API Client (Standard SMM API v2)
 * Production cURL integration for real external provider APIs
 */

require_once __DIR__ . '/../db.php';

class SMMProvider {
    private array $provider;

    public function __construct(array $provider) {
        $this->provider = $provider;
    }

    public static function find(int $id): ?self {
        $data = DB::fetch("SELECT * FROM providers WHERE id = ? LIMIT 1", [$id]);
        return $data ? new self($data) : null;
    }

    /**
     * Send API Request to External Provider via cURL
     */
    private function request(array $params): array {
        $url = trim($this->provider['api_url']);
        if (empty($url) || empty($this->provider['api_key'])) {
            return [
                'success' => false,
                'error' => 'Provider API URL or API Key is missing.'
            ];
        }

        $params['key'] = $this->provider['api_key'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RoseSMM/1.0'
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error' => 'cURL connection error: ' . $curlError
            ];
        }

        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from provider (HTTP ' . $httpCode . '): ' . substr(strip_tags($response), 0, 150)
            ];
        }

        if (isset($json['error'])) {
            return [
                'success' => false,
                'error' => is_string($json['error']) ? $json['error'] : json_encode($json['error'])
            ];
        }

        return [
            'success' => true,
            'data' => $json
        ];
    }

    /**
     * Query Real Account Balance from Provider
     */
    public function getBalance(): array {
        $res = $this->request(['action' => 'balance']);
        if ($res['success']) {
            $balance = (float)($res['data']['balance'] ?? 0.0);
            $currency = $res['data']['currency'] ?? $this->provider['currency'];
            
            // Update last sync and balance in DB
            DB::query(
                "UPDATE providers SET balance = ?, currency = ?, last_sync = NOW() WHERE id = ?",
                [$balance, $currency, $this->provider['id']]
            );

            return [
                'success' => true,
                'balance' => $balance,
                'currency' => $currency
            ];
        }
        return $res;
    }

    /**
     * Query Real Available Services from Provider
     */
    public function getServices(): array {
        $res = $this->request(['action' => 'services']);
        if ($res['success'] && is_array($res['data'])) {
            return [
                'success' => true,
                'services' => $res['data']
            ];
        }
        return [
            'success' => false,
            'error' => $res['error'] ?? 'Could not retrieve services from provider API.'
        ];
    }

    /**
     * Place Real Order with External Provider
     */
    public function placeOrder(string $providerServiceId, string $link, int $quantity, ?array $additionalParams = []): array {
        $params = array_merge([
            'action'   => 'add',
            'service'  => $providerServiceId,
            'link'     => $link,
            'quantity' => $quantity
        ], $additionalParams ?? []);

        $res = $this->request($params);
        if ($res['success'] && !empty($res['data']['order'])) {
            return [
                'success' => true,
                'provider_order_id' => (string)$res['data']['order']
            ];
        }

        return [
            'success' => false,
            'error' => $res['error'] ?? 'Provider failed to return a valid order ID.'
        ];
    }

    /**
     * Query Status of a Single Real Order from Provider
     */
    public function getOrderStatus(string $providerOrderId): array {
        $res = $this->request([
            'action' => 'status',
            'order'  => $providerOrderId
        ]);

        if ($res['success'] && is_array($res['data'])) {
            $statusRaw = strtolower($res['data']['status'] ?? 'pending');
            // Normalize status to standard panel statuses
            $statusMap = [
                'pending'     => 'pending',
                'in progress' => 'in_progress',
                'inprogress'  => 'in_progress',
                'processing'  => 'processing',
                'completed'   => 'completed',
                'partial'     => 'partial',
                'canceled'    => 'canceled',
                'cancelled'   => 'canceled',
                'refunded'    => 'canceled'
            ];
            $status = $statusMap[$statusRaw] ?? 'processing';

            return [
                'success'     => true,
                'status'      => $status,
                'charge'      => (float)($res['data']['charge'] ?? 0),
                'start_count' => (int)($res['data']['start_count'] ?? 0),
                'remains'     => (int)($res['data']['remains'] ?? 0),
                'currency'    => $res['data']['currency'] ?? $this->provider['currency']
            ];
        }

        return $res;
    }
}
