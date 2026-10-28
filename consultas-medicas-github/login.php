<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: cuenta.php');
    exit;
}
$showSignup = isset($_GET['registro']);
$message = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$pageTitle = 'Iniciar sesión | Vitalia';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-page">
  <section class="auth-intro"><p class="eyebrow">TU ESPACIO VITALIA</p><h1>Tu cuidado,<br><em>más cerca.</em></h1><p>Inicia sesión para revisar tus solicitudes de cita o crea una cuenta para empezar.</p></section>
  <section class="auth-panel auth-card" id="signIn" <?= $showSignup ? 'hidden' : '' ?>>
    <h2>Iniciar sesión</h2>
    <?php if ($message !== ''): ?><p class="notice" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="post" action="auth.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="signIn">
      <label>Correo electrónico<input type="email" name="email" autocomplete="email" required maxlength="255"></label>
      <label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label>
      <button type="submit" class="button">Entrar <span>↗</span></button>
    </form>
    <p class="auth-switch">¿No tienes una cuenta? <button type="button" id="signUpButton">Crear cuenta</button></p>
  </section>
  <section class="auth-panel auth-card" id="signup" <?= $showSignup ? '' : 'hidden' ?>>
    <h2>Crear cuenta</h2>
    <?php if ($message !== ''): ?><p class="notice" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="post" action="auth.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="signUp">
      <label>Nombre<input type="text" name="fName" autocomplete="given-name" required maxlength="100"></label>
      <label>Apellido<input type="text" name="lName" autocomplete="family-name" required maxlength="100"></label>
      <label>Correo electrónico<input type="email" name="email" autocomplete="email" required maxlength="255"></label>
      <label>Contraseña (mínimo 8 caracteres)<input type="password" name="password" autocomplete="new-password" required minlength="8"></label>
      <button type="submit" class="button">Crear cuenta <span>↗</span></button>
    </form>
    <p class="auth-switch">¿Ya tienes cuenta? <button type="button" id="signInButton">Iniciar sesión</button></p>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
