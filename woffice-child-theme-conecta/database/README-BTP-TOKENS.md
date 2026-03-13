# Documentação da Tabela btpconecta_tokens

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Instalação](#instalação)
- [Estrutura da Tabela](#estrutura-da-tabela)
- [Índices](#índices)
- [Manutenção](#manutenção)
- [Queries Úteis](#queries-úteis)

---

## 🎯 Visão Geral

A tabela `btpconecta_tokens` armazena tokens de autenticação gerados pela integração com a **Senior Platform API**. Cada token representa uma sessão de usuário válida por 1 hora.

**Banco de Dados:** `btpconecta2`

---

## 🔧 Instalação

### Opção 1: Script Completo (Recomendado para Produção)

```bash
mysql -u usuario -p < btpconecta_tokens.sql
```

**Nota:** O script já contém `USE btpconecta2;` no início.

**Inclui:**
- ✅ Tabela completa
- ✅ Índices otimizados
- ✅ Trigger de expiração automática
- ✅ Event scheduler para limpeza diária
- ✅ Comentários e documentação

### Opção 2: Script Simples (Mínimo Necessário)

```bash
mysql -u usuario -p < btpconecta_tokens_simple.sql
```

**Nota:** O script já contém `USE btpconecta2;` no início.

**Inclui:**
- ✅ Tabela completa
- ✅ Índices otimizados

### Opção 3: Via phpMyAdmin

1. Acesse phpMyAdmin
2. Selecione o banco de dados `btpconecta2` (ou deixe que o script selecione automaticamente)
3. Clique em "SQL"
4. Cole o conteúdo de `btpconecta_tokens_simple.sql`
5. Clique em "Executar"

### Opção 4: Via WP-CLI

```bash
wp db query < tema-do-projeto/woffice-child-theme/database/btpconecta_tokens_simple.sql
```

---

## 📊 Estrutura da Tabela

| Campo | Tipo | Tamanho | Null | Default | Descrição |
|-------|------|---------|------|---------|-----------|
| `id` | INT | 11 | NO | AUTO_INCREMENT | ID único do token |
| `user` | VARCHAR | 255 | NO | - | Matrícula do usuário |
| `pass` | TEXT | 65535 | NO | - | Senha em base64 |
| `token` | VARCHAR | 255 | NO | - | Token de 32 caracteres |
| `ip` | VARCHAR | 45 | NO | - | IP do usuário (IPv4/IPv6) |
| `ativo` | TINYINT | 1 | NO | 1 | Status: 1=ativo, 0=inativo |
| `created_at` | TIMESTAMP | - | NO | CURRENT_TIMESTAMP | Data/hora de criação |
| `expires_at` | DATETIME | - | YES | NULL | Data/hora de expiração |

### Detalhes dos Campos

#### `id`
- Chave primária
- Auto-incremento
- Usado internamente

#### `user`
- Matrícula do usuário
- Exemplos: `12345` ou `12345@btp.com.br`
- Indexado para buscas rápidas

#### `pass`
- Senha codificada em base64
- Armazenada para renovação de sessão
- **Não é a senha em texto puro**

#### `token`
- String de 32 caracteres gerados aleatoriamente
- Exemplo: `abc123def456ghi789jkl012mno345pq`
- **UNIQUE** - Cada token é único no sistema
- Usado nos cookies `btpUserToken`

#### `ip`
- Endereço IP do cliente
- Suporta IPv4 (ex: `192.168.1.1`)
- Suporta IPv6 (ex: `2001:0db8:85a3:0000:0000:8a2e:0370:7334`)
- Usado para auditoria e segurança

#### `ativo`
- `1` = Token ativo e válido
- `0` = Token inativo/expirado/deslogado
- Tokens inativos não permitem autenticação

#### `created_at`
- Data/hora de criação do token (UTC)
- Preenchido automaticamente pelo MySQL
- Usado para auditoria

#### `expires_at`
- Data/hora de expiração do token (UTC)
- Calculado como: `NOW() + 1 HOUR`
- Pode ser `NULL` para tokens sem expiração
- Usado pela função `logged()` para validação

---

## 🔍 Índices

A tabela possui 5 índices para otimizar performance:

### 1. PRIMARY KEY (`id`)
- Chave primária
- Busca por ID específico

### 2. UNIQUE KEY `idx_token` (`token`)
- Garante que cada token seja único
- Otimiza validação: `WHERE token = ?`

### 3. KEY `idx_user` (`user`)
- Busca por matrícula: `WHERE user = ?`
- Lista tokens de um usuário

### 4. KEY `idx_user_ativo` (`user`, `ativo`)
- Índice composto
- Otimiza: `WHERE user = ? AND ativo = 1`
- Usado frequentemente pela validação

### 5. KEY `idx_token_ativo` (`token`, `ativo`)
- Índice composto
- Otimiza: `WHERE token = ? AND ativo = 1`
- **Mais usado** - Validação de autenticação

### 6. KEY `idx_expires_at` (`expires_at`)
- Otimiza limpeza de tokens expirados
- Usado pelo event scheduler

---

## 🛠️ Manutenção

### Verificar Tokens Expirados Ativos (Bug)

```sql
SELECT id, user, token, expires_at, ativo
FROM btpconecta_tokens
WHERE expires_at < UTC_TIMESTAMP()
  AND ativo = 1;
```

**Resultado esperado:** 0 linhas (nenhum token expirado ativo)

### Inativar Tokens Expirados

```sql
UPDATE btpconecta_tokens
SET ativo = 0
WHERE expires_at < UTC_TIMESTAMP()
  AND ativo = 1;
```

### Limpar Tokens Antigos (30+ dias inativos)

```sql
DELETE FROM btpconecta_tokens
WHERE ativo = 0
  AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Estatísticas da Tabela

```sql
SELECT
  COUNT(*) as total_tokens,
  SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
  SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos,
  SUM(CASE WHEN expires_at < UTC_TIMESTAMP() AND ativo = 1 THEN 1 ELSE 0 END) as expirados_ativos
FROM btpconecta_tokens;
```

**Resultado ideal:**
```
total_tokens | ativos | inativos | expirados_ativos
-------------|--------|----------|------------------
     150     |   25   |   125    |        0
```

---

## 📝 Queries Úteis

### Listar Todos os Tokens Ativos

```sql
SELECT id, user, token, ip, created_at, expires_at
FROM btpconecta_tokens
WHERE ativo = 1
ORDER BY created_at DESC;
```

### Buscar Tokens de um Usuário Específico

```sql
SELECT id, token, ativo, created_at, expires_at
FROM btpconecta_tokens
WHERE user = '12345@btp.com.br'
ORDER BY created_at DESC
LIMIT 10;
```

### Verificar Token Específico

```sql
SELECT *
FROM btpconecta_tokens
WHERE token = 'abc123def456ghi789jkl012mno345pq';
```

### Validar Token (Como o PHP faz)

```sql
SELECT *
FROM btpconecta_tokens
WHERE token = ?
  AND user = ?
  AND ativo = 1
  AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP());
```

### Inativar Token (Logout)

```sql
UPDATE btpconecta_tokens
SET ativo = 0
WHERE token = ?
  AND user = ?
  AND ativo = 1;
```

### Ver IPs Mais Ativos

```sql
SELECT ip, COUNT(*) as total_logins
FROM btpconecta_tokens
GROUP BY ip
ORDER BY total_logins DESC
LIMIT 10;
```

### Tokens Criados Hoje

```sql
SELECT COUNT(*) as logins_hoje
FROM btpconecta_tokens
WHERE DATE(created_at) = CURDATE();
```

### Tokens Expirados nas Últimas 24h

```sql
SELECT COUNT(*) as expirados_24h
FROM btpconecta_tokens
WHERE expires_at BETWEEN DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW();
```

---

## 🔒 Segurança

### Boas Práticas

1. **Limpeza Regular:**
   - Executar limpeza de tokens inativos semanalmente
   - Manter apenas últimos 30 dias de histórico

2. **Monitoramento:**
   - Verificar tokens expirados ativos diariamente
   - Alertar se houver mais de 10

3. **Auditoria:**
   - Revisar logins de IPs desconhecidos
   - Verificar múltiplos tokens ativos do mesmo usuário

4. **Backup:**
   - Incluir `btpconecta_tokens` nos backups diários
   - Testar restore periodicamente

### Event Scheduler (Opcional)

Se você executou `btpconecta_tokens.sql` completo, há um event scheduler que roda diariamente às 03:00:

```sql
-- Verificar se está ativo
SHOW VARIABLES LIKE 'event_scheduler';

-- Ativar se necessário
SET GLOBAL event_scheduler = ON;

-- Ver eventos
SHOW EVENTS WHERE Db = 'btpconecta2';

-- Desabilitar evento (se necessário)
ALTER EVENT btpconecta_tokens_cleanup_expired DISABLE;
```

---

## 📊 Performance

### Tamanho Estimado

**Estimativa de espaço:**
- 1 token ≈ 500 bytes
- 1.000 tokens ≈ 500 KB
- 10.000 tokens ≈ 5 MB
- 100.000 tokens ≈ 50 MB

**Recomendação:** Limpar tokens inativos mensalmente para manter tabela leve.

### Análise de Índices

```sql
SHOW INDEX FROM btpconecta_tokens;
```

### Analisar Tabela

```sql
ANALYZE TABLE btpconecta_tokens;
```

### Otimizar Tabela

```sql
OPTIMIZE TABLE btpconecta_tokens;
```

---

## 🧪 Testes

### Teste 1: Criar Token

```sql
INSERT INTO btpconecta_tokens (user, pass, token, ip, ativo, expires_at)
VALUES (
  '12345@btp.com.br',
  'c2VuaGFfdGVzdGU=',
  'test123token456demo789abc012def345',
  '127.0.0.1',
  1,
  DATE_ADD(NOW(), INTERVAL 1 HOUR)
);
```

### Teste 2: Validar Token

```sql
SELECT * FROM btpconecta_tokens
WHERE token = 'test123token456demo789abc012def345'
  AND user = '12345@btp.com.br'
  AND ativo = 1
  AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP());
```

**Resultado esperado:** 1 linha

### Teste 3: Inativar Token (Logout)

```sql
UPDATE btpconecta_tokens
SET ativo = 0
WHERE token = 'test123token456demo789abc012def345';
```

### Teste 4: Validar Token Inativo

```sql
SELECT * FROM btpconecta_tokens
WHERE token = 'test123token456demo789abc012def345'
  AND ativo = 1;
```

**Resultado esperado:** 0 linhas

---

## 🚨 Troubleshooting

### Erro: "Table already exists"

**Solução:** A tabela já foi criada. Use:
```sql
DROP TABLE IF EXISTS btpconecta_tokens;
```

### Erro: "Duplicate entry for key 'idx_token'"

**Causa:** Token já existe no banco
**Solução:** Gerar novo token ou inativar o existente

### Muitos Tokens Expirados Ativos

**Causa:** Event scheduler não está rodando
**Solução:**
```sql
SET GLOBAL event_scheduler = ON;
UPDATE btpconecta_tokens SET ativo = 0 WHERE expires_at < UTC_TIMESTAMP();
```

### Performance Lenta

**Causa:** Tabela muito grande
**Solução:**
```sql
DELETE FROM btpconecta_tokens WHERE ativo = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
OPTIMIZE TABLE btpconecta_tokens;
```

---

## 📞 Suporte

Para problemas com a tabela:

1. Verificar logs do MySQL: `SHOW ENGINE INNODB STATUS;`
2. Verificar permissões: `SHOW GRANTS FOR CURRENT_USER;`
3. Verificar integridade: `CHECK TABLE btpconecta_tokens;`

---

**Documentação criada em:** 2025-01-13
**Versão da Tabela:** 1.0
**Compatibilidade:** MySQL 5.7+, MariaDB 10.3+
