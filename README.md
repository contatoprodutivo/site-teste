# Inscrição — Curso de Power BI

Aplicação PHP/MySQL para testar o fluxo completo de um formulário publicado em uma VPS Hostinger com Webuzo.

## Instalação

1. Crie o banco e execute `database.sql` no phpMyAdmin.
2. Copie `config.example.php` como `config.php` na VPS.
3. Preencha em `config.php` o banco, usuário e senha.
4. Envie os arquivos para a pasta pública do domínio ou subdomínio.
5. Garanta PHP 8.1+ com as extensões `pdo_mysql` e `mbstring`.

`config.php` está no `.gitignore` e nunca deve ser enviado ao GitHub.
