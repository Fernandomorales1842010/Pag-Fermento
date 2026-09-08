USE `DB_fermento`;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `telefono` VARCHAR(50),
  `direccion` TEXT,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin', 'supervisor', 'cliente') NOT NULL DEFAULT 'cliente',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Categorías
CREATE TABLE IF NOT EXISTS `categorias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `icono` VARCHAR(100) DEFAULT 'fas fa-bread-slice',
    `orden` INT DEFAULT 0,
    `activa` TINYINT(1) DEFAULT 1,
    UNIQUE (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Zonas de Envío
CREATE TABLE IF NOT EXISTS `zonas_envio` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `descripcion` TEXT,
    `activa` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Configuración (para settings globales)
CREATE TABLE IF NOT EXISTS `configuracion` (
    `llave` VARCHAR(100) PRIMARY KEY,
    `valor` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Cupones de Descuento
CREATE TABLE IF NOT EXISTS `cupones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `tipo` ENUM('porcentaje', 'fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` DECIMAL(10,2) NOT NULL,
  `fecha_expira` DATE,
  `usos_maximos` INT DEFAULT 100,
  `usos_actuales` INT DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1
);


-- ==========================================
-- DATOS DE PRUEBA PARTE 2
-- ==========================================

INSERT IGNORE INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `direccion`, `password`, `rol`) VALUES
(1, 'Administrador Principal', 'admin@fermento.com', '5555-5555', 'Zona 10, Local Fermento', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- Password es 'password'
(2, 'Cliente Ejemplo', 'cliente@ejemplo.com', '1234-5678', 'Zona 1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente'); 

INSERT IGNORE INTO `categorias` (`id`, `nombre`, `icono`, `orden`) VALUES
(1, 'Panes Artesanales', 'fas fa-bread-slice', 1),
(2, 'Bollería', 'fas fa-croissant', 2),
(3, 'Galletería', 'fas fa-cookie', 3);

INSERT IGNORE INTO `zonas_envio` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Zona 1, 2 y 3', 'Envío estándar zona céntrica'),
(2, 'Zona 9, 10 y 14', 'Envío rápido zona financiera');

INSERT IGNORE INTO `configuracion` (`llave`, `valor`) VALUES
('nombre_tienda', 'Fermento Panadería'),
('moneda', 'Q'),
('costo_envio_base', '25.00');

INSERT IGNORE INTO `cupones` (`codigo`, `tipo`, `valor`, `fecha_expira`) VALUES
('BIENVENIDA10', 'porcentaje', 10.00, '2099-12-31'),
('DESCUENTOQ50', 'fijo', 50.00, '2099-12-31');
