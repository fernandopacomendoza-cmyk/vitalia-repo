<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

function volverServicio(bool $error = false, string $correo = 'pending'): void
{
    header('Location: solicitar_servicio.php' . ($error ? '?error=1#solicitar' : '?enviado=1&correo=' . rawurlencode($correo) . '#solicitar'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: solicitar_servicio.php');
    exit;
}
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) volverServicio(true);

$nombre = trim(preg_replace('/[\r\n]+/', ' ', $_POST['nombre'] ?? ''));
$documento = strtoupper(trim($_POST['documento'] ?? ''));
$fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$telefono = trim(preg_replace('/[\r\n]+/', ' ', $_POST['telefono'] ?? ''));
$email = isset($_SESSION['user_id']) ? ($_SESSION['email'] ?? '') : strtolower(trim($_POST['email'] ?? ''));
$tipo = trim($_POST['tipo_servicio'] ?? '');
$lugar = trim($_POST['lugar'] ?? '');
$direccion = trim(preg_replace('/[\r\n]+/', ' ', $_POST['direccion'] ?? ''));
if ($lugar !== 'domicilio') $direccion = '';
$fecha = trim($_POST['fecha_preferida'] ?? '');
$horario = trim($_POST['horario_preferido'] ?? '');
$cobertura = trim($_POST['cobertura'] ?? '');
$aseguradora = trim(preg_replace('/[\r\n]+/', ' ', $_POST['aseguradora'] ?? ''));
$poliza = trim($_POST['poliza'] ?? '');
$autorizacion = trim($_POST['autorizacion'] ?? '');
$serviciosPermitidos = ['Laboratorio clínico — análisis de sangre', 'Laboratorio clínico — análisis de orina', 'Imágenes — radiografía', 'Imágenes — ecografía', 'Imágenes — resonancia magnética', 'Procedimiento — curación', 'Procedimiento — inyección o suero', 'Domicilio — enfermería', 'Domicilio — visita médica', 'Otro servicio'];
$horariosPermitidos = ['Mañana', 'Tarde', 'Sin preferencia'];
$nacimientoValido = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
$fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);

if ($nombre === '' || mb_strlen($nombre) > 120 || !preg_match('/^[0-9A-Z.-]{6,20}$/', $documento) ||
    !$nacimientoValido || $nacimientoValido->format('Y-m-d') !== $fechaNacimiento || $fechaNacimiento > date('Y-m-d') ||
    $telefono === '' || mb_strlen($telefono) > 30 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180 ||
    !in_array($tipo, $serviciosPermitidos, true) || !in_array($lugar, ['clínica', 'domicilio'], true) ||
    ($lugar === 'domicilio' && ($direccion === '' || mb_strlen($direccion) > 350)) ||
    !$fechaValida || $fechaValida->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d') ||
    !in_array($horario, $horariosPermitidos, true) || !in_array($cobertura, ['particular', 'seguro'], true) ||
    mb_strlen($aseguradora) > 120 || mb_strlen($poliza) > 80 || mb_strlen($autorizacion) > 80 ||
    ($cobertura === 'seguro' && ($aseguradora === '' || $poliza === '')) ||
    ($_POST['indicaciones_leidas'] ?? '') !== '1' || ($_POST['privacidad'] ?? '') !== '1') {
    volverServicio(true);
}

$nombreArchivo = null;
$rutaArchivo = null;
$archivo = $_FILES['orden'] ?? null;
if ($archivo && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024 || !is_uploaded_file($archivo['tmp_name'])) volverServicio(true);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $archivo['tmp_name']) : false;
    if ($finfo) finfo_close($finfo);
    $extensiones = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensiones[$mime])) volverServicio(true);
    $directorio = __DIR__ . '/storage/ordenes';
    if (!is_dir($directorio) && !mkdir($directorio, 0700, true) && !is_dir($directorio)) volverServicio(true);
    $nombreArchivo = bin2hex(random_bytes(20)) . '.' . $extensiones[$mime];
    $rutaArchivo = $directorio . '/' . $nombreArchivo;
    if (!move_uploaded_file($archivo['tmp_name'], $rutaArchivo)) volverServicio(true);
}

try {
    require __DIR__ . '/conexion.php';
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $indicacionesLeidas = 1;
    $consentimiento = 1;
    if ($userId === null) {
        $stmt = $conn->prepare('INSERT INTO solicitudes_servicio (nombre, documento, fecha_nacimiento, telefono, email, tipo_servicio, lugar, direccion, fecha_preferida, horario_preferido, cobertura, aseguradora, poliza, autorizacion, orden_archivo, indicaciones_leidas, consentimiento_datos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(str_repeat('s', 15) . 'ii', $nombre, $documento, $fechaNacimiento, $telefono, $email, $tipo, $lugar, $direccion, $fecha, $horario, $cobertura, $aseguradora, $poliza, $autorizacion, $nombreArchivo, $indicacionesLeidas, $consentimiento);
    } else {
        $stmt = $conn->prepare('INSERT INTO solicitudes_servicio (user_id, nombre, documento, fecha_nacimiento, telefono, email, tipo_servicio, lugar, direccion, fecha_preferida, horario_preferido, cobertura, aseguradora, poliza, autorizacion, orden_archivo, indicaciones_leidas, consentimiento_datos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('i' . str_repeat('s', 15) . 'ii', $userId, $nombre, $documento, $fechaNacimiento, $telefono, $email, $tipo, $lugar, $direccion, $fecha, $horario, $cobertura, $aseguradora, $poliza, $autorizacion, $nombreArchivo, $indicacionesLeidas, $consentimiento);
    }
    $stmt->execute();
    $solicitudId = (int)$conn->insert_id;
} catch (Throwable $e) {
    if ($rutaArchivo && is_file($rutaArchivo)) unlink($rutaArchivo);
    volverServicio(true);
}

require_once __DIR__ . '/notificar_cita.php';
$envios = notificarServicio([
    'id' => $solicitudId,
    'nombre' => $nombre,
    'documento' => $documento,
    'fecha_nacimiento' => $fechaNacimiento,
    'telefono' => $telefono,
    'email' => $email,
    'tipo' => $tipo,
    'lugar' => $lugar,
    'direccion' => $direccion,
    'fecha' => $fecha,
    'horario' => $horario,
    'cobertura' => $cobertura,
    'aseguradora' => $aseguradora,
    'poliza' => $poliza,
    'autorizacion' => $autorizacion,
    'indicaciones_leidas' => 'Confirmadas por el paciente',
    'adjunto' => $rutaArchivo ? ['path' => $rutaArchivo, 'mime' => $mime, 'name' => 'orden-medica.' . pathinfo($nombreArchivo, PATHINFO_EXTENSION)] : null,
]);
$estadoCorreo = $envios['paciente'] && $envios['creador'] ? 'enviado' : (($envios['paciente'] || $envios['creador']) ? 'parcial' : 'pendiente');
try {
    $actualizar = $conn->prepare('UPDATE solicitudes_servicio SET correo_estado = ? WHERE id = ?');
    $actualizar->bind_param('si', $estadoCorreo, $solicitudId);
    $actualizar->execute();
} catch (mysqli_sql_exception $e) {
    // La solicitud ya quedó registrada; conserva su estado y muestra el resultado del correo.
}

volverServicio(false, $estadoCorreo === 'enviado' ? 'ok' : ($estadoCorreo === 'parcial' ? 'partial' : 'pending'));
