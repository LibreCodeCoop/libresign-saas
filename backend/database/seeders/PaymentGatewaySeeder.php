<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentGateway;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'Sicoob PIX',
                'slug' => 'sicoob_pix',
                'type' => 'pix',
                'description' => 'Pagamento via PIX através do Sicoob. Aprovação instantânea.',
                'is_active' => false,
                'sort_order' => 1,
                'settings' => [
                    'client_id' => '',
                    'certificate_path' => '',
                    'beneficiary_code' => '',
                    'key_pix' => '',
                ],
                'metadata' => [
                    'icon' => 'pix',
                    'payment_time' => 'instant',
                ],
            ],
            [
                'name' => 'Sicoob Boleto',
                'slug' => 'sicoob_boleto',
                'type' => 'boleto',
                'description' => 'Boleto bancário Sicoob. Vencimento em 3 dias úteis.',
                'is_active' => false,
                'sort_order' => 2,
                'settings' => [
                    'client_id' => '',
                    'certificate_path' => '',
                    'beneficiary_code' => '',
                    'wallet_code' => '',
                ],
                'metadata' => [
                    'icon' => 'barcode',
                    'payment_time' => '1-3 dias',
                ],
            ],
            [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'type' => 'credit_card',
                'description' => 'Cartão de crédito via Stripe. Parcele em até 12x.',
                'is_active' => false,
                'sort_order' => 3,
                'settings' => [
                    'publishable_key' => '',
                    'secret_key' => '',
                    'webhook_secret' => '',
                ],
                'metadata' => [
                    'icon' => 'credit-card',
                    'payment_time' => 'instant',
                    'installments' => [1, 2, 3, 6, 12],
                ],
            ],
            [
                'name' => 'PayPal',
                'slug' => 'paypal',
                'type' => 'paypal',
                'description' => 'Pagamento via PayPal',
                'is_active' => false,
                'sort_order' => 4,
                'settings' => [
                    'client_id' => '',
                    'secret' => '',
                    'mode' => 'sandbox',
                ],
                'metadata' => [
                    'icon' => 'paypal',
                    'payment_time' => 'instant',
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['slug' => $gateway['slug']],
                $gateway
            );
        }
    }
}
