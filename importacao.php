<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

if (!isset($adminUser, $adminPass) || $adminPass === 'TROQUE_POR_UMA_SENHA_FORTE') {
    http_response_code(500);
    exit('Configure adminUser e adminPass no arquivo config.php.');
}

if (isset($_GET['sair'])) {
    unset($_SESSION['import_authenticated']);
    header('Location: importacao.php');
    exit;
}

if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $loginToken = (string) ($_POST['csrf_token'] ?? '');
    $providedUser = trim((string) ($_POST['usuario'] ?? ''));
    $providedPass = (string) ($_POST['senha'] ?? '');

    if (!hash_equals($_SESSION['login_csrf_token'], $loginToken)) {
        $loginError = 'Sessão expirada. Atualize a página.';
    } elseif (hash_equals($adminUser, $providedUser) && hash_equals($adminPass, $providedPass)) {
        session_regenerate_id(true);
        $_SESSION['import_authenticated'] = true;
        unset($_SESSION['login_csrf_token']);
        header('Location: importacao.php');
        exit;
    } else {
        $loginError = 'Usuário ou senha incorretos.';
    }
}

if (empty($_SESSION['import_authenticated'])) {
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Login | Importação de inscritos</title>
      <link rel="stylesheet" href="styles.css">
    </head>
    <body>
      <main class="import-shell">
        <section class="import-card login-card">
          <p class="form-kicker">ACESSO RESTRITO</p>
          <h1 class="import-title">Área administrativa</h1>
          <p class="form-description">Entre para importar inscritos do curso.</p>
          <?php if ($loginError): ?>
            <div class="alert error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
          <form method="post" action="importacao.php">
            <input type="hidden" name="login_action" value="1">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="usuario">Usuário</label>
            <input id="usuario" name="usuario" type="text" autocomplete="username" required autofocus>
            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" autocomplete="current-password" required>
            <button type="submit">Entrar</button>
          </form>
          <a class="back-link" href="index.php">Voltar ao formulário</a>
        </section>
      </main>
    </body>
    </html>
    <?php
    exit;
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
      <a class="back-link logout-link" href="importacao.php?sair=1">Sair</a>
    </section>
  </main>
</body>
</html>
