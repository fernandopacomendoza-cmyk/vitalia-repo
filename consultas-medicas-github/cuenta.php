<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/conexion.php';
$userId = (int)$_SESSION['user_id'];
$email = $_SESSION['email'];
$appointments = [];
$serviceRequests = [];
$appointmentError = false;
$serviceError = false;
try {
    $stmt = $conn->prepare('SELECT especialidad, fecha_preferida, estado, correo_estado, creado_en FROM solicitudes_cita WHERE user_id = ? OR email = ? ORDER BY creado_en DESC');
    $stmt->bind_param('is', $userId, $email);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $appointmentError = true;
}
try {
    $stmt = $conn->prepare('SELECT tipo_servicio, lugar, fecha_preferida, estado, creado_en FROM solicitudes_servicio WHERE user_id = ? OR email = ? ORDER BY creado_en DESC');
    $stmt->bind_param('is', $userId, $email);
    $stmt->execute();
    $serviceRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $serviceError = true;
}
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$pageTitle = 'Mi cuenta | Vitalia';
require __DIR__ . '/includes/header.php';
?>
<main class="account-page">
  <div class="account-welcome"><div><p class="eyebrow">TU ESPACIO VITALIA</p><h1>Hola, <?= htmlspecialchars($_SESSION['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>.</h1><p>Revisa tus solicitudes de citas y servicios asociadas a tu cuenta.</p></div><form method="post" action="salir.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="button button-outline">Cerrar sesión</button></form></div>
  <section class="account-card"><h2>Mis citas médicas</h2>
    <?php if ($appointmentError): ?><p class="notice">Falta actualizar la base de datos de Vitalia. Importa una vez el archivo <strong>migrar_vitalia_login.sql</strong> en phpMyAdmin.</p>
    <?php elseif (!$appointments): ?><p>Aún no hay citas solicitadas con este correo.</p><a class="button" href="index.php#cita">Solicitar una cita <span>↗</span></a>
    <?php else: ?><div class="table-scroll"><table class="appointments-table"><thead><tr><th>Especialidad</th><th>Fecha solicitada</th><th>Estado</th><th>Registro</th></tr></thead><tbody><?php foreach ($appointments as $appointment): ?><tr><td><?= htmlspecialchars($appointment['especialidad'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($appointment['fecha_preferida'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="status-pill"><?= htmlspecialchars(ucfirst($appointment['estado']), ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars($appointment['creado_en'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
  <section class="account-card account-card-spaced"><h2>Mis solicitudes de servicio</h2>
    <?php if ($serviceError): ?><p class="notice">Falta crear la tabla de servicios. Importa una vez el archivo <strong>migrar_vitalia_servicios.sql</strong> en phpMyAdmin.</p>
    <?php elseif (!$serviceRequests): ?><p>Aún no hay servicios solicitados con este correo.</p><a class="button" href="solicitar_servicio.php">Solicitar un servicio <span>↗</span></a>
    <?php else: ?><div class="table-scroll"><table class="appointments-table"><thead><tr><th>Servicio solicitado</th><th>Lugar</th><th>Fecha preferida</th><th>Estado</th><th>Registro</th></tr></thead><tbody><?php foreach ($serviceRequests as $request): ?><tr><td><?= htmlspecialchars($request['tipo_servicio'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars(ucfirst($request['lugar']), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($request['fecha_preferida'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="status-pill"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $request['estado'])), ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars($request['creado_en'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
  <p class="demo-note">Las solicitudes quedan pendientes hasta que el equipo Vitalia confirme la disponibilidad. Para cambios, comunícate al (01) 444 5555.</p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
