SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `icono` varchar(60) DEFAULT 'fas fa-bread-slice',
  `orden` int(11) DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` VALUES 
(1,'DULCE','fas fa-cookie',1,1,'2026-06-03 04:50:48'),
(2,'SALADO','fas fa-bread-slice',2,1,'2026-06-03 04:50:48'),
(3,'PANADERÍA','fas fa-birthday-cake',3,1,'2026-06-03 04:50:48');

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(80) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion` VALUES 
(1,'costo_envio','30.00','Costo de envío estándar en Quetzales'),
(2,'envio_gratis_minimo','0','Monto mínimo para envío gratis (0 = desactivado)'),
(3,'whatsapp_numero','50249420696','Número de WhatsApp con código de país'),
(4,'nombre_tienda','Fermento','Nombre de la panadería'),
(5,'email_contacto','admin@gmail.com','Email de contacto principal'),
(6,'moneda','Q','Símbolo de moneda'),
(7,'zona_predeterminada','1','ID de zona de envío por defecto'),
(8,'pedidos_activos','1','1 = acepta pedidos, 0 = tienda cerrada'),
(9,'mensaje_cerrado','Estamos preparando tu próximo pedido. Vuelve pronto.','Mensaje cuando la tienda está cerrada');

DROP TABLE IF EXISTS `detalles_pedido`;
CREATE TABLE `detalles_pedido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_producto` varchar(100) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `variante_id` int(11) DEFAULT NULL,
  `variante_nombre` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre_cliente` varchar(100) DEFAULT NULL,
  `direccion_envio` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(20) DEFAULT 'pendiente',
  `fecha` datetime DEFAULT current_timestamp(),
  `notas` text DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `costo_envio` decimal(10,2) DEFAULT 0.00,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `zona_envio_id` int(11) DEFAULT NULL,
  `cupon_id` int(11) DEFAULT NULL,
  `cupon_codigo` varchar(50) DEFAULT NULL,
  `metodo_contacto` varchar(20) DEFAULT 'normal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `producto_variantes`;
CREATE TABLE `producto_variantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `tamano` varchar(80) DEFAULT NULL,
  `sabor` varchar(80) DEFAULT NULL,
  `nombre` varchar(200) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(60) DEFAULT NULL,
  `minimo_compra` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `producto_variantes` VALUES 
(1,17,NULL,'Queso','Queso',10.00,10,'PQ05.1 / PP05.2'),
(2,17,NULL,'Piña','Piña',10.00,5,'PQ05.1 / PP05.2'),
(3,18,NULL,'Piña','Piña',8.00,50,'S04.H2.1 / C03.1j / S04.H2.2'),
(4,18,NULL,'Fresa','Fresa',8.00,50,'S04.H2.1 / C03.1j / S04.H2.2'),
(5,18,NULL,'Manjar','Manjar',8.00,50,'S04.H2.1 / C03.1j / S04.H2.2'),
(6,36,'Caja 45 U.',NULL,'Caja 45 U.',8.50,500,'B.H14.1'),
(7,36,'Caja 90 U.',NULL,'Caja 90 U.',7.50,600,'B.H14.1'),
(8,37,'Caja 45 U.',NULL,'Caja 45 U.',8.50,600,'B.HOT 14.2'),
(9,37,'Caja 90 U.',NULL,'Caja 90 U.',7.50,500,'B.HOT 14.2'),
(10,38,'Caja 45 U.',NULL,'Caja 45 U.',9.50,900,'B.HAM14.1.1'),
(11,38,'Caja 90 U.',NULL,'Caja 90 U.',8.50,100,'B.HAM14.1.1'),
(12,39,'Caja 45 U.',NULL,'Caja 45 U.',9.50,300,'B.HOT.PA 14.2.1'),
(13,39,'Caja 90 U.',NULL,'Caja 90 U.',8.50,400,'B.HOT.PA 14.2.1'),
(14,19,NULL,'Chocolate con mania','Chocolate con mania',10.00,100,NULL),
(15,19,NULL,'chocolate simple','chocolate simple',10.00,100,NULL),
(16,19,NULL,'fresa con chispas','fresa con chispas',10.00,100,NULL),
(17,19,NULL,'chocolate con chispas','chocolate con chispas',10.00,100,NULL);

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `maridaje` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `imagen` varchar(255) DEFAULT 'default_pan.png',
  `destacado` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `stock` int(11) DEFAULT 0,
  `oferta` tinyint(1) DEFAULT 0,
  `tiene_variantes` tinyint(1) DEFAULT 0,
  `imagen_2` varchar(255) DEFAULT NULL,
  `imagen_3` varchar(255) DEFAULT NULL,
  `sku` varchar(60) DEFAULT NULL,
  `precio_distribuidor` decimal(10,2) DEFAULT NULL,
  `minimo_compra` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `productos` VALUES 
