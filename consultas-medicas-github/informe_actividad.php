<?php
declare(strict_types=1);

// Este proceso es exclusivo de la tarea programada de Windows; no se ejecuta desde el navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/conexion.php';
require_once __DIR__ . '/notificar_cita.php';

$conn->query("CREATE TABLE IF NOT EXISTS vitalia_informe_cursor (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    ultima_cita_id INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_servicio_id INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_usuario_id INT UNSIGNED NOT NULL DEFAULT 0,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Actualiza la tabla de seguimiento ya creada antes de incorporar las cuentas al informe.
$columnasCursor = $conn->query("SHOW COLUMNS FROM vitalia_informe_cursor LIKE 'ultimo_usuario_id'");
if ($columnasCursor->num_rows === 0) {
    $conn->query('ALTER TABLE vitalia_informe_cursor ADD COLUMN ultimo_usuario_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER ultimo_servicio_id');
    $maxUsuarioPrevio = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS maximo FROM users')->fetch_assoc()['maximo'];
    $conn->query('UPDATE vitalia_informe_cursor SET ultimo_usuario_id = ' . $maxUsuarioPrevio . ' WHERE id = 1');
}

$lock = $conn->query("SELECT GET_LOCK('vitalia_informe_cada_5_minutos', 0) AS tomado")->fetch_assoc();
if (!$lock || (int)$lock['tomado'] !== 1) exit(0);

try {
    $estado = $conn->query('SELECT ultima_cita_id, ultimo_servicio_id, ultimo_usuario_id FROM vitalia_informe_cursor WHERE id = 1')->fetch_assoc();
    if (!$estado) {
        // La primera ejecución marca el punto de inicio y evita enviar de golpe el historial anterior.
        $maxCita = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS maximo FROM solicitudes_cita')->fetch_assoc()['maximo'];
        $maxServicio = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS maximo FROM solicitudes_servicio')->fetch_assoc()['maximo'];
        $maxUsuario = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS maximo FROM users')->fetch_assoc()['maximo'];
        $inicializar = $conn->prepare('INSERT INTO vitalia_informe_cursor (id, ultima_cita_id, ultimo_servicio_id, ultimo_usuario_id) VALUES (1, ?, ?, ?)');
        $inicializar->bind_param('iii', $maxCita, $maxServicio, $maxUsuario);
        $inicializar->execute();
        echo "INICIO_OK\n";
        exit(0);
    }

    $desdeCita = (int)$estado['ultima_cita_id'];
    $desdeServicio = (int)$estado['ultimo_servicio_id'];
    $desdeUsuario = (int)$estado['ultimo_usuario_id'];
    $hastaCita = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS maximo FROM solicitudes_cita')->fetch_assoc()['maximo'];
    $hastaServicio = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS maximo FROM solicitudes_servicio')->fetch_assoc()['maximo'];
    $hastaUsuario = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS maximo FROM users')->fetch_assoc()['maximo'];

    $citas = [];
    if ($hastaCita > $desdeCita) {
        $stmt = $conn->prepare('SELECT id, nombre, documento, fecha_nacimiento, sexo, telefono, email, especialidad, medico_preferido, fecha_preferida, horario_preferido, modalidad, cobertura, aseguradora, poliza, autorizacion, estado, creado_en FROM solicitudes_cita WHERE id > ? AND id <= ? ORDER BY id ASC');
        $stmt->bind_param('ii', $desdeCita, $hastaCita);
        $stmt->execute();
        $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $servicios = [];
    if ($hastaServicio > $desdeServicio) {
        $stmt = $conn->prepare('SELECT id, nombre, documento, fecha_nacimiento, telefono, email, tipo_servicio, lugar, direccion, fecha_preferida, horario_preferido, cobertura, aseguradora, poliza, autorizacion, orden_archivo, estado, creado_en FROM solicitudes_servicio WHERE id > ? AND id <= ? ORDER BY id ASC');
        $stmt->bind_param('ii', $desdeServicio, $hastaServicio);
        $stmt->execute();
        $servicios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $usuarios = [];
    if ($hastaUsuario > $desdeUsuario) {
        // Nunca se consulta ni se envía el campo de contraseña.
        $stmt = $conn->prepare('SELECT id, firstName, lastName, email FROM users WHERE id > ? AND id <= ? ORDER BY id ASC');
        $stmt->bind_param('ii', $desdeUsuario, $hastaUsuario);
        $stmt->execute();
        $usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    if (!$citas && !$servicios && !$usuarios) exit(0);

    $esc = static function ($valor): string { return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    $card = static function (string $titulo, string $referencia, array $campos, string $estado, string $registrado) use ($esc): string {
        $filas = '';
        foreach ($campos as $etiqueta => $valor) {
            $filas .= '<tr><td style="padding:9px 12px;border-bottom:1px solid #e8efeb;color:#6d817a;font-size:12px;width:38%">' . $esc($etiqueta) . '</td><td style="padding:9px 12px;border-bottom:1px solid #e8efeb;color:#183d39;font-size:12px;font-weight:600">' . $esc($valor === '' || $valor === null ? 'No indicado' : $valor) . '</td></tr>';
        }
        return '<section style="margin:0 0 18px;padding:18px;border:1px solid #e2ebe5;border-radius:8px"><h2 style="margin:0 0 4px;color:#174f47;font-size:17px">' . $esc($titulo) . '</h2><p style="margin:0 0 12px;color:#71827b;font-size:12px">' . $esc($referencia) . ' · Estado: <strong>' . $esc($estado) . '</strong> · Registro: ' . $esc($registrado) . '</p><table role="presentation" style="width:100%;border-collapse:collapse">' . $filas . '</table></section>';
    };

    $contenido = '';
    foreach ($citas as $cita) {
        $contenido .= $card('Cita médica', 'CITA-' . $cita['id'], [
            'Paciente' => $cita['nombre'], 'Documento' => $cita['documento'], 'Nacimiento' => $cita['fecha_nacimiento'],
            'Sexo' => $cita['sexo'], 'Teléfono' => $cita['telefono'], 'Correo' => $cita['email'],
            'Especialidad' => $cita['especialidad'], 'Doctor preferido' => $cita['medico_preferido'],
            'Fecha preferida' => $cita['fecha_preferida'], 'Horario' => $cita['horario_preferido'],
            'Modalidad' => $cita['modalidad'], 'Cobertura' => $cita['cobertura'], 'Aseguradora' => $cita['aseguradora'],
            'Póliza/carné' => $cita['poliza'], 'Autorización' => $cita['autorizacion'],
        ], ucfirst($cita['estado']), $cita['creado_en']);
    }
    foreach ($servicios as $servicio) {
        $contenido .= $card('Servicio médico', 'SERVICIO-' . $servicio['id'], [
            'Paciente' => $servicio['nombre'], 'Documento' => $servicio['documento'], 'Nacimiento' => $servicio['fecha_nacimiento'],
            'Teléfono' => $servicio['telefono'], 'Correo' => $servicio['email'], 'Servicio requerido' => $servicio['tipo_servicio'],
            'Lugar' => $servicio['lugar'], 'Dirección/referencias' => $servicio['direccion'],
            'Fecha preferida' => $servicio['fecha_preferida'], 'Horario' => $servicio['horario_preferido'],
            'Cobertura' => $servicio['cobertura'], 'Aseguradora' => $servicio['aseguradora'],
            'Póliza/carné' => $servicio['poliza'], 'Autorización' => $servicio['autorizacion'],
            'Orden médica adjunta' => $servicio['orden_archivo'] ? 'Sí (enviada con el aviso inmediato)' : 'No',
        ], ucfirst(str_replace('_', ' ', $servicio['estado'])), $servicio['creado_en']);
    }
    foreach ($usuarios as $usuario) {
        $contenido .= $card('Cuenta nueva', 'USUARIO-' . $usuario['id'], [
            'Nombre' => trim($usuario['firstName'] . ' ' . $usuario['lastName']),
            'Correo' => $usuario['email'],
            'Contraseña' => 'Dato protegido; no se incluye en el informe',
        ], 'Registrada', 'Detectada en este ciclo');
    }

    $cantidad = count($citas) + count($servicios) + count($usuarios);
    $ahora = (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))->format('d/m/Y H:i');
    $html = '<!doctype html><html lang="es"><body style="margin:0;padding:24px;background:#f2f6f2;font-family:Arial,sans-serif;color:#183d39"><div style="max-width:720px;margin:0 auto;background:#fff;border:1px solid #e2ebe5;border-radius:12px;overflow:hidden"><header style="padding:26px 30px;background:#174f47;color:#fff"><div style="font-size:13px;letter-spacing:2px;color:#f2b79b;font-weight:bold">✳ VITALIA</div><h1 style="margin:12px 0 5px;font-size:23px">Informe de actividad</h1><p style="margin:0;color:#d5e7df;font-size:13px">' . $cantidad . ' movimiento(s) nuevo(s) desde el último informe · ' . $esc($ahora) . ' (Perú)</p></header><main style="padding:24px 26px">' . $contenido . '</main><footer style="padding:16px 28px;background:#f7f9f6;color:#809089;font-size:11px">Resumen automático de cuentas nuevas y solicitudes de citas y servicios. Se ejecuta cada 5 minutos; no se envía si no hay actividad.</footer></div></body></html>';

    $config = require __DIR__ . '/config/correo_config.php';
    $asunto = 'Informe de actividad Vitalia | ' . $cantidad . ' movimiento(s) nuevo(s)';
    if (!enviarCorreoSmtp($config, $config['creator_email'] ?? '', $asunto, $html, '', null, true)) {
        fwrite(STDERR, "SMTP_FALLO; se reintentará en el siguiente intervalo.\n");
        exit(1);
    }

    $actualizar = $conn->prepare('UPDATE vitalia_informe_cursor SET ultima_cita_id = ?, ultimo_servicio_id = ?, ultimo_usuario_id = ? WHERE id = 1');
    $actualizar->bind_param('iii', $hastaCita, $hastaServicio, $hastaUsuario);
    $actualizar->execute();
    echo "INFORME_ENVIADO {$cantidad}\n";
} finally {
    $conn->query("SELECT RELEASE_LOCK('vitalia_informe_cada_5_minutos')");
}
