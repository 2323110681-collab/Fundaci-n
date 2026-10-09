<?php
require_once dirname(__DIR__) . '/config/recaptcha.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Fundación DU</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="assets/css/login.css">
  <!-- Logo de la Fundación DU en la pestaña del navegador. Se usa el favicon de
       64px y no logo-fdu.png (640 KB), que es demasiado pesado para una pestaña. -->
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/images/logo-fdu.png">
</head>
<body class="login-body bg-slate-50 font-sans p-6 md:p-12 text-slate-900">
  <div class="login-page-wrapper">
    <section class="login-card bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
      <h1 class="text-2xl font-bold text-slate-900 mb-4">Acceso Administrador</h1>
      <p class="text-sm text-slate-600 mb-6">Ingrese con usuario y contraseña para acceder al panel.</p>
      <form id="loginPageForm" class="space-y-4">
        <div>
          <label for="username" class="text-sm text-slate-700">Usuario</label>
          <input id="username" name="username" class="input-field" autocomplete="username">
        </div>
        <div>
          <label for="password" class="text-sm text-slate-700">Contraseña</label>
          <input id="password" name="password" type="password" class="input-field" autocomplete="current-password">
        </div>
        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(getRecaptchaSiteKey(), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="flex gap-3 items-center">
          <button type="button" id="loginSubmit" class="btn-primary">Entrar</button>
          <span id="loginMessage" class="text-sm text-red-600" role="alert" aria-live="assertive"></span>
        </div>
      </form>
    </section>
  </div>
  <script src="https://www.google.com/recaptcha/api.js?hl=es" async defer></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/login.js?v=<?= filemtime(__DIR__ . '/assets/js/login.js') ?>"></script>
</body>
</html>