(17,'Línea de Pies','Deliciosa concha de pie horneada a la perfección, disponible en dos clásicos y exquisitos rellenos: queso tradicional o mermelada de piña.',NULL,10.00,'DULCE','pan_1780498669_200c.png',1,'2026-06-02 18:59:52',15,0,1,'pan_1780498669_f9d3.png','','PQ05.1 / PP05.2',NULL,26),
(18,'Línea de Strudels','Delicada y escamosa pasta de hojaldre con cobertura de azúcar, disponible con abundantes rellenos a tu elección: mermelada de piña, mermelada de fresa o tradicional manjar.',NULL,8.00,'DULCE','pan_1780499003_54a9.png',1,'2026-06-02 18:59:52',150,0,1,'pan_1780499003_8072.png','','S04.H2.1 / C03.1j / S04.H2.2',NULL,48),
(19,'Línea de Donas','Suave dona disponible con diferentes coberturas (chocolate clásico, chocolate blanco o glaseado de fresa), y exquisitas decoraciones que incluyen coco, anicillos y manía.',NULL,10.00,'DULCE','pan_1780502062_d98a.png',0,'2026-06-02 18:59:52',400,0,1,'','','DACH12.1',NULL,1),
(20,'Croissant simple','Clásico croissant hecho con fina pasta de hojaldre semidulce sin relleno, ideal para acompañar tus bebidas favoritas.',NULL,8.00,'DULCE','pan_1780500840_60d5.png',0,'2026-06-02 18:59:52',500,0,0,'','','CO3',NULL,1),
(21,'Milhojas','Postre icónico de capas de hojaldre relleno de abundante manjar, decorado con cobertura de azúcar.',NULL,10.00,'DULCE','pan_1780500814_9cf9.png',0,'2026-06-02 18:59:52',100,0,0,'','','S04.H2.2',NULL,1),
(22,'Pañuelo de manzana','Suave pañuelo de hojaldre relleno de deliciosa mermelada de fresa y finalizado con cobertura de azúcar.',NULL,8.00,'DULCE','pan_1780501079_d568.png',1,'2026-06-02 18:59:52',1000,0,0,'','','C03',NULL,1),
(23,'Muffin de vainilla con chispas de chocolate','Un bizcocho increíblemente esponjoso con rico sabor a vainilla, deliciosamente decorado con chispas de chocolate.',NULL,8.00,'DULCE','pan_1780501195_4a83.png',0,'2026-06-02 18:59:52',500,0,0,'pan_1780501195_2bee.png','','MV07.1',NULL,1),
(24,'Galletas con chispas de chocolate','La galleta perfecta: de textura crujiente y generosamente elaborada con chispas de chocolate.',NULL,5.50,'DULCE','pan_1780501407_876e.png',1,'2026-06-02 18:59:52',500,0,0,'pan_1780501407_15a9.png','','G08',NULL,1),
(25,'Enrollado de fresa','Postre crujiente elaborado como una deliciosa galleta con chispas de chocolate.',NULL,15.00,'DULCE','default_pan.png',0,'2026-06-02 18:59:52',0,0,0,NULL,NULL,'G08',NULL,1),
(26,'Champurradas','El clásico para compartir. Auténticas galletas crujientes y tostadas, realzadas con un exquisito toque de ajonjolí.',NULL,13.00,'DULCE','pan_1780501454_78fe.png',0,'2026-06-02 18:59:52',400,0,0,'','','CH06.1',NULL,1),
(27,'Churros','Deliciosos churros tostados y crujientes al morder, terminados con una dulce cobertura de azúcar.',NULL,13.00,'DULCE','pan_1780501740_4040.png',1,'2026-06-02 18:59:52',400,0,0,'','','CH06.2',NULL,1),
(28,'Empanada de pollo','Empanada elaborada con crujiente pasta de hojaldre en forma de media luna, que envuelve un sabroso relleno de ensalada de pollo.',NULL,12.00,'SALADO','pan_1780502732_0d90.png',0,'2026-06-02 18:59:52',300,0,0,'pan_1780502795_12bc.png','','E04.H1',NULL,1),
(29,'Volován de carne','Un bocado perfecto de pasta de hojaldre ligero y crujiente, generosamente rellena de carne muy bien sazonada.',NULL,12.00,'SALADO','pan_1780503884_880a.png',0,'2026-06-02 18:59:52',600,0,0,'','','VC04.H3',NULL,1),
(30,'Croissant de Jamón y queso','Increíble croissant de fina pasta de hojaldre semidulce, con un clásico relleno salado de jamón y queso.',NULL,13.00,'SALADO','pan_1780502328_e11b.png',0,'2026-06-02 18:59:52',699,0,0,'','','C03.1j',NULL,1),
(31,'Bollo','Delicioso pan de corteza fina con una miga interior compacta y esponjosa, decorado por encima con semillas de ajonjolí.',NULL,18.00,'PANADERÍA','pan_1780501940_bc30.png',0,'2026-06-02 18:59:52',900,0,0,'','','BOL.09',NULL,1),
(32,'Baguette','Pan francés alargado artesanal de 4 onzas, con una miga muy suave y porosa, y una cubierta nutritiva de ajonjolí, pepitoria y chía.',NULL,9.00,'PANADERÍA','pan_1780501982_ed60.png',1,'2026-06-02 18:59:52',600,0,0,'','','B02',NULL,1),
(33,'Francés','El infaltable pan tradicional, horneado para lograr una corteza dorada y crujiente que contrasta con su miga maravillosamente suave y ligera.',NULL,5.00,'PANADERÍA','pan_1780503602_d7be.png',0,'2026-06-02 18:59:52',600,0,0,'','','F01',NULL,1),
(34,'Trenza','Hermoso pan trenzado de sabor semidulce, que presenta una textura suave, esponjosa y una corteza ligeramente dorada cubierta con semillas de ajonjolí.',NULL,25.00,'PANADERÍA','pan_1780502388_0e6f.png',0,'2026-06-02 18:59:52',50,0,0,'','','TRE10',NULL,1),
(35,'Concha Mexicana','Tradicional pan dulce cuya miga extra suave y esponjosa se complementa de maravilla con su característica y deliciosa costra azucarada.',NULL,18.00,'PANADERÍA','pan_1780502684_cd87.png',1,'2026-06-02 18:59:52',400,0,0,'','','CON11',NULL,1),
(36,'Pan de hamburguesa brioche','Bollo premium de masa madre suave y ligero. Elaborado con mantequilla y huevo para una miga esponjosa, terminado con una atractiva corteza brillante cubierta de ajonjolí.',NULL,7.50,'SALADO','pan_1780503216_9234.png',0,'2026-06-02 18:59:52',1100,0,1,'','','B.H14.1',NULL,1),
(37,'Pan de hot dog brioche','Sofisticado pan alargado de masa madre, con una textura muy suave y ligera gracias a su formulación con mantequilla y huevo, coronado con una corteza brillante.',NULL,7.50,'SALADO','pan_1780503231_929f.png',0,'2026-06-02 18:59:52',1100,0,1,'','','B.HOT 14.2',NULL,1),
(38,'Pan de papa hamburguesa','Delicioso pan de masa madre caracterizado por ser muy suave, húmedo y de un llamativo color dorado; su receta incorpora papa para balancear su sabor y lograr una miga delicada.',NULL,8.50,'SALADO','pan_1780503184_0dcb.png',0,'2026-06-02 18:59:52',1000,0,1,'','','B.HAM14.1.1',NULL,1),
(39,'Pan de papa hot dog','Un irresistible pan alargado a base de masa madre que incorpora papa en su formulación, dándole una miga extremadamente tierna, húmeda y de suave textura.',NULL,8.50,'SALADO','pan_1780503166_a3c0.png',0,'2026-06-02 18:59:52',700,0,1,'','','B.HOT.PA 14.2.1',NULL,1),
(40,'Pan de molde brioche','Pan artesanal de masa madre en corte rectangular, profundamente enriquecido con mantequilla, leche y huevo, lo que resulta en una miga súper esponjosa y una corteza suave.',NULL,30.00,'SALADO','pan_1780503137_54e4.png',1,'2026-06-02 18:59:52',500,0,0,'','','B.MOL.14.3',NULL,1),
(41,'Grissini','Delgados y apetitosos palitos de pan, perfectamente horneados hasta lograr una textura dorada y crujiente, sazonados con un toque de hierbas y estilo italiano.',NULL,18.00,'SALADO','pan_1780502989_a4f4.png',1,'2026-06-02 18:59:52',1000,0,0,'','','B.HOT 14.2',NULL,1);

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default_avatar.png',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `rol` enum('cliente','admin','supervisor') DEFAULT 'cliente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` VALUES 
(1,'Carlos Morales','admin@gmail.com','49420696','L36 M21 colonia la trinidad San Juan sacatepequez ','$2y$10$wr7MXdshfkleDHs3zOhgnuVeGeBntzyo1wMJU1aPysvnREHXF6.0q',NULL,'default_avatar.png','2026-01-20 20:58:41','admin'),
(2,'Dennis Ortiz','sistemas@grupopremia.com','47688774','24 calle 1-05 zona 3','$2y$10$Osm1qAhvut68RTNFxbmmP.a5q2Lyr33HjFFPIUlioZ7iHPpj9f2TG',NULL,'default_avatar.png','2026-01-21 23:08:27','cliente'),
(3,'Fernando Morales','fernandomorales1842010@gmail.com','49420696','L36M21 Colonia la Trinidad San Juan Sacatepequez','$2y$10$lKe7C53GCYh768lZYT98Ku7HFXX358pUvCfNOc12k1A/O3U1BRK8m',NULL,'default_avatar.png','2026-06-03 15:27:47','cliente');

DROP TABLE IF EXISTS `zonas_envio`;
CREATE TABLE `zonas_envio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `zonas_envio` VALUES 
(1,'Zona 3','Ciudad de Guatemala, no entregamos a zonas rojas',1,'2026-06-03 04:50:48'),
(5,'Zona 1','No entregamos a zonas rojas',1,'2026-06-03 16:34:03'),
(6,'zona 4','Ciudad de Guatemala, no entregamos a zonas rojas',1,'2026-06-03 16:34:35'),
(7,'zona 13','Ciudad de Guatemala, no entregamos a zonas rojas',1,'2026-06-03 16:34:42'),
(8,'zona 21','Ciudad de Guatemala, no entregamos a zonas rojas',1,'2026-06-03 16:34:48'),
(9,'zona 5','Ciudad de Guatemala, no entregamos a zonas rojas',1,'2026-06-03 16:34:54'),
(10,'zona 9','Ciudad de Guatemala, no entregamos a zonas rojas',1,'2026-06-03 16:35:01');

-- Cupones
DROP TABLE IF EXISTS `cupones`;
CREATE TABLE `cupones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `tipo` ENUM('porcentaje', 'fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` DECIMAL(10,2) NOT NULL,
  `fecha_expira` DATE,
  `usos_maximos` INT DEFAULT 100,
  `usos_actuales` INT DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1
);
INSERT IGNORE INTO `cupones` (`codigo`, `tipo`, `valor`, `fecha_expira`) VALUES
('BIENVENIDA10', 'porcentaje', 10.00, '2099-12-31'),
('DESCUENTOQ50', 'fijo', 50.00, '2099-12-31');

-- ACTUALIZAR EL INVENTARIO A 100
UPDATE `productos` SET `stock` = 100;
UPDATE `producto_variantes` SET `stock` = 100;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
