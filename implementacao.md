Leia a estrutura atual em: /woffice-child-theme-conecta , este é o tema que será substituido.
Você irá criar um tema WordPress customizado do zero chamado "/btpconecta", sem depender de nenhum tema pai. O objetivo é substituir o tema woffice (vulnerável e desatualizado) preservando 100% da aparência visual do cabeçalho e menu lateral existentes, mas entregando páginas de listagem de notícias e posts individuais muito melhores, com suporte nativo ao editor Gutenberg.
---
## CONTEXTO DO PROJETO
Este é o portal de intranet BTP Conecta (btpconecta.com.br), um WordPress com autenticação customizada via PHP que integra com a plataforma Senior. O site é temporário (substituto enquanto o novo site está em desenvolvimento), então a solução precisa ser simples, segura e funcional, sem plugins pagos.
---
## IDENTIDADE VISUAL — PRESERVE EXATAMENTE
### Cores (não alterar nenhuma delas)
- **Fundo geral do site:** `#ffffff`
- **Cor de texto principal:** `#214549`
- **Sidebar/menu lateral — fundo:** `#ffffff`
- **Sidebar/menu lateral — largura:** `250px` fixa
- **Superheader (barra de breadcrumb) — fundo:** `rgb(33, 69, 73)` = `#214549`
- **Superheader — altura:** `40px`, padding `5px 15px`
- **Superheader — texto ativo/categoria (ex: "ACONTECE NA BTP"):** `rgb(189, 201, 21)` = `#bdc915`
- **Superheader — link "Início":** `rgb(49, 70, 197)` = `#3146c5`
- **Superheader — link "Home" (direita):** `#ffffff`
- **Featuredbox (hero da página) — borda inferior:** `6px solid #3146c5`
### Cores dos marcadores do menu lateral (blocos coloridos à esquerda de cada item — NÃO ALTERAR)
Cada item do menu de primeiro nível tem um bloco colorido à esquerda implementado via `::before`. Preserve exatamente:
- **Institucional** (`#menu-item-90`): `#214549` (azul escuro/petróleo)
- **Central de Serviços** (sem id específico): `#E94E1B` (laranja/vermelho)
- **RH para você** (`#menu-item-87`): `#3AAA35` (verde)
- **Performance e Processos** (`#menu-item-89`): `#E2AB3B` (amarelo/âmbar)
- **Notícias** (`#menu-item-220`): `#1C6C7F` (azul teal)
O bloco colorido ocupa `10%` da largura do item, altura `100%`, com `margin-right: 5px`.
Itens de sub-menu têm fundo `#ffffff`, fonte `0.8rem`, sem o bloco colorido.
### Tipografia
- **Fonte do corpo e menu:** `Arial, Helvetica, sans-serif`
- **Fonte dos títulos (h1, h2, h3):** `Roboto, 'PT Sans', Arial, Helvetica, sans-serif` — importar via Google Fonts
- **Hierarquia visual de títulos a ser criada:**
  - `h1`: título principal da página — peso bold, cor `#214549`, tamanho grande
  - `h2`: título de seção dentro do post — peso bold, cor `#214549`, tamanho médio-grande, com separador visual (borda esquerda `4px solid #bdc915`)
  - `h3`: subtítulo / título de card — peso semi-bold, cor `#214549`, tamanho médio
  - `h4`, `h5`: variações menores, mesma cor
  - Parágrafos: cor `#333333`, line-height `1.7`, tamanho base legível
  - Links no conteúdo: cor `#3146c5`, hover com sublinhado
