-- ============================================================
-- MIGRATION: Módulo Gerenciamento de Apostas
-- Acesso restrito: usuarios.apostas_autorizado = 1 OU link público
-- ============================================================

USE `financas`;

-- Flag de autorização de acesso ao módulo de apostas
ALTER TABLE `usuarios`
    ADD COLUMN `apostas_autorizado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ativo`;

-- ============================================================
-- TABLE: apostas_categorias (Futebol, Tênis, Basquete, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS `apostas_categorias` (
    `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`   VARCHAR(100) NOT NULL,
    `icone`  VARCHAR(100) DEFAULT 'fa-futbol',
    `ativo`  TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `apostas_categorias` (`nome`, `icone`) VALUES
('Futebol',       'fa-futbol'),
('Tênis',         'fa-table-tennis-paddle-ball'),
('Tênis de Mesa', 'fa-table-tennis-paddle-ball'),
('Basquete',      'fa-basketball'),
('E-sports',      'fa-computer'),
('Vôlei',         'fa-volleyball'),
('Vôlei de Praia', 'fa-volleyball'),
('MMA / UFC',     'fa-hand-fist'),
('Boxe',          'fa-hand-back-fist'),
('Fórmula 1',     'fa-flag-checkered'),
('Rugby',         'fa-football'),
('Handebol',      'fa-hand-dots'),
('Golfe',         'fa-golf-ball-tee'),
('Sinuca',        'fa-circle-dot'),
('Outros',        'fa-dice');

-- ============================================================
-- TABLE: apostas_banca_movimentos (entradas / saques da banca)
-- ============================================================
CREATE TABLE IF NOT EXISTS `apostas_banca_movimentos` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`  INT UNSIGNED NOT NULL,
    `tipo`        ENUM('entrada','saque') NOT NULL,
    `valor`       DECIMAL(12,2) NOT NULL,
    `descricao`   VARCHAR(255) DEFAULT NULL,
    `data`        DATE NOT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_abm_usuario` (`usuario_id`),
    KEY `idx_abm_data` (`data`),
    CONSTRAINT `fk_abm_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: apostas (registro principal: simples ou múltipla)
-- ============================================================
CREATE TABLE IF NOT EXISTS `apostas` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`       INT UNSIGNED NOT NULL,
    `tipo`             ENUM('simples','multipla') NOT NULL DEFAULT 'simples',
    `descricao`        VARCHAR(255) NOT NULL,
    `categoria_id`      INT UNSIGNED NULL,
    `odd`              DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    `valor_apostado`   DECIMAL(12,2) NOT NULL,
    `status`           ENUM('pendente','vitoria','derrota','reembolso') NOT NULL DEFAULT 'pendente',
    `valor_fechamento` DECIMAL(12,2) NULL,
    `data_aposta`      DATE NOT NULL,
    `data_resultado`   DATE NULL,
    `observacao`       TEXT,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_ap_usuario`   (`usuario_id`),
    KEY `fk_ap_categoria` (`categoria_id`),
    KEY `idx_ap_data`     (`data_aposta`),
    KEY `idx_ap_status`   (`status`),
    CONSTRAINT `fk_ap_usuario`   FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`          (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ap_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `apostas_categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: apostas_selecoes (seleções de uma aposta múltipla)
-- ============================================================
CREATE TABLE IF NOT EXISTS `apostas_selecoes` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `aposta_id`    INT UNSIGNED NOT NULL,
    `descricao`    VARCHAR(255) NOT NULL,
    `categoria_id` INT UNSIGNED NULL,
    `odd`          DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_asel_aposta`    (`aposta_id`),
    KEY `fk_asel_categoria` (`categoria_id`),
    CONSTRAINT `fk_asel_aposta`    FOREIGN KEY (`aposta_id`)    REFERENCES `apostas`           (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_asel_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `apostas_categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: apostas_links_publicos (compartilhamento por link)
-- ============================================================
CREATE TABLE IF NOT EXISTS `apostas_links_publicos` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`  INT UNSIGNED NOT NULL,
    `token`       VARCHAR(64) NOT NULL,
    `ativo`       TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_alp_token` (`token`),
    KEY `fk_alp_usuario` (`usuario_id`),
    CONSTRAINT `fk_alp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
