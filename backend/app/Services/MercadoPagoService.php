<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mercado Pago Payment Service
 * 
 * Handles integration with Mercado Pago's payment API
 * Supports PIX, Credit Card, Boleto, and other payment methods
 */
class MercadoPagoService
{
    // API Endpoints by country
    const API_BASE_URL = [
        'MLB' => 'https://api.mercadopago.com', // Brazil
        'MLA' => 'https://api.mercadopago.com', // Argentina
        'MLM' => 'https://api.mercadopago.com', // Mexico
        'MLU' => 'https://api.mercadopago.com', // Uruguay
        'MLC' => 'https://api.mercadopago.com', // Chile
        'MCO' => 'https://api.mercadopago.com', // Colombia
        'MPE' => 'https://api.mercadopago.com', // Peru
    ];

    protected $accessToken;
    protected $publicKey;
    protected $country; // MLB for Brazil, MLA for Argentina, etc

    /**
     * Constructor
     * 
     * @param string $accessToken Private access token
     * @param string $publicKey Public key (optional, for frontend)
     * @param string $country Country code (MLB, MLA, etc) default: MLB (Brazil)
     */
    public function __construct(string $accessToken, string $publicKey = null, string $country = 'MLB')
    {
        $this->accessToken = $accessToken;
        $this->publicKey = $publicKey;
        $this->country = $country;
    }

    /**
     * Get base URL for API requests
     */
    protected function getBaseUrl(): string
    {
        return self::API_BASE_URL[$this->country] ?? self::API_BASE_URL['MLB'];
    }

    /**
     * Make authenticated request to Mercado Pago API
     */
    protected function makeRequest(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        try {
            $url = $this->getBaseUrl() . $endpoint;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'X-Idempotency-Key' => uniqid('mp_', true), // Prevent duplicate payments
            ])->$method($url, $data);

            $statusCode = $response->status();
            $responseData = $response->json();

