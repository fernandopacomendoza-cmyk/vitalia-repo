<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Atención médica cercana y confiable en Vitalia.">
  <title><?= htmlspecialchars($pageTitle ?? 'Vitalia | Consultas médicas', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=20261028-layoutfix">
</head>
<body>
<header class="site-header">
  <a class="brand" href="index.php" aria-label="Vitalia, inicio"><span class="brand-mark">✳</span> vitalia<span class="brand-dot">.</span></a>
  <button class="menu-toggle" aria-label="Abrir menú" aria-expanded="false">☰</button>
  <nav class="main-nav" aria-label="Navegación principal">
    <a href="nosotros.php">Nosotros</a><a href="servicios.php">Servicios</a><a href="especialistas.php">Especialistas</a><a href="contacto.php">Contacto</a>
    <a class="auth-link" href="<?= $isLoggedIn ? 'cuenta.php' : 'login.php' ?>"><?= $isLoggedIn ? 'Mi cuenta' : 'Iniciar sesión' ?></a>
    <a class="button button-small" href="index.php#cita">Solicitar cita <span>↗</span></a>
  </nav>
</header>
