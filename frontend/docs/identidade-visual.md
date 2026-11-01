# Identidade visual

## Regra principal

**Use tokens, não cores fixas.** Os tokens se adaptam sozinhos ao tema, então não é preciso repetir a cor com `dark:`.

```html
<!-- Certo -->
<p class="text-muted">...</p>

<!-- Errado -->
<p class="text-[#64748b] dark:text-[#a1a1aa]">...</p>
```

A exceção são as cores de status (verde, âmbar, vermelho, roxo), que ainda são fixas nos componentes.

## Tokens

Definidos em `assets/css/main.css` e expostos em `tailwind.config.ts`.

### Superfícies e texto

| Token | Claro | Escuro | Uso |
| --- | --- | --- | --- |
| `canvas` | `#f8fafc` | `#09090b` | Fundo da página |
| `surface` | `#ffffff` | `#18181b` | Cartões e campos |
| `surface-soft` | `#fbfcfd` | `#111113` | Cabeçalhos de tabela e áreas secundárias |
| `line` | `#e2e8f0` | `#27272a` | Bordas |
| `line-soft` | `#f1f5f9` | `#27272a` | Divisórias e hover neutro |
| `ink` | `#0f172a` | `#fafafa` | Texto de maior destaque |
| `heading` | `#1e293b` | `#fafafa` | Títulos |
| `body` | `#334155` | `#d4d4d8` | Texto comum |
| `muted` | `#64748b` | `#a1a1aa` | Texto secundário |
| `faint` | `#94a3b8` | `#71717a` | Ícones e placeholders |

`faint` tem contraste baixo: não use em texto que precisa ser lido.

### Marca

| Token | Claro | Escuro | Uso |
| --- | --- | --- | --- |
| `brand` | `#0284c7` | `#0284c7` | Preenchimento de botões e dados em destaque |
| `brand-hover` | `#0369a1` | `#0369a1` | Hover de botões |
| `brand-accent` | `#0ea5e9` | `#0ea5e9` | Borda de foco e destaques |
| `brand-text` | `#0369a1` | `#38bdf8` | Links e textos de marca |
| `brand-tint` | `#f0f9ff` | `#0b2533` | Fundo suave e hover |
| `brand-soft` | `#e0f2fe` | `#0c3347` | Item ativo, selos e avatares |
| `brand-line` | `#bae6fd` | `#075985` | Bordas de áreas de marca |

Para texto na cor da marca, use sempre `brand-text`, nunca `brand`.

## Tema

- O tema é controlado pelo `@nuxtjs/color-mode`, que aplica a classe `dark` no `<html>`.
- No primeiro acesso, segue o sistema operacional. A escolha do usuário fica salva no navegador.
- Em componentes, use `useTheme`, que expõe `isDark` e `toggleTheme`.
- Toda tela nova precisa ser conferida nos temas claro e escuro.

## Marca e tipografia

- O nome exibido é "sorriso.", renderizado por `AppLogo`.
- Títulos usam Manrope (`font-display`); o restante usa DM Sans.

## Tamanhos

`AppFormField` tem dois tamanhos:

| Tamanho | Controle | Texto | Rótulo | Onde usar |
| --- | --- | --- | --- | --- |
| Padrão | 37px | 11px | 10px | Painel, que é denso |
| `large` | 44px | 14px | 13px | Telas de autenticação |

Os breakpoints são próprios do projeto: `sm` em 561px, `md` em 821px e `lg` em 1101px.

## Telas de autenticação

Quem usa é a recepção ou o dentista-gestor, minutos antes do primeiro paciente, querendo chegar à agenda. A tela deve parecer calma e precisa, como um consultório preparado.

- Layout dividido: formulário à esquerda, painel azul-escuro à direita. Abaixo de `md`, o painel some.
- A assinatura visual é a arcada dentária em traço de odontograma (`AuthDentalArch`). Ela é decorativa e fica oculta para leitores de tela (`aria-hidden`).
- Sem depoimentos, números inventados ou outros elementos que distraiam do formulário.
