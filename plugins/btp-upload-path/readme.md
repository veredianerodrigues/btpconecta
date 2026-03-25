# BTP Secure Uploads - Documentação Técnica

## Visão Geral
O plugin BTP Secure Uploads gerencia o armazenamento seguro de arquivos em um diretório dedicado (`E:/uploads/btp`) enquanto mantém a estrutura de URLs padrão do WordPress. Implementa autenticação baseada em tokens opacos armazenados em banco de dados para controlar o acesso a arquivos protegidos.

## Funcionalidades Principais
- Armazenamento físico em localização segura fora do webroot
- Validação de acesso via tokens opacos (tabela `btpconecta_tokens`)
- Proteção contra acesso direto aos arquivos via `.htaccess`
- Entrega de arquivos através da API REST do WordPress
- Proteção contra path traversal e symlinks
- Sistema de logs para monitoramento

## Requisitos Técnicos
- WordPress 5.6 ou superior
- PHP 7.4+
- Mod_rewrite ativado
- Permissões de escrita no diretório `E:/uploads/btp`

## Configuração

1. Instalação:
   - Copiar o diretório do plugin para `wp-content/plugins/`
   - Ativar via painel administrativo do WordPress

2. Constantes configuráveis (em `wp-config.php`):
```php
const BTP_UPLOAD_DIR = 'E:/uploads/btp';
const BTP_TTL_HOURS  = 12;
define('BTP_DEBUG', false);
```

## Fluxo de Autenticação

O plugin aceita dois tipos de identidade:

### 1. Usuário WordPress (bypass)
Usuários WP logados com as capabilities `manage_options`, `edit_others_posts` ou `upload_files` têm acesso direto sem necessidade de token BTP.

### 2. Usuário BTP (token opaco)
- O sistema de login externo gera um token aleatório e o grava na tabela `btpconecta_tokens` com TTL de `BTP_TTL_HOURS` horas
- O token é armazenado no cookie `btpUserToken` e o e-mail do usuário em `btpUserName`
- A cada requisição, o plugin consulta a tabela verificando:
  - `token = %s` (correspondência exata)
  - `LOWER(user) = LOWER(%s)` (e-mail case-insensitive)
  - `ativo = 1`
  - `expires_at > UTC_TIMESTAMP()` (ou NULL para sem expiração)

> **Nota:** Os tokens **não são JWT**. São strings opacas geradas pelo sistema de login e validadas contra o banco de dados a cada requisição.

## Fluxo de uma Requisição

```
GET /wp-content/uploads/arquivo.pdf
        ↓  .htaccess rewrite
GET /wp-json/btp/v1/download/arquivo.pdf
        ↓  permission_callback: btp_token_ok()
   sem auth  →  403 Forbidden  (interrompido antes do callback)
   com auth  →  btp_rest_download()
                  ↓  valida segmentos do path (rejeita .. e .)
                  ↓  rejeita symlinks
                  ↓  resolve realpath e confirma dentro do root permitido
                  ↓  200 OK + readfile()
```

## Estrutura de Respostas

| Código | Situação |
|--------|----------|
| 200 OK | Arquivo entregue com sucesso |
| 400 Bad Request | Path inválido (traversal detectado) |
| 401 Unauthorized | Sem cookies de autenticação |
| 403 Forbidden | Token inválido, inativo ou expirado |
| 404 Not Found | Arquivo não existe nos roots permitidos |

## Segurança

- **Path traversal**: cada segmento do path é validado individualmente; qualquer `..` ou `.` resulta em 400
- **Symlinks**: paths que são symlinks são rejeitados antes de `realpath()`
- **Prefix spoofing**: comparação de root usa separador (`/root/path/`) para evitar falsos positivos
- **Header injection**: filename no `Content-Disposition` tem `\r`, `\n`, `"` e `\` substituídos por `_`
- **SQL**: nome da tabela é escapado com `esc_sql()` antes de interpolação

## Configuração do Servidor

Regras de rewrite inseridas automaticamente no `.htaccess` principal na ativação:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^wp-content/uploads/(.+)$ index.php?rest_route=/btp/v1/download/$1 [QSA,L]
</IfModule>
<IfModule mod_headers.c>
Header set Vary "Cookie"
</IfModule>
```

O diretório `E:/uploads/btp` recebe um `.htaccess` que nega todo acesso direto.

## Monitoramento e Solução de Problemas

1. Ativar logs: `define('BTP_DEBUG', true)` em `wp-config.php`
2. Saída registrada em `wp-content/debug.log`

Verificações comuns:
- Permissões de escrita em `E:/uploads/btp`
- Regras do `.htaccess` principal (bloco `BTP_SECURE_UPLOADS`)
- Validade dos cookies `btpUserToken` e `btpUserName` no browser
- Registros na tabela `btpconecta_tokens` (campo `ativo` e `expires_at`)

## Histórico de Versões

**Versão 2.1.0:**
- Autenticação movida para `permission_callback` do endpoint REST
- Correção de path traversal: validação por segmento em vez de remoção de `..`
- Rejeição de symlinks antes de `realpath()`
- Correção de prefix spoofing na comparação de root
- Proteção contra header injection no `Content-Disposition`
- Escape de nome de tabela com `esc_sql()`
- Remoção do uso de `goto`

**Versão 2.0.5:**
- Melhorias no sistema de logs
- Otimização das queries de banco de dados
- Correção de timezone para tokens
- Validação reforçada de caminhos de arquivos

## Suporte Técnico
Para assistência, contatar: verediane.monteiro@supero.com.br

Informações necessárias para suporte:
- Versão do WordPress
- Logs de erro (debug.log com BTP_DEBUG ativado)
- Descrição detalhada do problema

## Licenciamento
Distribuído sob licença GPLv2.
