# Estratégia de Gerenciamento de Senhas

## Situação Atual

Atualmente, quando um usuário é criado no SaaS LibreSign e posteriormente no Nextcloud, **são utilizadas senhas diferentes**:

- **SaaS**: A senha é definida pelo usuário no registro e armazenada com hash seguro
- **Nextcloud**: Uma senha aleatória é gerada automaticamente (linha 46 do `CreateNextcloudUser.php`)

```php
// Senha gerada no Nextcloud (aleatória)
$password = Str::random(16) . Str::upper(Str::random(2)) . rand(10, 99) . '!@';
// Exemplo: "a8f3k2m9p5q7n4w1XY47!@"
```

## Impacto no SSO

Com a implementação de Single Sign-On (SSO), o usuário **nunca precisa saber a senha do Nextcloud**, pois o acesso é feito através de tokens temporários validados pelo SaaS.

## Opções de Implementação

### Opção 1: Manter Senhas Diferentes (✅ Recomendado)

**Descrição:**
- Usuário tem uma senha no SaaS
- Nextcloud usa senha aleatória forte (nunca revelada)
- Acesso ao Nextcloud **sempre via SSO**

**Vantagens:**
- ✅ Maior segurança (senha do Nextcloud nunca exposta)
- ✅ Separação de responsabilidades
- ✅ Usuário só precisa lembrar uma senha (do SaaS)
- ✅ Implementação mais simples
- ✅ Não requer armazenar senha em texto plano

**Desvantagens:**
- ❌ Usuário não pode fazer login direto no Nextcloud
- ❌ Se SSO falhar, usuário fica sem acesso

**Melhorias Necessárias:**
1. Implementar endpoint de "Esqueci minha senha" no Nextcloud
2. Adicionar botão "Recuperar acesso" no dashboard
3. Documentar claramente que acesso é via SaaS

**Código necessário:**
```php
// No dashboard do SaaS
public function resetNextcloudPassword(User $user)
{
    $nc = new NextcloudService($user->nextcloudInstance);
    
    // Gera nova senha aleatória
    $newPassword = Str::random(20) . '!@#';
    
    // Envia email com senha temporária
    $nc->setUserPassword($user->nextcloud_user_id, $newPassword);
    $nc->sendPasswordResetEmail($user->nextcloud_user_id);
    
    return response()->json([
        'message' => 'Email de recuperação enviado com sucesso'
    ]);
}
```

---

### Opção 2: Usar a Mesma Senha

**Descrição:**
- Mesma senha no SaaS e no Nextcloud
- Usuário pode fazer login direto em ambos os sistemas

**Vantagens:**
- ✅ Usuário pode acessar Nextcloud diretamente
- ✅ Backup caso SSO falhe
- ✅ Experiência familiar para usuários

**Desvantagens:**
- ❌ **PROBLEMA CRÍTICO**: Senha não está disponível após registro
- ❌ Senha é hasheada imediatamente (Laravel Bcrypt)
- ❌ Não é possível recuperar a senha original
- ❌ Violaria princípios de segurança

**Por que não funciona:**
```php
// No registro do SaaS
User::create([
    'password' => Hash::make($request->password) // Hash irreversível
]);

// Depois não é possível fazer:
$password = $user->password; // ❌ Retorna hash, não senha original
```

**Implementação seria possível apenas com:**
- Armazenar senha em texto plano (❌ INSEGURO)
- Usar criptografia reversível (❌ MÁ PRÁTICA)
- Requerer que usuário redefina senha no Nextcloud (trabalhoso)

---

### Opção 3: Sincronização de Senha no Primeiro SSO

**Descrição:**
- Criar usuário com senha aleatória (como está)
- No primeiro acesso via SSO, atualizar senha do Nextcloud
- Requer middleware especial para capturar senha

**Vantagens:**
- ✅ Senhas sincronizadas após primeiro uso
- ✅ Usuário pode usar mesma senha depois
- ✅ Não armazena senha em texto plano permanentemente

**Desvantagens:**
- ❌ Complexidade adicional
- ❌ Requer interceptar senha no login
- ❌ Senha fica temporariamente em memória
- ❌ Sincronização pode falhar

**Implementação:**
```php
// Interceptar login para capturar senha
class SyncNextcloudPasswordMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if ($request->is('api/login') && $response->isSuccessful()) {
            $user = $request->user();
            $password = $request->input('password'); // Senha em texto plano
            
            // Se usuário tem Nextcloud e é primeiro acesso
            if ($user->nextcloud_user_id && !$user->nextcloud_password_synced) {
                dispatch(new SyncNextcloudPassword($user, $password));
            }
        }
        
        return $response;
    }
}

// Job para sincronizar
class SyncNextcloudPassword implements ShouldQueue
{
    public function __construct(
        public User $user,
        public string $password
    ) {}
    
    public function handle()
    {
        $nc = new NextcloudService($this->user->nextcloudInstance);
        $nc->setUserPassword($this->user->nextcloud_user_id, $this->password);
        
        $this->user->update(['nextcloud_password_synced' => true]);
    }
}
```

---

### Opção 4: Token de Acesso de Longa Duração

**Descrição:**
- Usar Nextcloud App Passwords / OAuth tokens
- Gerar token de app no primeiro acesso
- Armazenar token criptografado no banco

**Vantagens:**
- ✅ Mais seguro que armazenar senha
- ✅ Tokens podem ser revogados
- ✅ Acesso programático facilitado
- ✅ Alinhado com boas práticas OAuth

