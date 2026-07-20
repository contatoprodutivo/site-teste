<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

if (!isset($adminUser, $adminPass) || $adminPass === 'TROQUE_POR_UMA_SENHA_FORTE') {
    http_response_code(500);
    exit('Configure adminUser e adminPass no arquivo config.php.');
}

$providedUser = (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
$providedPass = (string) ($_SERVER['PHP_AUTH_PW'] ?? '');

if (!hash_equals($adminUser, $providedUser) || !hash_equals($adminPass, $providedPass)) {
    header('WWW-Authenticate: Basic realm="Importação de inscritos"');
    http_response_code(401);
    exit('Acesso não autorizado.');
}

if (empty($_SESSION['import_csrf_token'])) {
    $_SESSION['import_csrf_token'] = bin2hex(random_bytes(32));
}

$result = $_SESSION['import_result'] ?? null;
unset($_SESSION['import_result']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Importar inscritos | Curso de Power BI</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="import-shell">
    <section class="import-card">
      <p class="form-kicker">ÁREA ADMINISTRATIVA</p>
      <h1 class="import-title">Importar inscritos</h1>
      <p class="form-description">Baixe o modelo, preencha no Excel e salve como CSV UTF-8 antes de enviar.</p>

      <a class="download-link" href="modelo_inscricoes.csv" download>Baixar modelo CSV</a>

      <?php if (is_array($result)): ?>
        <div class="import-result <?= $result['errors'] ? 'warning' : 'success' ?>">
          <strong><?= (int) $result['inserted'] ?> inserido(s)</strong>
          <span><?= (int) $result['duplicates'] ?> duplicado(s) ignorado(s)</span>
          <span><?= count($result['errors']) ?> linha(s) com erro</span>
          <?php if ($result['errors']): ?>
            <details>
              <summary>Ver erros</summary>
              <ul>
                <?php foreach ($result['errors'] as $error): ?>
                  <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
              </ul>
            </details>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <form class="import-form" action="processar_importacao.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['import_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="arquivo">Arquivo CSV UTF-8</label>
        <input id="arquivo" name="arquivo" type="file" accept=".csv,text/csv" required>

        <label class="consent" for="confirmacao">
          <input id="confirmacao" name="confirmacao" type="checkbox" value="1" required>
          <span>Confirmo que os inscritos autorizaram o uso dos dados para esta inscrição.</span>
        </label>

        <button type="submit">Importar planilha</button>
      </form>
      <a class="back-link" href="index.php">Voltar ao formulário</a>
    </section>
  </main>
</body>
</html>
