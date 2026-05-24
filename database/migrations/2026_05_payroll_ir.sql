-- Adiciona colunas de IR e cálculo base×quantidade em folha_itens

ALTER TABLE `folha_itens`
    ADD COLUMN IF NOT EXISTS `compoe_ir`   TINYINT(1)    NOT NULL DEFAULT 1
        COMMENT '1 = inclui no cálculo da base de IR; 0 = não inclui',
    ADD COLUMN IF NOT EXISTS `valor_base`  DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Valor unitário base (ex: valor da hora extra)',
    ADD COLUMN IF NOT EXISTS `quantidade`  DECIMAL(8,2)  DEFAULT NULL
        COMMENT 'Quantidade (ex: número de horas extras); valor = valor_base × quantidade';
