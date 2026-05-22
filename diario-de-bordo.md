# Diário de Bordo — JW Finanças Pessoais

> Documento de acompanhamento da evolução do sistema.  
> Criado em: 22/05/2026 | Última atualização: 22/05/2026

---

## Visão Geral do Projeto

**Nome:** JW Finanças Pessoais  
**Tipo:** Aplicação web de gestão financeira pessoal  
**Stack principal:** PHP 8.0+ · MySQL · Vanilla JS · Chart.js  
**Arquitetura:** MVC customizado com roteamento próprio  
**Público-alvo:** Usuários brasileiros — controle de finanças pessoais  
**Pacote Composer:** `jw/financas-pessoais`

---

## Estado Atual do Sistema — 22/05/2026

### Resumo Executivo

O sistema está **funcionalmente operacional** no seu núcleo principal. As funcionalidades de autenticação, movimentações, cartões de crédito, folha de pagamento, dívidas parceladas, dashboard e relatórios estão implementadas e conectadas ao banco de dados. Existe um módulo em desenvolvimento (Poupança) que possui modelos criados mas sem views e controller completos. Não há testes automatizados.

---

## Módulos e Status

### ✅ Completos e Funcionais

| Módulo | Arquivos Principais | Observações |
|--------|--------------------|-|
| **Autenticação** | `AuthController.php`, `User.php` | Login CPF+senha, bcrypt, recuperação por e-mail, CSRF |
| **Movimentações** | `MovementController.php`, `Movement.php` | CRUD completo, modos: avulso / fixo / parcelado |
| **Movimentações Fixas** | Integrado ao Movement | Geração automática por mês, ativar/desativar |
| **Cartões de Crédito** | `CardController.php`, `CreditCard.php`, `CardMovement.php` | Até 5 cartões (Premium), fatura, parcelas |
| **Folha de Pagamento** | `PayrollController.php`, `Payroll.php` | Proventos e descontos em JSON, gera movimentação automática |
| **Dívidas Parceladas** | `InstallmentDebtController.php`, `InstallmentDebt.php` | Rastreamento de parcelas, série de redução 8 meses |
| **Dashboard** | `DashboardController.php` | 6 visualizações: saldo, tendência 6 meses, categorias, fluxo diário |
| **Relatórios** | `ReportController.php` | Comparativo 12 meses, categorias, fluxo diário, exportação CSV |
| **Perfil do Usuário** | `UserController.php` | Atualização de dados e senha |
| **Layout / UI** | `public/css/style.css` (2.088 linhas) | Sidebar responsiva, dark sidebar + indigo accent, Chart.js |

---

### ⚠️ Parcialmente Implementado

| Módulo | Status | O que falta |
|--------|--------|-------------|
| **Poupança** | Modelos criados (`Savings.php`, `SavingsMovement.php`, `SavingsController.php` com 345 linhas) | Views ausentes, rotas incompletas, lógica de metas não finalizada |

---

### ❌ Não Implementado / Previsto

| Funcionalidade | Prioridade Sugerida | Notas |
|----------------|--------------------|-|
| Módulo de Poupança (completo) | Alta | Base já criada, precisa das views e lógica |
| Modo escuro (dark mode) | Média | UI apenas em tema claro |
| Notificações in-app | Média | Hoje apenas e-mail para reset de senha |
| Importação de dados (CSV/OFX) | Média | Só exportação está disponível |
| Painel administrativo | Baixa | Sem gestão de planos/categorias via UI |
| Autenticação dois fatores (2FA) | Baixa | Sem 2FA |
| Testes automatizados | Alta | Zero cobertura de testes |
| Framework de migrações de banco | Média | Schema manual via `schema.sql` |
| API REST autenticada | Baixa | Apenas endpoint AJAX de subcategorias |
| Multi-empresa (Plano Empresarial) | Baixa | Mencionado nos planos, não construído |

---

## Estrutura de Arquivos

```
financas/
├── app/
│   ├── Controllers/         # 8 controllers — ~1.882 linhas
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── MovementController.php    # maior: ~447 linhas
│   │   ├── CardController.php
│   │   ├── PayrollController.php
│   │   ├── InstallmentDebtController.php
│   │   ├── ReportController.php
│   │   ├── SavingsController.php     # incompleto
│   │   └── UserController.php
│   ├── Core/                # Framework MVC próprio
│   │   ├── Database.php     # Singleton PDO
│   │   ├── Router.php       # Roteamento com regex
│   │   ├── Controller.php   # Base
│   │   ├── Model.php        # Base
│   │   ├── Validator.php
│   │   └── Mail.php         # PHPMailer wrapper
│   ├── Models/              # 11 modelos — ~1.559 linhas
│   └── Views/               # 18+ templates organizados por feature
│       ├── layouts/         # main.php (autenticado), auth.php (login)
│       ├── auth/            # login, forgot_password, reset_password
│       ├── dashboard/
│       ├── movements/       # index, form, fixed, edit_page
│       ├── cards/           # index, movements (fatura)
│       ├── payroll/
│       ├── debts/
│       ├── reports/
│       ├── user/
│       └── errors/          # 404.php
├── public/
│   ├── index.php            # Front controller
│   ├── css/style.css        # 2.088 linhas
│   └── js/
│       ├── app.js           # Sidebar, modais, flash messages
│       └── masks.js         # Máscaras CPF, moeda, telefone
├── database/
│   └── schema.sql           # 11 tabelas + dados seed (467 linhas)
├── routes/
│   └── web.php              # 37 rotas definidas
├── vendor/                  # Dependências Composer
├── index.php                # Bootstrap
├── composer.json
├── .env.example
└── .htaccess
```

