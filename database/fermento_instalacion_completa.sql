-- ==============================================================
--  FERMENTO — Script de instalacion completa de base de datos
--  Version : 1.2
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
--
--  HISTORIAL (consolidado en este archivo — ver CHANGELOG_v1.2.md
--  para el detalle de cada cambio):
--    v1.0  Instalacion base (categorias, configuracion, usuarios,
--          zonas_envio, cupones, productos, producto_variantes,
--          pedidos, detalles_pedido).
--    v1.1  + admin_logs (registro de actividad del panel admin)
--          + dias_feriados, columnas pedidos.fecha/hora_envio_programada
--            (envio programado, F1)
--          + pedido_historial (modificar pedido, F2)
--    v1.2  + productos.unidades_paquete (venta por lote de produccion)
--          + categoria GOURMET (linea de brioches/Grissini)
--          + sabores reales de la Linea de Donas (9 variantes)
--          + config multiplicador_pedido_grande (aviso de pedido
--            especial por WhatsApp)
--          + productos "Pan Chapata" y "Focaccia"
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
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(100)  NOT NULL,
  `email`           VARCHAR(100)  NOT NULL,
  `telefono`        VARCHAR(20)   DEFAULT NULL,
  `direccion`       TEXT          DEFAULT NULL,
  `password`        VARCHAR(255)  DEFAULT NULL,
  `google_id`       VARCHAR(255)  DEFAULT NULL,
  `avatar`          VARCHAR(255)  DEFAULT 'default_avatar.png',
  `fecha_registro`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `rol`             ENUM('cliente','admin','supervisor') DEFAULT 'cliente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4  zonas_envio
CREATE TABLE `zonas_envio` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(100)  NOT NULL,
  `descripcion`  TEXT          DEFAULT NULL,
  `activa`       TINYINT(1)    NOT NULL DEFAULT 1,
  `costo_envio`  DECIMAL(10,2) DEFAULT NULL COMMENT 'Costo especifico para esta zona. NULL = usar costo global',
  `creado_en`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_zonas_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5  cupones
