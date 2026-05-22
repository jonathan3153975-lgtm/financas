# Credenciais de Teste — JW Finanças Pessoais

> Documento para uso local em desenvolvimento.  
> **Não commitar em repositório público.**  
> Criado em: 22/05/2026

---

## Usuário de Teste (Banco de Dados)

Registrado via seed no `database/schema.sql` (linha 457).

| Campo            | Valor                  |
|-----------------|------------------------|
| **Nome**        | Admin Teste            |
| **CPF (login)** | `000.000.000-00`       |
| **E-mail**      | admin@financas.local   |
| **Senha**       | `Admin@123`            |
| **Plano**       | Premium (plano_id = 2) |
| **Ativo**       | Sim                    |
| **E-mail verificado** | Sim            |

> Hash bcrypt (custo 12) armazenado no banco:  
> `$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`

---

## Acesso à Aplicação

| Item        | Valor                              |
|------------|-------------------------------------|
| **URL**    | `http://localhost/financas/public/` |
| **Login**  | Campo CPF: `000.000.000-00`         |
| **Senha**  | `Admin@123`                         |

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

| ID | Nome         | Preço    | Cartões | Relatórios | Exportação |
|----|-------------|----------|---------|-----------|-----------|
| 1  | Básico      | Grátis   | 1       | Não       | Não       |
| 2  | Premium     | R$ 19,90 | 5       | Sim       | Sim       |
| 3  | Empresarial | R$ 49,90 | Ilimitado | Sim    | Sim       |

O usuário de teste está no plano **Premium** e tem acesso a todos os módulos implementados.

---

## Como Recriar o Usuário de Teste

Caso o banco seja recriado do zero, o seed está incluído no próprio schema:

```bash
mysql -u root -p financas < database/schema.sql
```

Ou inserir manualmente:

```sql
INSERT INTO `usuarios` (`nome`, `cpf`, `email`, `senha`, `plano_id`, `ativo`, `email_verificado`)
VALUES (
    'Admin Teste',
    '000.000.000-00',
    'admin@financas.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    2, 1, 1
);
```

---

## Aviso de Segurança

- O arquivo `.env` com a senha do banco está atualmente **commitado no repositório**. Avaliar adicionar ao `.gitignore` antes de tornar o repositório público.
- As credenciais acima são exclusivas para ambiente de desenvolvimento local.
- Em produção, substituir todos os valores por credenciais seguras e únicas.
