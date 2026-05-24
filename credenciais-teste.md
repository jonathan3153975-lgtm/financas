# Credenciais de Teste — JW Finanças Pessoais

> Documento para uso local em desenvolvimento.  
> **Não commitar em repositório público.**  
> Criado em: 22/05/2026 | Atualizado em: 22/05/2026

---

## Usuários Ativos no Banco (estado atual)

### Usuário 1 — Admin Teste (ID 1)

| Campo            | Valor                  |
|-----------------|------------------------|
| **Nome**        | Admin Teste            |
| **CPF (login)** | `019.744.290-08`       |
| **E-mail**      | admin@financas.local   |
| **Senha**       | `Admin@123` *(senha do seed — confirmar se CPF foi atualizado manualmente)* |
| **Plano**       | Premium (plano_id = 2) |
| **Ativo**       | Sim                    |

> **Obs.:** O CPF no banco (`019.744.290-08`) difere do seed original do `schema.sql` (`000.000.000-00`).  
> O CPF foi alterado manualmente após o seed inicial. A senha permanece a mesma enquanto não for trocada via sistema.

---

### Usuário 2 — Novo Usuário Teste (ID 2)

| Campo            | Valor                        |
|-----------------|------------------------------|
| **Nome**        | Novo Usuário Teste           |
| **CPF (login)** | `529.982.247-25`             |
| **E-mail**      | novo.usuario@financas.local  |
| **Senha**       | *(criada via sistema — não registrada aqui)* |
| **Plano**       | Premium (plano_id = 2)       |
| **Ativo**       | Sim                          |

---

## Acesso à Aplicação

| Item        | Valor                              |
|------------|-------------------------------------|
| **URL**    | `http://localhost/financas/public/` |
| **Login**  | Campo CPF (ver tabela acima)        |

> A URL exata depende de como o servidor local está configurado.  
> Se usar o `APP_BASE_PATH` vazio (auto-detecção), ajustar conforme o ambiente.

---

## Banco de Dados (Ambiente Local)

Configuração atual do arquivo `.env`:

| Parâmetro      | Valor       |
|---------------|-------------|
| **Host**      | localhost   |
| **Porta**     | 3306        |
| **Database**  | financas    |
| **Usuário**   | root        |
| **Senha**     | `YSara6a9u` |
| **Charset**   | utf8mb4     |

---

## Planos Disponíveis (Seed)

| ID | Nome         | Preço    | Cartões   | Relatórios | Exportação |
|----|-------------|----------|-----------|-----------|-----------|
| 1  | Básico      | Grátis   | 1         | Não       | Não       |
| 2  | Premium     | R$ 19,90 | 5         | Sim       | Sim       |
| 3  | Empresarial | R$ 49,90 | Ilimitado | Sim       | Sim       |

Ambos os usuários estão no plano **Premium**.

---

## Limpeza de Dados

Script disponível em `database/cleanup.sql`. Apaga todos os dados transacionais mantendo usuários, planos, categorias e subcategorias:

```bash
# Via XAMPP
C:\xampp\mysql\bin\mysql.exe -u root -p financas < database/cleanup.sql

# Via MariaDB nativo
"C:\Program Files\MariaDB 12.0\bin\mysql.exe" -u root -p financas < database/cleanup.sql
```

Tabelas limpas pelo script: `movimentacoes`, `movimentacoes_fixas`, `cartoes_credito`, `movimentacoes_cartao`, `folha_pagamento`, `folha_itens`, `dividas_parceladas`.

---

## Como Recriar o Schema do Zero

```bash
C:\xampp\mysql\bin\mysql.exe -u root -p financas < database/schema.sql
```

> Recria todas as tabelas e insere o seed padrão (Admin Teste com CPF `000.000.000-00` / senha `Admin@123`).

---

## Aviso de Segurança

- O arquivo `.env` com a senha do banco está atualmente **commitado no repositório**. Adicionar ao `.gitignore` antes de tornar o repositório público.
- As credenciais acima são exclusivas para ambiente de desenvolvimento local.
- Em produção, substituir todos os valores por credenciais seguras e únicas.
