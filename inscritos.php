<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['import_authenticated'])) {
    header('Location: importacao.php');
    exit;
}

require __DIR__ . '/config.php';

$search = trim((string) ($_GET['busca'] ?? ''));
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

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

$where = '';
$params = [];
if ($search !== '') {
    $digits = preg_replace('/\D/', '', $search) ?? '';
    $where = ' WHERE nome LIKE :search OR email LIKE :search OR empresa LIKE :search';
    $params[':search'] = '%' . $search . '%';
    if ($digits !== '') {
        $where .= ' OR cpf LIKE :digits OR telefone LIKE :digits';
        $params[':digits'] = '%' . $digits . '%';
    }
}

$countStatement = $pdo->prepare('SELECT COUNT(*) FROM inscricoes_curso' . $where);
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$listStatement = $pdo->prepare(
    'SELECT id, nome, cpf, email, telefone, empresa, data_inscricao '
    . 'FROM inscricoes_curso' . $where
    . ' ORDER BY data_inscricao DESC, id DESC LIMIT :limit OFFSET :offset'
);
foreach ($params as $key => $value) {
    $listStatement->bindValue($key, $value);
}
$listStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStatement->execute();
$inscritos = $listStatement->fetchAll();

function formatCpf(string $cpf): string
{
    return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf) ?? $cpf;
}

function formatPhone(string $phone): string
{
    if (strlen($phone) === 11) {
        return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $phone) ?? $phone;
    }
    return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $phone) ?? $phone;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscritos | Curso de Power BI</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="admin-shell">
    <section class="admin-panel">
      <header class="admin-header">
        <div>
          <p class="form-kicker">ÁREA ADMINISTRATIVA</p>
          <h1 class="admin-title">Inscritos</h1>
          <p class="form-description"><?= $total ?> registro(s) encontrado(s)</p>
        </div>
        <nav class="admin-nav">
          <a href="importacao.php">Importar</a>
          <a href="importacao.php?sair=1" class="danger-link">Sair</a>
        </nav>
      </header>

      <form class="search-form" method="get" action="inscritos.php">
        <label class="sr-only" for="busca">Pesquisar</label>
        <input id="busca" name="busca" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Pesquisar por nome, CPF, e-mail ou empresa">
        <button type="submit">Pesquisar</button>
        <?php if ($search !== ''): ?><a href="inscritos.php">Limpar</a><?php endif; ?>
      </form>

      <div class="table-wrap">
        <table>
          <thead><tr><th>Nome</th><th>CPF</th><th>Contato</th><th>Empresa</th><th>Inscrição</th><th></th></tr></thead>
          <tbody>
            <?php if (!$inscritos): ?>
              <tr><td colspan="6" class="empty-state">Nenhum inscrito encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($inscritos as $inscrito): ?>
              <tr>
                <td><strong><?= htmlspecialchars($inscrito['nome'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td><?= htmlspecialchars(formatCpf($inscrito['cpf']), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span><?= htmlspecialchars($inscrito['email'], ENT_QUOTES, 'UTF-8') ?></span>
                  <small><?= htmlspecialchars(formatPhone($inscrito['telefone']), ENT_QUOTES, 'UTF-8') ?></small>
                </td>
                <td><?= htmlspecialchars($inscrito['empresa'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($inscrito['data_inscricao'])) ?></td>
                <td><a class="edit-link" href="editar_inscrito.php?id=<?= (int) $inscrito['id'] ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Paginação">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="?pagina=<?= $i ?>&amp;busca=<?= urlencode($search) ?>"><?= $i ?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
