-- Módulo Simples: tabela independente de entradas e saídas

CREATE TABLE IF NOT EXISTS `modulo_simples_lancamentos` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`     INT UNSIGNED NOT NULL,
    `descricao`      VARCHAR(255) NOT NULL,
    `tipo`           ENUM('entrada','saida') NOT NULL,
    `valor`          DECIMAL(12,2) NOT NULL,
    `data_referencia` DATE NOT NULL,
    `observacao`     TEXT,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_msl_usuario` (`usuario_id`),
    KEY `idx_msl_data` (`data_referencia`),
    CONSTRAINT `fk_msl_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
