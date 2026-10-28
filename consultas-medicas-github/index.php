<?php
$sent = isset($_GET['enviado']);
$error = isset($_GET['error']);
$specialtyOptions = ['medicina-general' => 'Medicina general', 'pediatria' => 'Pediatría', 'cardiologia' => 'Cardiología', 'dermatologia' => 'Dermatología'];
$selectedSpecialty = $specialtyOptions[$_GET['especialidad'] ?? ''] ?? '';
$selectedDoctor = $_GET['doctor'] ?? '';
$pageTitle = 'Vitalia | Consultas médicas';
require __DIR__ . '/includes/header.php';
?>
<main>
  <section class="hero" id="inicio">
    <div class="hero-copy">
      <p class="eyebrow"><span class="pulse"></span> CUIDADO MÉDICO A TU ALCANCE</p>
      <h1>Tu salud merece<br>una atención <em>cercana.</em></h1>
      <p class="hero-text">Escuchamos, cuidamos y acompañamos cada paso. Encuentra al especialista adecuado y agenda tu consulta de forma sencilla.</p>
      <div class="hero-actions"><a class="button" href="#cita">Agenda tu consulta <span>↗</span></a><a class="text-link" href="servicios.php">Conoce nuestros servicios <span>↗</span></a></div>
      <div class="trust-row"><div class="avatar-stack"><span>👩🏻‍⚕️</span><span>👨🏽‍⚕️</span><span>👩🏽‍⚕️</span></div><p><strong>Atención con calidez</strong><br>Personas primero, siempre.</p></div>
    </div>
    <div class="hero-art" aria-label="Ilustración de una profesional de salud" role="img">
      <div class="art-sun"></div><div class="art-card card-one"><span class="card-icon">♥</span><span><b>Cuidado integral</b><small>Para cada etapa de tu vida</small></span></div>
      <div class="doctor-illustration"><div class="doctor-hair"></div><div class="doctor-head"><div class="doctor-face"><i></i><i></i><b></b></div></div><div class="doctor-neck"></div><div class="doctor-coat"><span class="stethoscope">⌕</span></div><div class="doctor-arm"></div></div>
      <div class="art-card card-two"><span class="check-icon">✓</span><span><b>Estamos contigo</b><small>Atención personalizada</small></span></div><span class="sparkle sparkle-a">✳</span><span class="sparkle sparkle-b">✦</span>
    </div>
    <div class="hero-bottom"><span>01 — 10</span><span class="hero-line"></span><span>Cuidamos de ti</span></div>
  </section>

  <section class="page-links section-pad"><div class="section-heading"><div><p class="eyebrow">CONOCE VITALIA</p><h2>Encuentra lo que <em>necesitas.</em></h2></div><p>Explora cada apartado en su propia página.</p></div><div class="page-link-grid"><a href="nosotros.php"><span>01</span><b>Nosotros</b><small>Quiénes somos y cómo te cuidamos</small><i>↗</i></a><a href="servicios.php"><span>02</span><b>Servicios</b><small>Especialidades y formularios de solicitud</small><i>↗</i></a><a href="especialistas.php"><span>03</span><b>Especialistas</b><small>Conoce al equipo médico</small><i>↗</i></a><a href="contacto.php"><span>04</span><b>Contacto</b><small>Ubicación, horarios y teléfonos</small><i>↗</i></a></div></section>

  <section class="appointment section-pad" id="cita"><div class="appointment-copy"><p class="eyebrow">04 / SOLICITA UNA CITA</p><h2>Un pequeño paso<br>para sentirte <em>mejor.</em></h2><p>Déjanos tus datos y nos pondremos en contacto para confirmar la disponibilidad de tu consulta.</p><div class="appointment-note"><span>✳</span><p>Tu solicitud no queda confirmada hasta que nuestro equipo se comunique contigo.</p></div></div>
    <form class="appointment-form" action="guardar_cita.php" method="post">
      <?php if ($sent && ($_GET['correo'] ?? '') === 'ok'): ?><div class="form-alert success" role="status">Solicitud guardada. Enviamos la confirmación al correo indicado y el informe al equipo Vitalia.</div><?php elseif ($sent && ($_GET['correo'] ?? '') === 'partial'): ?><div class="form-alert success" role="status">La solicitud se guardó, pero solo se envió uno de los dos correos. Revisa la configuración SMTP.</div><?php elseif ($sent): ?><div class="form-alert success" role="status">Solicitud guardada. El envío de correo está pendiente de configurar el SMTP.</div><?php endif; ?>
      <?php if ($error): ?><div class="form-alert error" role="alert">No pudimos guardar la solicitud. Revisa los datos e inténtalo nuevamente.</div><?php endif; ?>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <h3>Datos del paciente</h3>
      <div class="field-row"><label>Nombre completo<input name="nombre" type="text" autocomplete="name" maxlength="120" value="<?= htmlspecialchars(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. María Pérez" required></label><label>DNI o documento<input name="documento" type="text" maxlength="20" placeholder="Número de documento" required></label></div>
      <div class="field-row"><label>Fecha de nacimiento<input name="fecha_nacimiento" type="date" max="<?= date('Y-m-d') ?>" required></label><label>Teléfono<input name="telefono" type="tel" autocomplete="tel" maxlength="30" placeholder="Ej. 987 654 321" required></label></div>
      <div class="field-row"><label>Correo electrónico<input name="email" type="email" autocomplete="email" maxlength="180" value="<?= htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= $isLoggedIn ? 'readonly' : '' ?> placeholder="tu@correo.com" required></label><label>Sexo (opcional)<select name="sexo"><option value="">Prefiero no indicarlo</option><option>Femenino</option><option>Masculino</option><option>Otro</option></select></label></div>
      <h3>Detalles de la cita</h3>
      <div class="field-row"><label>Especialidad<select name="especialidad" id="appointmentSpecialty" required><option value="" <?= $selectedSpecialty === '' ? 'selected' : '' ?> disabled>Elige una opción</option><?php foreach ($specialtyOptions as $specialty): ?><option value="<?= htmlspecialchars($specialty, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedSpecialty === $specialty ? 'selected' : '' ?>><?= htmlspecialchars($specialty, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label><label>Doctor preferido (opcional)<select name="medico_preferido" id="appointmentDoctor"><option value="">Sin preferencia</option><option data-specialty="Medicina general" <?= $selectedDoctor === 'Dra. Ana Morales' ? 'selected' : '' ?>>Dra. Ana Morales</option><option data-specialty="Pediatría" <?= $selectedDoctor === 'Dr. Luis Herrera' ? 'selected' : '' ?>>Dr. Luis Herrera</option><option data-specialty="Cardiología" <?= $selectedDoctor === 'Dra. Camila Ríos' ? 'selected' : '' ?>>Dra. Camila Ríos</option><option data-specialty="Dermatología" <?= $selectedDoctor === 'Dra. Elena Paredes' ? 'selected' : '' ?>>Dra. Elena Paredes</option></select></label></div>
      <div class="field-row"><label>Fecha preferida<input name="fecha" type="date" required></label><label>Horario preferido<select name="horario_preferido" required><option value="" selected disabled>Elige una opción</option><option>Mañana</option><option>Tarde</option><option>Sin preferencia</option></select></label></div>
      <label>Modalidad<select name="modalidad" required><option value="" selected disabled>Elige una opción</option><option>Presencial</option><option>Virtual</option></select></label>
      <h3>Seguro y cobertura</h3>
      <label>Tipo de cobertura<select name="cobertura" id="appointmentCoverage" required><option value="" selected disabled>Elige una opción</option><option value="particular">Particular</option><option value="seguro">Seguro médico / EPS</option></select></label>
      <div id="appointmentInsuranceFields" hidden><label>Aseguradora<input name="aseguradora" id="appointmentInsurer" type="text" maxlength="120"></label><div class="field-row"><label>Número de póliza o carné<input name="poliza" id="appointmentPolicy" type="text" maxlength="80"></label><label>Número de autorización (opcional)<input name="autorizacion" type="text" maxlength="80"></label></div></div>
      <label class="consent"><input type="checkbox" name="privacidad" value="1" required><span>Leí el <a href="#privacidad">aviso de privacidad</a> y acepto que el informe completo de mi cita, incluidos mis datos personales y de cobertura, se envíe al correo indicado y a correo-equipo@ejemplo.com.</span></label>
      <button class="button form-button" type="submit">Enviar solicitud <span>↗</span></button>
      <p class="form-footnote">El informe completo se enviará a los dos correos indicados. No escribas síntomas ni diagnósticos aquí.</p>
    </form>
  </section>

  <section class="my-appointments section-pad" id="mis-citas"><div class="my-cita-icon">▤</div><div><p class="eyebrow">05 / MIS CITAS</p><h2>¿Ya tienes una cita?</h2><p>Inicia sesión para consultar las solicitudes asociadas a tu correo.</p></div><a class="button button-outline" href="<?= $isLoggedIn ? 'cuenta.php' : 'login.php' ?>"><?= $isLoggedIn ? 'Ver mis citas' : 'Iniciar sesión' ?> <span>↗</span></a></section>

  <section class="faq section-pad" id="preguntas"><div class="faq-heading"><p class="eyebrow">08 / PREGUNTAS FRECUENTES</p><h2>¿Tienes alguna <em>duda?</em></h2><p>Estas son algunas respuestas que pueden ayudarte.</p></div><div class="faq-list"><details><summary>¿Cómo solicito una cita?<span>＋</span></summary><p>Completa el formulario de esta página. Nuestro equipo te llamará para confirmar la disponibilidad.</p></details><details><summary>¿Puedo cambiar o cancelar mi cita?<span>＋</span></summary><p>Sí. Llámanos al (01) 444 5555 con anticipación y te ayudaremos.</p></details><details><summary>¿Qué debo llevar a mi consulta?<span>＋</span></summary><p>Trae tu documento de identidad y, si aplica, la lista de medicamentos que usas o resultados previos.</p></details></div></section>

  <section class="privacy section-pad" id="privacidad"><div class="privacy-symbol">◎</div><div><p class="eyebrow">09 / AVISO DE PRIVACIDAD</p><h2>Tus datos se tratan <em>con cuidado.</em></h2><p>Usaremos los datos que ingreses para gestionar tu cita o solicitud de servicio. Esto puede incluir nombre, DNI, fecha de nacimiento, contacto, preferencia de doctor, servicio solicitado, modalidad y cobertura; si pides atención a domicilio, también la dirección. Enviaremos el informe completo de cada solicitud al correo que indiques y al equipo de Vitalia en <a href="mailto:correo-equipo@ejemplo.com">correo-equipo@ejemplo.com</a>. El informe puede incluir documento, fecha de nacimiento, datos de seguro y dirección. Si adjuntas una orden médica al formulario de servicio, también se enviará como archivo adjunto a ambos correos. Puedes solicitar la actualización o eliminación de tus datos escribiéndonos a <a href="mailto:hola@vitalia.pe">hola@vitalia.pe</a>.</p><p class="emergency-inline"><strong>¿Es una emergencia médica?</strong> Esta página no brinda atención de emergencias. Si necesitas ayuda inmediata en Perú, llama al <a href="tel:106"><strong>106 (SAMU)</strong></a> o acude al servicio de urgencias más cercano.</p></div><a class="privacy-link" href="mailto:hola@vitalia.pe">Consultas de privacidad ↗</a></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
