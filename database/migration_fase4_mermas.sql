-- database/migration_fase4_mermas.sql
-- Fase 4: Crear tablas para el registro de merma de supervisores.

CREATE TABLE IF NOT EXISTS `mermas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` INT(11) NOT NULL,
  `supervisor_id` INT(11) NOT NULL,
  `supervisor_nombre` VARCHAR(150) NOT NULL,
  `notas` TEXT DEFAULT NULL,
  `confirmado` TINYINT(1) NOT NULL DEFAULT 0,
  `total_merma` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `supervisor_id` (`supervisor_id`),
  KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `merma_detalles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `merma_id` INT(11) NOT NULL,
  `producto_id` INT(11) DEFAULT NULL,
  `nombre_producto` VARCHAR(150) NOT NULL,
  `variante_id` INT(11) DEFAULT NULL,
  `variante_nombre` VARCHAR(200) DEFAULT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cantidad` INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `merma_id` (`merma_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
