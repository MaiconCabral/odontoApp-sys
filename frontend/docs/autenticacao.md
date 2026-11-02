# Autenticação

> A API e as regras de sessão estão em [backend/docs/multitenancy.md](../../backend/docs/multitenancy.md).

## Fluxos

### Login

1. No domínio central, o usuário informa o endereço da clínica e vai para o subdomínio dela.
2. No subdomínio, informa e-mail e senha.
3. Ao entrar, volta à página do parâmetro `redirect`, se for válido, ou vai para a página inicial do papel.
4. Credenciais erradas mostram sempre "E-mail ou senha incorretos.", sem indicar qual dos dois falhou.

### Cadastro

Cria o usuário administrador, a clínica e o banco dela, no domínio central. Depois leva ao login do subdomínio, com o e-mail preenchido: a sessão é presa ao host, então o usuário entra uma vez lá.

- **Campos:** nome, e-mail, senha, confirmação, nome da clínica e unidade (opcional).
- **Slug:** gerado a partir do nome da clínica, sem acentos. "Clínica São José" vira `clinica-sao-jose`. Slug repetido recebe um número no final.
- **Unidade vazia:** vira "Unidade principal".
- **Plano inicial:** trial do plano Pro, com 14 dias.

### Recuperação de senha

1. Em `/recuperar-senha`, o usuário informa o e-mail.
2. A resposta é sempre a mesma: "Se houver uma conta para…". **A tela nunca revela se o e-mail existe.**
3. O link leva a `/redefinir-senha?token=...&email=...`.
4. Depois de salvar, o usuário vai para `/login?redefinida=1`, que mostra a confirmação, e entra com a nova senha.
5. Link sem token, sem e-mail ou expirado mostra a tela de link inválido, com a opção de pedir outro.

### Saída

"Sair da conta", na sidebar, encerra a sessão, limpa os dados em memória e volta ao login. Veja [Limpeza ao sair da conta](arquitetura.md#limpeza-ao-sair-da-conta).

## Regras de validação

As regras ficam em `utils/auth.ts`.

| Campo | Regra | Onde vale |
| --- | --- | --- |
| E-mail | Formato válido | Todas as telas que pedem e-mail |
| Senha nova | Ao menos 8 caracteres, com letra e número | Cadastro e redefinição |
| Confirmação | Igual à senha | Cadastro e redefinição |
| Senha atual | Apenas obrigatória | Login |
| Nome e nome da clínica | Ao menos 2 caracteres | Cadastro |

A política de senha não se aplica ao login, para não bloquear contas com senhas antigas.

A validação do frontend serve à experiência do usuário. **A API precisa repetir todas essas regras.**

## Como construir um formulário de autenticação

Use `useAuthForm`, que já cuida de:

- impedir envio duplicado;
- validar antes de enviar e expor o estado de carregamento;
- mostrar erros da API no campo correspondente ou, sem campo, no topo do formulário;
- trocar erros inesperados por uma mensagem genérica, sem expor detalhes técnicos;
- levar o foco ao primeiro campo inválido.

```vue
<script setup lang="ts">
definePageMeta({ layout: 'auth', middleware: 'guest' })

const form = reactive<ForgotPasswordPayload>({ email: '' })

const { errors, formError, isSubmitting, submit } = useAuthForm(
  form,
  validateForgotPassword,
  () => forgotPassword(form),
)
</script>
```

Regras dos campos:

- Use `AppFormField` com `size="large"` e `AuthPasswordField` para senhas.
- Use o `autocomplete` correto: `email`, `current-password` ou `new-password`.
- Use `AuthAlert` para mensagens gerais e `AuthSubmitButton` para enviar.
- Marque o `<form>` com `novalidate`, para que as mensagens sejam as da aplicação.

## Regras de segurança

- **Redirecionamento:** todo destino vindo da URL passa por `safeRedirect`. Só caminhos `/app/...` são aceitos; `//`, `\` e `..` são recusados, o que impede o envio do usuário a outro site.
- **Armazenamento:** senhas e tokens nunca ficam em `localStorage` ou `sessionStorage`. A sessão real deve usar o cookie do Sanctum.
- **Estado do frontend:** nunca é prova de autorização. Middleware e store só decidem o que exibir.

## Convites

Usuários criados pelo admin recebem por e-mail um link para definir a própria senha, que usa a mesma tela de `/redefinir-senha`. Em desenvolvimento, o e-mail vai para `storage/logs/laravel.log`, no backend.
