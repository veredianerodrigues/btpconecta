-- ============================================
-- Tabela: btpconecta_tokens (Versão Simplificada)
-- Banco de Dados: btpconectabd
-- Descrição: Armazena tokens de autenticação da Senior Platform
-- Data: 2025-01-13
-- ============================================

USE `btpconectabd`;

CREATE TABLE IF NOT EXISTS `btpconecta_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(255) NOT NULL,
  `pass` TEXT NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_user` (`user`),
  KEY `idx_user_ativo` (`user`, `ativo`),
  KEY `idx_token_ativo` (`token`, `ativo`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
