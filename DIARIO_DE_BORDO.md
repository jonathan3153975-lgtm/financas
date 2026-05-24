# Diário de Bordo — JW Finanças Pessoais

## Última sessão de trabalho

**Data:** 2026-05-24

---

## Estado atual do projeto

### Módulos e controllers existentes

| Controller | View | Status |
|---|---|---|
| AuthController | auth/login, forgot_password, reset_password | Concluído |
| DashboardController | dashboard/index | Concluído |
| MovementController | movements/index, form, fixed, edit_page | Concluído |
| CardController | cards/index, cards/movements | Concluído |
| PayrollController | payroll/index | Em evolução |
| InstallmentDebtController | debts/index | Concluído |
| ReportController | reports/index | Básico |
| UserController | user/profile | Concluído |
| SavingsController | (sem view própria) | A verificar |

### Banco de dados — tabelas

- `usuarios`
- `categorias` / `subcategorias`
- `movimentacoes`
- `movimentacoes_fixas`
- `movimentacoes_cartao`
- `cartoes_credito`
- `dividas_parceladas`
- `folha_pagamento` / `folha_itens`
- `planos`

### Migrations aplicadas

- `2026_05_debt_savings.sql` — colunas `total_economia` e `total_juros` em `dividas_parceladas`
- `2026_05_payroll_ir.sql` — colunas `compoe_ir`, `valor_base`, `quantidade` em `folha_itens`

---

## O que foi desenvolvido (histórico recente)

### Movimentações (`movements/index.php`)

- Barra fixa de rodapé com botões de ação:
  - Desktop: Exportar CSV, Lançar Dívida, Nova Movimentação lado a lado
  - Mobile: agrupados em botão "Ferramentas" com dropdown (posicionado via `getBoundingClientRect`)
- Valores do rodapé (Entradas, Saídas, Saldo do Mês) refletem os filtros ativos em tempo real
- Remoção do bloco `page-header` para tela mais limpa
- Edição de movimentação via modal AJAX (sem sair da página, filtros preservados)
  - Detecta movimentações vinculadas à folha de pagamento (`[FOLHA_ID:X]` na observação) e redireciona para edição da folha em vez de abrir o modal genérico
- Paginação desktop com elipses (máx. 7 botões)
- Scroll infinito no mobile via `IntersectionObserver`
- Filtro mobile: sempre visível no topo; quando há filtro ativo, exibe chips com botão de redefinir

### Dívidas Parceladas (`debts/index.php`)

- Rastreamento de economia (pagamento antecipado com desconto) e juros (pagamento com acréscimo)
- Cálculo automático ao validar ou reverter movimentação vinculada à dívida
- Painel de resumo com 4 cards (incluindo economia líquida)
- Exibição por dívida quando os valores são não-zero

### Folha de Pagamento (`payroll/index.php`)

- Itens da folha com campos:
  - Descrição, Tipo (Provento / Desconto)
  - Checkbox **Compõe IR** — determina se o item entra na base de cálculo do IRRF
  - **Base unitária × Quantidade = Valor** (cálculo automático, útil para hora extra etc.)
- Linha de **Imposto de Renda gerada automaticamente** quando a base tributável supera R$ 2.259,20
  - Tabela progressiva IRRF 2025 (7,5% / 15% / 22,5% / 27,5%)
  - Valor editável pelo usuário
- Painel de resumo com base IR, alíquota aplicada e valor calculado
- Ao salvar, cria movimentação de entrada como preview (não validada) com marcador `[FOLHA_ID:X]` na observação
- Novos itens são inseridos no topo da lista
- Arrastar para reordenar itens (HTML5 Drag API com handle de grip)
- Layout em dois blocos por item:
  - Linha 1: Descrição | Tipo | Compõe IR | Excluir
  - Linha 2: Base unitária × Quantidade = Valor

---

## Pendente / A desenvolver

### Alta prioridade

- [ ] **SavingsController** — verificar se tem view ou funcionalidade incompleta
- [ ] **Folha de Pagamento — edição de itens ao reabrir modal** — garantir que `compoe_ir`, `valor_base` e `quantidade` são restaurados corretamente na edição
- [ ] **Folha de Pagamento — preview de movimento recorrente** — avaliar se deve gerar prévia automaticamente para meses futuros (atualmente gera apenas para o mês de referência)
- [ ] **Relatórios** — a view `reports/index.php` está básica; avaliar expansão com gráficos por categoria, comparativo mensal e exportação de folha de pagamento

### Média prioridade

- [ ] **Dashboard** — integrar card de folha de pagamento (próximo salário previsto, último registrado)
- [ ] **Cartões de crédito** — verificar integração com o painel do dashboard e se a fatura fechada aparece corretamente nas movimentações
- [ ] **Movimentações fixas** — revisar fluxo de geração automática (`FixedMovement::generateForMonth`) e feedback visual quando itens são gerados
- [ ] **Planos** — tabela `planos` existe no banco mas não há controller/view mapeado nas rotas

### Baixa prioridade / Melhorias

- [ ] **Mobile — folha de pagamento** — testar layout dos itens (dois blocos) em telas pequenas
- [ ] **Acessibilidade** — revisar contraste, labels e navegação por teclado nos modais
- [ ] **Tratamento de erro** — padronizar mensagens de erro de validação server-side nos formulários
- [ ] **Testes** — não há suite de testes automatizados; considerar ao menos testes de integração para os models críticos (Movement, InstallmentDebt, Payroll)

---

## Notas técnicas

- **Stack:** PHP 8.0+ (MVC próprio, sem framework), MySQL/MariaDB, PDO
- **Autenticação:** sessão PHP + CSRF em todos os formulários POST
- **XAMPP local:** `C:\xampp\mysql\bin\mysql.exe` | DB: `financas` | User: `root`
- **Breakpoint mobile:** 900px
- **Sidebar fixa:** `260px` (variável CSS `--sidebar-width`)
- **Padrão de marcadores na observação de movimentações:**
  - `[PREVIEW_FOLHA]` — prévia de salário gerada automaticamente
  - `[FOLHA_ID:X]` — movimentação vinculada à folha de pagamento ID X
  - `[DIVIDA_ID:X][PARCELA:Y]` — movimentação vinculada a dívida parcelada
- **IR** — tabela IRRF 2025 implementada em `Payroll::calculateIR()` e espelhada no JS da view
