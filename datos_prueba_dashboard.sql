-- =============================================================
-- DATOS DE PRUEBA - DASHBOARD FERMENTO (v2 corregido)
-- =============================================================

USE DB_fermento;

-- -----------------------------------------------
-- 1. PEDIDOS (30 días, estados variados)
-- -----------------------------------------------
INSERT INTO pedidos (usuario_id, nombre_cliente, telefono, direccion_envio, subtotal, descuento, costo_envio, total, estado, zona_envio_id, metodo_contacto, fecha) VALUES
(1, 'Ana Martínez',    '5501-1234', 'Zona 10, Guatemala',   280.00, 0,  30.00, 310.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 29 DAY),
(1, 'Carlos López',    '5502-2345', 'Zona 15, Guatemala',   450.00, 0,  30.00, 480.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 28 DAY),
(1, 'María García',    '5503-3456', 'Zona 1, Guatemala',    180.00, 20, 30.00, 190.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 27 DAY),
(1, 'José Rodríguez',  '5504-4567', 'Zona 4, Guatemala',    320.00, 0,  30.00, 350.00, 'cancelado',  1, 'normal',   NOW() - INTERVAL 26 DAY),
(1, 'Laura Pérez',     '5505-5678', 'Zona 12, Guatemala',   560.00, 50, 30.00, 540.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 25 DAY),
(1, 'Pedro Sánchez',   '5506-6789', 'Zona 9, Guatemala',    230.00, 0,  30.00, 260.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 24 DAY),
(1, 'Sofía Ramírez',   '5507-7890', 'Zona 7, Guatemala',    410.00, 0,  30.00, 440.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 23 DAY),
(1, 'Diego Torres',    '5508-8901', 'Zona 13, Guatemala',   890.00, 80, 30.00, 840.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 22 DAY),
(1, 'Valentina Cruz',  '5509-9012', 'Zona 6, Guatemala',    145.00, 0,  30.00, 175.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 21 DAY),
(1, 'Roberto Herrera', '5510-0123', 'Zona 11, Guatemala',   670.00, 0,  30.00, 700.00, 'en_camino',  1, 'normal',   NOW() - INTERVAL 20 DAY),
(1, 'Isabella Morales','5511-1234', 'Zona 2, Guatemala',    290.00, 30, 30.00, 290.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 19 DAY),
(1, 'Andrés Jiménez',  '5512-2345', 'Zona 14, Guatemala',   520.00, 0,  30.00, 550.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 18 DAY),
(1, 'Camila Vargas',   '5513-3456', 'Zona 5, Guatemala',    380.00, 0,  30.00, 410.00, 'cancelado',  2, 'normal',   NOW() - INTERVAL 17 DAY),
(1, 'Fernando Ríos',   '5514-4567', 'Zona 8, Guatemala',    730.00, 70, 30.00, 690.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 16 DAY),
(1, 'Natalia Ortega',  '5515-5678', 'Zona 10, Guatemala',   210.00, 0,  30.00, 240.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 15 DAY),
(1, 'Sebastián Ruiz',  '5516-6789', 'Zona 3, Guatemala',    480.00, 0,  30.00, 510.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 14 DAY),
(1, 'Gabriela Mendez', '5517-7890', 'Zona 16, Guatemala',   960.00,100, 30.00, 890.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 13 DAY),
(1, 'Ricardo Castillo','5518-8901', 'Zona 7, Guatemala',    340.00, 0,  30.00, 370.00, 'en_camino',  1, 'normal',   NOW() - INTERVAL 12 DAY),
(1, 'Patricia Flores', '5519-9012', 'Zona 11, Guatemala',   610.00, 60, 30.00, 580.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 11 DAY),
(1, 'Miguel Ángel',    '5520-0123', 'Zona 9, Guatemala',    270.00, 0,  30.00, 300.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 10 DAY),
(1, 'Daniela Reyes',   '5521-1234', 'Zona 15, Guatemala',   820.00, 0,  30.00, 850.00, 'preparando', 2, 'normal',   NOW() - INTERVAL 9 DAY),
(1, 'Alejandro Mora',  '5522-2345', 'Zona 12, Guatemala',   435.00, 0,  30.00, 465.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 7 DAY),
(1, 'Lucía Fuentes',   '5523-3456', 'Zona 4, Guatemala',    580.00, 50, 30.00, 560.00, 'entregado',  2, 'normal',   NOW() - INTERVAL 6 DAY),
(1, 'Eduardo Blanco',  '5524-4567', 'Zona 10, Guatemala',   720.00, 0,  30.00, 750.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 5 DAY),
(1, 'Mariana Silva',   '5525-5678', 'Zona 6, Guatemala',    290.00, 0,  30.00, 320.00, 'preparando', 2, 'normal',   NOW() - INTERVAL 4 DAY),
(1, 'Cristian Vega',   '5526-6789', 'Zona 13, Guatemala',   940.00, 90, 30.00, 880.00, 'entregado',  1, 'normal',   NOW() - INTERVAL 3 DAY),
(1, 'Renata Guzmán',   '5527-7890', 'Zona 1, Guatemala',    410.00, 0,  30.00, 440.00, 'en_camino',  2, 'normal',   NOW() - INTERVAL 2 DAY),
(1, 'Tomás Aguilar',   '5528-8901', 'Zona 8, Guatemala',    655.00, 0,  30.00, 685.00, 'pendiente',  1, 'normal',   NOW() - INTERVAL 1 DAY),
(1, 'Elena Paredes',   '5529-9012', 'Zona 5, Guatemala',    380.00, 0,  30.00, 410.00, 'pendiente',  2, 'normal',   NOW()),
(1, 'Bruno Castañeda', '5530-0123', 'Zona 14, Guatemala',   520.00, 0,  30.00, 550.00, 'pendiente',  1, 'normal',   NOW());

