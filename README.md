# Inscrição — Curso de Power BI

Aplicação PHP/MySQL para testar o fluxo completo de um formulário publicado em uma VPS Hostinger com Webuzo.

## Instalação

1. Crie o banco e execute `database.sql` no phpMyAdmin.
2. Copie `config.example.php` como `config.php` na VPS.
3. Preencha em `config.php` o banco, usuário e senha.
4. Envie os arquivos para a pasta pública do domínio ou subdomínio.
5. Garanta PHP 8.1+ com a extensão `pdo_mysql`.

## Teste de conexão

Depois de criar `config.php`, abra `teste_conexao.php` pelo navegador. O resultado esperado é:

```text
CONEXÃO COM O BANCO: OK
TABELA inscricoes_curso: OK
```

Apague `teste_conexao.php` da VPS após concluir o diagnóstico.

`config.php` está no `.gitignore` e nunca deve ser enviado ao GitHub.

## Importação em lote

1. Defina `adminUser` e `adminPass` no `config.php` da VPS.
2. Abra `importacao.php` e informe as credenciais administrativas.
3. Baixe `modelo_inscricoes.csv`, preencha no Excel e salve como CSV UTF-8.
4. Envie o arquivo e confira o resumo da importação.
