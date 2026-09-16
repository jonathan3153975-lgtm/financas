-- ============================================================
-- MIGRATION: Categorias extras para o módulo de Apostas
-- Execute apenas se a migration 2026_09_gerenciamento_apostas.sql já foi aplicada.
-- ============================================================

USE `financas`;

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Tênis de Mesa' AS nome, 'fa-table-tennis-paddle-ball' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Tênis de Mesa');

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Vôlei de Praia' AS nome, 'fa-volleyball' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Vôlei de Praia');

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Boxe' AS nome, 'fa-hand-back-fist' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Boxe');

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Fórmula 1' AS nome, 'fa-flag-checkered' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Fórmula 1');

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Rugby' AS nome, 'fa-football' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Rugby');

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Handebol' AS nome, 'fa-hand-dots' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Handebol');

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Golfe' AS nome, 'fa-golf-ball-tee' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Golfe');

INSERT INTO `apostas_categorias` (`nome`, `icone`)
SELECT * FROM (SELECT 'Sinuca' AS nome, 'fa-circle-dot' AS icone) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `apostas_categorias` WHERE `nome` = 'Sinuca');
