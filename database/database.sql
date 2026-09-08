CREATE DATABASE IF NOT EXISTS `DB_fermento` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `DB_fermento`;

-- Tabla de Productos
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `categoria` VARCHAR(100),
  `stock` INT DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Variantes (por si usan atributos adicionales al producto base)
CREATE TABLE IF NOT EXISTS `producto_variantes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `stock` INT DEFAULT 0,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
);

-- Tabla de Pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_cliente` VARCHAR(255) DEFAULT 'Cliente Frecuente',
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `total` DECIMAL(10,2) NOT NULL,
  `estado` ENUM('pendiente', 'procesando', 'completado', 'cancelado') DEFAULT 'pendiente'
);

-- Tabla de Detalles de Pedido
CREATE TABLE IF NOT EXISTS `detalles_pedido` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `producto_id` INT,
  `nombre_producto` VARCHAR(255) NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE SET NULL
);

-- ==========================================
-- DATOS DE PRUEBA (MOCK DATA)
-- ==========================================

INSERT INTO `productos` (`id`, `nombre`, `precio`, `categoria`, `stock`) VALUES
(1, 'Pan de Masa Madre Clásico', 35.00, 'Panes Artesanales', 20),
(2, 'Baguette Rústica', 18.00, 'Panes Artesanales', 10),
(3, 'Croissant de Mantequilla', 15.00, 'Bollería', 8),
(4, 'Rol de Canela y Nuez', 22.00, 'Bollería', 4),
(5, 'Galletas Chocochips (Docena)', 45.00, 'Galletería', 15);

INSERT INTO `producto_variantes` (`id`, `producto_id`, `nombre`, `stock`) VALUES
(1, 1, 'Tamaño Grande', 10),
(2, 1, 'Tamaño Mediano', 10);

INSERT INTO `pedidos` (`id`, `nombre_cliente`, `fecha`, `total`, `estado`) VALUES
(1, 'Juan Pérez', '2026-08-15 10:30:00', 70.00, 'completado'),
(2, 'María González', '2026-08-18 14:15:00', 45.00, 'completado'),
(3, 'Cliente Mostrador', '2026-08-19 09:00:00', 33.00, 'pendiente'),
(4, 'Carlos Ruiz', '2026-08-20 08:30:00', 110.00, 'pendiente');

INSERT INTO `detalles_pedido` (`pedido_id`, `producto_id`, `nombre_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 'Pan de Masa Madre Clásico', 2, 35.00),
(2, 5, 'Galletas Chocochips (Docena)', 1, 45.00),
(3, 2, 'Baguette Rústica', 1, 18.00),
(3, 3, 'Croissant de Mantequilla', 1, 15.00),
(4, 1, 'Pan de Masa Madre Clásico', 1, 35.00),
(4, 3, 'Croissant de Mantequilla', 5, 15.00);
