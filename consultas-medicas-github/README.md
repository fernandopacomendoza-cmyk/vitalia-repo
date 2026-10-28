# Vitalia — página de consultas médicas

Sitio responsive en PHP, HTML, CSS y JavaScript. Incluye páginas independientes, registro e inicio de sesión, consulta de citas de la cuenta y especialidades con información ampliada. Las solicitudes se guardan en MySQL.

## Abrir en XAMPP

1. Copia la carpeta `consultas-medicas` dentro de `C:\xampp\htdocs\`.
2. Abre XAMPP Control Panel e inicia **Apache** y **MySQL**.
3. Entra en `http://localhost/phpmyadmin`, abre **Importar** y selecciona `vitalia.sql`. Si Vitalia ya estaba instalado, importa una sola vez `migrar_vitalia_login.sql`, `migrar_vitalia_citas_detalle.sql` y `migrar_vitalia_servicios.sql`.
4. Abre `http://localhost/consultas-medicas/`.
5. Para editar el proyecto, abre la carpeta en Visual Studio Code.
6. El SMTP está preparado para Gmail (`smtp.gmail.com`, puerto 587/TLS) y usa `correo-equipo@ejemplo.com` como remitente y destinatario de coordinación. Para activar los envíos, copia `config/correo_config.example.php` como `config/correo_config.php` y configura allí tu cuenta SMTP y contraseña de aplicación; ese archivo está excluido de GitHub por seguridad; nunca uses ni compartas la contraseña normal. El sistema envía el informe completo de cada solicitud tanto a la dirección ingresada como al equipo Vitalia.

Si MySQL tiene contraseña, actualiza `$contrasena` en `conexion.php`. El teléfono, correo de contacto, dirección, horarios y perfiles que aparecen son datos de ejemplo; cámbialos antes de publicar. Hay dos formularios independientes: cita médica con preferencia de doctor, fecha, horario, modalidad y cobertura; y solicitud de servicios de laboratorio, imágenes, procedimientos o atención domiciliaria. La orden médica opcional se guarda en `storage/ordenes/`, protegida contra acceso directo desde el navegador, y se adjunta a ambos correos si se incluyó en la solicitud. SMTP debe configurarse para que los avisos se envíen; mientras tanto, la solicitud se guarda y la página indica que el correo está pendiente.

## Secciones incluidas

Inicio, Nosotros, Servicios médicos, Especialistas, Solicitud de cita, Mis citas, Horarios, Contacto, Preguntas frecuentes, Aviso de privacidad y Emergencias. El acceso ofrece inicio de sesión, creación de cuenta y una página para revisar solicitudes vinculadas al correo de la cuenta.

## Informe automático cada 5 minutos

En XAMPP se instala la tarea de Windows `Vitalia - Informe cada 5 minutos`. El proceso `informe_actividad.php` agrupa las nuevas cuentas registradas y las solicitudes de citas y servicios y envía un informe HTML a `creator_email` únicamente cuando hubo actividad desde el último envío. La primera ejecución crea el punto de inicio y no reenvía las solicitudes antiguas. Windows debe estar encendido, el usuario debe mantener su sesión iniciada y MySQL debe estar activo.