-- -----------------------------------------------
-- 2. DETALLES DE PEDIDOS
--    Sin columna subtotal (estructura real de la tabla)
-- -----------------------------------------------

-- IDs de los pedidos recién insertados
SET @base = (SELECT MIN(id) FROM pedidos ORDER BY id DESC LIMIT 30);
SET @p1  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 29);
SET @p2  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 28);
SET @p3  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 27);
SET @p4  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 26);
SET @p5  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 25);
SET @p6  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 24);
SET @p7  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 23);
SET @p8  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 22);
SET @p9  = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 21);
SET @p10 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 20);
SET @p11 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 19);
SET @p12 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 18);
SET @p13 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 17);
SET @p14 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 16);
SET @p15 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 15);
SET @p16 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 14);
SET @p17 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 13);
SET @p18 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 12);
SET @p19 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 11);
SET @p20 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 10);
SET @p21 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 9);
SET @p22 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 8);
SET @p23 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 7);
SET @p24 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 6);
SET @p25 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 5);
SET @p26 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 4);
SET @p27 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 3);
SET @p28 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 2);
SET @p29 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 1);
SET @p30 = (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1 OFFSET 0);

-- Productos del catálogo
SET @prod1 = (SELECT id   FROM productos ORDER BY id LIMIT 1 OFFSET 0);
SET @prod2 = (SELECT id   FROM productos ORDER BY id LIMIT 1 OFFSET 1);
SET @prod3 = (SELECT id   FROM productos ORDER BY id LIMIT 1 OFFSET 2);
SET @prod4 = (SELECT id   FROM productos ORDER BY id LIMIT 1 OFFSET 3);
SET @prod5 = (SELECT id   FROM productos ORDER BY id LIMIT 1 OFFSET 4);
SET @nom1  = (SELECT nombre FROM productos ORDER BY id LIMIT 1 OFFSET 0);
SET @nom2  = (SELECT nombre FROM productos ORDER BY id LIMIT 1 OFFSET 1);
SET @nom3  = (SELECT nombre FROM productos ORDER BY id LIMIT 1 OFFSET 2);
SET @nom4  = (SELECT nombre FROM productos ORDER BY id LIMIT 1 OFFSET 3);
SET @nom5  = (SELECT nombre FROM productos ORDER BY id LIMIT 1 OFFSET 4);
SET @pre1  = (SELECT precio FROM productos ORDER BY id LIMIT 1 OFFSET 0);
SET @pre2  = (SELECT precio FROM productos ORDER BY id LIMIT 1 OFFSET 1);
SET @pre3  = (SELECT precio FROM productos ORDER BY id LIMIT 1 OFFSET 2);
SET @pre4  = (SELECT precio FROM productos ORDER BY id LIMIT 1 OFFSET 3);
SET @pre5  = (SELECT precio FROM productos ORDER BY id LIMIT 1 OFFSET 4);

