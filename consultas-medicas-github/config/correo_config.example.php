<?php
// Copia este archivo como config/correo_config.php y coloca tus propios datos SMTP.
// Para Gmail usa una contraseña de aplicación, nunca tu contraseña normal.
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls',
    'smtp_username' => 'tu-correo@gmail.com',
    'smtp_password' => 'PON_AQUI_TU_CLAVE_DE_APLICACION',
    'from_email' => 'tu-correo@gmail.com',
    'from_name' => 'Vitalia',
    'creator_email' => 'tu-correo@gmail.com',
];