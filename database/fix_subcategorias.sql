-- Corrige o vínculo categoria_id nas subcategorias
-- Problema: seed do schema usou IDs 15-20 sem considerar que
-- "Cartão de Crédito" (15) e "Dívidas parceladas" (17) deslocaram tudo.

-- Financeiro (empréstimos) → id real 16, estava em 15
UPDATE subcategorias SET categoria_id = 16 WHERE id IN (76,77,78,79,80);

-- Comunicação → id real 18, estava em 16
UPDATE subcategorias SET categoria_id = 18 WHERE id IN (81,82,83);

-- Pets → id real 19, estava em 17
UPDATE subcategorias SET categoria_id = 19 WHERE id IN (84,85,86,87);

-- Viagens → id real 20, estava em 18
UPDATE subcategorias SET categoria_id = 20 WHERE id IN (88,89,90,91);

-- Impostos → id real 21, estava em 19
UPDATE subcategorias SET categoria_id = 21 WHERE id IN (92,93,94,95,96);

-- Outros (despesa) → id real 22, estava em 20
UPDATE subcategorias SET categoria_id = 22 WHERE id IN (97,98,99,100);
