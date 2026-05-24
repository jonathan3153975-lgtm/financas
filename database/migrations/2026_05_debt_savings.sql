-- Adiciona colunas de economia e juros em dividas_parceladas
-- Executa apenas se as colunas ainda não existirem

ALTER TABLE `dividas_parceladas`
    ADD COLUMN IF NOT EXISTS `total_economia` DECIMAL(12,2) NOT NULL DEFAULT 0
        COMMENT 'Soma de descontos obtidos em pagamentos antecipados',
    ADD COLUMN IF NOT EXISTS `total_juros`    DECIMAL(12,2) NOT NULL DEFAULT 0
        COMMENT 'Soma de juros pagos em pagamentos em atraso ou com acréscimo';
