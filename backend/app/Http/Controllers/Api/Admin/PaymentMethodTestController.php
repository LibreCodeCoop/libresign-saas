<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\SicoobPaymentService;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class PaymentMethodTestController extends Controller
{
    /**
     * Test payment method configuration
     * 
     * @param int $id Payment method ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function test($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        $result = match ($paymentMethod->type) {
            'sicoob_pix' => $this->testSicoobPix($paymentMethod),
            'sicoob_boleto' => $this->testSicoobBoleto($paymentMethod),
            'mercadopago_pix' => $this->testMercadoPagoPix($paymentMethod),
            'mercadopago_card' => $this->testMercadoPagoCard($paymentMethod),
            'mercadopago_boleto' => $this->testMercadoPagoBoleto($paymentMethod),
            default => [
                'success' => false,
                'error' => "Tipo de pagamento '{$paymentMethod->type}' não suporta teste automático ainda.",
                'tests' => [],
            ]
        };

        return response()->json($result);
    }

    /**
     * Test Sicoob PIX configuration
     */
    protected function testSicoobPix(PaymentMethod $method): array
    {
        $tests = [];
        $config = $method->config;

        // Test 1: Check configuration
        $tests[] = [
            'name' => 'Verificar configuração',
            'passed' => !empty($method->api_key) && 
                       !empty($config['certificate_path'] ?? null) && 
                       !empty($config['pix_key'] ?? null),
            'message' => !empty($method->api_key) && !empty($config['certificate_path']) && !empty($config['pix_key'])
                ? 'Client ID, certificado e chave PIX configurados'
                : 'Faltando: ' . implode(', ', array_filter([
                    empty($method->api_key) ? 'Client ID' : null,
                    empty($config['certificate_path']) ? 'Certificado' : null,
                    empty($config['pix_key']) ? 'Chave PIX' : null,
                ])),
        ];

        // Test 2: Check certificate file exists
        if (!empty($config['certificate_path'])) {
            $certExists = file_exists($config['certificate_path']);
            $tests[] = [
                'name' => 'Verificar certificado',
                'passed' => $certExists,
                'message' => $certExists 
                    ? 'Certificado encontrado em: ' . $config['certificate_path']
                    : 'Certificado não encontrado: ' . $config['certificate_path'],
            ];
        }

        // Test 3: Try to get OAuth token
        if (!empty($method->api_key) && !empty($config['certificate_path'])) {
            try {
                $sicoob = new SicoobPaymentService(
                    $method->api_key,
                    $config['certificate_path'],
                    $method->api_secret
                );

                // Try to get token (this tests authentication)
                $reflection = new \ReflectionClass($sicoob);
                $method = $reflection->getMethod('getAccessToken');
                $method->setAccessible(true);
                $tokenResult = $method->invoke($sicoob, SicoobPaymentService::PIX_SCOPE);

                $tests[] = [
                    'name' => 'Autenticação OAuth',
                    'passed' => $tokenResult['success'] ?? false,
                    'message' => $tokenResult['success'] 
                        ? 'Token obtido com sucesso'
                        : 'Erro: ' . ($tokenResult['error'] ?? 'Falha ao obter token'),
                ];
            } catch (\Exception $e) {
                $tests[] = [
                    'name' => 'Autenticação OAuth',
                    'passed' => false,
                    'message' => 'Erro: ' . $e->getMessage(),
                ];
            }
        }

        $allPassed = !empty($tests) && collect($tests)->every(fn($test) => $test['passed']);

        return [
            'success' => $allPassed,
            'message' => $allPassed 
                ? 'Todos os testes passaram! O método está configurado corretamente.'
                : 'Alguns testes falharam. Verifique as configurações.',
            'tests' => $tests,
        ];
    }

    /**
     * Test Sicoob Boleto configuration
     */
    protected function testSicoobBoleto(PaymentMethod $method): array
    {
        return $this->testSicoobPix($method); // Same tests apply
    }

    /**
     * Test Mercado Pago PIX configuration
     */
    protected function testMercadoPagoPix(PaymentMethod $method): array
    {
        $tests = [];
        $config = $method->config;

        // Test 1: Check configuration
        $tests[] = [
            'name' => 'Verificar configuração',
            'passed' => !empty($method->api_key),
            'message' => !empty($method->api_key)
                ? 'Access Token configurado'
                : 'Access Token não configurado',
        ];

        // Test 2: Try to get payment methods (validates credentials)
        if (!empty($method->api_key)) {
            try {
                $mp = new MercadoPagoService(
                    $method->api_key,
                    $method->api_secret,
                    $config['country'] ?? 'MLB'
                );

                $result = $mp->getPaymentMethods();

                $tests[] = [
                    'name' => 'Validar credenciais',
                    'passed' => $result['success'] ?? false,
                    'message' => $result['success']
                        ? 'Credenciais válidas - ' . count($result['data'] ?? []) . ' métodos de pagamento disponíveis'
                        : 'Credenciais inválidas: ' . ($result['error'] ?? 'Erro desconhecido'),
                ];

                // Test 3: Check if PIX is available
                if ($result['success']) {
                    $hasPix = collect($result['data'] ?? [])->contains(fn($pm) => $pm['id'] === 'pix');
                    $tests[] = [
                        'name' => 'Verificar suporte a PIX',
                        'passed' => $hasPix,
                        'message' => $hasPix
                            ? 'PIX está disponível nesta conta'
                            : 'PIX não está disponível (pode não estar habilitado na sua conta)',
                    ];
                }

            } catch (\Exception $e) {
                $tests[] = [
                    'name' => 'Validar credenciais',
                    'passed' => false,
                    'message' => 'Erro: ' . $e->getMessage(),
                ];
            }
        }

        $allPassed = !empty($tests) && collect($tests)->every(fn($test) => $test['passed']);

        return [
            'success' => $allPassed,
            'message' => $allPassed
                ? 'Todos os testes passaram! O método está configurado corretamente.'
                : 'Alguns testes falharam. Verifique as configurações.',
            'tests' => $tests,
        ];
    }

    /**
     * Test Mercado Pago Card configuration
     */
    protected function testMercadoPagoCard(PaymentMethod $method): array
    {
        $tests = [];
        $config = $method->config;

        // Test 1: Check configuration
        $tests[] = [
            'name' => 'Verificar configuração',
            'passed' => !empty($method->api_key) && !empty($method->api_secret),
            'message' => !empty($method->api_key) && !empty($method->api_secret)
                ? 'Access Token e Public Key configurados'
                : 'Faltando: ' . implode(', ', array_filter([
                    empty($method->api_key) ? 'Access Token' : null,
                    empty($method->api_secret) ? 'Public Key' : null,
                ])),
        ];

        // Test 2: Validate credentials
        if (!empty($method->api_key)) {
            try {
                $mp = new MercadoPagoService(
                    $method->api_key,
                    $method->api_secret,
                    $config['country'] ?? 'MLB'
                );

                $result = $mp->getPaymentMethods();

                $tests[] = [
                    'name' => 'Validar credenciais',
                    'passed' => $result['success'] ?? false,
                    'message' => $result['success']
                        ? 'Credenciais válidas'
                        : 'Credenciais inválidas: ' . ($result['error'] ?? 'Erro desconhecido'),
                ];

                // Test 3: Check available card types
                if ($result['success']) {
                    $cardMethods = collect($result['data'] ?? [])->filter(fn($pm) => $pm['payment_type_id'] === 'credit_card');
                    $tests[] = [
                        'name' => 'Métodos de cartão disponíveis',
                        'passed' => $cardMethods->isNotEmpty(),
                        'message' => $cardMethods->isNotEmpty()
                            ? $cardMethods->count() . ' bandeiras disponíveis: ' . $cardMethods->pluck('name')->take(5)->implode(', ')
                            : 'Nenhuma bandeira de cartão disponível',
                    ];
                }

            } catch (\Exception $e) {
                $tests[] = [
                    'name' => 'Validar credenciais',
                    'passed' => false,
                    'message' => 'Erro: ' . $e->getMessage(),
                ];
            }
        }

        $allPassed = !empty($tests) && collect($tests)->every(fn($test) => $test['passed']);

        return [
            'success' => $allPassed,
            'message' => $allPassed
                ? 'Todos os testes passaram! O método está configurado corretamente.'
                : 'Alguns testes falharam. Verifique as configurações.',
            'tests' => $tests,
        ];
    }

    /**
     * Test Mercado Pago Boleto configuration
     */
    protected function testMercadoPagoBoleto(PaymentMethod $method): array
    {
        return $this->testMercadoPagoPix($method); // Similar tests
    }
}
