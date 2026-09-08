-- ==========================================================
-- migration_fk.sql - Foreign Keys para Fermento
-- INSTRUCCIONES: En phpMyAdmin, primero haz clic en tu base
-- de datos en el panel IZQUIERDO para seleccionarla, luego
-- pega este script en la pestana SQL y ejecutalo.
-- ==========================================================

-- Paso 1: Indices en detalles_pedido
ALTER TABLE detalles_pedido ADD INDEX idx_pedido_id (pedido_id);
ALTER TABLE detalles_pedido ADD INDEX idx_producto_id (producto_id);

-- Paso 2: FK detalles_pedido.pedido_id -> pedidos.id
-- (CASCADE: al borrar pedido, se borran sus detalles)
ALTER TABLE detalles_pedido
    ADD CONSTRAINT fk_detalles_pedido_id
    FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Paso 3: FK detalles_pedido.producto_id -> productos.id
-- (RESTRICT: no permite borrar un producto con pedidos)
ALTER TABLE detalles_pedido
    ADD CONSTRAINT fk_detalles_producto_id
    FOREIGN KEY (producto_id) REFERENCES productos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- Paso 4: FK producto_variantes.producto_id -> productos.id
-- (CASCADE: al borrar producto padre, se borran sus variantes)
ALTER TABLE producto_variantes
    ADD CONSTRAINT fk_variantes_producto_id
    FOREIGN KEY (producto_id) REFERENCES productos (id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Paso 5: Verificar resultado
SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME,
       REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
