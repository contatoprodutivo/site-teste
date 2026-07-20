<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Inscrição para o curso de Power BI da Contato Produtivo.">
  <title>Curso de Power BI | Inscrição</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="page-shell">
    <section class="course-intro" aria-labelledby="course-title">
      <p class="eyebrow">CONTATO PRODUTIVO APRESENTA</p>
      <h1 id="course-title">Power BI na prática</h1>
      <p class="lead">Aprenda a transformar dados em decisões com dashboards claros, úteis e profissionais.</p>

      <div class="course-details">
        <article>
          <span>01</span>
          <div><strong>Fundamentos</strong><small>Do tratamento dos dados à modelagem.</small></div>
        </article>
        <article>
          <span>02</span>
          <div><strong>Dashboards</strong><small>Indicadores que comunicam com clareza.</small></div>
        </article>
        <article>
          <span>03</span>
          <div><strong>Aplicação real</strong><small>Exercícios voltados ao dia a dia.</small></div>
        </article>
      </div>
    </section>

    <section class="form-card" aria-labelledby="form-title">
      <p class="form-kicker">GARANTA SUA VAGA</p>
      <h2 id="form-title">Faça sua inscrição</h2>
      <p class="form-description">Preencha seus dados. Os campos marcados são obrigatórios.</p>

      <div id="formAlert" class="alert" role="alert" aria-live="polite" hidden></div>

      <form id="registrationForm" action="salvar_inscricao.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="nome">Nome completo</label>
        <input id="nome" name="nome" type="text" maxlength="150" autocomplete="name" required>

        <div class="field-row">
          <div>
            <label for="cpf">CPF</label>
            <input id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" required>
          </div>
          <div>
            <label for="telefone">Telefone</label>
            <input id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15" placeholder="(84) 99999-9999" autocomplete="tel" required>
          </div>
        </div>

        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required>

        <label for="empresa">Empresa</label>
        <input id="empresa" name="empresa" type="text" maxlength="150" autocomplete="organization" required>

        <label class="consent" for="consentimento">
          <input id="consentimento" name="consentimento" type="checkbox" value="1" required>
          <span>Autorizo o uso dos dados para processar esta inscrição e receber informações sobre o curso.</span>
        </label>

        <button id="submitButton" type="submit">Confirmar inscrição</button>
      </form>
    </section>
  </main>
  <script src="script.js"></script>
</body>
</html>