-- Insertar detalles SIN columna subtotal (solo las columnas que existen)
INSERT INTO detalles_pedido (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad) VALUES
(@p1,  @prod1, @nom1, @pre1, 2),
(@p1,  @prod2, @nom2, @pre2, 1),
(@p2,  @prod1, @nom1, @pre1, 3),
(@p2,  @prod3, @nom3, @pre3, 2),
(@p3,  @prod2, @nom2, @pre2, 4),
(@p4,  @prod4, @nom4, @pre4, 1),
(@p4,  @prod5, @nom5, @pre5, 2),
(@p5,  @prod1, @nom1, @pre1, 5),
(@p5,  @prod2, @nom2, @pre2, 2),
(@p6,  @prod3, @nom3, @pre3, 3),
(@p7,  @prod1, @nom1, @pre1, 4),
(@p7,  @prod4, @nom4, @pre4, 1),
(@p8,  @prod2, @nom2, @pre2, 6),
(@p8,  @prod5, @nom5, @pre5, 3),
(@p9,  @prod3, @nom3, @pre3, 2),
(@p10, @prod1, @nom1, @pre1, 4),
(@p10, @prod2, @nom2, @pre2, 3),
(@p11, @prod4, @nom4, @pre4, 2),
(@p12, @prod1, @nom1, @pre1, 6),
(@p12, @prod3, @nom3, @pre3, 2),
(@p13, @prod5, @nom5, @pre5, 4),
(@p14, @prod2, @nom2, @pre2, 5),
(@p14, @prod4, @nom4, @pre4, 3),
(@p15, @prod1, @nom1, @pre1, 2),
(@p16, @prod3, @nom3, @pre3, 4),
(@p16, @prod5, @nom5, @pre5, 2),
(@p17, @prod1, @nom1, @pre1, 8),
(@p17, @prod2, @nom2, @pre2, 4),
(@p18, @prod4, @nom4, @pre4, 3),
(@p19, @prod1, @nom1, @pre1, 5),
(@p19, @prod3, @nom3, @pre3, 3),
(@p20, @prod2, @nom2, @pre2, 3),
(@p21, @prod5, @nom5, @pre5, 6),
(@p21, @prod4, @nom4, @pre4, 2),
(@p22, @prod1, @nom1, @pre1, 4),
(@p22, @prod2, @nom2, @pre2, 2),
(@p23, @prod3, @nom3, @pre3, 5),
(@p24, @prod1, @nom1, @pre1, 3),
(@p24, @prod5, @nom5, @pre5, 3),
(@p25, @prod2, @nom2, @pre2, 7),
(@p25, @prod4, @nom4, @pre4, 2),
(@p26, @prod1, @nom1, @pre1, 9),
(@p26, @prod3, @nom3, @pre3, 3),
(@p27, @prod5, @nom5, @pre5, 5),
(@p27, @prod2, @nom2, @pre2, 3),
(@p28, @prod1, @nom1, @pre1, 4),
(@p28, @prod4, @nom4, @pre4, 4),
(@p29, @prod3, @nom3, @pre3, 6),
(@p29, @prod2, @nom2, @pre2, 2),
(@p30, @prod1, @nom1, @pre1, 5),
(@p30, @prod5, @nom5, @pre5, 4);

-- -----------------------------------------------
-- 3. Bajar stock de productos para alertas de stock
-- -----------------------------------------------
UPDATE productos SET stock = 3  WHERE id = @prod3;
UPDATE productos SET stock = 7  WHERE id = @prod4;
UPDATE productos SET stock = 12 WHERE id = @prod5;

-- -----------------------------------------------
-- 4. VERIFICACIÓN
-- -----------------------------------------------
SELECT 'Pedidos insertados'   AS info, COUNT(*) AS total FROM pedidos;
SELECT 'Detalles insertados'  AS info, COUNT(*) AS total FROM detalles_pedido;
SELECT 'Ventas (no cancelado)'AS info, CONCAT('Q', FORMAT(SUM(total),2)) AS total FROM pedidos WHERE estado != 'cancelado';
