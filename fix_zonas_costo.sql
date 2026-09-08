-- Añadir columna costo_envio a zonas_envio si no existe
ALTER TABLE `zonas_envio` ADD COLUMN IF NOT EXISTS `costo_envio` DECIMAL(10,2) DEFAULT NULL COMMENT 'Costo de envío específico para esta zona. NULL = usar costo global';

-- Verificar
SELECT id, nombre, costo_envio FROM zonas_envio;
