<?php
declare(strict_types=1);

function leerRespuestaSmtp($socket): string
{
    $respuesta = '';
    do {
        $linea = fgets($socket, 515);
        if ($linea === false) throw new RuntimeException('El servidor SMTP cerró la conexión.');
        $respuesta .= $linea;
    } while (isset($linea[3]) && $linea[3] === '-');
    return $respuesta;
}

function comandoSmtp($socket, string $comando, array $codigos): string
{
    if (fwrite($socket, $comando . "\r\n") === false) throw new RuntimeException('No se pudo escribir en el servidor SMTP.');
    $respuesta = leerRespuestaSmtp($socket);
    if (!in_array((int)substr($respuesta, 0, 3), $codigos, true)) throw new RuntimeException('El servidor SMTP rechazó una operación.');
    return $respuesta;
}

function enviarCorreoSmtp(array $config, string $destinatario, string $asunto, string $cuerpo, string $responderA = '', ?array $adjunto = null, bool $html = false): bool
{
    $host = trim($config['smtp_host'] ?? '');
    $usuario = trim($config['smtp_username'] ?? '');
    // Google puede mostrar las contraseñas de aplicación agrupadas con espacios.
    $clave = preg_replace('/\s+/', '', (string)($config['smtp_password'] ?? ''));
    $remitente = trim($config['from_email'] ?? '');
    $puerto = (int)($config['smtp_port'] ?? 587);
    $cifrado = $config['smtp_encryption'] ?? 'tls';
    if ($host === '' || $usuario === '' || $clave === '' || !filter_var($remitente, FILTER_VALIDATE_EMAIL) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) return false;

    $socket = null;
    try {
        $contexto = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $prefijo = $cifrado === 'ssl' ? 'ssl://' : 'tcp://';
        $socket = @stream_socket_client($prefijo . $host . ':' . $puerto, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $contexto);
        if (!$socket) throw new RuntimeException('No se pudo conectar con SMTP.');
        stream_set_timeout($socket, 12);
        $saludo = leerRespuestaSmtp($socket);
        if ((int)substr($saludo, 0, 3) !== 220) throw new RuntimeException('Saludo SMTP no válido.');
        comandoSmtp($socket, 'EHLO vitalia.local', [250]);
        if ($cifrado === 'tls') {
            comandoSmtp($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('No se pudo activar TLS.');
            comandoSmtp($socket, 'EHLO vitalia.local', [250]);
        }
        comandoSmtp($socket, 'AUTH LOGIN', [334]);
        comandoSmtp($socket, base64_encode($usuario), [334]);
        comandoSmtp($socket, base64_encode($clave), [235]);
        comandoSmtp($socket, 'MAIL FROM:<' . $remitente . '>', [250]);
        comandoSmtp($socket, 'RCPT TO:<' . $destinatario . '>', [250, 251]);
        comandoSmtp($socket, 'DATA', [354]);

        $nombreRemitente = '=?UTF-8?B?' . base64_encode($config['from_name'] ?? 'Vitalia') . '?=';
        $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
        $encabezados = [
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'From: ' . $nombreRemitente . ' <' . $remitente . '>',
            'To: <' . $destinatario . '>',
            'Subject: ' . $asuntoCodificado,
            'MIME-Version: 1.0',
        ];
        if ($responderA !== '' && filter_var($responderA, FILTER_VALIDATE_EMAIL)) $encabezados[] = 'Reply-To: ' . $responderA;
        if ($adjunto && is_file($adjunto['path'] ?? '') && in_array($adjunto['mime'] ?? '', ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            $limite = 'vitalia_' . bin2hex(random_bytes(12));
            $encabezados[] = 'Content-Type: multipart/mixed; boundary="' . $limite . '"';
            $mensaje = implode("\r\n", $encabezados) . "\r\n\r\n";
            $tipoCuerpo = $html ? 'text/html' : 'text/plain';
            $mensaje .= '--' . $limite . "\r\nContent-Type: {$tipoCuerpo}; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
            $mensaje .= chunk_split(base64_encode($cuerpo), 76, "\r\n");
            $contenido = file_get_contents($adjunto['path']);
            if ($contenido === false || strlen($contenido) > 5 * 1024 * 1024) throw new RuntimeException('No se pudo leer el adjunto.');
            $nombreAdjunto = preg_replace('/[^A-Za-z0-9._-]/', '_', $adjunto['name'] ?? 'orden-medica');
            $mensaje .= '--' . $limite . "\r\nContent-Type: " . $adjunto['mime'] . "; name=\"{$nombreAdjunto}\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"{$nombreAdjunto}\"\r\n\r\n";
            $mensaje .= chunk_split(base64_encode($contenido), 76, "\r\n") . '--' . $limite . "--\r\n";
        } else {
            $encabezados[] = 'Content-Type: ' . ($html ? 'text/html' : 'text/plain') . '; charset=UTF-8';
            $encabezados[] = 'Content-Transfer-Encoding: base64';
            $mensaje = implode("\r\n", $encabezados) . "\r\n\r\n" . chunk_split(base64_encode($cuerpo), 76, "\r\n");
        }
        if (fwrite($socket, $mensaje . "\r\n.\r\n") === false) throw new RuntimeException('No se pudo enviar el mensaje.');
        $respuesta = leerRespuestaSmtp($socket);
        if ((int)substr($respuesta, 0, 3) !== 250) throw new RuntimeException('SMTP no aceptó el mensaje.');
        comandoSmtp($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        if (is_resource($socket)) fclose($socket);
        return false;
    }
}

function plantillaInformeVitalia(string $tipo, string $referencia, array $campos, string $estado): string
{
    $escape = static function ($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    $filas = '';
    foreach ($campos as $etiqueta => $valor) {
        $filas .= '<tr><td style="padding:12px 14px;border-bottom:1px solid #e8efeb;color:#6d817a;font-size:13px;width:38%">' . $escape($etiqueta) . '</td><td style="padding:12px 14px;border-bottom:1px solid #e8efeb;color:#183d39;font-size:13px;font-weight:600">' . $escape($valor === '' || $valor === null ? 'No indicado' : $valor) . '</td></tr>';
    }
    $fecha = (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))->format('d/m/Y, H:i');
    return '<!doctype html><html lang="es"><body style="margin:0;padding:24px;background:#f2f6f2;font-family:Arial,sans-serif;color:#183d39"><div style="max-width:680px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2ebe5"><div style="padding:28px 32px;background:#174f47;color:#fff"><div style="font-size:13px;letter-spacing:2px;color:#f2b79b;font-weight:bold">✳ VITALIA</div><h1 style="margin:14px 0 5px;font-size:24px">Nuevo movimiento registrado</h1><p style="margin:0;color:#d5e7df;font-size:14px">' . $escape($tipo) . ' · ' . $escape($referencia) . '</p></div><div style="padding:28px 32px"><div style="margin:0 0 20px;padding:13px 16px;background:#edf6f0;border-left:4px solid #2c806a;border-radius:4px"><span style="font-size:12px;color:#678078">ESTADO</span><br><strong style="display:inline-block;margin-top:5px;color:#245f50">' . $escape($estado) . '</strong></div><table role="presentation" style="width:100%;border-collapse:collapse">' . $filas . '</table><p style="margin:22px 0 0;color:#71827b;font-size:12px">Registrado el ' . $escape($fecha) . ' (hora de Perú).</p></div><div style="padding:18px 32px;background:#f7f9f6;color:#809089;font-size:11px">Informe automático del sitio web Vitalia · No responder a este mensaje.</div></div></body></html>';
}

function notificarCita(array $cita): array
{
    $config = require __DIR__ . '/config/correo_config.php';
    $nombre = trim(preg_replace('/[\r\n]+/', ' ', $cita['nombre']));
    $email = trim($cita['email']);
    $telefono = trim(preg_replace('/[\r\n]+/', ' ', $cita['telefono']));
    $especialidad = trim(preg_replace('/[\r\n]+/', ' ', $cita['especialidad']));
    $medico = trim($cita['medico'] ?? '') ?: 'Sin preferencia';
    $fecha = trim($cita['fecha']);
    $horario = trim($cita['horario'] ?? '');
    $modalidad = trim($cita['modalidad'] ?? '');

    $valor = static function ($clave) use ($cita) { $dato = trim((string)($cita[$clave] ?? '')); return $dato === '' ? 'No indicado' : $dato; };
    $campos = ['Nombre completo' => $nombre, 'Documento' => $valor('documento'), 'Fecha de nacimiento' => $valor('fecha_nacimiento'), 'Sexo' => $valor('sexo'), 'Teléfono' => $telefono, 'Correo' => $email, 'Especialidad' => $especialidad, 'Doctor preferido' => $medico, 'Fecha preferida' => $fecha, 'Horario' => $horario, 'Modalidad' => $modalidad, 'Cobertura' => $valor('cobertura'), 'Aseguradora' => $valor('aseguradora'), 'Póliza/carné' => $valor('poliza'), 'Autorización' => $valor('autorizacion')];
    $referencia = 'CITA-' . (int)($cita['id'] ?? 0);
    $informe = plantillaInformeVitalia('Solicitud de cita médica', $referencia, $campos, 'Pendiente de confirmación');
    $alPaciente = enviarCorreoSmtp($config, $email, 'Informe de tu solicitud de cita | Vitalia', $informe, '', null, true);
    $alCreador = enviarCorreoSmtp($config, $config['creator_email'] ?? '', 'Nuevo movimiento: cita médica | Vitalia', $informe, $email, null, true);
    return ['paciente' => $alPaciente, 'creador' => $alCreador];
}

function notificarServicio(array $solicitud): array
{
    $config = require __DIR__ . '/config/correo_config.php';
    $nombre = trim(preg_replace('/[\r\n]+/', ' ', $solicitud['nombre']));
    $email = trim($solicitud['email']);
    $telefono = trim(preg_replace('/[\r\n]+/', ' ', $solicitud['telefono']));
    $tipo = trim(preg_replace('/[\r\n]+/', ' ', $solicitud['tipo']));
    $lugar = trim($solicitud['lugar']);
    $fecha = trim($solicitud['fecha']);
    $horario = trim($solicitud['horario']);
    $id = (int)$solicitud['id'];

    $valor = static function ($clave) use ($solicitud) { $dato = trim((string)($solicitud[$clave] ?? '')); return $dato === '' ? 'No indicado' : $dato; };
    $campos = ['Nombre completo' => $nombre, 'Documento' => $valor('documento'), 'Fecha de nacimiento' => $valor('fecha_nacimiento'), 'Teléfono' => $telefono, 'Correo' => $email, 'Servicio requerido' => $tipo, 'Lugar' => $lugar, 'Dirección y referencias' => $valor('direccion'), 'Fecha preferida' => $fecha, 'Horario preferido' => $horario, 'Cobertura' => $valor('cobertura'), 'Aseguradora' => $valor('aseguradora'), 'Póliza/carné' => $valor('poliza'), 'Autorización' => $valor('autorizacion'), 'Indicaciones previas' => $valor('indicaciones_leidas'), 'Orden médica adjunta' => !empty($solicitud['adjunto']['path']) ? 'Sí' : 'No'];
    $informe = plantillaInformeVitalia('Solicitud de servicio médico', 'SERVICIO-' . $id, $campos, 'Pendiente de confirmación');
    $adjunto = $solicitud['adjunto'] ?? null;
    $alPaciente = enviarCorreoSmtp($config, $email, 'Informe de tu solicitud de servicio | Vitalia', $informe, '', $adjunto, true);
    $alCreador = enviarCorreoSmtp($config, $config['creator_email'] ?? '', 'Nuevo movimiento: servicio médico | Vitalia', $informe, $email, $adjunto, true);
    return ['paciente' => $alPaciente, 'creador' => $alCreador];
}
