-- JW Finanças Pessoais - Database Schema
-- MariaDB / MySQL
-- Encoding: UTF-8mb4

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET time_zone = '-03:00';

CREATE DATABASE IF NOT EXISTS `financas`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `financas`;

-- ============================================================
-- TABLE: planos
-- ============================================================
CREATE TABLE IF NOT EXISTS `planos` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`        VARCHAR(100) NOT NULL,
    `descricao`   TEXT,
    `preco`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `recursos`    JSON,
    `ativo`       TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`             VARCHAR(150) NOT NULL,
    `data_nascimento`  DATE,
    `telefone`         VARCHAR(20),
    `cpf`              VARCHAR(14) NOT NULL,
    `municipio`        VARCHAR(100),
    `uf`               CHAR(2),
    `email`            VARCHAR(200) NOT NULL,
    `senha`            VARCHAR(255) NOT NULL,
    `plano_id`         INT UNSIGNED NOT NULL DEFAULT 1,
    `ativo`            TINYINT(1) NOT NULL DEFAULT 1,
    `email_verificado` TINYINT(1) NOT NULL DEFAULT 0,
    `token_reset`      VARCHAR(100),
    `token_expira`     DATETIME,
    `dt_cadastro`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usuarios_cpf`   (`cpf`),
    UNIQUE KEY `uq_usuarios_email` (`email`),
    KEY `fk_usuarios_plano`        (`plano_id`),
    CONSTRAINT `fk_usuarios_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: categorias
