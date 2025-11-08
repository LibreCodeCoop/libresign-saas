# Como Validar Métodos de Pagamento

## Visão Geral

O sistema possui uma funcionalidade integrada para testar se os métodos de pagamento estão configurados corretamente antes de disponibilizá-los para os usuários.

## Método 1: Teste Automatizado via Interface Admin

### Como Usar

1. Acesse `/admin/payment-methods`
2. Localize o método de pagamento que deseja testar
3. Clique no botão **🧪 Testar** na linha do método
4. Um modal será aberto mostrando os resultados dos testes em tempo real

### O Que é Testado

#### Sicoob PIX / Boleto

1. **Verificar configuração**
   - Client ID está configurado?
   - Caminho do certificado está configurado?
   - Chave PIX está configurada?

2. **Verificar certificado**
   - O arquivo de certificado existe no caminho especificado?

3. **Autenticação OAuth**
   - Consegue obter token de acesso?
   - Credenciais são válidas?

#### Mercado Pago PIX

1. **Verificar configuração**
   - Access Token está configurado?

2. **Validar credenciais**
   - Credenciais são válidas?
   - Quantos métodos de pagamento estão disponíveis?

3. **Verificar suporte a PIX**
   - PIX está habilitado na conta?

#### Mercado Pago Cartão

1. **Verificar configuração**
   - Access Token está configurado?
   - Public Key está configurada?

2. **Validar credenciais**
   - Credenciais são válidas?

3. **Métodos de cartão disponíveis**
   - Quais bandeiras estão disponíveis? (Visa, Master, Amex, etc)

#### Mercado Pago Boleto

1. **Verificar configuração**
   - Access Token está configurado?

2. **Validar credenciais**
   - Credenciais são válidas?

### Interpretando os Resultados

**Sucesso (✅)**
- Banner verde: "Todos os testes passaram! O método está configurado corretamente."
- Todos os itens com ✓ verde
- Você pode marcar o método como "Disponível" com segurança

**Falha (❌)**
- Banner vermelho: "Alguns testes falharam. Verifique as configurações."
- Itens com ✗ vermelho mostram o que precisa ser corrigido
- Corrija os problemas antes de disponibilizar aos usuários

## Método 2: Teste Manual via API

### Endpoint

```
POST /api/admin/payment-methods/{id}/test
```

**Headers:**
```
Authorization: Bearer {seu_token}
Content-Type: application/json
```

### Exemplo de Resposta - Sucesso

```json
{
  "success": true,
  "message": "Todos os testes passaram! O método está configurado corretamente.",
  "tests": [
    {
      "name": "Verificar configuração",
      "passed": true,
      "message": "Access Token configurado"
    },
    {
      "name": "Validar credenciais",
      "passed": true,
      "message": "Credenciais válidas - 15 métodos de pagamento disponíveis"
    },
    {
      "name": "Verificar suporte a PIX",
      "passed": true,
      "message": "PIX está disponível nesta conta"
    }
  ]
}
```

### Exemplo de Resposta - Falha

```json
{
  "success": false,
  "message": "Alguns testes falharam. Verifique as configurações.",
  "tests": [
    {
      "name": "Verificar configuração",
      "passed": false,
      "message": "Faltando: Client ID, Certificado"
    },
    {
      "name": "Verificar certificado",
      "passed": false,
      "message": "Certificado não encontrado: /path/to/cert.pem"
    }
  ]
}
```

## Método 3: Teste de Pagamento Real (Sandbox)

### Sicoob

1. Use credenciais de **teste/sandbox**
2. Configure o certificado de teste
3. Crie um pagamento de teste:

```php
$sicoob = new SicoobPaymentService(
    'test_client_id',
    '/path/to/test/cert.pem'
);

$result = $sicoob->createPixCob([
    'cpf' => '12345678909',
    'nome' => 'Teste Usuario',
    'valor' => 0.01, // R$ 0,01 para teste
], 'sua_chave_pix_teste@email.com', 'Teste de integração');

if ($result['success']) {
    echo "PIX criado: " . $result['data']['txid'];
    echo "QR Code: " . $result['data']['brcode'];
}
```

### Mercado Pago

1. Use credenciais de **teste** (começam com `TEST-`)
2. Use cartões de teste:

