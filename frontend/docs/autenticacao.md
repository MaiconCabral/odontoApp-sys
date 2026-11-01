# Autenticação

> A implementação atual é um mock de desenvolvimento, em `mocks/auth.ts`. Veja [como trocar pela API](#como-trocar-o-mock-pela-api).

## Fluxos

### Login

1. O usuário informa e-mail e senha.
2. Ao entrar, volta à página do parâmetro `redirect`, se for válido, ou vai para o painel da clínica.
3. Credenciais erradas mostram sempre "E-mail ou senha incorretos.", sem indicar qual dos dois falhou.

### Cadastro

Cria o usuário administrador e a clínica ao mesmo tempo, e já entra no painel.

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

## Limitações do mock

- A conta demo é `demo@sorriso.app` / `Sorriso123`.
- A sessão fica em `sessionStorage` e termina ao fechar a aba.
- Contas criadas e senhas alteradas valem até recarregar a página.
- Clínicas novas veem os mesmos dados de demonstração.
- Nenhum e-mail é enviado. O link de redefinição aparece no console do navegador.

## Como trocar o mock pela API

O contrato está em `types/auth.ts`, e `services/auth.ts` é o único arquivo que aponta para o mock.

### Em `composables/useApi.ts`

O `useApi` atual só envia cookies. A API com Sanctum também exige:

- `GET /sanctum/csrf-cookie` antes de requisições que alteram dados;
- o cabeçalho `X-XSRF-TOKEN`, com o valor do cookie `XSRF-TOKEN`;
- o cabeçalho `Accept: application/json`.

### Em `services/auth.ts`

- Trocar cada função do mock por uma chamada via `useApi`.
- Converter `passwordConfirmation` para `password_confirmation`, e os demais campos conforme o contrato da API.
- Devolver erros de campo com os mesmos nomes usados nos formulários. `parseApiError` lê o formato do Laravel: `{ message, errors: { campo: [mensagens] } }`.
- Em `/redefinir-senha`, token inválido precisa vir em `errors.token`, que ativa a tela de link inválido.
- Em `getSession`, tratar a resposta `401` como `null`.

### Na API

- Enviar o link de recuperação apontando para `/redefinir-senha` do frontend.
- Devolver a mesma resposta para e-mails existentes e inexistentes, inclusive no tempo de resposta.
- Limitar tentativas nos endpoints de autenticação.
- Validar em cada requisição se o usuário pertence à clínica.

### Depois da troca

- Remover `mocks/auth.ts`. `tests/utils/session.ts` e os testes de `useAuth` dependem dele e precisam de novos dados.
- Remover a conta demo desta documentação e o aviso de mock do [README](README.md).