            if ($response->successful()) {
                Log::info('MercadoPago: Request successful', [
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                    'status' => $statusCode,
                ];
            }

            $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            
            Log::error('MercadoPago: Request failed', [
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'error' => $errorMessage,
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status' => $statusCode,
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('MercadoPago: Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create PIX payment
     * 
     * @param array $paymentData Payment information
     * @return array
     */
    public function createPixPayment(array $paymentData): array
    {
        $payment = [
            'transaction_amount' => $paymentData['amount'],
            'description' => $paymentData['description'] ?? 'Pagamento',
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $paymentData['payer_email'],
                'first_name' => $paymentData['payer_first_name'] ?? '',
                'last_name' => $paymentData['payer_last_name'] ?? '',
            ],
        ];

        // Add CPF/CNPJ if available
        if (!empty($paymentData['payer_identification'])) {
            $payment['payer']['identification'] = [
                'type' => $paymentData['payer_identification_type'] ?? 'CPF',
                'number' => $paymentData['payer_identification'],
            ];
        }

        // Add additional info
        if (!empty($paymentData['external_reference'])) {
            $payment['external_reference'] = $paymentData['external_reference'];
        }

        $result = $this->makeRequest('/v1/payments', $payment);

        if ($result['success'] && isset($result['data']['point_of_interaction'])) {
            // Extract PIX information
            $pixData = $result['data']['point_of_interaction']['transaction_data'];
            
            return [
                'success' => true,
                'payment_id' => $result['data']['id'],
                'status' => $result['data']['status'],
                'qr_code' => $pixData['qr_code'] ?? null,
                'qr_code_base64' => $pixData['qr_code_base64'] ?? null,
                'ticket_url' => $pixData['ticket_url'] ?? null,
                'expiration_date' => $result['data']['date_of_expiration'] ?? null,
                'raw_response' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create Credit Card payment
     * 
     * @param array $paymentData Payment information including card token
     * @return array
     */
    public function createCardPayment(array $paymentData): array
    {
        $payment = [
            'transaction_amount' => $paymentData['amount'],
            'token' => $paymentData['token'], // Card token from frontend
            'description' => $paymentData['description'] ?? 'Pagamento',
            'installments' => $paymentData['installments'] ?? 1,
            'payment_method_id' => $paymentData['payment_method_id'],
            'issuer_id' => $paymentData['issuer_id'] ?? null,
            'payer' => [
                'email' => $paymentData['payer_email'],
                'identification' => [
                    'type' => $paymentData['payer_identification_type'] ?? 'CPF',
                    'number' => $paymentData['payer_identification'],
                ],
            ],
        ];

        if (!empty($paymentData['external_reference'])) {
            $payment['external_reference'] = $paymentData['external_reference'];
        }

        return $this->makeRequest('/v1/payments', $payment);
    }

    /**
     * Create Boleto payment
     * 
     * @param array $paymentData Payment information
     * @return array
     */
    public function createBoletoPayment(array $paymentData): array
    {
        $payment = [
            'transaction_amount' => $paymentData['amount'],
            'description' => $paymentData['description'] ?? 'Pagamento',
            'payment_method_id' => 'bolbradesco', // or other boleto types
            'payer' => [
                'email' => $paymentData['payer_email'],
                'first_name' => $paymentData['payer_first_name'] ?? '',
                'last_name' => $paymentData['payer_last_name'] ?? '',
                'identification' => [
                    'type' => $paymentData['payer_identification_type'] ?? 'CPF',
                    'number' => $paymentData['payer_identification'],
                ],
            ],
        ];

        if (!empty($paymentData['external_reference'])) {
            $payment['external_reference'] = $paymentData['external_reference'];
        }

        $result = $this->makeRequest('/v1/payments', $payment);

        if ($result['success']) {
            return [
                'success' => true,
                'payment_id' => $result['data']['id'],
                'status' => $result['data']['status'],
                'boleto_url' => $result['data']['transaction_details']['external_resource_url'] ?? null,
                'barcode' => $result['data']['barcode']['content'] ?? null,
                'expiration_date' => $result['data']['date_of_expiration'] ?? null,
                'raw_response' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get payment by ID
     * 
     * @param string $paymentId Payment ID
     * @return array
     */
    public function getPayment(string $paymentId): array
    {
        return $this->makeRequest("/v1/payments/{$paymentId}", [], 'GET');
    }

    /**
     * Refund payment
     * 
     * @param string $paymentId Payment ID
     * @param float|null $amount Amount to refund (null for full refund)
     * @return array
     */
    public function refundPayment(string $paymentId, ?float $amount = null): array
    {
        $data = $amount ? ['amount' => $amount] : [];
        return $this->makeRequest("/v1/payments/{$paymentId}/refunds", $data);
    }

    /**
     * Get available payment methods
     * 
     * @return array
     */
    public function getPaymentMethods(): array
    {
        return $this->makeRequest('/v1/payment_methods', [], 'GET');
    }

    /**
     * Create preference for Checkout Pro (redirect)
     * 
     * @param array $preferenceData Preference data
     * @return array
     */
    public function createPreference(array $preferenceData): array
    {
        $preference = [
            'items' => $preferenceData['items'],
            'payer' => $preferenceData['payer'] ?? [],
            'back_urls' => $preferenceData['back_urls'] ?? [],
            'auto_return' => $preferenceData['auto_return'] ?? 'approved',
            'notification_url' => $preferenceData['notification_url'] ?? null,
            'external_reference' => $preferenceData['external_reference'] ?? null,
        ];

        $result = $this->makeRequest('/checkout/preferences', $preference);

        if ($result['success']) {
            return [
                'success' => true,
                'preference_id' => $result['data']['id'],
                'init_point' => $result['data']['init_point'], // URL to redirect user
                'sandbox_init_point' => $result['data']['sandbox_init_point'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Get public key (for frontend use)
     * 
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey ?? '';
    }
}
