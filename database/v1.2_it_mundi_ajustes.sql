-- ================================================================
-- FERMENTO v1.2 — Ajustes "Equipo IT mundi"
-- Fuente: hoja de cálculo compartida (pestañas "Solicitudes antes de
-- entrega final" y "Productos corregidos"), filtrada a filas con
-- Realizado por = "Equipo IT mundi".
--
-- Cubre:
--   #22 Cantidades mínimas de pedido por producto (datos de Sheny).
--        En Fermento la venta se maneja por BATCH de producción:
--        minimo_compra = UNIDADES BATCH (mínimo Y paso de incremento).
--        unidades_paquete (columna nueva) = unidades por paquete físico,
--        solo para mostrarle al cliente cómo se compone su pedido.
--   Config para #7 (aviso de pedido grande/especial vía WhatsApp).
--
-- Ejecutar con la base de datos del proyecto seleccionada (DB_fermento).
-- Es idempotente: puede correrse más de una vez sin duplicar cambios.
-- ================================================================

SET NAMES utf8mb4;

USE DB_fermento;

-- 1. Nueva columna: unidades por paquete (referencia informativa para el cliente)
-- NOTA: Si tu MySQL/MariaDB no soporta "ADD COLUMN IF NOT EXISTS", quita el IF NOT EXISTS.
ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS unidades_paquete INT(11) NULL DEFAULT NULL
    COMMENT 'Unidades por paquete físico (ver también minimo_compra = unidades por batch)'
    AFTER minimo_compra;

-- 2. Multiplicador configurable para el aviso de "pedido grande/especial" (#7)
--    Ej: multiplicador 3 => se avisa cuando la cantidad de un producto es
--    3 veces o más su minimo_compra (batch). Editable en Configuración.
INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('multiplicador_pedido_grande', '3', 'Multiplo del minimo de compra (batch) que activa el aviso de pedido grande/especial por WhatsApp')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- 3. Cantidades mínimas de pedido (= tamaño de batch) y unidades por paquete,
--    según datos entregados por Sheny. Coincidencia por SKU cuando es
--    confiable; por nombre cuando el SKU en BD tenía un error previo
--    (no se toca la columna sku — fuera de alcance de este ajuste).
--
--    NO incluidos (fuera de alcance / requieren decisión aparte):
--      - Pan de hamburguesa/hot dog brioche, Pan de papa hamburguesa/hot dog
--        (productos 36-39): ya usan variantes "Caja 45 U./90 U." para el
--        tamaño de empaque; aplicar batch al mínimo del producto padre
--        podría chocar con ese mecanismo. Requiere definición del negocio.
--      - "Porción de 3 leches": fila con datos incompletos (PEND) en la hoja.
--
--    Productos NUEVOS (no existían en la tienda, se agregan con los datos
--    de la hoja — "PRECIO UNITARIO POR MAYOR" se usa como precio de venta
--    porque es el único precio disponible para estos dos productos):

UPDATE productos SET minimo_compra = 30,  unidades_paquete = 1, precio_distribuidor = 6.50 WHERE sku = 'PQ05.1 / PP05.2';                 -- Linea de Pies (Queso/Piña)
UPDATE productos SET minimo_compra = 48,  unidades_paquete = 1, precio_distribuidor = 4.50 WHERE nombre = 'Linea de Strudels';            -- ya coincidía en 48
UPDATE productos SET minimo_compra = 25,  unidades_paquete = 1, precio_distribuidor = 4.50 WHERE nombre = 'Linea de Donas';
UPDATE productos SET minimo_compra = 36,  unidades_paquete = 1, precio_distribuidor = 4.00 WHERE nombre = 'Croissant simple';
UPDATE productos SET minimo_compra = 16,  unidades_paquete = 1, precio_distribuidor = 7.50 WHERE nombre = 'Milhojas';
UPDATE productos SET minimo_compra = 20,  unidades_paquete = 1, precio_distribuidor = 7.50 WHERE nombre = 'Panuelo de manzana';
UPDATE productos SET minimo_compra = 20,  unidades_paquete = 1, precio_distribuidor = 5.00 WHERE nombre = 'Muffin de vainilla con chispas de chocolate';
UPDATE productos SET minimo_compra = 50,  unidades_paquete = 1, precio_distribuidor = 3.50 WHERE nombre = 'Galletas con chispas de chocolate';
UPDATE productos SET minimo_compra = 24,  unidades_paquete = 1, precio_distribuidor = 7.50 WHERE nombre = 'Enrollado de fresa';
UPDATE productos SET minimo_compra = 90,  unidades_paquete = 5, precio_distribuidor = 1.50 WHERE nombre = 'Champurradas';
UPDATE productos SET minimo_compra = 108, unidades_paquete = 6, precio_distribuidor = 1.25 WHERE nombre = 'Churros';
UPDATE productos SET minimo_compra = 50,  unidades_paquete = 1, precio_distribuidor = 7.00 WHERE nombre = 'Empanada de pollo';
UPDATE productos SET minimo_compra = 50,  unidades_paquete = 1, precio_distribuidor = 7.00 WHERE nombre = 'Volovan de carne';
UPDATE productos SET minimo_compra = 36,  unidades_paquete = 1, precio_distribuidor = 7.00 WHERE nombre = 'Croissant de Jamon y queso';
UPDATE productos SET minimo_compra = 48,  unidades_paquete = 6, precio_distribuidor = 2.25 WHERE nombre = 'Bollo';
UPDATE productos SET minimo_compra = 32,  unidades_paquete = 4, precio_distribuidor = 5.50 WHERE nombre = 'Baguette';
UPDATE productos SET minimo_compra = 126, unidades_paquete = 6, precio_distribuidor = 0.65 WHERE nombre = 'Frances';
UPDATE productos SET minimo_compra = 26,  unidades_paquete = 1, precio_distribuidor = 20.00 WHERE nombre = 'Trenza';
UPDATE productos SET minimo_compra = 36,  unidades_paquete = 6, precio_distribuidor = 13.50 WHERE nombre = 'Concha Mexicana';
UPDATE productos SET minimo_compra = 6,   unidades_paquete = 1, precio_distribuidor = 25.00 WHERE nombre = 'Pan de molde brioche';
UPDATE productos SET minimo_compra = 100, unidades_paquete = 10, precio_distribuidor = 1.20 WHERE nombre = 'Grissini';

-- 4. Productos nuevos (no existían en la tienda)
INSERT INTO productos
    (nombre, descripcion, precio, categoria, imagen, destacado, stock, oferta, tiene_variantes, sku, minimo_compra, unidades_paquete)
SELECT 'Pan Chapata',
       'Pan rustico de corteza fina, crujiente y enharinada con una miga suave y esponjosa de alveolos irregulares.',
       3.50, 'PANADERIA', 'default_pan.png', 0, 100, 0, 0, 'CHA.13', 42, 6
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE sku = 'CHA.13');

INSERT INTO productos
    (nombre, descripcion, precio, categoria, imagen, destacado, stock, oferta, tiene_variantes, sku, minimo_compra, unidades_paquete)
SELECT 'Focaccia',
       'Pan esponjoso por dentro y crujiente por fuera, se distingue por sus agujeros caracteristicos, aromatizada con hierbas. Presentacion en plancha.',
       35.00, 'PANADERIA', 'default_pan.png', 0, 100, 0, 0, 'FOC.19', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE sku = 'FOC.19');

