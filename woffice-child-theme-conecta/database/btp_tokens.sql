-- ============================================
-- Tabela: btpconecta_tokens
-- Banco de Dados: btpconectabd
-- Descrição: Armazena tokens de autenticação da Senior Platform
-- Data: 2025-01-13
-- ============================================

USE `btpconectabd`;

CREATE TABLE IF NOT EXISTS `btpconecta_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID único do token',
  `user` VARCHAR(255) NOT NULL COMMENT 'Matrícula do usuário (ex: 12345 ou 12345@btp.com.br)',
  `pass` TEXT NOT NULL COMMENT 'Senha em base64',
  `token` VARCHAR(255) NOT NULL COMMENT 'Token de autenticação (32 caracteres)',
  `ip` VARCHAR(45) NOT NULL COMMENT 'Endereço IP do usuário (suporta IPv6)',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Status do token: 1=ativo, 0=inativo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora de criação do token',
  `expires_at` DATETIME NULL DEFAULT NULL COMMENT 'Data/hora de expiração do token (NULL = sem expiração)',

  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_user` (`user`),
  KEY `idx_user_ativo` (`user`, `ativo`),
  KEY `idx_token_ativo` (`token`, `ativo`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens de autenticação da Senior Platform';

-- ============================================
-- Índices e Performance
-- ============================================
-- idx_token: UNIQUE para garantir tokens únicos
-- idx_user: Busca rápida por matrícula
-- idx_user_ativo: Otimiza queries WHERE user=? AND ativo=1
-- idx_token_ativo: Otimiza validação de token ativo
-- idx_expires_at: Otimiza limpeza de tokens expirados

-- ============================================
-- Trigger: Inativar tokens expirados (Opcional)
-- ============================================
DELIMITER $$

CREATE TRIGGER `btpconecta_tokens_check_expiration`
BEFORE UPDATE ON `btpconecta_tokens`
FOR EACH ROW
BEGIN
  IF NEW.expires_at IS NOT NULL AND NEW.expires_at < NOW() AND NEW.ativo = 1 THEN
    SET NEW.ativo = 0;
  END IF;
END$$

DELIMITER ;

-- ============================================
-- Event: Limpeza automática de tokens expirados (Opcional)
-- ============================================
-- Executa diariamente às 03:00 para marcar tokens expirados como inativos

SET GLOBAL event_scheduler = ON;

DELIMITER $$

CREATE EVENT IF NOT EXISTS `btpconecta_tokens_cleanup_expired`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 1 DAY
DO
BEGIN
  UPDATE `btpconecta_tokens`
  SET `ativo` = 0
  WHERE `expires_at` IS NOT NULL
    AND `expires_at` < UTC_TIMESTAMP()
    AND `ativo` = 1;
END$$

DELIMITER ;

-- ============================================
-- Dados de Exemplo (Comentado - Descomente se necessário para testes)
-- ============================================
/*
INSERT INTO `btpconecta_tokens` (`user`, `pass`, `token`, `ip`, `ativo`, `expires_at`) VALUES
('12345@btp.com.br', 'c2VuaGFfZXhhbXBsZQ==', 'abc123def456ghi789jkl012mno345pq', '192.168.1.100', 1, DATE_ADD(NOW(), INTERVAL 1 HOUR)),
('67890@btp.com.br', 'b3V0cmFfc2VuaGE=', 'rst678uvw901xyz234abc567def890ghi', '10.0.0.50', 1, DATE_ADD(NOW(), INTERVAL 1 HOUR)),
('11111@btp.com.br', 'c2VuaGFfYW50aWdh', 'old111token222expired333inactive444', '172.16.0.10', 0, '2024-01-01 00:00:00');
*/

-- ============================================
-- Queries Úteis para Manutenção
-- ============================================

-- Ver todos os tokens ativos
-- SELECT * FROM btpconecta_tokens WHERE ativo = 1;

-- Ver tokens expirados que ainda estão ativos (para debug)
-- SELECT * FROM btpconecta_tokens WHERE expires_at < UTC_TIMESTAMP() AND ativo = 1;

-- Inativar tokens expirados manualmente
-- UPDATE btpconecta_tokens SET ativo = 0 WHERE expires_at < UTC_TIMESTAMP() AND ativo = 1;

-- Contar tokens por status
-- SELECT ativo, COUNT(*) as total FROM btpconecta_tokens GROUP BY ativo;

-- Ver tokens de um usuário específico
-- SELECT * FROM btpconecta_tokens WHERE user = '12345@btp.com.br' ORDER BY created_at DESC;

-- Limpar tokens antigos (mais de 30 dias inativos)
-- DELETE FROM btpconecta_tokens WHERE ativo = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ============================================
-- Fim do Script
-- ============================================