**Desvantagens:**
- ❌ Requer configurar OAuth no Nextcloud
- ❌ Mais complexo de implementar
- ❌ Usuário ainda não pode fazer login manual

---

## Recomendação Final

**Escolha: Opção 1 (Manter Senhas Diferentes)**

### Justificativa

1. **Segurança em primeiro lugar**: Não armazenar/transmitir senhas desnecessariamente
2. **SSO elimina a necessidade**: Com SSO funcionando, usuário não precisa da senha do Nextcloud
3. **Simplicidade**: Mantém arquitetura atual, apenas adiciona recuperação de emergência
4. **Escalabilidade**: Facilita adicionar novas instâncias Nextcloud no futuro

### Implementação Recomendada

#### 1. Adicionar Recuperação de Emergência

```php
// app/Http/Controllers/Api/NextcloudPasswordController.php
class NextcloudPasswordController extends Controller
{
    public function requestPasswordReset(Request $request)
    {
        $user = $request->user();
        
        if (!$user->nextcloud_user_id) {
            return response()->json([
                'message' => 'Conta Nextcloud ainda não configurada'
            ], 400);
        }
        
        try {
            $nc = new NextcloudService($user->nextcloudInstance);
            
            // Envia email de reset pelo próprio Nextcloud
            $nc->sendPasswordResetEmail($user->nextcloud_user_id);
            
            Log::info("Password reset solicitado", [
                'user_id' => $user->id,
                'nextcloud_user_id' => $user->nextcloud_user_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Email de recuperação enviado para ' . $user->email
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erro ao solicitar reset de senha", [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'message' => 'Erro ao enviar email de recuperação'
            ], 500);
        }
    }
}
```

#### 2. Adicionar no Frontend

```typescript
// frontend/app/dashboard/page.tsx
const handleRecoverNextcloudAccess = async () => {
  try {
    setToast({ message: "Enviando email de recuperação...", type: "info" });
    
    await api.post('/nextcloud/password-reset');
    
    setToast({ 
      message: "Email enviado! Confira sua caixa de entrada.", 
      type: "success" 
    });
  } catch (error: any) {
    setToast({
      message: error.response?.data?.message || "Erro ao enviar email",
      type: "error"
    });
  }
};

// Adicionar botão no card "Plataforma de Assinatura"
<button
  onClick={handleRecoverNextcloudAccess}
  className="text-sm text-blue-600 hover:underline mt-2"
>
  Problemas para acessar? Recuperar senha do Nextcloud
</button>
```

#### 3. Documentar para Usuários

```markdown
# Como Acessar o Nextcloud

## Método Principal (Recomendado)
1. Faça login no dashboard do SaaS LibreSign
2. Clique em "Acessar Plataforma"
3. Você será automaticamente logado no Nextcloud (SSO)

## Método Alternativo (Emergência)
Se o acesso via SSO não funcionar:
1. No dashboard, clique em "Problemas para acessar?"
2. Você receberá um email com link para redefinir sua senha do Nextcloud
3. Defina uma nova senha
4. Acesse diretamente via URL do Nextcloud

**Nota**: Por segurança, recomendamos sempre usar o acesso via SSO.
```

---

## Checklist de Implementação

### Fase 1: SSO Completo ✅
- [x] Criar tabela `login_tokens`
- [x] Implementar `LoginToken` model
- [x] Criar `SSOController`
- [x] Integrar frontend com SSO
- [x] Adicionar limpeza automática de tokens
- [ ] Criar endpoint no Nextcloud para validar SSO

### Fase 2: Recuperação de Emergência
- [ ] Adicionar `NextcloudPasswordController`
- [ ] Implementar método `sendPasswordResetEmail` no `NextcloudService`
- [ ] Adicionar rota `/api/nextcloud/password-reset`
- [ ] Criar botão no frontend
- [ ] Testar fluxo completo de recuperação

### Fase 3: Documentação e Suporte
- [ ] Criar FAQ sobre acesso ao Nextcloud
- [ ] Adicionar tooltip explicativo no dashboard
- [ ] Documentar processo para equipe de suporte
- [ ] Criar vídeo tutorial (opcional)

---

## Métricas para Monitorar

1. **Taxa de uso SSO vs. login direto**
   - Meta: >95% dos acessos via SSO
   
2. **Solicitações de reset de senha**
   - Meta: <5% dos usuários por mês
   
3. **Falhas de SSO**
   - Meta: <1% das tentativas
   
4. **Tempo médio de primeiro acesso**
   - Meta: <5 segundos do clique ao Nextcloud carregado

---

## Alternativas Futuras

Se no futuro for necessário permitir login direto no Nextcloud com mesma senha:

### Solução: Serviço de Identity Provider (IDP)

Implementar um IDP centralizado que gerencia autenticação:
- **Keycloak** ou **Auth0**
- Tanto SaaS quanto Nextcloud usam o IDP
- Single Source of Truth para credenciais
- Suporte a SAML/OAuth/OIDC

**Arquitetura:**
```
Usuário → IDP (Keycloak) → SaaS
                         → Nextcloud
```

Isso permitiria:
- Mesma senha para ambos os sistemas
- SSO nativo
- Gestão centralizada de usuários
- 2FA unificado
- Federação com outros serviços

---

## Conclusão

A estratégia de **manter senhas diferentes** com **acesso via SSO** é a melhor opção considerando:
- Segurança
- Usabilidade
- Simplicidade de implementação
- Manutenibilidade

A adição de um fluxo de recuperação de emergência garante que usuários não fiquem sem acesso em caso de problemas com o SSO.
