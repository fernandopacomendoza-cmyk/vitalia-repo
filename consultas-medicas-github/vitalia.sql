CREATE DATABASE IF NOT EXISTS vitalia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vitalia;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  firstName VARCHAR(100) NOT NULL,
  lastName VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS solicitudes_cita (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  nombre VARCHAR(120) NOT NULL,
  documento VARCHAR(20) NULL,
  fecha_nacimiento DATE NULL,
  sexo ENUM('Femenino','Masculino','Otro') NULL,
  telefono VARCHAR(30) NOT NULL,
  email VARCHAR(180) NOT NULL,
  especialidad VARCHAR(60) NOT NULL,
  medico_preferido VARCHAR(120) NULL,
  fecha_preferida DATE NOT NULL,
  horario_preferido ENUM('Mañana','Tarde','Sin preferencia') NULL,
  modalidad ENUM('Presencial','Virtual') NULL,
  cobertura ENUM('particular','seguro') NULL,
  aseguradora VARCHAR(120) NULL,
  poliza VARCHAR(80) NULL,
  autorizacion VARCHAR(80) NULL,
  estado ENUM('pendiente','confirmada','cancelada') NOT NULL DEFAULT 'pendiente',
  correo_estado ENUM('pendiente','parcial','enviado') NOT NULL DEFAULT 'pendiente',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_solicitudes_cita_user_email (user_id, email)
);

CREATE TABLE IF NOT EXISTS solicitudes_servicio (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  nombre VARCHAR(120) NOT NULL,
  documento VARCHAR(20) NOT NULL,
  fecha_nacimiento DATE NOT NULL,
  telefono VARCHAR(30) NOT NULL,
  email VARCHAR(180) NOT NULL,
  tipo_servicio VARCHAR(120) NOT NULL,
  lugar ENUM('clínica','domicilio') NOT NULL,
  direccion VARCHAR(350) NULL,
  fecha_preferida DATE NOT NULL,
  horario_preferido ENUM('Mañana','Tarde','Sin preferencia') NOT NULL,
  cobertura ENUM('particular','seguro') NOT NULL,
  aseguradora VARCHAR(120) NULL,
  poliza VARCHAR(80) NULL,
  autorizacion VARCHAR(80) NULL,
  orden_archivo VARCHAR(80) NULL,
  indicaciones_leidas TINYINT(1) NOT NULL DEFAULT 0,
  consentimiento_datos TINYINT(1) NOT NULL DEFAULT 0,
  estado ENUM('pendiente','en_revision','confirmada','cancelada') NOT NULL DEFAULT 'pendiente',
  correo_estado ENUM('pendiente','parcial','enviado') NOT NULL DEFAULT 'pendiente',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_solicitudes_servicio_user_email (user_id, email)
);
