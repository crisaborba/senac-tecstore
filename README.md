# TecStore

E-commerce acadêmico de produtos eletrônicos desenvolvido com PHP (PDO), MySQL/MariaDB,
Bootstrap 5, HTML5, CSS3 e JavaScript. Funciona localmente no XAMPP.

## Funcionalidades
- Catálogo com busca por nome e filtro por categoria
- Detalhes do produto e carrinho (sessão PHP) com controle de estoque
- Cadastro e login (`password_hash` / `password_verify`)
- Checkout simulado (PIX, boleto ou cartão) com transação PDO e baixa de estoque
- Histórico de pedidos do cliente
- Painel administrativo: produtos (CRUD, upload de imagem, ativar/desativar) e pedidos

## Como rodar
1. Copie a pasta `tecstore` para `C:\xampp\htdocs\`.
2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. No phpMyAdmin (`http://localhost/phpmyadmin`), importe `database/schema.sql`.
4. Copie `.env.example` para `.env` e preencha (padrão do XAMPP):
   ```
   DB_HOST=localhost
   DB_NAME=tecstore
   DB_USER=root
   DB_PASS=
   ```
5. Acesse `http://localhost/tecstore/`.

## Acesso de demonstração (administrador)
- E-mail: `admin@tecstore.com`
- Senha: `admin123`

Novos cadastros recebem automaticamente o perfil de cliente.

## Estrutura
```
admin/      painel administrativo
assets/     css, js e imagens do site
config/     conexão PDO (lê o .env)
database/   schema.sql
includes/   header, footer, autenticação e funções
uploads/    imagens enviadas pelo painel
```

## Segurança aplicada
- PDO com prepared statements em todas as consultas com dados variáveis
- Senhas com `password_hash()`; mensagem única de erro no login e limite de tentativas
- `htmlspecialchars()` em todas as saídas; validação no servidor
- Token CSRF em todos os formulários que alteram dados
- Sessão com cookie `HttpOnly` + `SameSite` e renovação do ID no login
- Páginas admin restritas ao perfil `admin`; cliente só vê os próprios pedidos
- Estoque protegido por transação (`FOR UPDATE`) e condição no `UPDATE`
- Upload validado pelo conteúdo real (JPG/PNG/WEBP, até 2 MB), nome aleatório, scripts bloqueados em `uploads/`
- `.htaccess` bloqueia `.env`, `config/`, `includes/` e `database/`
- Erros de banco vão para o log do Apache, sem expor detalhes ao visitante
