<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

function volverCita(string $resultado)
{
    $destino = $resultado === 'error' ? 'index.php?error=1#cita' : 'index.php?enviado=1&correo=' . rawurlencode($resultado) . '#cita';
    header('Location: ' . $destino);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#cita');
    exit;
}
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) volverCita('error');

$nombre = trim(preg_replace('/[\r\n]+/', ' ', $_POST['nombre'] ?? ''));
$documento = strtoupper(trim($_POST['documento'] ?? ''));
$fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$sexoEntrada = trim($_POST['sexo'] ?? '');
$sexo = $sexoEntrada === '' ? null : $sexoEntrada;
$telefono = trim(preg_replace('/[\r\n]+/', ' ', $_POST['telefono'] ?? ''));
$email = isset($_SESSION['user_id']) ? ($_SESSION['email'] ?? '') : strtolower(trim($_POST['email'] ?? ''));
$especialidad = trim($_POST['especialidad'] ?? '');
$medico = trim($_POST['medico_preferido'] ?? '');
$fecha = trim($_POST['fecha'] ?? '');
$horario = trim($_POST['horario_preferido'] ?? '');
$modalidad = trim($_POST['modalidad'] ?? '');
$cobertura = trim($_POST['cobertura'] ?? '');
$aseguradora = trim(preg_replace('/[\r\n]+/', ' ', $_POST['aseguradora'] ?? ''));
$poliza = trim($_POST['poliza'] ?? '');
$autorizacion = trim($_POST['autorizacion'] ?? '');
$permitidas = ['Medicina general', 'Pediatría', 'Cardiología', 'Dermatología'];
$doctores = ['Dra. Ana Morales' => 'Medicina general', 'Dr. Luis Herrera' => 'Pediatría', 'Dra. Camila Ríos' => 'Cardiología', 'Dra. Elena Paredes' => 'Dermatología'];
$nacimientoValido = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
$fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);

if ($nombre === '' || mb_strlen($nombre) > 120 || !preg_match('/^[0-9A-Z.-]{6,20}$/', $documento) ||
    !$nacimientoValido || $nacimientoValido->format('Y-m-d') !== $fechaNacimiento || $fechaNacimiento > date('Y-m-d') ||
    ($sexo !== null && !in_array($sexo, ['Femenino', 'Masculino', 'Otro'], true)) || $telefono === '' || mb_strlen($telefono) > 30 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180 ||
    !in_array($especialidad, $permitidas, true) || ($medico !== '' && (($doctores[$medico] ?? '') !== $especialidad)) ||
    !$fechaValida || $fechaValida->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d') ||
    !in_array($horario, ['Mañana', 'Tarde', 'Sin preferencia'], true) || !in_array($modalidad, ['Presencial', 'Virtual'], true) ||
    !in_array($cobertura, ['particular', 'seguro'], true) || ($cobertura === 'seguro' && ($aseguradora === '' || $poliza === '')) ||
    ($_POST['privacidad'] ?? '') !== '1') {
    volverCita('error');
}

try {
    require __DIR__ . '/conexion.php';
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    if ($userId === null) {
        $stmt = $conn->prepare('INSERT INTO solicitudes_cita (nombre, documento, fecha_nacimiento, sexo, telefono, email, especialidad, medico_preferido, fecha_preferida, horario_preferido, modalidad, cobertura, aseguradora, poliza, autorizacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(str_repeat('s', 15), $nombre, $documento, $fechaNacimiento, $sexo, $telefono, $email, $especialidad, $medico, $fecha, $horario, $modalidad, $cobertura, $aseguradora, $poliza, $autorizacion);
    } else {
        $stmt = $conn->prepare('INSERT INTO solicitudes_cita (user_id, nombre, documento, fecha_nacimiento, sexo, telefono, email, especialidad, medico_preferido, fecha_preferida, horario_preferido, modalidad, cobertura, aseguradora, poliza, autorizacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('i' . str_repeat('s', 15), $userId, $nombre, $documento, $fechaNacimiento, $sexo, $telefono, $email, $especialidad, $medico, $fecha, $horario, $modalidad, $cobertura, $aseguradora, $poliza, $autorizacion);
    }
    $stmt->execute();
    $citaId = (int)$conn->insert_id;

    require_once __DIR__ . '/notificar_cita.php';
    $envios = notificarCita(['id' => $citaId, 'nombre' => $nombre, 'documento' => $documento, 'fecha_nacimiento' => $fechaNacimiento, 'sexo' => $sexo, 'telefono' => $telefono, 'email' => $email, 'especialidad' => $especialidad, 'medico' => $medico, 'fecha' => $fecha, 'horario' => $horario, 'modalidad' => $modalidad, 'cobertura' => $cobertura, 'aseguradora' => $aseguradora, 'poliza' => $poliza, 'autorizacion' => $autorizacion]);
    $estadoCorreo = $envios['paciente'] && $envios['creador'] ? 'enviado' : (($envios['paciente'] || $envios['creador']) ? 'parcial' : 'pendiente');
    $actualizar = $conn->prepare('UPDATE solicitudes_cita SET correo_estado = ? WHERE id = ?');
    $actualizar->bind_param('si', $estadoCorreo, $citaId);
    $actualizar->execute();
} catch (Throwable $e) {
    volverCita('error');
}

volverCita($estadoCorreo === 'enviado' ? 'ok' : ($estadoCorreo === 'parcial' ? 'partial' : 'pending'));
