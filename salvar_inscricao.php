<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function validCpf(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($digit = 9; $digit < 11; $digit++) {
        $sum = 0;
        for ($i = 0; $i < $digit; $i++) {
            $sum += (int) $cpf[$i] * (($digit + 1) - $i);
        }
        $check = (10 * $sum) % 11;
        $check = $check === 10 ? 0 : $check;
        if ((int) $cpf[$digit] !== $check) {
            return false;
        }
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Método não permitido.');
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    respond(419, false, 'Sessão expirada. Atualize a página e tente novamente.');
}

$nome = trim((string) ($_POST['nome'] ?? ''));
$cpf = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '';
$email = trim((string) ($_POST['email'] ?? ''));
$telefone = preg_replace('/\D/', '', (string) ($_POST['telefone'] ?? '')) ?? '';
$empresa = trim((string) ($_POST['empresa'] ?? ''));
$consentimento = ($_POST['consentimento'] ?? '') === '1';

if ($nome === '' || mb_strlen($nome) < 3 || mb_strlen($nome) > 150) {
    respond(422, false, 'Informe um nome completo válido.');
}
if (!validCpf($cpf)) {
    respond(422, false, 'Informe um CPF válido.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    respond(422, false, 'Informe um e-mail válido.');
}
if (strlen($telefone) < 10 || strlen($telefone) > 11) {
    respond(422, false, 'Informe um telefone válido com DDD.');
}
if ($empresa === '' || mb_strlen($empresa) > 150) {
    respond(422, false, 'Informe o nome da empresa.');
}
if (!$consentimento) {
    respond(422, false, 'É necessário autorizar o uso dos dados para realizar a inscrição.');
}

require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $statement = $pdo->prepare(
        'INSERT INTO inscricoes_curso (nome, cpf, email, telefone, empresa, consentimento)\n'
        . 'VALUES (:nome, :cpf, :email, :telefone, :empresa, 1)'
    );
    $statement->execute([
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':email' => $email,
        ':telefone' => $telefone,
        ':empresa' => $empresa,
    ]);

    unset($_SESSION['csrf_token']);
    respond(201, true, 'Inscrição realizada com sucesso! Em breve entraremos em contato.');
} catch (PDOException $exception) {
    if ((int) $exception->getCode() === 23000) {
        respond(409, false, 'Já existe uma inscrição cadastrada para este CPF.');
    }
    error_log('Erro ao salvar inscrição: ' . $exception->getMessage());
    respond(500, false, 'Não foi possível concluir a inscrição. Tente novamente mais tarde.');
}
