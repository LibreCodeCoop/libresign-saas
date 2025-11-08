<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sicoob Payment Service
 * 
 * Handles integration with Sicoob's payment API for PIX and Boleto
 * Requires mTLS certificate authentication
 */
class SicoobPaymentService
{
    // API Endpoints
    const AUTH_ENDPOINT = 'https://auth.sicoob.com.br/auth/realms/cooperado/protocol/openid-connect/token';
    const PIX_ENDPOINT = 'https://api.sicoob.com.br/pix/api/v2';
    const BOLETO_ENDPOINT = 'https://api.sicoob.com.br/cobranca-bancaria/v3/boletos';

    // Scopes
    const PIX_SCOPE = 'cob.read cob.write cobv.write cobv.read lotecobv.write lotecobv.read pix.write pix.read webhook.read webhook.write payloadlocation.write payloadlocation.read';
    const BOLETO_SCOPE = 'boletos_inclusao boletos_consulta boletos_alteracao';

    protected $clientId;
    protected $certificatePath;
    protected $certificatePassword;

    public function __construct($clientId, $certificatePath, $certificatePassword = null)
    {
        $this->clientId = $clientId;
        $this->certificatePath = $certificatePath;
        $this->certificatePassword = $certificatePassword;
    }

    /**
     * Get OAuth access token
     */
    protected function getAccessToken(string $scope): array
    {
        try {
            $ch = curl_init();

            $postData = http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'scope' => $scope,
            ]);

            curl_setopt_array($ch, [
                CURLOPT_URL => self::AUTH_ENDPOINT,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                // mTLS Certificate
                CURLOPT_SSLCERT => $this->certificatePath,
                CURLOPT_SSLKEY => $this->certificatePath,
            ]);

            if ($this->certificatePassword) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificatePassword);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \Exception("cURL error: {$error}");
            }

            curl_close($ch);

            $data = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300 && isset($data['access_token'])) {
                return [
                    'success' => true,
                    'token' => $data['access_token'],
                    'expires_in' => $data['expires_in'] ?? 3600,
                ];
            }

            $errorMsg = $data['error_description'] ?? $data['error'] ?? 'Unknown error';
            throw new \Exception("Failed to get token (HTTP {$httpCode}): {$errorMsg}");

        } catch (\Exception $e) {
            Log::error('Sicoob: Failed to get access token', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make authenticated request to Sicoob API
     */
    protected function makeAuthenticatedRequest(string $endpoint, array $data = [], string $method = 'POST', string $scope = self::PIX_SCOPE): array
    {
        $tokenResult = $this->getAccessToken($scope);

        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $ch = curl_init();

            $headers = [
                'Authorization: Bearer ' . $tokenResult['token'],
                'Content-Type: application/json',
                'Accept: application/json',
            ];

            $curlOptions = [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSLCERT => $this->certificatePath,
                CURLOPT_SSLKEY => $this->certificatePath,
            ];

            if ($this->certificatePassword) {
                $curlOptions[CURLOPT_SSLCERTPASSWD] = $this->certificatePassword;
            }

            if (strtoupper($method) === 'POST') {
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            } elseif (strtoupper($method) === 'GET') {
                if (!empty($data)) {
                    $curlOptions[CURLOPT_URL] .= '?' . http_build_query($data);
                }
            } else {
                $curlOptions[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
                if (!empty($data)) {
                    $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
                }
            }

            curl_setopt_array($ch, $curlOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \Exception("cURL error: {$error}");
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'http_code' => $httpCode,
                ];
            }

            $errorMsg = $responseData['error_description'] ?? $responseData['message'] ?? 'Unknown error';
            throw new \Exception("API error (HTTP {$httpCode}): {$errorMsg}");

        } catch (\Exception $e) {
            Log::error('Sicoob: API request failed', [
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
     * Create PIX COB (Cobrança Imediata)
     * 
     * @param array $orderData ['cpf' => '12345678901', 'nome' => 'John Doe', 'valor' => 100.00]
     * @param string $pixKey PIX key (email, phone, CPF/CNPJ)
     * @param string $description Payment description
     * @return array
     */
    public function createPixCob(array $orderData, string $pixKey, string $description): array
    {
        // Validate required fields
        if (empty($orderData['cpf']) || empty($orderData['nome']) || empty($orderData['valor'])) {
            return [
                'success' => false,
                'error' => 'Missing required fields: cpf, nome, valor',
            ];
        }

        if (empty($pixKey) || empty($description)) {
            return [
                'success' => false,
                'error' => 'Missing PIX key or description',
            ];
        }

        // Prepare PIX data
        $pixData = [
            'calendario' => [
                'expiracao' => 108000, // 30 hours in seconds
            ],
            'devedor' => [
                'cpf' => preg_replace('/[^0-9]/', '', $orderData['cpf']),
                'nome' => $orderData['nome'],
            ],
            'valor' => [
                'original' => number_format($orderData['valor'], 2, '.', ''),
            ],
            'chave' => $pixKey,
            'solicitacaoPagador' => substr($description, 0, 140), // Max 140 chars
        ];

        $endpoint = self::PIX_ENDPOINT . '/cob';
        $result = $this->makeAuthenticatedRequest($endpoint, $pixData, 'POST', self::PIX_SCOPE);

        if ($result['success']) {
            Log::info('Sicoob: PIX COB created successfully', [
                'txid' => $result['data']['txid'] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Get PIX COB by transaction ID
     */
    public function getPixCob(string $txid): array
    {
        $endpoint = self::PIX_ENDPOINT . "/cob/{$txid}";
        return $this->makeAuthenticatedRequest($endpoint, [], 'GET', self::PIX_SCOPE);
    }

    /**
     * Create Boleto
     * 
     * @param array $orderData Boleto data
     * @return array
     */
    public function createBoleto(array $orderData): array
    {
        // This would require more detailed boleto data structure
        // Based on Sicoob's boleto API specification
        $endpoint = self::BOLETO_ENDPOINT;
        return $this->makeAuthenticatedRequest($endpoint, $orderData, 'POST', self::BOLETO_SCOPE);
    }

    /**
     * Get Boleto by ID
     */
    public function getBoleto(string $boletoId): array
    {
        $endpoint = self::BOLETO_ENDPOINT . "/{$boletoId}";
        return $this->makeAuthenticatedRequest($endpoint, [], 'GET', self::BOLETO_SCOPE);
    }
}
