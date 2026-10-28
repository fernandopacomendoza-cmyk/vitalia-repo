<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Solicitar un servicio | Vitalia';
require __DIR__ . '/includes/header.php';
?>
<main class="subpage service-request-page">
  <section class="subpage-hero services-hero"><div><p class="eyebrow">VITALIA / SOLICITUD DE SERVICIOS</p><h1>¿Qué servicio <em>necesitas?</em></h1><p>Usa este formulario para solicitar análisis, imágenes, procedimientos ambulatorios o atención a domicilio. Para una consulta con un doctor, usa el formulario de cita médica.</p></div><div class="subpage-art services-art"><span>＋</span><b>Un servicio<br>adecuado para<br>cada necesidad.</b></div></section>
  <section class="service-request-layout" id="solicitar">
    <div class="service-request-intro"><p class="eyebrow">FORMULARIO 02 / SOLICITUD DE SERVICIO</p><h2>Cuéntanos qué necesitas.</h2><p>El equipo revisará la solicitud y se comunicará contigo para confirmar disponibilidad, preparación y cualquier requisito previo.</p><div class="request-note"><strong>Antes de solicitar</strong><p>Adjunta una orden médica si el servicio lo requiere. No suspendas medicamentos ni sigas indicaciones de ayuno sin confirmarlo con un profesional.</p></div></div>
    <form class="service-request-form" action="guardar_servicio.php" method="post" enctype="multipart/form-data">
      <?php if (isset($_GET['enviado']) && ($_GET['correo'] ?? '') === 'ok'): ?><div class="form-alert success" role="status">Solicitud guardada. Enviamos la confirmación al correo indicado y avisamos al equipo Vitalia.</div><?php elseif (isset($_GET['enviado']) && ($_GET['correo'] ?? '') === 'partial'): ?><div class="form-alert success" role="status">Solicitud guardada, pero solo se pudo enviar uno de los dos correos. Revisa la configuración SMTP.</div><?php elseif (isset($_GET['enviado'])): ?><div class="form-alert success" role="status">Solicitud guardada. El envío de correo está pendiente de configurar el SMTP.</div><?php elseif (isset($_GET['error'])): ?><div class="form-alert error" role="alert">No pudimos guardar la solicitud. Revisa los datos e inténtalo de nuevo.</div><?php endif; ?>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <h3>Datos del paciente</h3>
      <div class="field-row"><label>Nombre completo<input name="nombre" type="text" autocomplete="name" maxlength="120" value="<?= htmlspecialchars(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" required></label><label>DNI o documento<input name="documento" type="text" maxlength="20" autocomplete="off" placeholder="Número de documento" required></label></div>
      <div class="field-row"><label>Fecha de nacimiento<input name="fecha_nacimiento" type="date" max="<?= date('Y-m-d') ?>" required></label><label>Teléfono<input name="telefono" type="tel" maxlength="30" autocomplete="tel" required></label></div>
      <label>Correo electrónico<input name="email" type="email" maxlength="180" autocomplete="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= isset($_SESSION['user_id']) ? 'readonly' : '' ?> required></label>

      <h3>Servicio requerido</h3>
      <label>Tipo de servicio<select name="tipo_servicio" required><option value="" selected disabled>Selecciona el servicio</option><option>Laboratorio clínico — análisis de sangre</option><option>Laboratorio clínico — análisis de orina</option><option>Imágenes — radiografía</option><option>Imágenes — ecografía</option><option>Imágenes — resonancia magnética</option><option>Procedimiento — curación</option><option>Procedimiento — inyección o suero</option><option>Domicilio — enfermería</option><option>Domicilio — visita médica</option><option>Otro servicio</option></select></label>
      <label>Orden médica (opcional)<input name="orden" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"><small>PDF, JPG o PNG; máximo 5 MB. Adjunta la orden si el procedimiento la requiere.</small></label>

      <h3>Fecha y lugar</h3>
      <div class="field-row"><label>Lugar de atención<select name="lugar" id="lugarServicio" required><option value="" selected disabled>Elige una opción</option><option value="clínica">En la clínica</option><option value="domicilio">A domicilio</option></select></label><label>Fecha preferida<input name="fecha_preferida" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required></label></div>
      <label>Horario preferido<select name="horario_preferido" required><option value="" selected disabled>Elige una opción</option><option>Mañana</option><option>Tarde</option><option>Sin preferencia</option></select></label>
      <label id="direccionServicioLabel" hidden>Dirección del domicilio y referencias<input name="direccion" id="direccionServicio" type="text" maxlength="350" autocomplete="street-address"></label>

      <h3>Seguro y autorización</h3>
      <label>Forma de pago<select name="cobertura" id="coberturaServicio" required><option value="" selected disabled>Elige una opción</option><option value="particular">Particular</option><option value="seguro">Seguro médico / EPS</option></select></label>
      <div id="seguroServicioFields" hidden><label>Aseguradora<input name="aseguradora" id="aseguradoraServicio" type="text" maxlength="120"></label><div class="field-row"><label>Número de póliza o carné<input name="poliza" id="polizaServicio" type="text" maxlength="80"></label><label>Número de autorización (si ya lo tienes)<input name="autorizacion" type="text" maxlength="80"></label></div></div>

      <label class="consent"><input type="checkbox" name="indicaciones_leidas" value="1" required><span>Confirmo que revisaré con Vitalia las indicaciones y preparaciones antes del servicio.</span></label>
      <label class="consent"><input type="checkbox" name="privacidad" value="1" required><span>Acepto el <a href="index.php#privacidad">aviso de privacidad</a> y que el informe completo de mi solicitud, incluidos mis datos personales y de cobertura, se envíe al correo indicado y a correo-equipo@ejemplo.com. Si adjunto una orden médica, también se enviará a ambos correos.</span></label>
      <button class="button form-button" type="submit">Enviar solicitud de servicio <span>↗</span></button>
      <p class="form-footnote">La solicitud no confirma una cita ni reemplaza una evaluación médica. La orden médica adjunta se enviará junto con el informe a ambos correos.</p>
    </form>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