-- 5. Categoría GOURMET (la línea de brioches/Grissini estaba mal clasificada
--    como SALADO; la hoja de Sheny los agrupa como "PANADERIA GOURMET").
INSERT INTO categorias (nombre, icono, orden, activa)
SELECT 'GOURMET', 'fas fa-crown', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nombre = 'GOURMET');

UPDATE productos SET categoria = 'GOURMET'
WHERE nombre IN (
    'Pan de hamburguesa brioche', 'Pan de hot dog brioche',
    'Pan de papa hamburguesa', 'Pan de papa hot dog',
    'Pan de molde brioche', 'Grissini'
);

-- 6. Línea de Donas: los 9 sabores reales según la hoja de Sheny reemplazan
--    las 4 variantes de ejemplo que había cargadas (no correspondían a
--    ningún sabor real del negocio).
DELETE FROM producto_variantes WHERE producto_id = (SELECT id FROM productos WHERE nombre = 'Linea de Donas');

INSERT INTO producto_variantes (producto_id, sabor, nombre, precio, stock, sku, minimo_compra)
SELECT p.id, v.sabor, v.sabor, p.precio, 100, v.sku, NULL
FROM productos p
CROSS JOIN (
    SELECT 'Coco'                AS sabor, 'DACH12.13'  AS sku UNION ALL
    SELECT 'Maní',                         'DACH12.1M'        UNION ALL
    SELECT 'Capuchino',                    'DACH12.14'        UNION ALL
    SELECT 'Anicillo',                     'DACH12.1A'        UNION ALL
    SELECT 'Pedritos',                     'DACH12.1.5'       UNION ALL
    SELECT 'Blanco liso',                  'DACH12.1.6'       UNION ALL
    SELECT 'Blanco con anicillo',          'DACH12.3A'        UNION ALL
    SELECT 'Fresa con anicillo',           'DAF12.2'          UNION ALL
    SELECT 'Azúcar glass',                 'DAAZU12.4'
) v
WHERE p.nombre = 'Linea de Donas';

