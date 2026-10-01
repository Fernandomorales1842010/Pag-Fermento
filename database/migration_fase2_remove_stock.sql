-- database/migration_fase2_remove_stock.sql
-- Fase 2: Remoción definitiva de la columna `stock` tras transición a modelo bajo pedido.

ALTER TABLE productos DROP COLUMN stock;
ALTER TABLE producto_variantes DROP COLUMN stock;
