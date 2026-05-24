-- Limpeza de dados transacionais
-- Mantém: usuarios, planos, categorias, subcategorias
-- Remove: todos os registros gerados pelo usuário

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `folha_itens`;
DELETE FROM `folha_pagamento`;
DELETE FROM `movimentacoes_cartao`;
DELETE FROM `cartoes_credito`;
DELETE FROM `movimentacoes`;
DELETE FROM `movimentacoes_fixas`;
DELETE FROM `dividas_parceladas`;

SET FOREIGN_KEY_CHECKS = 1;