- O menu lateral usa `text-transform: uppercase`, `font-size: 0.95em`, cor `#214549`
- Sub-itens do menu: `font-size: 0.8rem`, cor `#b9b9b9`
---
## ESTRUTURA DE ARQUIVOS DO TEMA
Criar a seguinte estrutura em `/wp-content/themes/btpconecta/`:
btpconecta/
├── style.css
├── functions.php
├── index.php
├── header.php
├── footer.php
├── sidebar.php
├── single.php
├── archive.php
├── page.php
├── search.php
├── 404.php
├── login/
│   └── php/
│       ├── login.php       ← copiar do tema atual
│       ├── logout.php      ← copiar do tema atual
│       └── auth.php        ← copiar do tema atual (verificar nome real dos arquivos)
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
└── template-parts/
├── content-card.php    ← card de notícia para a listagem
└── content-single.php  ← conteúdo do post individual
---
## AUTENTICAÇÃO CUSTOMIZADA
A autenticação atual está em `/wp-content/themes/woffice-child-theme-conecta/login/php/`. Ela:
- Intercepta todo acesso ao site (via hook `template_redirect` ou verificação no `functions.php`)
- Verifica se há sessão/cookie ativo
- Redireciona para a página de login customizada se não autenticado
- A senha/login valida contra a plataforma Senior (sistema externo)
- O logout está em `logout.php` e limpa a sessão
**Tarefa:** Copiar todos os arquivos da pasta `login/php/` para o novo tema e ajustar todos os caminhos de `get_template_directory()`, `get_stylesheet_directory()` e paths absolutos que referenciem o tema antigo para o novo. O hook de autenticação deve ser registrado no `functions.php` do novo tema.
---
## LAYOUT — O QUE DEVE SER PRESERVADO SEM ALTERAÇÃO VISUAL
### Estrutura geral da página
[borda topo 5px — cor #bdc915]
┌─────────────────────────────────────────┐
│  SIDEBAR/MENU LATERAL (250px, branco)   │  CONTEÚDO PRINCIPAL
│  - Logo BTP Conecta (topo)              │  ┌──────────────────────────────────────┐
│  - Lupa de busca                        │  │ SUPERHEADER (40px, fundo #214549)    │
│  - "BEM-VINDO(A)! (SAIR)"              │  │ Início / Categoria — Home →          │
│  - Menu com marcadores coloridos        │  ├──────────────────────────────────────┤
│  - Calendário (sidebar direita)         │  │ FEATUREDBOX/HERO (imagem + título)   │
└─────────────────────────────────────────┘  │ borda inferior 6px #3146c5           │
├──────────────────────────────────────┤
│ ÁREA DE CONTEÚDO                     │
└──────────────────────────────────────┘
[borda bottom 5px — cor #bdc915]
O menu lateral (`navigation-wrapper`, `#navigation`) e o cabeçalho (`#main-header`, `#navbar`) devem ser recriados com a mesma estrutura visual e funcional. **Não há margem para erro nestes elementos** — o restante da empresa usa essa navegação diariamente.
---
## MELHORIAS — LISTAGEM DE NOTÍCIAS (`archive.php`)
Esta é a melhoria principal. A página `/acontece-na-btp/` e outras listagens de categoria devem exibir um grid moderno de cards.
**Problemas atuais a resolver:**
- Cards sem imagem de capa
- Títulos quebrando feio em colunas estreitas
- Sem excerpt/resumo
- Sem data visível
- Sem categoria visível
- Sem paginação
**Como deve ficar cada card:**
┌─────────────────────────────────┐
│  [IMAGEM DE CAPA — 16:9]        │
├─────────────────────────────────┤
│  [TAG CATEGORIA — cor do menu]  │
│  Título da Notícia              │
│  Resumo de 2-3 linhas...        │
│  📅 10/03/2026    Leia mais →  │
└─────────────────────────────────┘
**Especificações do grid:**
- Desktop: 3 colunas, gap 24px
- Tablet (< 900px): 2 colunas
- Mobile (< 600px): 1 coluna
- Card com `border-radius: 12px`, sombra sutil `box-shadow: 0 2px 12px rgba(0,0,0,0.08)`
- Hover: sombra mais forte + leve translate(-2px)
- Imagem de capa: se não houver thumbnail, exibir um placeholder com a cor da categoria e o logo BTP
- Paginação numérica ao final usando `the_posts_pagination()`
- Filtro de categoria no topo (opcional, usar `wp_list_categories()` com estilo pill/badge)
**PHP:** Usar `WP_Query` com `posts_per_page: 12`. O excerpt deve usar `get_the_excerpt()` com fallback para `wp_trim_words(get_the_content(), 20)`.
---
## MELHORIAS — POST INDIVIDUAL (`single.php`)
**Problema principal atual:** o conteúdo dos posts é postado como uma imagem única (PNG/JPG). O novo template deve estimular e renderizar conteúdo HTML rico via Gutenberg.
**Estrutura do single post:**
[SUPERHEADER com breadcrumb]
[HERO — imagem de capa grande (100% largura), com overlay escuro e título sobreposto]
┌──────────────────────────────────────────────────────┐
│  Categoria (badge colorido)   📅 Data de publicação  │
│  ─────────────────────────────────────────────────── │
│                                                      │
│  [CONTEÚDO GUTENBERG — the_content()]                │
│  Parágrafos, títulos h2/h3, imagens, listas,         │
│  citações, colunas, botões — tudo renderizado        │
│  com a tipografia da hierarquia visual definida      │
│                                                      │
│  ─────────────────────────────────────────────────── │
│  ← Post Anterior          Próximo Post →             │
└──────────────────────────────────────────────────────┘
**Especificações:**
- Hero com `min-height: 320px`, imagem de capa como `background-image`, overlay `rgba(33, 69, 73, 0.6)`
- Título no hero: branco, fonte Roboto bold
- Área de conteúdo: largura máxima `780px`, centralizada, padding generoso
- Suporte completo ao Gutenberg: chamar `wp_head()` e `wp_footer()` corretamente, incluir `add_theme_support('editor-styles')` e `add_theme_support('align-wide')` no `functions.php`
- Estilizar os blocos nativos do Gutenberg: parágrafo, heading, imagem, lista, quote, separator, columns, button
- Navegação anterior/próximo: restrita à mesma categoria do post atual (`in_same_term: true`)
- Botão "Voltar" no final apontando para a categoria pai (não usar `javascript:history.back()`)
---
## SUPORTE AO GUTENBERG
No `functions.php`, adicionar:
```php
add_theme_support('post-thumbnails');
add_theme_support('title-tag');
add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption']);
add_theme_support('editor-styles');
add_theme_support('align-wide');
add_theme_support('responsive-embeds');
add_theme_support('wp-block-styles');
// Registrar larguras de conteúdo para o editor
function btpconecta_setup_content_width() {
    $GLOBALS['content_width'] = 780;
}
add_action('after_setup_theme', 'btpconecta_setup_content_width');
```
---
## SEGURANÇA
- Remover o cabeçalho `X-Powered-By` e a versão do WordPress do HTML
- Adicionar no `functions.php`: `remove_action('wp_head', 'wp_generator')`
- O sistema de autenticação deve verificar a sessão antes de qualquer output (sem headers already sent)
- Usar `wp_nonce` nos formulários do sistema de login customizado
---
## ENTREGÁVEIS
1. Todos os arquivos PHP do tema listados na estrutura de arquivos acima
2. O CSS principal com as cores, tipografia e layouts especificados
3. O JS mínimo necessário (toggle do menu mobile, se aplicável)
4. Instruções de como ativar o tema e verificar se a autenticação foi portada corretamente
**Não usar:** Elementor, WPBakery, ACF, ou qualquer plugin pago. Apenas WordPress core + PHP + CSS.