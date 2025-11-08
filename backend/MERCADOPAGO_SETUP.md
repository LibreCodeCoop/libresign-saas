# Mercado Pago - Guia de Integração

## Visão Geral

A integração com o Mercado Pago permite processar pagamentos via **PIX**, **Cartão de Crédito**, **Boleto** e outros métodos de pagamento disponíveis na América Latina.

## Requisitos

1. **Conta Mercado Pago** ([Criar conta](https://www.mercadopago.com.br/hub/registration/landing))
2. **Credenciais de API** (Access Token e Public Key)
3. **SSL Certificate** no servidor

## Obter Credenciais

### 1. Acesse suas credenciais

1. Faça login no [Mercado Pago](https://www.mercadopago.com.br)
2. Vá em **Seu Negócio** → **Configurações** → **Credenciais**
3. Você verá duas opções:
   - **Credenciais de teste** (para desenvolvimento)
   - **Credenciais de produção** (para uso real)

### 2. Tipos de Credenciais

- **Public Key**: Usada no frontend (começa com `APP_USR` ou `TEST`)
- **Access Token**: Usada no backend (começa com `APP_USR` ou `TEST`)

**Importante**: Nunca exponha o Access Token no frontend!

## Configuração no Sistema

### 1. Adicionar Método de Pagamento - PIX

Acesse `/admin/payment-methods` e adicione:

- **Nome**: `PIX Mercado Pago`
- **Tipo**: `mercadopago_pix`
- **API Key**: `seu_access_token` (privado)
- **API Secret**: `sua_public_key` (pública, opcional)
- **Webhook URL**: `https://seu-dominio.com/api/webhooks/mercadopago`
- **Config** (JSON):
```json
{
  "country": "MLB",
  "description": "Pagamento LibreSign"
}
```
- ✅ **Disponível**: Marque quando pronto para produção

### 2. Adicionar Método de Pagamento - Cartão de Crédito

- **Nome**: `Cartão de Crédito Mercado Pago`
- **Tipo**: `mercadopago_card`
- **API Key**: `seu_access_token`
- **API Secret**: `sua_public_key`
- **Webhook URL**: `https://seu-dominio.com/api/webhooks/mercadopago`
- **Config** (JSON):
```json
{
  "country": "MLB",
  "max_installments": 12,
  "min_installment_amount": 5.00
}
```

### 3. Adicionar Método de Pagamento - Boleto

- **Nome**: `Boleto Mercado Pago`
- **Tipo**: `mercadopago_boleto`
- **API Key**: `seu_access_token`
- **API Secret**: `sua_public_key`
- **Webhook URL**: `https://seu-dominio.com/api/webhooks/mercadopago`
- **Config** (JSON):
```json
{
  "country": "MLB",
  "boleto_type": "bolbradesco"
}
```

## Países Suportados

- `MLB` - Brasil
- `MLA` - Argentina
- `MLM` - México
- `MLU` - Uruguai
- `MLC` - Chile
- `MCO` - Colômbia
- `MPE` - Peru

## Uso no Código

### Criar Pagamento PIX

```php
use App\Models\PaymentMethod;
use App\Services\MercadoPagoService;

$method = PaymentMethod::where('type', 'mercadopago_pix')
    ->where('is_available', true)
    ->first();

$config = $method->config;

$mp = new MercadoPagoService(
    $method->api_key,
    $method->api_secret,
    $config['country'] ?? 'MLB'
);

$result = $mp->createPixPayment([
    'amount' => 149.90,
    'description' => 'Assinatura Profissional',
    'payer_email' => $user->email,
    'payer_first_name' => $user->name,
    'payer_identification' => '12345678901', // CPF
    'payer_identification_type' => 'CPF',
    'external_reference' => 'ORDER-' . $order->id,
]);

if ($result['success']) {
    $order->update([
        'payment_id' => $result['payment_id'],
        'payment_qrcode' => $result['qr_code'],
        'payment_qrcode_base64' => $result['qr_code_base64'],
        'payment_status' => $result['status'], // pending, approved, rejected
    ]);
}
```

### Criar Pagamento com Cartão

```php
// Token do cartão vem do frontend via MercadoPago.js
$result = $mp->createCardPayment([
    'amount' => 149.90,
    'token' => $request->input('card_token'),
    'description' => 'Assinatura Profissional',
    'installments' => 1,
    'payment_method_id' => 'visa', // visa, master, amex, etc
    'payer_email' => $user->email,
    'payer_identification' => '12345678901',
    'payer_identification_type' => 'CPF',
    'external_reference' => 'ORDER-' . $order->id,
]);
```

### Criar Boleto

```php
$result = $mp->createBoletoPayment([
    'amount' => 149.90,
    'description' => 'Assinatura Profissional',
    'payer_email' => $user->email,
    'payer_first_name' => $user->name,
    'payer_last_name' => $user->last_name,
    'payer_identification' => '12345678901',
    'payer_identification_type' => 'CPF',
    'external_reference' => 'ORDER-' . $order->id,
]);

if ($result['success']) {
    $boletoUrl = $result['boleto_url'];
    $barcode = $result['barcode'];
}
```

### Consultar Status de Pagamento

```php
$result = $mp->getPayment($paymentId);

if ($result['success']) {
    $status = $result['data']['status'];
    // pending, approved, authorized, in_process, in_mediation, 
    // rejected, cancelled, refunded, charged_back
}
```

### Reembolso

```php
// Reembolso total
$result = $mp->refundPayment($paymentId);

// Reembolso parcial
$result = $mp->refundPayment($paymentId, 50.00);
```

## Webhooks (IPN - Instant Payment Notification)

O Mercado Pago envia notificações quando um pagamento muda de status.

### 1. Criar Controller de Webhook

```php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\MercadoPagoService;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $type = $request->input('type');
        $dataId = $request->input('data.id');

        if ($type === 'payment') {
            $method = PaymentMethod::where('type', 'like', 'mercadopago%')
                ->where('is_available', true)
                ->first();

            $mp = new MercadoPagoService($method->api_key);
            $result = $mp->getPayment($dataId);

            if ($result['success']) {
                $payment = $result['data'];
                $externalRef = $payment['external_reference']; // ORDER-123
                
                // Atualizar status do pedido
                $order = Order::find(str_replace('ORDER-', '', $externalRef));
                $order->payment_status = $payment['status'];
                $order->save();

                // Se aprovado, ativar assinatura
                if ($payment['status'] === 'approved') {
                    $order->user->activateSubscription();
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
```

### 2. Adicionar Rota

```php
// routes/api.php
Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle']);
```

### 3. Configurar URL no Mercado Pago

Ao criar o pagamento, configure o `notification_url`:
```php
'notification_url' => 'https://seu-dominio.com/api/webhooks/mercadopago'
```

## Status de Pagamento

- `pending` - Aguardando pagamento
- `approved` - Pagamento aprovado
- `authorized` - Pagamento autorizado (ainda não capturado)
- `in_process` - Em análise
- `in_mediation` - Em disputa
- `rejected` - Rejeitado
- `cancelled` - Cancelado
- `refunded` - Reembolsado
- `charged_back` - Chargeback

## Ambiente de Testes

### Usar Credenciais de Teste

1. Use as credenciais que começam com `TEST-`
2. Use cartões de teste fornecidos pela documentação
3. Use CPFs de teste: `12345678909`

### Cartões de Teste

**Aprovados:**
- **VISA**: 4509 9535 6623 3704
- **Mastercard**: 5031 7557 3453 0604

**Rejeitados:**
- Qualquer cartão terminado em `00`

**CVV**: 123
**Data de Validade**: Qualquer data futura
**Nome**: APRO (aprovado) ou OTHE (outros cenários)

## Frontend Integration

Para capturar dados de cartão com segurança, use o MercadoPago.js:

```html
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
const mp = new MercadoPago('PUBLIC_KEY');
const cardForm = mp.cardForm({
  amount: "149.90",
  iframe: true,
  form: {
    id: "form-checkout",
    cardNumber: {
      id: "form-checkout__cardNumber",
      placeholder: "Número do cartão",
    },
    expirationDate: {
      id: "form-checkout__expirationDate",
      placeholder: "MM/YY",
    },
    securityCode: {
      id: "form-checkout__securityCode",
      placeholder: "CVV",
    },
    cardholderName: {
      id: "form-checkout__cardholderName",
      placeholder: "Titular do cartão",
    },
    issuer: {
      id: "form-checkout__issuer",
      placeholder: "Banco emissor",
    },
    installments: {
      id: "form-checkout__installments",
      placeholder: "Parcelas",
    },
    identificationType: {
      id: "form-checkout__identificationType",
    },
    identificationNumber: {
      id: "form-checkout__identificationNumber",
      placeholder: "CPF",
    },
    cardholderEmail: {
      id: "form-checkout__cardholderEmail",
      placeholder: "E-mail",
    },
  },
  callbacks: {
    onFormMounted: error => {
      if (error) return console.warn("Form Mounted handling error: ", error);
    },
    onSubmit: event => {
      event.preventDefault();

      const {
        paymentMethodId: payment_method_id,
        issuerId: issuer_id,
        cardholderEmail: email,
        amount,
        token,
        installments,
        identificationNumber,
        identificationType,
      } = cardForm.getCardFormData();

      fetch("/api/process_payment", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          token,
          issuer_id,
          payment_method_id,
          transaction_amount: Number(amount),
          installments: Number(installments),
          description: "Assinatura LibreSign",
          payer: {
            email,
            identification: {
              type: identificationType,
              number: identificationNumber,
            },
          },
        }),
      });
    },
  },
});
</script>
```

## Troubleshooting

### Erro: "Invalid credentials"
- Verifique se está usando o Access Token correto
- Confirme que não está misturando credenciais de teste e produção

### Erro: "Invalid transaction_amount"
- O valor deve ser positivo
- Use formato decimal com ponto (149.90, não 149,90)

### Pagamento não aprovado
- Verifique o status do pagamento via API
- Veja os `status_detail` para entender o motivo da rejeição

## Referências

- [Documentação Oficial](https://www.mercadopago.com.br/developers/pt)
- [API Reference](https://www.mercadopago.com.br/developers/pt/reference)
- [SDKs e Bibliotecas](https://www.mercadopago.com.br/developers/pt/docs/sdks-library)
- [Cartões de Teste](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/testing)
