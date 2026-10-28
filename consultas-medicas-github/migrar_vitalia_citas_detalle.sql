-- Ejecuta una sola vez en Vitalia para ampliar las citas médicas existentes.
USE vitalia;

ALTER TABLE solicitudes_cita
  ADD COLUMN documento VARCHAR(20) NULL AFTER nombre,
  ADD COLUMN fecha_nacimiento DATE NULL AFTER documento,
  ADD COLUMN sexo ENUM('Femenino','Masculino','Otro') NULL AFTER fecha_nacimiento,
  ADD COLUMN medico_preferido VARCHAR(120) NULL AFTER especialidad,
  ADD COLUMN horario_preferido ENUM('Mañana','Tarde','Sin preferencia') NULL AFTER fecha_preferida,
  ADD COLUMN modalidad ENUM('Presencial','Virtual') NULL AFTER horario_preferido,
  ADD COLUMN cobertura ENUM('particular','seguro') NULL AFTER modalidad,
  ADD COLUMN aseguradora VARCHAR(120) NULL AFTER cobertura,
  ADD COLUMN poliza VARCHAR(80) NULL AFTER aseguradora,
  ADD COLUMN autorizacion VARCHAR(80) NULL AFTER poliza;