**Aprovado:**
- VISA: `4509 9535 6623 3704`
- Mastercard: `5031 7557 3453 0604`
- CVV: `123`
- Validade: Qualquer data futura
- Nome: `APRO`
- CPF: `12345678909`

**Teste PIX:**
```php
$mp = new MercadoPagoService(
    'TEST-1234567890-...',
    'TEST-...'
);

$result = $mp->createPixPayment([
    'amount' => 0.01,
    'description' => 'Teste PIX',
    'payer_email' => 'test@test.com',
    'payer_identification' => '12345678909',
]);

if ($result['success']) {
    echo "Payment ID: " . $result['payment_id'];
    echo "QR Code: " . $result['qr_code'];
}
```

## Checklist de Validação

Antes de marcar um método como "Disponível", certifique-se:

### Configuração Básica
- [ ] Nome do método está claro e descritivo
- [ ] Tipo está correto
- [ ] Credenciais estão preenchidas

### Testes Automatizados
- [ ] Todos os testes passam (botão 🧪 Testar)
- [ ] Sem erros de autenticação
- [ ] Certificados encontrados (se aplicável)

### Teste de Transação
- [ ] Consegue criar pagamento de teste
- [ ] QR Code/Link é gerado
- [ ] Webhook recebe notificação (se configurado)

### Documentação
- [ ] Equipe sabe como usar o método
- [ ] Webhooks estão configurados
- [ ] URLs de callback estão corretas

### Ambiente de Produção
- [ ] Credenciais de **produção** configuradas
- [ ] Certificado de **produção** instalado (se aplicável)
- [ ] Conta está aprovada/verificada no gateway
- [ ] Limites de transação são adequados

## Troubleshooting

### Erro: "Certificado não encontrado"

**Causa:** O arquivo de certificado não existe no caminho especificado.

**Solução:**
1. Verifique o caminho no campo Config (JSON)
2. Use caminho absoluto: `/home/user/storage/certificates/sicoob.pem`
3. Confirme que o arquivo existe: `ls -la /caminho/para/certificado.pem`
4. Verifique permissões: `chmod 600 /caminho/para/certificado.pem`

### Erro: "Credenciais inválidas"

**Causa:** Access Token ou Client ID incorreto/expirado.

**Solução:**
1. Regenere as credenciais no painel do gateway
2. Copie e cole novamente (evite espaços extras)
3. Confirme que está usando credenciais do ambiente correto (teste vs produção)

### Erro: "PIX não está disponível nesta conta"

**Causa:** PIX não está habilitado na sua conta do gateway.

**Solução:**
1. **Mercado Pago:** Acesse [Configurações de PIX](https://www.mercadopago.com.br/settings/account/pix)
2. **Sicoob:** Entre em contato com seu gerente para habilitar PIX

### Teste passa mas pagamento real falha

**Possíveis causas:**
1. Usando credenciais de teste em produção
2. Webhook URL não está acessível publicamente
3. Limites de transação excedidos
4. Conta não aprovada para produção

**Solução:**
1. Verifique se está usando credenciais de produção
2. Teste webhook: `curl -X POST https://seu-dominio.com/api/webhooks/...`
3. Verifique limites no painel do gateway
4. Complete processo de verificação da conta

## Comando CLI para Teste em Massa

Você pode criar um comando Artisan para testar todos os métodos:

```bash
php artisan payment-methods:test-all
```

Exemplo de implementação:

```php
// app/Console/Commands/TestPaymentMethods.php
$methods = PaymentMethod::all();

foreach ($methods as $method) {
    $this->info("Testando: {$method->name}");
    
    $response = Http::post(
        config('app.url') . "/api/admin/payment-methods/{$method->id}/test"
    );
    
    $result = $response->json();
    
    if ($result['success']) {
        $this->info("✓ Passou em todos os testes");
    } else {
        $this->error("✗ Falhou em alguns testes");
        foreach ($result['tests'] as $test) {
            if (!$test['passed']) {
                $this->warn("  - {$test['name']}: {$test['message']}");
            }
        }
    }
    
    $this->newLine();
}
```

## Monitoramento Contínuo

Considere implementar:

1. **Testes agendados** - Executar testes automaticamente (diário/semanal)
2. **Alertas** - Notificar administradores quando testes falharem
3. **Dashboard** - Mostrar status de saúde de cada método
4. **Logs** - Manter histórico de testes e falhas

```php
// routes/console.php
Schedule::command('payment-methods:test-all')->daily();
```
