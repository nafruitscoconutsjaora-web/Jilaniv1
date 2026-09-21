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
        $url = trim((string)($this->provider['api_url'] ?? ''));
        $key = trim((string)($this->provider['api_key'] ?? ''));

        if (empty($url) || empty($key)) {
            return [
                'success' => false,
                'error' => 'Provider API URL or API Key is missing. Please configure them in Providers settings.'
            ];
        }

        $params['key'] = $key;

        try {
            $ch = curl_init();
            if ($ch === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to initialize cURL on server.'
                ];
            }

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RoseSMM/1.0'
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'Connection to provider failed: ' . $curlError
                ];
            }

            if ($response === false || trim((string)$response) === '') {
                return [
                    'success' => false,
                    'error' => 'Empty response received from provider API (HTTP ' . $httpCode . ').'
                ];
            }

            $json = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
                $snippet = substr(trim(strip_tags((string)$response)), 0, 160);
                return [
                    'success' => false,
                    'error' => 'Provider API returned non-JSON response (HTTP ' . $httpCode . ')' . ($snippet !== '' ? ': ' . $snippet : '.')
                ];
            }

            // Standard SMM API error response: {"error": "..."}
            if (isset($json['error'])) {
                $errMsg = is_string($json['error']) ? $json['error'] : json_encode($json['error']);
                return [
                    'success' => false,
                    'error' => $errMsg
                ];
            }

            // Error array: {"errors": ["..."]}
            if (isset($json['errors'])) {
                $errMsg = is_array($json['errors']) ? implode(', ', $json['errors']) : (string)$json['errors'];
                return [
                    'success' => false,
                    'error' => $errMsg
                ];
            }

            // Status fail response: {"status": "fail", "message": "..."}
            if (isset($json['status']) && in_array(strtolower((string)$json['status']), ['error', 'fail', 'failed'])) {
                $errMsg = $json['message'] ?? $json['error'] ?? 'Provider returned status: ' . $json['status'];
                return [
                    'success' => false,
                    'error' => is_string($errMsg) ? $errMsg : json_encode($errMsg)
                ];
            }

            if ($httpCode >= 400) {
                $errMsg = $json['message'] ?? $json['error'] ?? ('HTTP Error ' . $httpCode);
                return [
                    'success' => false,
                    'error' => is_string($errMsg) ? $errMsg : json_encode($errMsg)
                ];
            }

            return [
                'success' => true,
                'data' => $json
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Provider request error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Query Real Account Balance from Provider
     */
    public function getBalance(): array {
        $res = $this->request(['action' => 'balance']);
        if ($res['success'] && is_array($res['data'])) {
            $balance = (float)($res['data']['balance'] ?? 0.0);
            $currency = (string)($res['data']['currency'] ?? $this->provider['currency']);
            
            // Update last sync and balance in DB
            try {
                DB::query(
                    "UPDATE providers SET balance = ?, currency = ?, last_sync = NOW() WHERE id = ?",
                    [$balance, $currency, $this->provider['id']]
                );
            } catch (\Throwable $e) {
                error_log("Failed to update provider balance in DB: " . $e->getMessage());
            }

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
        if (!$res['success']) {
            return [
                'success' => false,
                'error' => $res['error'] ?? 'Could not retrieve services from provider API.'
            ];
        }

        $data = $res['data'] ?? null;
        if (!is_array($data)) {
            return [
                'success' => false,
                'error' => 'Provider returned invalid data format for services.'
            ];
        }

        // Some providers wrap services list under a sub-key
        if (isset($data['services']) && is_array($data['services'])) {
            $data = $data['services'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        // If it is not a sequential list, check if it's an error message object
        if (!array_is_list($data)) {
            $err = $data['message'] ?? $data['error'] ?? $data['description'] ?? null;
            if ($err !== null) {
                return [
                    'success' => false,
                    'error' => is_string($err) ? $err : json_encode($err)
                ];
            }
        }

        // Filter and sanitize each service entry
        $cleanServices = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $srvId = trim((string)($item['service'] ?? $item['id'] ?? ''));
            if ($srvId === '') {
                continue;
            }

            $rawRate = str_replace(['$', ',', ' '], '', (string)($item['rate'] ?? '0'));
            $rate = is_numeric($rawRate) ? max(0.0, (float)$rawRate) : 0.0;

            $rawMin = str_replace([',', ' '], '', (string)($item['min'] ?? '10'));
            $min = (is_numeric($rawMin) && (int)$rawMin > 0) ? (int)$rawMin : 10;

            $rawMax = str_replace([',', ' '], '', (string)($item['max'] ?? '10000'));
            $max = (is_numeric($rawMax) && (int)$rawMax > 0) ? (int)$rawMax : 10000;
            if ($max < $min) {
                $max = $min * 100;
            }

            $cleanServices[] = [
                'service'  => $srvId,
                'name'     => trim((string)($item['name'] ?? "Service #{$srvId}")),
                'category' => trim((string)($item['category'] ?? 'General')),
                'rate'     => $rate,
                'min'      => $min,
                'max'      => $max,
                'type'     => trim((string)($item['type'] ?? 'Default')),
            ];
        }

        return [
            'success'  => true,
            'services' => $cleanServices
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
