# Sistema de Métodos de Pagamento

## Visão Geral

O sistema permite que administradores configurem múltiplos métodos de pagamento que ficarão disponíveis para os usuários no checkout.

## Funcionalidades

### Para Administradores

1. **Adicionar Métodos de Pagamento** (`/admin/payment-methods`)
   - Nome personalizado do método
   - Tipo (PIX, Boleto, Cartão de Crédito, Sicoob PIX, Sicoob Boleto, etc.)
   - Credenciais da API (API Key, API Secret)
   - URL de Webhook para receber notificações
   - Configurações adicionais em JSON
   - Checkbox "Disponível" para controlar se está em testes ou produção

2. **Gerenciar Métodos**
   - Editar configurações existentes
   - Ativar/desativar disponibilidade
   - Excluir métodos não utilizados

### Para Usuários

1. **Seleção no Checkout** (`/dashboard/checkout`)
   - Visualizam apenas métodos marcados como "Disponível"
   - Selecionam método de pagamento desejado
   - Sistema valida e processa pagamento

## Estrutura do Banco de Dados

### Tabela: `payment_methods`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | ID único |
| name | string | Nome exibido (ex: "PIX Sicoob") |
| type | string | Tipo do método (pix, boleto, credit_card, etc) |
| api_key | text | Chave pública/API key |
| api_secret | text | Chave secreta (oculta na API) |
| webhook_url | string | URL para receber webhooks |
| config | json | Configurações específicas do gateway |
| is_available | boolean | Se está disponível para usuários |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |

## Tipos de Pagamento Suportados

- `credit_card` - Cartão de Crédito genérico
- `pix` - PIX genérico
- `boleto` - Boleto genérico
- `sicoob_pix` - PIX via Sicoob
- `sicoob_boleto` - Boleto via Sicoob
- `mercadopago_pix` - PIX via Mercado Pago
- `mercadopago_card` - Cartão via Mercado Pago
- `mercadopago_boleto` - Boleto via Mercado Pago
- `paypal` - PayPal
- `stripe` - Stripe
- `pagseguro` - PagSeguro

## API Endpoints

### Admin (Autenticado + Middleware Admin)

```
GET    /api/admin/payment-methods       # Listar todos
POST   /api/admin/payment-methods       # Criar novo
GET    /api/admin/payment-methods/{id}  # Ver detalhes
PUT    /api/admin/payment-methods/{id}  # Atualizar
DELETE /api/admin/payment-methods/{id}  # Excluir
```

### Público (Sem autenticação)

```
GET /api/payment-methods  # Lista apenas métodos com is_available=true
```

## Exemplo de Uso - Sicoob PIX

### 1. Adicionar Método no Admin

Acesse `/admin/payment-methods` e preencha:

- **Nome**: `PIX Sicoob`
- **Tipo**: `sicoob_pix`
- **API Key**: `seu_client_id_sicoob`
- **API Secret**: `senha_certificado` (opcional)
- **Webhook URL**: `https://seu-dominio.com/api/webhooks/sicoob`
- **Config** (JSON):
```json
{
  "certificate_path": "/path/to/storage/certificates/sicoob.pem",
  "pix_key": "contato@empresa.com",
  "pix_description": "Assinatura LibreSign"
}
```
- ✅ **Disponível**: Marque quando pronto

### 2. Usar no Código

```php
use App\Models\PaymentMethod;
use App\Services\SicoobPaymentService;

// Buscar método configurado
$method = PaymentMethod::where('type', 'sicoob_pix')
    ->where('is_available', true)
    ->first();

if ($method) {
    $config = $method->config;
    
    $sicoob = new SicoobPaymentService(
        $method->api_key,
        $config['certificate_path'],
        $method->api_secret
    );
    
    $result = $sicoob->createPixCob(
        [
            'cpf' => $user->document,
            'nome' => $user->name,
            'valor' => $order->total,
        ],
        $config['pix_key'],
        $config['pix_description']
    );
    
    if ($result['success']) {
        $order->payment_txid = $result['data']['txid'];
        $order->payment_qrcode = $result['data']['brcode'];
        $order->save();
    }
}
```

## Fase de Testes vs Produção

### Em Testes (is_available = false)
- Método **NÃO** aparece no checkout
- Visível apenas no painel admin
- Use para testar integração antes de liberar

### Em Produção (is_available = true)
- Método **APARECE** no checkout
- Usuários podem selecionar e pagar
- Use quando tudo estiver configurado e testado

## Segurança

1. **API Secret** é ocultada nas respostas da API (campo `hidden` no model)
2. **Certificados** devem ser armazenados com permissões restritas (chmod 600)
3. **Webhooks** devem validar assinaturas/tokens do gateway
4. **Config JSON** pode conter informações sensíveis - proteja adequadamente

## Próximos Passos

1. Implementar webhooks para confirmação automática de pagamentos
2. Adicionar mais gateways de pagamento
3. Criar dashboard de transações
4. Implementar reconciliação de pagamentos
5. Adicionar suporte a recorrência

## Documentações Específicas

- [Sicoob Setup](./SICOOB_SETUP.md) - Configuração completa do Sicoob
- [Mercado Pago Setup](./MERCADOPAGO_SETUP.md) - Configuração completa do Mercado Pago
