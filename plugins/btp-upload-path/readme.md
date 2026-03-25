# BTP Secure Uploads - Documentação Técnica

## Visão Geral
O plugin BTP Secure Uploads gerencia o armazenamento seguro de arquivos em um diretório dedicado (E:/uploads/btp) enquanto mantém a estrutura de URLs padrão do WordPress. Implementa um sistema de autenticação baseado em tokens para controle de acesso a arquivos protegidos.

## Funcionalidades Principais
- Armazenamento físico em localização segura
- Validação de acesso via tokens JWT
- Proteção contra acesso direto aos arquivos
- Integração com a API REST do WordPress
- Sistema de logs para monitoramento
- Tratamento padronizado de erros

## Requisitos Técnicos
- WordPress 5.6 ou superior
- PHP 7.4+
- Mod_rewrite ativado
- Permissões de escrita no diretório E:/uploads/btp

## Configuração

1. Instalação:
   - Copiar o diretório do plugin para wp-content/plugins/
   - Ativar via painel administrativo do WordPress

2. Constantes configuráveis:
```php
const BTP_UPLOAD_DIR = 'E:/uploads/btp';
const BTP_TTL_HOURS = 12;
define('BTP_DEBUG', false);
```

## Fluxo de Autenticação

1. Geração de Token:
   - O sistema gera um token JWT ao autenticar o usuário
   - Armazena no banco de dados com data de expiração

2. Validação de Acesso:
   - Verifica a presença dos cookies btpUserToken e btpUserName
   - Confirma a validade do token no banco de dados
   - Verifica se o token não expirou

## Estrutura de Respostas

Códigos HTTP e respostas padrão:

- 200 OK: Arquivo entregue com sucesso
- 403 Forbidden: Token inválido ou permissão negada
- 404 Not Found: Arquivo não existe
- 500 Internal Server Error: Falha no processamento

Exemplo de resposta de erro:
```json
{
  "error": "invalid_token",
  "message": "Token de acesso inválido ou expirado"
}
```

## Configuração do Servidor

Regras de rewrite (automáticas via plugin):
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/(.+)
RewriteCond %{DOCUMENT_ROOT}/wp-content/uploads/%1 !-f
RewriteRule ^ index.php?rest_route=/btp/v1/download/%1 [L]
</IfModule>
```

## Monitoramento e Solução de Problemas

1. Logs do Sistema:
   - Ativar via define('BTP_DEBUG', true)
   - Registrados em wp-content/debug.log

2. Verificações comuns:
   - Permissões no diretório de uploads
   - Configuração do .htaccess
   - Validade dos certificados SSL (para requisições HTTPS)

## Histórico de Versões

Versão 2.0.5 (Atual):
- Melhorias no sistema de logs
- Otimização das queries de banco de dados
- Correção de timezone para tokens
- Validação reforçada de caminhos de arquivos

## Suporte Técnico
Para assistência, contatar:
verediane.monteiro@supero.com.br

Informações necessárias para suporte:
- Versão do WordPress
- Logs de erro relevantes
- Descrição detalhada do problema

## Licenciamento
Distribuído sob licença GPLv2. O uso deste software está sujeito aos termos da licença GNU General Public License.