CREATE TABLE `cupones` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `codigo`         VARCHAR(50)   NOT NULL,
  `tipo`           ENUM('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
  `valor`          DECIMAL(10,2) NOT NULL,
  `fecha_expira`   DATE          DEFAULT NULL,
  `usos_maximos`   INT(11)       DEFAULT 100,
  `usos_actuales`  INT(11)       DEFAULT 0,
  `activo`         TINYINT(1)    DEFAULT 1,
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
  `minimo_compra`       INT(11)       DEFAULT 1 COMMENT 'Minimo de compra = tamano del lote de produccion (ver unidades_paquete)',
  `unidades_paquete`    INT(11)       DEFAULT NULL COMMENT 'Unidades por paquete fisico (informativo para el cliente)',
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
  `id`                      INT(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`              INT(11)       DEFAULT NULL,
  `nombre_cliente`          VARCHAR(100)  DEFAULT NULL,
  `direccion_envio`         TEXT          DEFAULT NULL,
  `telefono`                VARCHAR(20)   DEFAULT NULL,
  `total`                   DECIMAL(10,2) NOT NULL,
  `estado`                  VARCHAR(20)   DEFAULT 'pendiente',
  `fecha`                   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `notas`                   TEXT          DEFAULT NULL,
  `subtotal`                DECIMAL(10,2) DEFAULT 0.00,
  `costo_envio`             DECIMAL(10,2) DEFAULT 0.00,
  `descuento`               DECIMAL(10,2) DEFAULT 0.00,
  `zona_envio_id`           INT(11)       DEFAULT NULL,
  `cupon_id`                INT(11)       DEFAULT NULL,
  `cupon_codigo`            VARCHAR(50)   DEFAULT NULL,
  `metodo_contacto`         VARCHAR(20)   DEFAULT 'normal',
  `fecha_envio_programada`  DATE          DEFAULT NULL COMMENT 'Fecha elegida por el cliente para recibir el pedido',
  `hora_envio_programada`   TIME          DEFAULT NULL COMMENT 'Hora elegida por el cliente para recibir el pedido',
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

-- 2.10  admin_logs (v1.1 — registro de actividad del panel admin)
CREATE TABLE `admin_logs` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`      INT(11)       DEFAULT NULL,
  `usuario_nombre`  VARCHAR(100)  DEFAULT NULL,
  `accion`          VARCHAR(50)   NOT NULL,
  `entidad`         VARCHAR(50)   DEFAULT NULL,
  `entidad_id`      INT(11)       DEFAULT NULL,
  `detalle`         TEXT          DEFAULT NULL,
  `ip`              VARCHAR(45)   DEFAULT NULL,
  `fecha`           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.11  dias_feriados (v1.1 — F1, envio programado)
CREATE TABLE `dias_feriados` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `fecha`           DATE         NOT NULL COMMENT 'Fecha del feriado (YYYY-MM-DD)',
  `descripcion`     VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nombre del feriado',
  `creado_por`      INT(11)      DEFAULT NULL COMMENT 'usuario_id que lo registro',
  `fecha_registro`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.12  pedido_historial (v1.1 — F2, modificar pedido)
CREATE TABLE `pedido_historial` (
  `id`                INT(11)  NOT NULL AUTO_INCREMENT,
  `pedido_id`         INT(11)  NOT NULL,
  `usuario_id`        INT(11)  NOT NULL,
  `campo_modificado`  VARCHAR(100) DEFAULT NULL,
  `valor_anterior`    TEXT     DEFAULT NULL,
  `valor_nuevo`       TEXT     DEFAULT NULL,
  `motivo`            TEXT     DEFAULT NULL,
  `fecha`             DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`)
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

ALTER TABLE `pedido_historial`
  ADD CONSTRAINT `fk_historial_pedido_id`
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ==============================================================
-- 4. DATOS
-- ==============================================================

-- 4.1  categorias
INSERT INTO `categorias` (`id`, `nombre`, `icono`, `orden`, `activa`, `creado_en`) VALUES
(1, 'DULCE',     'fas fa-cookie',        1, 1, '2026-06-03 04:50:48'),
(2, 'SALADO',    'fas fa-bread-slice',   2, 1, '2026-06-03 04:50:48'),
(3, 'PANADERIA', 'fas fa-birthday-cake', 3, 1, '2026-06-03 04:50:48'),
(4, 'GOURMET',   'fas fa-crown',         4, 1, '2026-09-16 18:14:00');

-- 4.2  configuracion
INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`) VALUES
(1,  'costo_envio',                 '30.00',         'Costo de envio estandar en Quetzales'),
(2,  'envio_gratis_minimo',         '0',             'Monto minimo para envio gratis (0 = desactivado)'),
(3,  'whatsapp_numero',             '50249420696',   'Numero de WhatsApp con codigo de pais'),
(4,  'nombre_tienda',               'Fermento',      'Nombre de la panaderia'),
(5,  'email_contacto',              'admin@gmail.com','Email de contacto principal'),
(6,  'moneda',                      'Q',             'Simbolo de moneda'),
(7,  'zona_predeterminada',         '1',             'ID de zona de envio por defecto'),
(8,  'pedidos_activos',             '1',             '1 = acepta pedidos, 0 = tienda cerrada'),
(9,  'mensaje_cerrado',             'Estamos preparando tu proximo pedido. Vuelve pronto.', 'Mensaje cuando la tienda esta cerrada'),
(10, 'horario_lv_inicio',           '08:00',         'Hora de inicio de entregas Lunes-Viernes'),
(11, 'horario_lv_fin',              '17:00',         'Hora de fin de entregas Lunes-Viernes'),
(12, 'horario_sab_inicio',          '08:00',         'Hora de inicio de entregas Sabado'),
(13, 'horario_sab_fin',             '12:00',         'Hora de fin de entregas Sabado'),
(14, 'multiplicador_pedido_grande', '3',             'Multiplo del minimo de compra (batch) que activa el aviso de pedido grande/especial por WhatsApp');

-- 4.3  usuarios  (contrasenas hasheadas con bcrypt — NO modificar)
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `direccion`, `password`, `google_id`, `avatar`, `fecha_registro`, `rol`) VALUES
(1, 'Carlos Morales',   'admin@gmail.com',                  '49420696', 'L36 M21 colonia la trinidad San Juan sacatepequez', '$2y$10$O3CdJNNjCEuG/HwfVaSK6.oS8dahka6aDjIUB5omDbNw25Ymyp9GO', NULL, 'default_avatar.png', '2026-01-20 20:58:41', 'admin'),
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

-- 4.6  productos (27 productos). Precio = precio de mayoreo (Fermento vende
--      solo por lote de produccion, no hay precio "al detalle" separado).
--      Excepcion: productos 36-39 mantienen su precio anterior porque usan
--      variantes "Caja 45 U./90 U." con descuento por volumen — ver nota en
--      database/v1.2_it_mundi_ajustes.sql seccion 7.
INSERT INTO `productos` (`id`,`nombre`,`descripcion`,`maridaje`,`precio`,`categoria`,`imagen`,`destacado`,`fecha_creacion`,`stock`,`oferta`,`tiene_variantes`,`imagen_2`,`imagen_3`,`sku`,`precio_distribuidor`,`minimo_compra`,`unidades_paquete`) VALUES
(17,'Linea de Pies','Deliciosa concha de pie horneada a la perfeccion, disponible en dos clasicos y exquisitos rellenos: queso tradicional o mermelada de pina.',NULL,6.50,'DULCE','pan_1780498669_200c.png',1,'2026-06-02 18:59:52',100,0,1,'pan_1780498669_f9d3.png','','PQ05.1 / PP05.2',6.50,30,1),
(18,'Linea de Strudels','Delicada y escamosa pasta de hojaldre con cobertura de azucar, disponible con abundantes rellenos a tu eleccion: mermelada de pina, mermelada de fresa o tradicional manjar.',NULL,4.50,'DULCE','pan_1780499003_54a9.png',1,'2026-06-02 18:59:52',100,0,1,'pan_1780499003_8072.png','','S04.H2.1 / C03.1j / S04.H2.2',4.50,48,1),
(19,'Linea de Donas','Suave dona disponible con diferentes coberturas (chocolate clasico, chocolate blanco o glaseado de fresa), y exquisitas decoraciones que incluyen coco, anicillos y mania.',NULL,4.50,'DULCE','pan_1780502062_d98a.png',0,'2026-06-02 18:59:52',100,0,1,'','','DACH12.1',4.50,25,1),
(20,'Croissant simple','Clasico croissant hecho con fina pasta de hojaldre semidulce sin relleno, ideal para acompanar tus bebidas favoritas.',NULL,4.00,'DULCE','pan_1780500840_60d5.png',0,'2026-06-02 18:59:52',100,0,0,'','','CO3',4.00,36,1),
(21,'Milhojas','Postre iconico de capas de hojaldre relleno de abundante manjar, decorado con cobertura de azucar.',NULL,7.50,'DULCE','pan_1780500814_9cf9.png',0,'2026-06-02 18:59:52',100,0,0,'','','S04.H2.2',7.50,16,1),
(22,'Panuelo de manzana','Suave panuelo de hojaldre relleno de deliciosa mermelada de fresa y finalizado con cobertura de azucar.',NULL,7.50,'DULCE','pan_1780501079_d568.png',1,'2026-06-02 18:59:52',100,0,0,'','','C03',7.50,20,1),
(23,'Muffin de vainilla con chispas de chocolate','Un bizcocho increiblemente esponjoso con rico sabor a vainilla, deliciosamente decorado con chispas de chocolate.',NULL,5.00,'DULCE','pan_1780501195_4a83.png',0,'2026-06-02 18:59:52',100,0,0,'pan_1780501195_2bee.png','','MV07.1',5.00,20,1),
(24,'Galletas con chispas de chocolate','La galleta perfecta: de textura crujiente y generosamente elaborada con chispas de chocolate.',NULL,3.50,'DULCE','pan_1780501407_876e.png',1,'2026-06-02 18:59:52',100,0,0,'pan_1780501407_15a9.png','','G08',3.50,50,1),
(25,'Enrollado de fresa','Postre crujiente elaborado como una deliciosa galleta con chispas de chocolate.',NULL,7.50,'DULCE','default_pan.png',0,'2026-06-02 18:59:52',0,0,0,NULL,NULL,'G08',7.50,24,1),
(26,'Champurradas','El clasico para compartir. Autenticas galletas crujientes y tostadas, realzadas con un exquisito toque de ajonjoli.',NULL,1.50,'DULCE','pan_1780501454_78fe.png',0,'2026-06-02 18:59:52',100,0,0,'','','CH06.1',1.50,90,5),
(27,'Churros','Deliciosos churros tostados y crujientes al morder, terminados con una dulce cobertura de azucar.',NULL,1.25,'DULCE','pan_1780501740_4040.png',1,'2026-06-02 18:59:52',100,0,0,'','','CH06.2',1.25,108,6),
(28,'Empanada de pollo','Empanada elaborada con crujiente pasta de hojaldre en forma de media luna, que envuelve un sabroso relleno de ensalada de pollo.',NULL,7.00,'SALADO','pan_1780502732_0d90.png',0,'2026-06-02 18:59:52',100,0,0,'pan_1780502795_12bc.png','','E04.H1',7.00,50,1),
(29,'Volovan de carne','Un bocado perfecto de pasta de hojaldre ligero y crujiente, generosamente rellena de carne muy bien sazonada.',NULL,7.00,'SALADO','pan_1780503884_880a.png',0,'2026-06-02 18:59:52',100,0,0,'','','VC04.H3',7.00,50,1),
(30,'Croissant de Jamon y queso','Increible croissant de fina pasta de hojaldre semidulce, con un clasico relleno salado de jamon y queso.',NULL,7.00,'SALADO','pan_1780502328_e11b.png',0,'2026-06-02 18:59:52',100,0,0,'','','C03.1j',7.00,36,1),
(31,'Bollo','Delicioso pan de corteza fina con una miga interior compacta y esponjosa, decorado por encima con semillas de ajonjoli.',NULL,2.25,'PANADERIA','pan_1780501940_bc30.png',0,'2026-06-02 18:59:52',100,0,0,'','','BOL.09',2.25,48,6),
(32,'Baguette','Pan frances alargado artesanal de 4 onzas, con una miga muy suave y porosa, y una cubierta nutritiva de ajonjoli, pepitoria y chia.',NULL,5.50,'PANADERIA','pan_1780501982_ed60.png',1,'2026-06-02 18:59:52',100,0,0,'','','B02',5.50,32,4),
(33,'Frances','El infaltable pan tradicional, horneado para lograr una corteza dorada y crujiente que contrasta con su miga maravillosamente suave y ligera.',NULL,0.65,'PANADERIA','pan_1780503602_d7be.png',0,'2026-06-02 18:59:52',100,0,0,'','','F01',0.65,126,6),
(34,'Trenza','Hermoso pan trenzado de sabor semidulce, que presenta una textura suave, esponjosa y una corteza ligeramente dorada cubierta con semillas de ajonjoli.',NULL,20.00,'PANADERIA','pan_1780502388_0e6f.png',0,'2026-06-02 18:59:52',100,0,0,'','','TRE10',20.00,26,1),
(35,'Concha Mexicana','Tradicional pan dulce cuya miga extra suave y esponjosa se complementa de maravilla con su caracteristica y deliciosa costra azucarada.',NULL,13.50,'PANADERIA','pan_1780502684_cd87.png',1,'2026-06-02 18:59:52',100,0,0,'','','CON11',13.50,36,6),
(36,'Pan de hamburguesa brioche','Bollo premium de masa madre suave y ligero. Elaborado con mantequilla y huevo para una miga esponjosa, terminado con una atractiva corteza brillante cubierta de ajonjoli.',NULL,7.50,'GOURMET','pan_1780503216_9234.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.H14.1',NULL,1,NULL),
(37,'Pan de hot dog brioche','Sofisticado pan alargado de masa madre, con una textura muy suave y ligera gracias a su formulacion con mantequilla y huevo, coronado con una corteza brillante.',NULL,7.50,'GOURMET','pan_1780503231_929f.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.HOT 14.2',NULL,1,NULL),
(38,'Pan de papa hamburguesa','Delicioso pan de masa madre caracterizado por ser muy suave, humedo y de un llamativo color dorado; su receta incorpora papa para balancear su sabor y lograr una miga delicada.',NULL,8.50,'GOURMET','pan_1780503184_0dcb.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.HAM14.1.1',NULL,1,NULL),
(39,'Pan de papa hot dog','Un irresistible pan alargado a base de masa madre que incorpora papa en su formulacion, dandole una miga extremadamente tierna, humeda y de suave textura.',NULL,8.50,'GOURMET','pan_1780503166_a3c0.png',0,'2026-06-02 18:59:52',100,0,1,'','','B.HOT.PA 14.2.1',NULL,1,NULL),
(40,'Pan de molde brioche','Pan artesanal de masa madre en corte rectangular, profundamente enriquecido con mantequilla, leche y huevo, lo que resulta en una miga super esponjosa y una corteza suave.',NULL,25.00,'GOURMET','pan_1780503137_54e4.png',1,'2026-06-02 18:59:52',100,0,0,'','','B.MOL.14.3',25.00,6,1),
(41,'Grissini','Delgados y apetitosos palitos de pan, perfectamente horneados hasta lograr una textura dorada y crujiente, sazonados con un toque de hierbas y estilo italiano.',NULL,1.20,'GOURMET','pan_1780502989_a4f4.png',1,'2026-06-02 18:59:52',100,0,0,'','','B.HOT 14.2',1.20,100,10),
(42,'Pan Chapata','Pan rustico de corteza fina, crujiente y enharinada con una miga suave y esponjosa de alveolos irregulares.',NULL,3.50,'PANADERIA','default_pan.png',0,'2026-09-16 11:33:46',100,0,0,NULL,NULL,'CHA.13',NULL,42,6),
(43,'Focaccia','Pan esponjoso por dentro y crujiente por fuera, se distingue por sus agujeros caracteristicos, aromatizada con hierbas. Presentacion en plancha.',NULL,35.00,'PANADERIA','default_pan.png',0,'2026-09-16 11:33:46',100,0,0,NULL,NULL,'FOC.19',NULL,1,1);

-- 4.7  producto_variantes (25 variantes)
INSERT INTO `producto_variantes` (`id`,`producto_id`,`tamano`,`sabor`,`nombre`,`precio`,`stock`,`sku`,`minimo_compra`) VALUES
( 1,17,NULL,'Queso','Queso',6.50,100,'PQ05.1 / PP05.2',1),
( 2,17,NULL,'Pina','Pina',6.50,100,'PQ05.1 / PP05.2',1),
( 3,18,NULL,'Pina','Pina',4.50,100,'S04.H2.1 / C03.1j / S04.H2.2',1),
( 4,18,NULL,'Fresa','Fresa',4.50,100,'S04.H2.1 / C03.1j / S04.H2.2',1),
( 5,18,NULL,'Manjar','Manjar',4.50,100,'S04.H2.1 / C03.1j / S04.H2.2',1),
( 6,36,'Caja 45 U.',NULL,'Caja 45 U.',8.50,100,'B.H14.1',1),
( 7,36,'Caja 90 U.',NULL,'Caja 90 U.',7.50,100,'B.H14.1',1),
( 8,37,'Caja 45 U.',NULL,'Caja 45 U.',8.50,100,'B.HOT 14.2',1),
( 9,37,'Caja 90 U.',NULL,'Caja 90 U.',7.50,100,'B.HOT 14.2',1),
(10,38,'Caja 45 U.',NULL,'Caja 45 U.',9.50,100,'B.HAM14.1.1',1),
(11,38,'Caja 90 U.',NULL,'Caja 90 U.',8.50,100,'B.HAM14.1.1',1),
(12,39,'Caja 45 U.',NULL,'Caja 45 U.',9.50,100,'B.HOT.PA 14.2.1',1),
(13,39,'Caja 90 U.',NULL,'Caja 90 U.',8.50,100,'B.HOT.PA 14.2.1',1),
(14,19,NULL,'Coco','Coco',4.50,100,'DACH12.13',NULL),
(15,19,NULL,'Maní','Maní',4.50,100,'DACH12.1M',NULL),
(16,19,NULL,'Capuchino','Capuchino',4.50,100,'DACH12.14',NULL),
(17,19,NULL,'Anicillo','Anicillo',4.50,100,'DACH12.1A',NULL),
(18,19,NULL,'Pedritos','Pedritos',4.50,100,'DACH12.1.5',NULL),
(19,19,NULL,'Blanco liso','Blanco liso',4.50,100,'DACH12.1.6',NULL),
(20,19,NULL,'Blanco con anicillo','Blanco con anicillo',4.50,100,'DACH12.3A',NULL),
(21,19,NULL,'Fresa con anicillo','Fresa con anicillo',4.50,100,'DAF12.2',NULL),
(22,19,NULL,'Azúcar glass','Azúcar glass',4.50,100,'DAAZU12.4',NULL);

-- pedidos, detalles_pedido, admin_logs, dias_feriados y pedido_historial
-- se dejan vacios (instalacion limpia).

-- ==============================================================
-- 5. RESTAURAR MODOS ORIGINALES
-- ==============================================================
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS      = @OLD_UNIQUE_CHECKS */;

-- ==============================================================
-- 6. VERIFICACION RAPIDA
-- ==============================================================
SELECT 'categorias'          AS tabla, COUNT(*) AS filas FROM categorias
UNION ALL SELECT 'configuracion',     COUNT(*) FROM configuracion
UNION ALL SELECT 'usuarios',          COUNT(*) FROM usuarios
UNION ALL SELECT 'zonas_envio',       COUNT(*) FROM zonas_envio
UNION ALL SELECT 'cupones',           COUNT(*) FROM cupones
UNION ALL SELECT 'productos',         COUNT(*) FROM productos
UNION ALL SELECT 'producto_variantes',COUNT(*) FROM producto_variantes
UNION ALL SELECT 'pedidos',           COUNT(*) FROM pedidos
UNION ALL SELECT 'detalles_pedido',   COUNT(*) FROM detalles_pedido
UNION ALL SELECT 'admin_logs',        COUNT(*) FROM admin_logs
UNION ALL SELECT 'dias_feriados',     COUNT(*) FROM dias_feriados
UNION ALL SELECT 'pedido_historial',  COUNT(*) FROM pedido_historial;

-- FIN DEL SCRIPT
