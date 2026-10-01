-- database/migration_fase3_add_rol_proveedor.sql
-- Fase 3: Agregar el valor 'proveedor' al ENUM de la columna rol en la tabla usuarios.

ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM('admin','supervisor','proveedor','cliente') NOT NULL DEFAULT 'cliente';
