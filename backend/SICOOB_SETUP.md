# Sicoob Payment - Guia de Integração

## Visão Geral

A integração com o Sicoob permite processar pagamentos via **PIX** e **Boleto Bancário** utilizando autenticação mTLS (mutual TLS) com certificados digitais.

## Requisitos

1. **Conta Sicoob** com acesso à API de pagamentos
2. **Client ID** fornecido pelo Sicoob
3. **Certificado Digital** (.p12 ou .pem) fornecido pelo Sicoob
4. **Chave PIX** cadastrada no Sicoob

## Configuração

### 1. Obter Credenciais

Entre em contato com o Sicoob para solicitar:
- `client_id`: Identificador único da sua aplicação
- Certificado digital (arquivo `.p12` ou `.pem`)
- Senha do certificado (se aplicável)

### 2. Preparar Certificado

Se você recebeu um certificado `.p12`, pode ser necessário convertê-lo para `.pem`:

```bash
# Converter .p12 para .pem
openssl pkcs12 -in certificado.p12 -out certificado.pem -nodes

# O arquivo .pem conterá tanto a chave privada quanto o certificado
```

### 3. Armazenar Certificado

Copie o certificado para um diretório seguro no servidor:

```bash
mkdir -p storage/certificates
cp certificado.pem storage/certificates/sicoob.pem
chmod 600 storage/certificates/sicoob.pem
```

### 4. Adicionar Método de Pagamento

Acesse `/admin/payment-methods` e adicione um novo método:

**Para PIX:**
- **Nome**: `PIX Sicoob`
- **Tipo**: `pix`
- **API Key**: `seu_client_id`
- **API Secret**: *(deixe vazio ou use senha do certificado se aplicável)*
- **Webhook URL**: `https://seu-dominio.com/api/webhooks/sicoob`
- **Config** (JSON):
```json
{
  "certificate_path": "/caminho/completo/para/storage/certificates/sicoob.pem",
  "pix_key": "sua-chave-pix@email.com",
  "pix_description": "Pagamento LibreSign"
}
```
- ✅ **Disponível**: Marque quando pronto para produção

**Para Boleto:**
- **Nome**: `Boleto Sicoob`
- **Tipo**: `boleto`
- **API Key**: `seu_client_id`
- **API Secret**: *(deixe vazio ou use senha do certificado se aplicável)*
- **Webhook URL**: `https://seu-dominio.com/api/webhooks/sicoob`
- **Config** (JSON):
```json
{
  "certificate_path": "/caminho/completo/para/storage/certificates/sicoob.pem",
  "numero_contrato": "123456",
  "codigo_beneficiario": "789012"
}
```

## Uso no Código

### Criar PIX

```php
use App\Services\SicoobPaymentService;

$paymentMethod = PaymentMethod::where('type', 'pix')->where('is_available', true)->first();
$config = $paymentMethod->config;

$sicoob = new SicoobPaymentService(
    $paymentMethod->api_key,
    $config['certificate_path'],
    $paymentMethod->api_secret ?? null
);

$result = $sicoob->createPixCob(
    [
        'cpf' => '12345678901',
        'nome' => 'João da Silva',
        'valor' => 149.90,
    ],
    $config['pix_key'],
    $config['pix_description']
);

if ($result['success']) {
    $txid = $result['data']['txid'];
    $qrCode = $result['data']['brcode']; // PIX Copia e Cola
    $qrCodeImage = $result['data']['qrcode']; // QR Code em base64 (se disponível)
    
    // Armazenar no pedido
    $order->update([
        'payment_txid' => $txid,
        'payment_qrcode' => $qrCode,
    ]);
}
```

### Consultar Status do PIX

```php
$result = $sicoob->getPixCob($txid);

if ($result['success']) {
    $status = $result['data']['status']; // ATIVA, CONCLUIDA, REMOVIDA_PELO_USUARIO_RECEBEDOR
    if ($status === 'CONCLUIDA') {
        // Pagamento confirmado
    }
}
```

## Webhooks

O Sicoob envia notificações quando um pagamento é confirmado. Configure o endpoint no seu sistema:

```php
// routes/api.php
Route::post('/webhooks/sicoob', [SicoobWebhookController::class, 'handle']);
```

## API Endpoints

### Autenticação
- **URL**: `https://auth.sicoob.com.br/auth/realms/cooperado/protocol/openid-connect/token`
- **Método**: POST
- **Auth**: mTLS Certificate

### PIX
- **Base URL**: `https://api.sicoob.com.br/pix/api/v2`
- **Criar COB**: `POST /cob`
- **Consultar COB**: `GET /cob/{txid}`

### Boleto
- **Base URL**: `https://api.sicoob.com.br/cobranca-bancaria/v3`
- **Criar Boleto**: `POST /boletos`
- **Consultar Boleto**: `GET /boletos/{id}`

## Scopes Necessários

### Para PIX:
```
cob.read cob.write cobv.write cobv.read lotecobv.write lotecobv.read 
pix.write pix.read webhook.read webhook.write 
payloadlocation.write payloadlocation.read
```

### Para Boleto:
```
boletos_inclusao boletos_consulta boletos_alteracao
```

## Ambiente de Testes

O Sicoob pode fornecer um ambiente sandbox para testes. As URLs serão diferentes:

- Auth: `https://auth-sandbox.sicoob.com.br/...`
- PIX: `https://api-sandbox.sicoob.com.br/pix/...`
- Boleto: `https://api-sandbox.sicoob.com.br/cobranca-bancaria/...`

## Troubleshooting

### Erro: "Certificate verify failed"
- Verifique se o certificado está no formato correto (.pem)
- Confirme que o caminho do certificado está correto
- Verifique as permissões do arquivo (600)

### Erro: "Invalid client credentials"
- Confirme que o `client_id` está correto
- Verifique se o certificado corresponde ao `client_id` fornecido pelo Sicoob

### Erro: "Scope not valid"
- Verifique se você está solicitando os scopes corretos para o tipo de operação
- Confirme com o Sicoob se sua conta tem acesso aos scopes necessários

## Referências

- [Documentação Oficial Sicoob](https://developers.sicoob.com.br/)
- [Especificação PIX API](https://developers.sicoob.com.br/pix)
- [Especificação Boleto API](https://developers.sicoob.com.br/boleto)
