<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    exit("ERRO: o arquivo config.php não foi encontrado nesta pasta.\n");
}

require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->query('SELECT 1');
    $tableExists = (bool) $pdo->query("SHOW TABLES LIKE 'inscricoes_curso'")->fetchColumn();

    echo "CONEXÃO COM O BANCO: OK\n";
    echo $tableExists
        ? "TABELA inscricoes_curso: OK\n"
        : "ERRO: a tabela inscricoes_curso ainda não existe.\n";
} catch (PDOException $exception) {
    error_log('Teste de conexão MySQL: ' . $exception->getMessage());
    http_response_code(500);
    echo "ERRO: não foi possível conectar ao banco.\n";
    echo "Confira dbHost, dbName, dbUser, dbPass e as permissões do usuário.\n";
}
