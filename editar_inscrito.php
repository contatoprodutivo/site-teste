<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['import_authenticated'])) {
    header('Location: importacao.php');
    exit;
}

require __DIR__ . '/config.php';

function validCpf(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($digit = 9; $digit < 11; $digit++) {
        $sum = 0;
        for ($i = 0; $i < $digit; $i++) $sum += (int) $cpf[$i] * (($digit + 1) - $i);
        $check = (10 * $sum) % 11;
        $check = $check === 10 ? 0 : $check;
        if ((int) $cpf[$digit] !== $check) return false;
    }
    return true;
}

$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit('Inscrito não encontrado.');
}

if (empty($_SESSION['edit_csrf_token'])) $_SESSION['edit_csrf_token'] = bin2hex(random_bytes(32));
$error = '';
$success = isset($_GET['salvo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['edit_csrf_token'], $token)) {
        $error = 'Sessão expirada. Atualize a página.';
    } else {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $cpf = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '';
        $email = trim((string) ($_POST['email'] ?? ''));
        $telefone = preg_replace('/\D/', '', (string) ($_POST['telefone'] ?? '')) ?? '';
        $empresa = trim((string) ($_POST['empresa'] ?? ''));

        if (strlen($nome) < 3 || strlen($nome) > 150) $error = 'Informe um nome válido.';
        elseif (!validCpf($cpf)) $error = 'Informe um CPF válido.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $error = 'Informe um e-mail válido.';
        elseif (strlen($telefone) < 10 || strlen($telefone) > 11) $error = 'Informe um telefone válido.';
        elseif ($empresa === '' || strlen($empresa) > 150) $error = 'Informe a empresa.';
        else {
            try {
                $update = $pdo->prepare(
                    'UPDATE inscricoes_curso SET nome = :nome, cpf = :cpf, email = :email, telefone = :telefone, empresa = :empresa WHERE id = :id'
                );
                $update->execute([':nome' => $nome, ':cpf' => $cpf, ':email' => $email, ':telefone' => $telefone, ':empresa' => $empresa, ':id' => $id]);
                unset($_SESSION['edit_csrf_token']);
                header('Location: editar_inscrito.php?id=' . $id . '&salvo=1');
                exit;
            } catch (PDOException $exception) {
                $error = $exception->getCode() === '23000' ? 'Este CPF já pertence a outro inscrito.' : 'Não foi possível salvar a alteração.';
                error_log('Edição de inscrito: ' . $exception->getMessage());
            }
        }
    }
}

$statement = $pdo->prepare('SELECT id, nome, cpf, email, telefone, empresa FROM inscricoes_curso WHERE id = :id');
$statement->execute([':id' => $id]);
$inscrito = $statement->fetch();
if (!$inscrito) {
    http_response_code(404);
    exit('Inscrito não encontrado.');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar inscrito | Curso de Power BI</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="import-shell">
    <section class="import-card edit-card">
      <p class="form-kicker">ÁREA ADMINISTRATIVA</p>
      <h1 class="import-title">Editar inscrito</h1>
      <p class="form-description">Atualize os dados e salve as alterações.</p>
      <?php if ($success): ?><div class="alert success">Dados atualizados com sucesso.</div><?php endif; ?>
      <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <form method="post" action="editar_inscrito.php">
        <input type="hidden" name="id" value="<?= (int) $inscrito['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['edit_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="nome">Nome completo</label>
        <input id="nome" name="nome" type="text" maxlength="150" value="<?= htmlspecialchars($inscrito['nome'], ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="cpf">CPF</label>
        <input id="cpf" name="cpf" type="text" maxlength="14" value="<?= htmlspecialchars($inscrito['cpf'], ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" maxlength="190" value="<?= htmlspecialchars($inscrito['email'], ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="telefone">Telefone</label>
        <input id="telefone" name="telefone" type="tel" maxlength="15" value="<?= htmlspecialchars($inscrito['telefone'], ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="empresa">Empresa</label>
        <input id="empresa" name="empresa" type="text" maxlength="150" value="<?= htmlspecialchars($inscrito['empresa'], ENT_QUOTES, 'UTF-8') ?>" required>
        <button type="submit">Salvar alterações</button>
      </form>
      <a class="back-link" href="inscritos.php">Voltar para inscritos</a>
    </section>
  </main>
</body>
</html>
