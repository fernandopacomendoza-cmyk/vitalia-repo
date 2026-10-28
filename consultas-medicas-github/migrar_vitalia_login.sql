-- Ejecuta este archivo una sola vez si ya habías importado vitalia.sql anteriormente.
USE vitalia;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  firstName VARCHAR(100) NOT NULL,
  lastName VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

ALTER TABLE solicitudes_cita
  ADD COLUMN user_id INT UNSIGNED NULL AFTER id,
  ADD COLUMN correo_estado ENUM('pendiente','parcial','enviado') NOT NULL DEFAULT 'pendiente' AFTER estado,
  ADD INDEX idx_solicitudes_cita_user_email (user_id, email);
