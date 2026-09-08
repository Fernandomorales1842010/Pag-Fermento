-- ==============================================================
--  FERMENTO — Script de instalacion completa de base de datos
--  Motor  : MySQL 5.7+ / MariaDB 10.3+
--  Charset: utf8mb4 / utf8mb4_unicode_ci
--
--  USO:
--    Desde linea de comandos:
--      mysql -u root -p < fermento_instalacion_completa.sql
--
--    Desde phpMyAdmin:
--      Importar este archivo (sin seleccionar ninguna BD primero).
--      El script crea y selecciona la BD automaticamente.
--
--  ADVERTENCIA: Si ya existe `DB_fermento`, este script la
--  elimina por completo y la recrea desde cero.
-- ==============================================================

SET SQL_MODE          = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone         = '+00:00';
SET NAMES utf8mb4;

/*!40014 SET @OLD_UNIQUE_CHECKS      = @@UNIQUE_CHECKS,      UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- --------------------------------------------------------------
-- 1. CREAR / SELECCIONAR BASE DE DATOS
-- --------------------------------------------------------------
DROP DATABASE IF EXISTS `DB_fermento`;
CREATE DATABASE `DB_fermento`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `DB_fermento`;

-- ==============================================================
-- 2. TABLAS (orden: sin FK primero)
-- ==============================================================

-- 2.1  categorias
CREATE TABLE `categorias` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`     VARCHAR(80)  NOT NULL,
  `icono`      VARCHAR(60)  DEFAULT 'fas fa-bread-slice',
  `orden`      INT(11)      DEFAULT 0,
  `activa`     TINYINT(1)   NOT NULL DEFAULT 1,
  `creado_en`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2  configuracion
CREATE TABLE `configuracion` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `clave`       VARCHAR(80)   NOT NULL,
  `valor`       TEXT          DEFAULT NULL,
  `descripcion` VARCHAR(200)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_configuracion_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3  usuarios
CREATE TABLE `usuarios` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(100) NOT NULL,
  `email`          VARCHAR(100) NOT NULL,
  `telefono`       VARCHAR(20)  DEFAULT NULL,
  `direccion`      TEXT         DEFAULT NULL,
  `password`       VARCHAR(255) DEFAULT NULL,
  `google_id`      VARCHAR(255) DEFAULT NULL,
  `avatar`         VARCHAR(255) DEFAULT 'default_avatar.png',
  `fecha_registro` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `rol`            ENUM('cliente','admin','supervisor') DEFAULT 'cliente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4  zonas_envio  (incluye columna costo_envio del fix_zonas_costo.sql)
CREATE TABLE `zonas_envio` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100)  NOT NULL,
  `descripcion` TEXT          DEFAULT NULL,
  `activa`      TINYINT(1)    NOT NULL DEFAULT 1,
  `costo_envio` DECIMAL(10,2) DEFAULT NULL
    COMMENT 'Costo especifico para esta zona. NULL = usar costo global',
  `creado_en`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_zonas_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5  cupones
CREATE TABLE `cupones` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `codigo`        VARCHAR(50)  NOT NULL,
  `tipo`          ENUM('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
  `valor`         DECIMAL(10,2) NOT NULL,
  `fecha_expira`  DATE         DEFAULT NULL,
  `usos_maximos`  INT          DEFAULT 100,
  `usos_actuales` INT          DEFAULT 0,
  `activo`        TINYINT(1)   DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cupones_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6  productos
CREATE TABLE `productos` (
  `id`                  INT(11)       NOT NULL AUTO_INCREMENT,
  `nombre`              VARCHAR(100)  NOT NULL,
  `descripcion`         TEXT          DEFAULT NULL,
  `maridaje`            TEXT          DEFAULT NULL,
  `precio`              DECIMAL(10,2) NOT NULL,
  `categoria`           VARCHAR(50)   NOT NULL,
  `imagen`              VARCHAR(255)  DEFAULT 'default_pan.png',
  `destacado`           TINYINT(1)    DEFAULT 0,
  `fecha_creacion`      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `stock`               INT(11)       DEFAULT 0,
  `oferta`              TINYINT(1)    DEFAULT 0,
  `tiene_variantes`     TINYINT(1)    DEFAULT 0,
  `imagen_2`            VARCHAR(255)  DEFAULT NULL,
  `imagen_3`            VARCHAR(255)  DEFAULT NULL,
  `sku`                 VARCHAR(60)   DEFAULT NULL,
  `precio_distribuidor` DECIMAL(10,2) DEFAULT NULL,
  `minimo_compra`       INT(11)       DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7  producto_variantes
CREATE TABLE `producto_variantes` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `producto_id`   INT(11)       NOT NULL,
  `tamano`        VARCHAR(80)   DEFAULT NULL,
  `sabor`         VARCHAR(80)   DEFAULT NULL,
  `nombre`        VARCHAR(200)  DEFAULT NULL,
  `precio`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock`         INT(11)       DEFAULT 0,
  `sku`           VARCHAR(60)   DEFAULT NULL,
  `minimo_compra` INT(11)       DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_variantes_producto_id` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.8  pedidos
CREATE TABLE `pedidos` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`      INT(11)       DEFAULT NULL,
  `nombre_cliente`  VARCHAR(100)  DEFAULT NULL,
  `direccion_envio` TEXT          DEFAULT NULL,
  `telefono`        VARCHAR(20)   DEFAULT NULL,
  `total`           DECIMAL(10,2) NOT NULL,
  `estado`          VARCHAR(20)   DEFAULT 'pendiente',
  `fecha`           DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `notas`           TEXT          DEFAULT NULL,
  `subtotal`        DECIMAL(10,2) DEFAULT 0.00,
  `costo_envio`     DECIMAL(10,2) DEFAULT 0.00,
  `descuento`       DECIMAL(10,2) DEFAULT 0.00,
  `zona_envio_id`   INT(11)       DEFAULT NULL,
  `cupon_id`        INT(11)       DEFAULT NULL,
  `cupon_codigo`    VARCHAR(50)   DEFAULT NULL,
  `metodo_contacto` VARCHAR(20)   DEFAULT 'normal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.9  detalles_pedido
CREATE TABLE `detalles_pedido` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `pedido_id`       INT(11)       NOT NULL,
  `producto_id`     INT(11)       NOT NULL,
  `nombre_producto` VARCHAR(100)  DEFAULT NULL,
  `precio_unitario` DECIMAL(10,2) DEFAULT NULL,
  `cantidad`        INT(11)       NOT NULL,
  `variante_id`     INT(11)       DEFAULT NULL,
  `variante_nombre` VARCHAR(200)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detalles_pedido_id`   (`pedido_id`),
  KEY `idx_detalles_producto_id` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- 3. LLAVES FORANEAS
-- ==============================================================
ALTER TABLE `producto_variantes`
  ADD CONSTRAINT `fk_variantes_producto_id`
  FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `fk_detalles_pedido_id`
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `fk_detalles_producto_id`
  FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

-- ==============================================================
-- 4. DATOS
-- ==============================================================

-- 4.1  categorias
INSERT INTO `categorias` (`id`, `nombre`, `icono`, `orden`, `activa`, `creado_en`) VALUES
(1, 'DULCE',     'fas fa-cookie',        1, 1, '2026-06-03 04:50:48'),
(2, 'SALADO',    'fas fa-bread-slice',   2, 1, '2026-06-03 04:50:48'),
(3, 'PANADERIA', 'fas fa-birthday-cake', 3, 1, '2026-06-03 04:50:48');

-- 4.2  configuracion
INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`) VALUES
(1, 'costo_envio',         '30.00',         'Costo de envio estandar en Quetzales'),
(2, 'envio_gratis_minimo', '0',             'Monto minimo para envio gratis (0 = desactivado)'),
(3, 'whatsapp_numero',     '50249420696',   'Numero de WhatsApp con codigo de pais'),
(4, 'nombre_tienda',       'Fermento',      'Nombre de la panaderia'),
(5, 'email_contacto',      'admin@gmail.com','Email de contacto principal'),
(6, 'moneda',              'Q',             'Simbolo de moneda'),
(7, 'zona_predeterminada', '1',             'ID de zona de envio por defecto'),
(8, 'pedidos_activos',     '1',             '1 = acepta pedidos, 0 = tienda cerrada'),
(9, 'mensaje_cerrado',     'Estamos preparando tu proximo pedido. Vuelve pronto.', 'Mensaje cuando la tienda esta cerrada');

-- 4.3  usuarios  (contrasenas hasheadas con bcrypt — NO modificar)
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `direccion`, `password`, `google_id`, `avatar`, `fecha_registro`, `rol`) VALUES
(1, 'Carlos Morales',   'admin@gmail.com',                  '49420696', 'L36 M21 colonia la trinidad San Juan sacatepequez', '$2y$10$wr7MXdshfkleDHs3zOhgnuVeGeBntzyo1wMJU1aPysvnREHXF6.0q', NULL, 'default_avatar.png', '2026-01-20 20:58:41', 'admin'),
(2, 'Dennis Ortiz',     'sistemas@grupopremia.com',         '47688774', '24 calle 1-05 zona 3',                              '$2y$10$Osm1qAhvut68RTNFxbmmP.a5q2Lyr33HjFFPIUlioZ7iHPpj9f2TG', NULL, 'default_avatar.png', '2026-01-21 23:08:27', 'cliente'),
(3, 'Fernando Morales', 'fernandomorales1842010@gmail.com', '49420696', 'L36M21 Colonia la Trinidad San Juan Sacatepequez',  '$2y$10$lKe7C53GCYh768lZYT98Ku7HFXX358pUvCfNOc12k1A/O3U1BRK8m', NULL, 'default_avatar.png', '2026-06-03 15:27:47', 'cliente');

-- 4.4  zonas_envio
INSERT INTO `zonas_envio` (`id`, `nombre`, `descripcion`, `activa`, `costo_envio`, `creado_en`) VALUES
( 1, 'Zona 3',  'Ciudad de Guatemala, no entregamos a zonas rojas', 1, NULL, '2026-06-03 04:50:48'),
( 5, 'Zona 1',  'No entregamos a zonas rojas',                      1, NULL, '2026-06-03 16:34:03'),
( 6, 'zona 4',  'Ciudad de Guatemala, no entregamos a zonas rojas', 1, NULL, '2026-06-03 16:34:35'),
( 7, 'zona 13', 'Ciudad de Guatemala, no entregamos a zonas rojas', 1, NULL, '2026-06-03 16:34:42'),
( 8, 'zona 21', 'Ciudad de Guatemala, no entregamos a zonas rojas', 1, NULL, '2026-06-03 16:34:48'),
( 9, 'zona 5',  'Ciudad de Guatemala, no entregamos a zonas rojas', 1, NULL, '2026-06-03 16:34:54'),
(10, 'zona 9',  'Ciudad de Guatemala, no entregamos a zonas rojas', 1, NULL, '2026-06-03 16:35:01');

-- 4.5  cupones
INSERT INTO `cupones` (`codigo`, `tipo`, `valor`, `fecha_expira`, `usos_maximos`, `usos_actuales`, `activo`) VALUES
('BIENVENIDA10', 'porcentaje', 10.00, '2099-12-31', 100, 0, 1),
('DESCUENTOQ50', 'fijo',       50.00, '2099-12-31', 100, 0, 1);

-- 4.6  productos (25 productos reales)
INSERT INTO `productos` (`id`,`nombre`,`descripcion`,`maridaje`,`precio`,`categoria`,`imagen`,`destacado`,`fecha_creacion`,`stock`,`oferta`,`tiene_variantes`,`imagen_2`,`imagen_3`,`sku`,`precio_distribuidor`,`minimo_compra`) VALUES
(17,'Linea de Pies','Deliciosa concha de pie horneada a la perfeccion, disponible en dos clasicos y exquisitos rellenos: queso tradicional o mermelada de pina.',NULL,10.00,'DULCE','pan_1780498669_200c.png',1,'2026-06-02 18:59:52',100,0,1,'pan_1780498669_f9d3.png','','PQ05.1 / PP05.2',NULL,26),
(18,'Linea de Strudels','Delicada y escamosa pasta de hojaldre con cobertura de azucar, disponible con abundantes rellenos a tu eleccion: mermelada de pina, mermelada de fresa o tradicional manjar.',NULL,8.00,'DULCE','pan_1780499003_54a9.png',1,'2026-06-02 18:59:52',100,0,1,'pan_1780499003_8072.png','','S04.H2.1 / C03.1j / S04.H2.2',NULL,48),
(19,'Linea de Donas','Suave dona disponible con diferentes coberturas (chocolate clasico, chocolate blanco o glaseado de fresa), y exquisitas decoraciones que incluyen coco, anicillos y mania.',NULL,10.00,'DULCE','pan_1780502062_d98a.png',0,'2026-06-02 18:59:52',100,0,1,'','','DACH12.1',NULL,1),
(20,'Croissant simple','Clasico croissant hecho con fina pasta de hojaldre semidulce sin relleno, ideal para acompanar tus bebidas favoritas.',NULL,8.00,'DULCE','pan_1780500840_60d5.png',0,'2026-06-02 18:59:52',100,0,0,'','','CO3',NULL,1),
(21,'Milhojas','Postre iconico de capas de hojaldre relleno de abundante manjar, decorado con cobertura de azucar.',NULL,10.00,'DULCE','pan_1780500814_9cf9.png',0,'2026-06-02 18:59:52',100,0,0,'','','S04.H2.2',NULL,1),
(22,'Panuelo de manzana','Suave panuelo de hojaldre relleno de deliciosa mermelada de fresa y finalizado con cobertura de azucar.',NULL,8.00,'DULCE','pan_1780501079_d568.png',1,'2026-06-02 18:59:52',100,0,0,'','','C03',NULL,1),
(23,'Muffin de vainilla con chispas de chocolate','Un bizcocho increiblemente esponjoso con rico sabor a vainilla, deliciosamente decorado con chispas de chocolate.',NULL,8.00,'DULCE','pan_1780501195_4a83.png',0,'2026-06-02 18:59:52',100,0,0,'pan_1780501195_2bee.png','','MV07.1',NULL,1),
(24,'Galletas con chispas de chocolate','La galleta perfecta: de textura crujiente y generosamente elaborada con chispas de chocolate.',NULL,5.50,'DULCE','pan_1780501407_876e.png',1,'2026-06-02 18:59:52',100,0,0,'pan_1780501407_15a9.png','','G08',NULL,1),
(25,'Enrollado de fresa','Postre crujiente elaborado como una deliciosa galleta con chispas de chocolate.',NULL,15.00,'DULCE','default_pan.png',0,'2026-06-02 18:59:52',0,0,0,NULL,NULL,'G08',NULL,1),
(26,'Champurradas','El clasico para compartir. Autenticas galletas crujientes y tostadas, realzadas con un exquisito toque de ajonjoli.',NULL,13.00,'DULCE','pan_1780501454_78fe.png',0,'2026-06-02 18:59:52',100,0,0,'','','CH06.1',NULL,1),
(27,'Churros','Deliciosos churros tostados y crujientes al morder, terminados con una dulce cobertura de azucar.',NULL,13.00,'DULCE','pan_1780501740_4040.png',1,'2026-06-02 18:59:52',100,0,0,'','','CH06.2',NULL,1),
(28,'Empanada de pollo','Empanada elaborada con crujiente pasta de hojaldre en forma de media luna, que envuelve un sabroso relleno de ensalada de pollo.',NULL,12.00,'SALADO','pan_1780502732_0d90.png',0,'2026-06-02 18:59:52',100,0,0,'pan_1780502795_12bc.png','','E04.H1',NULL,1),
(29,'Volovan de carne','Un bocado perfecto de pasta de hojaldre ligero y crujiente, generosamente rellena de carne muy bien sazonada.',NULL,12.00,'SALADO','pan_1780503884_880a.png',0,'2026-06-02 18:59:52',100,0,0,'','','VC04.H3',NULL,1),
(30,'Croissant de Jamon y queso','Increible croissant de fina pasta de hojaldre semidulce, con un clasico relleno salado de jamon y queso.',NULL,13.00,'SALADO','pan_1780502328_e11b.png',0,'2026-06-02 18:59:52',100,0,0,'','','C03.1j',NULL,1),
(31,'Bollo','Delicioso pan de corteza fina con una miga interior compacta y esponjosa, decorado por encima con semillas de ajonjoli.',NULL,18.00,'PANADERIA','pan_1780501940_bc30.png',0,'2026-06-02 18:59:52',100,0,0,'','','BOL.09',NULL,1),
(32,'Baguette','Pan frances alargado artesanal de 4 onzas, con una miga muy suave y porosa, y una cubierta nutritiva de ajonjoli, pepitoria y chia.',NULL,9.00,'PANADERIA','pan_1780501982_ed60.png',1,'2026-06-02 18:59:52',100,0,0,'','','B02',NULL,1),
(33,'Frances','El infaltable pan tradicional, horneado para lograr una corteza dorada y crujiente que contrasta con su miga maravillosamente suave y ligera.',NULL,5.00,'PANADERIA','pan_1780503602_d7be.png',0,'2026-06-02 18:59:52',100,0,0,'','','F01',NULL,1),
(34,'Trenza','Hermoso pan trenzado de sabor semidulce, que presenta una textura suave, esponjosa y una corteza ligeramente dorada cubierta con semillas de ajonjoli.',NULL,25.00,'PANADERIA','pan_1780502388_0e6f.png',0,'2026-06-02 18:59:52',100,0,0,'','','TRE10',NULL,1),
(35,'Concha Mexicana','Tradicional pan dulce cuya miga extra suave y esponjosa se complementa de maravilla con su caracteristica y deliciosa costra azucarada.',NULL,18.00,'PANADERIA','pan_1780502684_cd87.png',1,'2026-06-02 18:59:52',100,0,0,'','','CON11',NULL,1),
(36,'Pan de hamburguesa brioche','Bollo premium de masa madre suave y ligero. Elaborado con mantequilla y huevo para una miga esponjosa, terminado con una atractiva corteza brillante cubierta de ajonjoli.',NULL,7.50,'SALADO','pan_1780503216_9234.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.H14.1',NULL,1),
(37,'Pan de hot dog brioche','Sofisticado pan alargado de masa madre, con una textura muy suave y ligera gracias a su formulacion con mantequilla y huevo, coronado con una corteza brillante.',NULL,7.50,'SALADO','pan_1780503231_929f.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.HOT 14.2',NULL,1),
(38,'Pan de papa hamburguesa','Delicioso pan de masa madre caracterizado por ser muy suave, humedo y de un llamativo color dorado; su receta incorpora papa para balancear su sabor y lograr una miga delicada.',NULL,8.50,'SALADO','pan_1780503184_0dcb.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.HAM14.1.1',NULL,1),
(39,'Pan de papa hot dog','Un irresistible pan alargado a base de masa madre que incorpora papa en su formulacion, dandole una miga extremadamente tierna, humeda y de suave textura.',NULL,8.50,'SALADO','pan_1780503166_a3c0.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.HOT.PA 14.2.1',NULL,1),
(40,'Pan de molde brioche','Pan artesanal de masa madre en corte rectangular, profundamente enriquecido con mantequilla, leche y huevo, lo que resulta en una miga super esponjosa y una corteza suave.',NULL,30.00,'SALADO','pan_1780503137_54e4.png',1,'2026-06-02 18:59:52',100,0,0,'','','B.MOL.14.3',NULL,1),
(41,'Grissini','Delgados y apetitosos palitos de pan, perfectamente horneados hasta lograr una textura dorada y crujiente, sazonados con un toque de hierbas y estilo italiano.',NULL,18.00,'SALADO','pan_1780502989_a4f4.png',1,'2026-06-02 18:59:52',100,0,0,'','','B.HOT 14.2',NULL,1);

-- 4.7  producto_variantes (17 variantes)
INSERT INTO `producto_variantes` (`id`,`producto_id`,`tamano`,`sabor`,`nombre`,`precio`,`stock`,`sku`,`minimo_compra`) VALUES
( 1,17,NULL,'Queso','Queso',10.00,100,'PQ05.1 / PP05.2',1),
( 2,17,NULL,'Pina','Pina',10.00,100,'PQ05.1 / PP05.2',1),
( 3,18,NULL,'Pina','Pina',8.00,100,'S04.H2.1 / C03.1j / S04.H2.2',1),
( 4,18,NULL,'Fresa','Fresa',8.00,100,'S04.H2.1 / C03.1j / S04.H2.2',1),
( 5,18,NULL,'Manjar','Manjar',8.00,100,'S04.H2.1 / C03.1j / S04.H2.2',1),
( 6,36,'Caja 45 U.',NULL,'Caja 45 U.',8.50,100,'B.H14.1',1),
( 7,36,'Caja 90 U.',NULL,'Caja 90 U.',7.50,100,'B.H14.1',1),
( 8,37,'Caja 45 U.',NULL,'Caja 45 U.',8.50,100,'B.HOT 14.2',1),
( 9,37,'Caja 90 U.',NULL,'Caja 90 U.',7.50,100,'B.HOT 14.2',1),
(10,38,'Caja 45 U.',NULL,'Caja 45 U.',9.50,100,'B.HAM14.1.1',1),
(11,38,'Caja 90 U.',NULL,'Caja 90 U.',8.50,100,'B.HAM14.1.1',1),
(12,39,'Caja 45 U.',NULL,'Caja 45 U.',9.50,100,'B.HOT.PA 14.2.1',1),
(13,39,'Caja 90 U.',NULL,'Caja 90 U.',8.50,100,'B.HOT.PA 14.2.1',1),
(14,19,NULL,'Chocolate con mania','Chocolate con mania',10.00,100,NULL,1),
(15,19,NULL,'chocolate simple','chocolate simple',10.00,100,NULL,1),
(16,19,NULL,'fresa con chispas','fresa con chispas',10.00,100,NULL,1),
(17,19,NULL,'chocolate con chispas','chocolate con chispas',10.00,100,NULL,1);

-- pedidos y detalles_pedido se dejan vacios (instalacion limpia).
-- Para restaurar historial de pedidos, usa un mysqldump adicional.

-- ==============================================================
-- 5. RESTAURAR MODOS ORIGINALES
-- ==============================================================
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS      = @OLD_UNIQUE_CHECKS */;

-- ==============================================================
-- 6. VERIFICACION RAPIDA
-- ==============================================================
SELECT 'categorias'          AS tabla, COUNT(*) AS filas FROM categorias
UNION ALL SELECT 'configuracion',    COUNT(*) FROM configuracion
UNION ALL SELECT 'usuarios',         COUNT(*) FROM usuarios
UNION ALL SELECT 'zonas_envio',      COUNT(*) FROM zonas_envio
UNION ALL SELECT 'cupones',          COUNT(*) FROM cupones
UNION ALL SELECT 'productos',        COUNT(*) FROM productos
UNION ALL SELECT 'producto_variantes',COUNT(*) FROM producto_variantes
UNION ALL SELECT 'pedidos',          COUNT(*) FROM pedidos
UNION ALL SELECT 'detalles_pedido',  COUNT(*) FROM detalles_pedido;

-- FIN DEL SCRIPT