---

## Banco de Dados

**11 tabelas implementadas:**

| Tabela | Finalidade |
|--------|-----------|
| `usuarios` | Usuários com CPF, e-mail, plano, senha bcrypt |
| `planos` | Planos de assinatura (Básico, Premium R$19,90, Empresarial R$49,90) |
| `categorias` | 20+ categorias pré-definidas com ícone e cor |
| `subcategorias` | 6 entrada + 14 despesa |
| `movimentacoes` | Transações principais (avulso/fixo/parcelado) |
| `movimentacoes_fixas` | Templates de movimentação recorrente |
| `cartoes_credito` | Definição dos cartões por usuário |
| `movimentacoes_cartao` | Compras no cartão com rastreamento de parcelas |
| `folha_pagamento` | Contracheques com JSON de descontos |
| `folha_itens` | Itens da folha (proventos e descontos) |
| `dividas_parceladas` | Dívidas com controle de parcelas |

**Não existe tabela para:**
- `poupancas` / `poupancas_movimentacoes` — modelos PHP existem, banco ainda não tem as tabelas mapeadas no schema atual (verificar)

---

## Roteamento

**37 rotas em 6 áreas:**

| Área | Rotas | Status |
|------|-------|--------|
| Auth (público) | `/login`, `/logout`, `/forgot-password`, `/reset-password` | ✅ |
| Dashboard | `/dashboard` (com params mês/ano) | ✅ |
| Movimentações | `/movimentacoes/*` (CRUD, fixas, pagamentos) | ✅ |
| Cartões | `/cartoes/*` (CRUD, movimentos, faturas) | ✅ |
| Folha de Pagamento | `/folha-pagamento/*` (CRUD) | ✅ |
| Dívidas Parceladas | `/dividas-parceladas/*` (CRUD) | ✅ |
| Relatórios | `/relatorios/*` (views, analytics, exportação) | ✅ |
| Perfil | `/perfil` | ✅ |
| API AJAX | `/api/subcategorias/{categoryId}` | ✅ |
| Poupança | — | ❌ ausente |

---

## Dependências Externas

| Biblioteca | Versão | Uso |
|-----------|--------|-----|
| PHPMailer | 6.8 | Envio de e-mail (reset de senha) |
| vlucas/phpdotenv | 5.5 | Variáveis de ambiente |
| Chart.js | 4.4.0 | Gráficos no dashboard e relatórios |
| SweetAlert2 | latest CDN | Diálogos de confirmação |
| Font Awesome | 6.5.0 | Ícones |
| Inter (Google Fonts) | — | Tipografia |

---

## Qualidade de Código

**Pontos positivos:**
- `declare(strict_types=1)` em todos os arquivos PHP
- Namespaces organizados (`App\Controllers`, `App\Models`, etc.)
- Type hints nas assinaturas de métodos
- PDO com prepared statements (sem SQL injection)
- Proteção CSRF nos formulários
- Senhas com bcrypt (custo 12)
- HTML5 semântico
- Layout responsivo com Flexbox/Grid

**Pontos de atenção:**
- Nenhum teste automatizado (PHPUnit não instalado)
- `MovementController.php` com ~447 linhas — candidato a refatoração
- Sem framework de migrações (schema manual)
- Tratamento de erros básico
- Sem rate limiting nas rotas
- Comentários no código são escassos

---

## Histórico de Commits

| Hash | Mensagem | Observação |
|------|----------|-----------|
| `0ba4078` | Ajustes do painel | Mais recente |
| `1896620` | ajustes no projeto | |
| `6dddec4` | inicial | |
| `bfebdaa` | Create outro | |
| `6a50d62` | Create index.php | Primeiro commit |

Histórico enxuto — projeto em fase inicial de desenvolvimento ativo.

---

## Próximos Passos Recomendados

### Curto prazo (sprint atual)
- [ ] Finalizar módulo de **Poupança** — criar views, completar controller e verificar se tabelas do banco estão no schema
- [ ] Adicionar rotas da Poupança em `routes/web.php`

### Médio prazo
- [ ] Implementar **testes automatizados** (PHPUnit) — pelo menos para controllers e models principais
- [ ] Criar **framework de migrações** simples (ou adotar Phinx) para versionar o schema
- [ ] Implementar **modo escuro** (variáveis CSS já facilitam)
- [ ] Adicionar **importação de dados** (CSV ou OFX bancário)

### Longo prazo
- [ ] Painel administrativo para gerenciar planos e categorias
- [ ] Autenticação 2FA
- [ ] Notificações in-app (alertas de vencimento, limites)
- [ ] API REST completa e autenticada para possível app mobile

---

## Atualizações do Diário

> Use esta seção para registrar cada sessão de trabalho.

### 22/05/2026 — Criação do documento
- Análise completa do sistema realizada
- Identificados 11 módulos: 10 implementados, 1 parcial (Poupança)
- Sistema operacional com autenticação, movimentações, cartões, folha, dívidas, dashboard e relatórios
- Zero testes automatizados — ponto crítico a endereçar
- Próximo foco: finalizar módulo de Poupança

---

*Documento mantido manualmente. Atualizar a seção "Atualizações do Diário" a cada sessão de trabalho significativa.*
