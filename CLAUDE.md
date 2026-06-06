# api_rest_jwt — Laravel Backend

## Stack
- PHP 8.4 + Laravel 13
- JWT: `PHPOpenSourceSaver/JWTAuth ^2.9`
- Banco: PostgreSQL com PostGIS (campo `location` do usuário)
- Ambiente de dev: **Laravel Sail** (Docker) — imagem `sail-8.4/app`
- SMS/WhatsApp: **Vonage Messages API** via `vonage/client ^4.3`
- IDs: ULID (`Str::ulid()`) — não usar auto-increment

## Rodar o projeto
```bash
./vendor/bin/sail up -d          # sobe os containers
./vendor/bin/sail artisan ...    # comandos artisan dentro do container
./vendor/bin/sail composer ...   # composer dentro do container
```
Container principal: `api_rest_jwt-laravel.test-1`

## Estrutura relevante

```
app/
  Http/Controllers/
    Auth/
      AuthController.php                          # login, me, logout, refresh
      EmailVerificationNotificationController.php # envia e-mail de verificação
      PhoneVerificationNotificationController.php # envia SMS/WhatsApp (rate limit: 15s)
      VerifyPhoneController.php                   # verifica código recebido
    User/
      RegisterController.php                      # cadastro de novo usuário
  Http/Resources/
    UserResource.php    # campos email e phone_number MASCARADOS (LGPD)
  Models/
    User.php            # usa ULID, HasTwoFactor, JWTSubject
  Services/
    VonageService.php   # sendSms() e sendWhatsApp()
  Traits/
    HasTwoFactor.php    # regenerateTwoFactorCode(), clearTwoFactorCode()
lang/
  pt_BR/
    notification-sms.php  # mensagem do código de verificação por SMS/WhatsApp
```

## Rotas da API (`/api`)

| Método | Rota                                    | Auth | Descrição                     |
|--------|-----------------------------------------|------|-------------------------------|
| POST   | `/user/register`                        | —    | Cadastro de novo usuário      |
| POST   | `/auth/login`                           | —    | Login → retorna JWT           |
| POST   | `/auth/logout`                          | JWT  | Logout (invalida token)       |
| POST   | `/auth/me`                              | JWT  | Dados do usuário logado       |
| POST   | `/auth/refresh`                         | JWT  | Renova o token JWT            |
| POST   | `/auth/email/verification-notification` | JWT  | Envia e-mail de verificação   |
| POST   | `/auth/phone/verification-notification` | JWT  | Envia código SMS ou WhatsApp  |
| POST   | `/verify-phone/{userId}/{hash}`         | —    | Verifica o código recebido    |
| GET    | `/auth/debug/token`                     | JWT  | Debug JWT (só em local/dev)   |

## Formato das respostas

### `POST /auth/me` — retorna com wrapper `data`
```json
{ "data": { "id": "...", "email": "g*****m@g****.com", ... } }
```

### `POST /user/register` — retorna flat (sem `data`)
```json
{ "id": "...", "email": "g*****m@g****.com", ... }
```
> Motivo: `response()->json(JsonResource)` NÃO adiciona wrapper `data`.
> `JsonResource::make($user)` retornado diretamente SIM adiciona.

## UserResource — mascaramento LGPD
- **email**: mantém 1º e último char da parte local + 1º char e TLD do domínio
  - `giovanijm@gmail.com` → `g*******m@g****.com`
- **phone_number**: mantém 3 primeiros (DDD + 1º dígito) e 4 últimos
  - `12992061431` → `129****1431`

## Verificação de telefone

### Envio (`PhoneVerificationNotificationController`)
- Body: `{ id, method: "sms"|"whatsapp" }`
- Rate limit: 1 tentativa a cada **15 segundos** por usuário (retorna 429 se excedido)
- Chama `$user->regenerateTwoFactorCode($method)` → salva `two_factor_code` (6 dígitos, expira em 10 min)
- Envia via `VonageService::sendSms()` ou `sendWhatsApp()`
- Retorna: `{ message, id, hash: sha1($user->email) }` *(id e hash são apenas para testes)*

### Verificação (`VerifyPhoneController`)
- Rota: `POST /verify-phone/{userId}/{hash}`
- Body: `{ code: "123456" }`
- Hash = `sha1($user->email)` — serve como token de sessão sem JWT

## VonageService — regras importantes

### Formatação de número (`formatPhone`)
- Remove tudo que não for dígito e adiciona DDI `55` se não presente
- `12992061431` → `5512992061431`
- Aplicado apenas ao `to` (destinatário); o `from` tem tratamento próprio

### Sandbox vs Produção
- SMS usa sempre `api.nexmo.com` (produção), independente do `VONAGE_SANDBOX`
- WhatsApp sandbox usa `messages-sandbox.nexmo.com` (apenas quando `VONAGE_SANDBOX=true`)
- O sandbox do WhatsApp requer opt-in: destinatário deve enviar `Join <keyword>` para o número do sandbox

### Variáveis de ambiente Vonage
```env
VONAGE_API_KEY=...
VONAGE_API_SECRET=...
VONAGE_SMS_FROM="5512992061431"       # número remetente SMS (sem +, com DDI)
VONAGE_WHATSAPP_FROM="+14157386102"   # número sandbox Vonage (ou WABA em produção)
VONAGE_SANDBOX=true                   # false em produção
```

## Cadastro de usuário (`RegisterController`)
- Validação: `name`, `email` (único), `password` (confirmado), `role` (admin|merchant|user), `phone_number` (11 dígitos), `latitude`, `longitude`
- Telefone armazenado apenas com dígitos (sem máscara)
- Localização salva como `geography(Point,4326)` via PostGIS

## Modelo User
- `email_verified_at` → booleano `email_verified` no resource
- `phone_verified_at` → booleano `phone_verified` no resource
- `two_factor_code` → código de 6 dígitos gerado por `HasTwoFactor`
- `two_factor_expires_at` → expira em 10 minutos
- `two_factor_method` → `sms` ou `whatsapp`
- `is_active` → bloqueia login se `false`
- Roles: `admin`, `merchant`, `user`

## Convenções
- Sem comentários óbvios; comentar apenas o "porquê" quando não for evidente
- Controllers de ação única usam `__invoke()`
- Sempre usar `Validator::make()` com retorno de erro antes de processar
- Não usar `request()->all()` para salvar — mapear campos explicitamente