-- ============================================================
CREATE TABLE IF NOT EXISTS `categorias` (
    `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`   VARCHAR(100) NOT NULL,
    `tipo`   ENUM('receita','despesa') NOT NULL,
    `icone`  VARCHAR(100) DEFAULT 'fa-circle',
    `cor`    VARCHAR(7)   DEFAULT '#6366f1',
    `ativo`  TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: subcategorias
-- ============================================================
CREATE TABLE IF NOT EXISTS `subcategorias` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `categoria_id` INT UNSIGNED NOT NULL,
    `nome`         VARCHAR(100) NOT NULL,
    `ativo`        TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_subcategorias_categoria` (`categoria_id`),
    CONSTRAINT `fk_subcategorias_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: movimentacoes
-- ============================================================
CREATE TABLE IF NOT EXISTS `movimentacoes` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`       INT UNSIGNED NOT NULL,
    `descricao`        VARCHAR(255) NOT NULL,
    `tipo`             ENUM('entrada','saida') NOT NULL,
    `modo`             ENUM('unico','fixo','parcelamento') NOT NULL DEFAULT 'unico',
    `categoria_id`     INT UNSIGNED,
    `subcategoria_id`  INT UNSIGNED,
    `valor`            DECIMAL(12,2) NOT NULL,
    `data_competencia` DATE NOT NULL,
    `data_vencimento`  DATE,
    `parcela_atual`    SMALLINT UNSIGNED DEFAULT 1,
    `total_parcelas`   SMALLINT UNSIGNED DEFAULT 1,
    `validado`         TINYINT(1) NOT NULL DEFAULT 0,
    `observacao`       TEXT,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_mov_usuario`       (`usuario_id`),
    KEY `fk_mov_categoria`     (`categoria_id`),
    KEY `fk_mov_subcategoria`  (`subcategoria_id`),
    KEY `idx_mov_data`         (`data_competencia`),
    CONSTRAINT `fk_mov_usuario`      FOREIGN KEY (`usuario_id`)      REFERENCES `usuarios`     (`id`),
    CONSTRAINT `fk_mov_categoria`    FOREIGN KEY (`categoria_id`)    REFERENCES `categorias`   (`id`),
    CONSTRAINT `fk_mov_subcategoria` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategorias`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: movimentacoes_fixas
-- ============================================================
CREATE TABLE IF NOT EXISTS `movimentacoes_fixas` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`      INT UNSIGNED NOT NULL,
    `descricao`       VARCHAR(255) NOT NULL,
    `tipo`            ENUM('entrada','saida') NOT NULL,
    `categoria_id`    INT UNSIGNED,
    `subcategoria_id` INT UNSIGNED,
    `valor`           DECIMAL(12,2) NOT NULL,
    `dia_vencimento`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `ativo`           TINYINT(1) NOT NULL DEFAULT 1,
    `observacao`      TEXT,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_mf_usuario`      (`usuario_id`),
    KEY `fk_mf_categoria`    (`categoria_id`),
    KEY `fk_mf_subcategoria` (`subcategoria_id`),
    CONSTRAINT `fk_mf_usuario`      FOREIGN KEY (`usuario_id`)      REFERENCES `usuarios`     (`id`),
    CONSTRAINT `fk_mf_categoria`    FOREIGN KEY (`categoria_id`)    REFERENCES `categorias`   (`id`),
    CONSTRAINT `fk_mf_subcategoria` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategorias`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: cartoes_credito
-- ============================================================
CREATE TABLE IF NOT EXISTS `cartoes_credito` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`      INT UNSIGNED NOT NULL,
    `nome_cartao`     VARCHAR(100) NOT NULL,
    `bandeira`        VARCHAR(50) DEFAULT 'visa',
    `limite`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `dia_fechamento`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `dia_vencimento`  TINYINT UNSIGNED NOT NULL DEFAULT 10,
    `cor`             VARCHAR(7) DEFAULT '#6366f1',
    `ativo`           TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_cc_usuario` (`usuario_id`),
    CONSTRAINT `fk_cc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: movimentacoes_cartao
-- ============================================================
CREATE TABLE IF NOT EXISTS `movimentacoes_cartao` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cartao_id`       INT UNSIGNED NOT NULL,
    `usuario_id`      INT UNSIGNED NOT NULL,
    `descricao`       VARCHAR(255) NOT NULL,
    `valor`           DECIMAL(12,2) NOT NULL,
    `data_compra`     DATE NOT NULL,
    `parcela_atual`   SMALLINT UNSIGNED DEFAULT 1,
    `total_parcelas`  SMALLINT UNSIGNED DEFAULT 1,
    `categoria_id`    INT UNSIGNED,
    `subcategoria_id` INT UNSIGNED,
    `data_fechamento` DATE,
    `pago`            TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_mc_cartao`       (`cartao_id`),
    KEY `fk_mc_usuario`      (`usuario_id`),
    KEY `fk_mc_categoria`    (`categoria_id`),
    KEY `fk_mc_subcategoria` (`subcategoria_id`),
    CONSTRAINT `fk_mc_cartao`       FOREIGN KEY (`cartao_id`)       REFERENCES `cartoes_credito` (`id`),
    CONSTRAINT `fk_mc_usuario`      FOREIGN KEY (`usuario_id`)      REFERENCES `usuarios`        (`id`),
    CONSTRAINT `fk_mc_categoria`    FOREIGN KEY (`categoria_id`)    REFERENCES `categorias`      (`id`),
    CONSTRAINT `fk_mc_subcategoria` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategorias`   (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: folha_pagamento
-- ============================================================
CREATE TABLE IF NOT EXISTS `folha_pagamento` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`     INT UNSIGNED NOT NULL,
    `descricao`      VARCHAR(255) NOT NULL,
    `valor_bruto`    DECIMAL(12,2) NOT NULL,
    `descontos`      JSON,
    `valor_liquido`  DECIMAL(12,2) NOT NULL,
    `mes_referencia` TINYINT UNSIGNED NOT NULL,
    `ano_referencia` YEAR NOT NULL,
    `data_pagamento` DATE,
    `observacao`     TEXT,
    `movimentacao_id` INT UNSIGNED NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_fp_usuario` (`usuario_id`),
    KEY `fk_fp_movimentacao` (`movimentacao_id`),
    CONSTRAINT `fk_fp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_fp_movimentacao` FOREIGN KEY (`movimentacao_id`) REFERENCES `movimentacoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: folha_itens
-- ============================================================
CREATE TABLE IF NOT EXISTS `folha_itens` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `folha_id`  INT UNSIGNED NOT NULL,
    `descricao` VARCHAR(255) NOT NULL,
    `tipo`      ENUM('provento','desconto') NOT NULL,
    `valor`     DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_fi_folha` (`folha_id`),
    CONSTRAINT `fk_fi_folha` FOREIGN KEY (`folha_id`) REFERENCES `folha_pagamento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: dividas_parceladas
-- ============================================================
CREATE TABLE IF NOT EXISTS `dividas_parceladas` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`       INT UNSIGNED NOT NULL,
    `descricao`        VARCHAR(255) NOT NULL,
    `valor_parcela`    DECIMAL(12,2) NOT NULL,
    `total_parcelas`   SMALLINT UNSIGNED NOT NULL,
    `parcelas_pagas`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `saldo_inicial`    DECIMAL(12,2) NOT NULL,
    `saldo_devedor`    DECIMAL(12,2) NOT NULL,
    `ativo`            TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_dp_usuario` (`usuario_id`),
    KEY `idx_dp_ativo` (`ativo`),
    CONSTRAINT `fk_dp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED: planos
-- ============================================================
INSERT INTO `planos` (`nome`, `descricao`, `preco`, `recursos`) VALUES
('Básico',      'Plano gratuito com funcionalidades essenciais',
 0.00,
 '{"movimentacoes": 100, "cartoes": 1, "relatorios": false, "exportacao": false}'),
('Premium',     'Plano completo para uso pessoal',
 19.90,
 '{"movimentacoes": -1, "cartoes": 5, "relatorios": true, "exportacao": true}'),
('Empresarial', 'Plano para pequenas empresas e autônomos',
 49.90,
 '{"movimentacoes": -1, "cartoes": -1, "relatorios": true, "exportacao": true, "multi_usuario": true}');

-- ============================================================
-- SEED: categorias (receitas)
-- ============================================================
INSERT INTO `categorias` (`nome`, `tipo`, `icone`, `cor`) VALUES
('Salário',               'receita', 'fa-money-bill-wave',  '#10b981'),
('Freelance / Autônomo',  'receita', 'fa-laptop-code',      '#06b6d4'),
('Investimentos',         'receita', 'fa-chart-line',       '#8b5cf6'),
('Rendimentos',           'receita', 'fa-piggy-bank',       '#f59e0b'),
('Benefícios',            'receita', 'fa-gift',             '#ec4899'),
('Outros (receita)',      'receita', 'fa-plus-circle',      '#6b7280');

-- ============================================================
-- SEED: categorias (despesas)
-- ============================================================
INSERT INTO `categorias` (`nome`, `tipo`, `icone`, `cor`) VALUES
('Alimentação',                  'despesa', 'fa-utensils',          '#ef4444'),
('Transporte',                   'despesa', 'fa-car',               '#f97316'),
('Moradia',                      'despesa', 'fa-home',              '#eab308'),
('Saúde',                        'despesa', 'fa-heartbeat',         '#ec4899'),
('Educação',                     'despesa', 'fa-graduation-cap',    '#3b82f6'),
('Lazer e Entretenimento',       'despesa', 'fa-film',              '#8b5cf6'),
('Vestuário',                    'despesa', 'fa-tshirt',            '#14b8a6'),
('Utilidades (água/luz/gás)',     'despesa', 'fa-bolt',              '#f59e0b'),
('Cartão de Crédito',             'despesa', 'fa-credit-card',       '#6366f1'),
('Financeiro (empréstimos)',      'despesa', 'fa-landmark',          '#64748b'),
('Dívidas parceladas',            'despesa', 'fa-hand-holding-dollar', '#0ea5e9'),
('Comunicação',                  'despesa', 'fa-mobile-alt',        '#06b6d4'),
('Pets',                         'despesa', 'fa-paw',               '#84cc16'),
('Viagens',                      'despesa', 'fa-plane',             '#0ea5e9'),
('Impostos',                     'despesa', 'fa-file-invoice',      '#dc2626'),
('Outros (despesa)',              'despesa', 'fa-minus-circle',      '#6b7280');

-- ============================================================
-- SEED: subcategorias (receitas - id 1..6)
-- ============================================================
-- Salário (1)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(1, 'Salário CLT'),
(1, 'Salário PJ'),
(1, 'Adiantamento'),
(1, '13º Salário'),
(1, 'Férias'),
(1, 'PLR / Bônus');

-- Freelance / Autônomo (2)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(2, 'Consultoria'),
(2, 'Projeto Pontual'),
(2, 'Comissão'),
(2, 'Venda de Serviço');

-- Investimentos (3)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(3, 'Ações'),
(3, 'Fundos Imobiliários'),
(3, 'CDB / LCI / LCA'),
(3, 'Tesouro Direto'),
(3, 'Criptomoedas'),
(3, 'Dividendos');

-- Rendimentos (4)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(4, 'Aluguel Recebido'),
(4, 'Juros de Aplicação'),
(4, 'Cashback');

-- Benefícios (5)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(5, 'Vale Refeição'),
(5, 'Vale Alimentação'),
(5, 'Auxílio Home Office'),
(5, 'Plano de Saúde Empresa'),
(5, 'Bolsa Educação');

-- Outros receita (6)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(6, 'Presente / Doação'),
(6, 'Reembolso'),
(6, 'Venda de Bem'),
(6, 'Herança');

-- ============================================================
-- SEED: subcategorias (despesas - id 7..20)
-- ============================================================
-- Alimentação (7)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(7, 'Supermercado'),
(7, 'Restaurante / Delivery'),
(7, 'Padaria / Café'),
(7, 'Feira / Hortifruti'),
(7, 'Fast Food'),
(7, 'Bebidas');

-- Transporte (8)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(8, 'Combustível'),
(8, 'Transporte Público'),
(8, 'Aplicativo (Uber/99)'),
(8, 'Manutenção Veículo'),
(8, 'Estacionamento'),
(8, 'Pedágio'),
(8, 'IPVA'),
(8, 'Seguro Veículo');

-- Moradia (9)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(9, 'Aluguel'),
(9, 'Condomínio'),
(9, 'IPTU'),
(9, 'Manutenção / Reforma'),
(9, 'Limpeza / Zeladoria'),
(9, 'Mobília / Decoração');

-- Saúde (10)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(10, 'Plano de Saúde'),
(10, 'Consulta Médica'),
(10, 'Exames'),
(10, 'Medicamentos'),
(10, 'Dentista'),
(10, 'Psicólogo / Terapia'),
(10, 'Academia / Esportes');

-- Educação (11)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(11, 'Mensalidade Escolar'),
(11, 'Cursos Online'),
(11, 'Livros / Material'),
(11, 'Faculdade / Pós'),
(11, 'Idiomas');

-- Lazer (12)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(12, 'Cinema / Teatro'),
(12, 'Streaming (Netflix etc.)'),
(12, 'Jogos'),
(12, 'Bares / Baladas'),
(12, 'Passeios / Parques'),
(12, 'Hobbies');

-- Vestuário (13)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(13, 'Roupas'),
(13, 'Calçados'),
(13, 'Acessórios'),
(13, 'Uniforme');

-- Utilidades (14)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(14, 'Energia Elétrica'),
(14, 'Água / Esgoto'),
(14, 'Gás'),
(14, 'Internet'),
(14, 'TV a Cabo');

-- Financeiro (15)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(15, 'Empréstimo Pessoal'),
(15, 'Financiamento Imóvel'),
(15, 'Financiamento Veículo'),
(15, 'Juros / Multas'),
(15, 'Tarifa Bancária');

-- Comunicação (16)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(16, 'Celular / Plano'),
(16, 'Telefone Fixo'),
(16, 'Correios / Envios');

-- Pets (17)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(17, 'Ração'),
(17, 'Veterinário'),
(17, 'Banho e Tosa'),
(17, 'Pet Shop');

-- Viagens (18)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(18, 'Passagens Aéreas'),
(18, 'Hospedagem'),
(18, 'Passeios Turísticos'),
(18, 'Alimentação Viagem');

-- Impostos (19)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(19, 'Imposto de Renda'),
(19, 'INSS'),
(19, 'IPTU'),
(19, 'Contribuição Sindical'),
(19, 'Outros Tributos');

-- Outros despesa (20)
INSERT INTO `subcategorias` (`categoria_id`, `nome`) VALUES
(20, 'Presente / Doação'),
(20, 'Assinatura Diversa'),
(20, 'Despesa Imprevista'),
(20, 'Multas');

-- ============================================================
-- SEED: test user (CPF: 000.000.000-00 / senha: Admin@123)
-- bcrypt hash of "Admin@123"
-- ============================================================
INSERT INTO `usuarios`
    (`nome`, `cpf`, `email`, `senha`, `plano_id`, `ativo`, `email_verificado`)
VALUES
    ('Admin Teste',
     '000.000.000-00',
     'admin@financas.local',
     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     2,
     1,
     1);
