-- ================================================================
-- FERMENTO v1.1 — FASE 2 — PASO 3: MODIFICAR PEDIDO (F2)
-- Ejecutar en HeidiSQL con DB_fermento seleccionada
-- ================================================================

USE DB_fermento;

CREATE TABLE IF NOT EXISTS pedido_historial (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  usuario_id INT NOT NULL,
  campo_modificado VARCHAR(100),
  valor_anterior TEXT,
  valor_nuevo TEXT,
  motivo TEXT,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ✅ VERIFICACIÓN
SELECT 'Tabla pedido_historial' AS check_name,
       IF(COUNT(*) > 0, 'OK ✅', 'ERROR ❌') AS estado
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'DB_fermento'
  AND TABLE_NAME   = 'pedido_historial';
