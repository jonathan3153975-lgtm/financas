-- ============================================================
-- MIGRATION: Levantamento de possíveis entradas (módulo de Apostas)
-- ============================================================

USE `financas`;

CREATE TABLE IF NOT EXISTS `apostas_prospectos` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`  INT UNSIGNED NOT NULL,
    `descricao`   VARCHAR(255) NOT NULL,
    `categoria_id` INT UNSIGNED NULL,
    `data_hora`   DATETIME NOT NULL,
    `odd`         DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    `aposta_id`   INT UNSIGNED NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_apr_usuario` (`usuario_id`),
    KEY `fk_apr_categoria` (`categoria_id`),
    KEY `fk_apr_aposta` (`aposta_id`),
    KEY `idx_apr_data` (`data_hora`),
    CONSTRAINT `fk_apr_usuario`   FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`          (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_apr_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `apostas_categorias` (`id`),
    CONSTRAINT `fk_apr_aposta`    FOREIGN KEY (`aposta_id`)    REFERENCES `apostas`            (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
