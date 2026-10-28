<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$usuario = 'root';
$contrasena = ''; // Cambia esto si configuraste una contraseña para MySQL.
$baseDatos = 'vitalia';

$conn = new mysqli($host, $usuario, $contrasena, $baseDatos);
$conn->set_charset('utf8mb4');