-- ✅ VERIFICACIÓN
SELECT id, nombre, sku, minimo_compra, unidades_paquete, precio_distribuidor
FROM productos
ORDER BY id;

SELECT clave, valor, descripcion FROM configuracion WHERE clave = 'multiplicador_pedido_grande';

SELECT id, nombre FROM categorias WHERE nombre = 'GOURMET';

SELECT v.id, v.sabor, v.sku
FROM producto_variantes v
JOIN productos p ON p.id = v.producto_id
WHERE p.nombre = 'Linea de Donas';

-- 7. Precio de venta = precio de mayoreo de la hoja.
--    Fermento ahora vende únicamente por lote/batch de producción, así que
--    el "precio de venta al público" pasa a ser el mismo precio de mayoreo
--    que ya se cargó en precio_distribuidor (paso 3). Se actualiza tanto el
--    precio del producto padre como el de sus variantes (cuando el sabor
--    comparte el mismo precio de mayoreo en la hoja, que es el caso de
--    Linea de Pies, Strudels y Donas).
--
--    NO incluye productos 36-39 (Pan hamburguesa/hot dog brioche, Pan de
--    papa hamburguesa/hot dog): sus variantes "Caja 45 U./90 U." ya tienen
--    un precio distinto por tamaño de caja (descuento por volumen). La hoja
--    solo trae UN precio de mayoreo por producto, así que aplicarlo a ambas
--    cajas eliminaría ese descuento por tamaño — requiere decisión del
--    negocio antes de tocarlo.

UPDATE productos SET precio = precio_distribuidor
WHERE precio_distribuidor IS NOT NULL;

UPDATE producto_variantes v
JOIN productos p ON p.id = v.producto_id
SET v.precio = p.precio
WHERE p.nombre IN ('Linea de Pies', 'Linea de Strudels', 'Linea de Donas');

-- ✅ VERIFICACIÓN
SELECT id, nombre, precio, precio_distribuidor FROM productos ORDER BY id;
SELECT v.id, v.sabor, v.precio FROM producto_variantes v JOIN productos p ON p.id=v.producto_id WHERE p.nombre IN ('Linea de Pies','Linea de Strudels','Linea de Donas');

-- FIN DEL SCRIPT
