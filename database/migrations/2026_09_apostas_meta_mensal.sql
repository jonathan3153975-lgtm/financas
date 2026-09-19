-- ============================================================
-- MIGRATION: Meta mensal do módulo de Apostas
-- Valor definido pelo usuário a ser atingido em um determinado mês.
-- ============================================================

USE `financas`;

CREATE TABLE IF NOT EXISTS `apostas_meta_mensal` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED NOT NULL,
    `mes`        TINYINT UNSIGNED NOT NULL,
    `ano`        SMALLINT UNSIGNED NOT NULL,
    `valor`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_amm_usuario_mes_ano` (`usuario_id`, `mes`, `ano`),
    CONSTRAINT `fk_amm_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;