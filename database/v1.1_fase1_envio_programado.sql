-- ================================================================
-- FERMENTO v1.1 — FASE 1 — PASO 2: ENVÍO PROGRAMADO (F1)
-- Ejecutar en HeidiSQL con DB_fermento seleccionada
-- ================================================================

USE DB_fermento;

-- 1. Columnas de fecha y hora en la tabla pedidos
-- NOTA: Si da error "Duplicate column", la columna ya existe — puedes saltarte este bloque.
ALTER TABLE pedidos
    ADD COLUMN fecha_envio_programada DATE NULL COMMENT 'Fecha elegida por el cliente para recibir el pedido',
    ADD COLUMN hora_envio_programada  TIME NULL COMMENT 'Hora elegida por el cliente para recibir el pedido';

-- 2. Tabla de días feriados (gestionada por el supervisor desde Configuración)
CREATE TABLE IF NOT EXISTS dias_feriados (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    fecha           DATE NOT NULL UNIQUE COMMENT 'Fecha del feriado (YYYY-MM-DD)',
    descripcion     VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nombre del feriado',
    creado_por      INT NULL COMMENT 'usuario_id que lo registró',
    fecha_registro  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Configuración de horarios de entrega
INSERT INTO configuracion (clave, valor) VALUES
    ('horario_lv_inicio',  '08:00')  ON DUPLICATE KEY UPDATE valor = VALUES(valor);
INSERT INTO configuracion (clave, valor) VALUES
    ('horario_lv_fin',     '17:00')  ON DUPLICATE KEY UPDATE valor = VALUES(valor);
INSERT INTO configuracion (clave, valor) VALUES
    ('horario_sab_inicio', '08:00')  ON DUPLICATE KEY UPDATE valor = VALUES(valor);
INSERT INTO configuracion (clave, valor) VALUES
    ('horario_sab_fin',    '12:00')  ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- ✅ VERIFICACIÓN
SELECT 'Columnas en pedidos' AS check_name,
       COUNT(*) AS encontradas
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'DB_fermento'
  AND TABLE_NAME   = 'pedidos'
  AND COLUMN_NAME IN ('fecha_envio_programada', 'hora_envio_programada');

SELECT 'Tabla dias_feriados' AS check_name,
       IF(COUNT(*) > 0, 'OK ✅', 'ERROR ❌') AS estado
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'DB_fermento'
  AND TABLE_NAME   = 'dias_feriados';

SELECT 'Config horarios' AS check_name, clave, valor
FROM configuracion
WHERE clave IN ('horario_lv_inicio','horario_lv_fin','horario_sab_inicio','horario_sab_fin');
