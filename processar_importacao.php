<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

function stop(int $status, string $message): never
{
    http_response_code($status);
    exit($message);
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

$providedUser = (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
$providedPass = (string) ($_SERVER['PHP_AUTH_PW'] ?? '');
if (!isset($adminUser, $adminPass)
    || !hash_equals($adminUser, $providedUser)
    || !hash_equals($adminPass, $providedPass)) {
    stop(401, 'Acesso não autorizado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stop(405, 'Método não permitido.');
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['import_csrf_token']) || !hash_equals($_SESSION['import_csrf_token'], $token)) {
    stop(419, 'Sessão expirada. Atualize a página e tente novamente.');
}
if (($_POST['confirmacao'] ?? '') !== '1') {
    stop(422, 'Confirme a autorização para uso dos dados.');
}
if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    stop(422, 'Não foi possível receber o arquivo.');
}
if ($_FILES['arquivo']['size'] > 2 * 1024 * 1024) {
    stop(413, 'O arquivo deve ter no máximo 2 MB.');
}

$handle = fopen($_FILES['arquivo']['tmp_name'], 'rb');
if (!$handle) {
    stop(422, 'Não foi possível abrir o arquivo.');
}

$firstLine = fgets($handle);
if ($firstLine === false) {
    fclose($handle);
    stop(422, 'O arquivo está vazio.');
}
$firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
$delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
$headers = array_map(
    static fn ($value) => strtolower(trim((string) $value)),
    str_getcsv(rtrim($firstLine, "\r\n"), $delimiter)
);

$required = ['nome', 'cpf', 'email', 'telefone', 'empresa'];
if ($headers !== $required) {
    fclose($handle);
    stop(422, 'Cabeçalho inválido. Use o modelo sem alterar as colunas ou a ordem.');
}

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
    $statement = $pdo->prepare(
        'INSERT INTO inscricoes_curso (nome, cpf, email, telefone, empresa, consentimento) '
        . 'VALUES (:nome, :cpf, :email, :telefone, :empresa, 1)'
    );
} catch (PDOException $exception) {
    fclose($handle);
    error_log('Importação: ' . $exception->getMessage());
    stop(500, 'Não foi possível conectar ao banco.');
}

$line = 1;
$inserted = 0;
$duplicates = 0;
$errors = [];

while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
    $line++;
    if (count($values) === 1 && trim((string) $values[0]) === '') {
        continue;
    }
    if (count($values) !== 5) {
        $errors[] = "Linha {$line}: quantidade de colunas inválida.";
        continue;
    }

    [$nome, $cpfRaw, $email, $telefoneRaw, $empresa] = array_map(
        static fn ($value) => trim((string) $value),
        $values
    );
    $cpf = preg_replace('/\D/', '', $cpfRaw) ?? '';
    $telefone = preg_replace('/\D/', '', $telefoneRaw) ?? '';

    if ($nome === '' || strlen($nome) < 3 || strlen($nome) > 150) {
        $errors[] = "Linha {$line}: nome inválido.";
        continue;
    }
    if (!validCpf($cpf)) {
        $errors[] = "Linha {$line}: CPF inválido.";
        continue;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = "Linha {$line}: e-mail inválido.";
        continue;
    }
    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        $errors[] = "Linha {$line}: telefone inválido.";
        continue;
    }
    if ($empresa === '' || strlen($empresa) > 150) {
        $errors[] = "Linha {$line}: empresa inválida.";
        continue;
    }

    try {
        $statement->execute([
            ':nome' => $nome,
            ':cpf' => $cpf,
            ':email' => $email,
            ':telefone' => $telefone,
            ':empresa' => $empresa,
        ]);
        $inserted++;
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            $duplicates++;
            continue;
        }
        error_log("Importação, linha {$line}: " . $exception->getMessage());
        $errors[] = "Linha {$line}: erro ao salvar no banco.";
    }
}

fclose($handle);
unset($_SESSION['import_csrf_token']);
$_SESSION['import_result'] = compact('inserted', 'duplicates', 'errors');
header('Location: importacao.php');
exit;
