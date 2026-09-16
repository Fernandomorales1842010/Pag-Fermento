# Documentación de Actualización - Fermento V1.2

La versión 1.2 de Fermento implementa los puntos asignados a **Equipo IT mundi** en el listado de mejoras acordado con el negocio, e incorpora las correcciones de catálogo (mínimos de compra, sabores, categorías) recibidas de Sheny.

## 1. Compra por Lote de Producción
* **Mínimos de compra = tamaño de lote:** Fermento vende por lote de producción, no por unidad suelta. El campo `minimo_compra` de cada producto ahora representa el tamaño real del lote (ej. Francés = 126 uds., Baguette = 32 uds.), actualizado con los datos entregados por Sheny para 20 productos existentes.
* **Selector de cantidad por lote:** En la página de producto y en el carrito, los botones `+`/`-` ahora avanzan de lote en lote (no de 1 en 1) para los productos sin variantes combinables, evitando que un cliente pida un lote parcial.
* **Nueva columna `unidades_paquete`:** Referencia informativa (unidades por paquete físico) que se muestra junto al selector de cantidad, ej. *"3 lotes de producción (96 uds.) — 24 paquetes de 4 uds. c/u"*.
* **Productos nuevos:** Se agregaron **Pan Chapata** y **Focaccia** al catálogo con los datos de la hoja de Sheny (precio, mínimo, empaque).
* **Categoría GOURMET:** La línea de brioches (hamburguesa/hot dog, pan de papa, pan de molde) y Grissini estaban mal clasificados como "SALADO"; ahora tienen su propia categoría GOURMET, visible como filtro en el catálogo.
* **Precio de venta = precio de mayoreo:** Como Fermento ahora solo vende por lote completo de producción, el precio que ve el cliente pasó a ser el mismo precio de mayoreo entregado por Sheny (antes eran dos precios distintos). Aplica a los 21 productos con dato de mayoreo en la hoja — **excepto** Pan/Hot dog de hamburguesa brioche, Pan de papa hamburguesa/hot dog (productos con variantes "Caja 45 U./90 U." que ya tienen descuento por volumen propio; la hoja solo trae un precio único por producto, así que unificarlo eliminaría ese descuento — pendiente de decisión del negocio).
* **Línea de Donas corregida:** Los 4 sabores de ejemplo cargados originalmente no correspondían a ningún producto real; se reemplazaron por los 9 sabores reales del negocio (Coco, Maní, Capuchino, Anicillo, Pedritos, Blanco liso, Blanco con anicillo, Fresa con anicillo, Azúcar glass).

## 2. WhatsApp — Aviso Automático y Pedido Especial
* **Recordatorio automático de envío (punto #5):** Al confirmar un pedido, aparece un popup automático explicando que el pedido **aún no llega a la panadería** hasta que el cliente lo envíe por WhatsApp, con botón directo. Complementa (no reemplaza) el botón "Continuar en WhatsApp" ya existente.
* **Aviso de pedido grande/especial (punto #7):** Se agregó un multiplicador configurable (`multiplicador_pedido_grande`, por defecto **3×** el mínimo/lote) que detecta pedidos grandes automáticamente. Cuando se cruza el umbral, el enlace de WhatsApp —antes un botón verde siempre visible y redundante junto a "Añadir al Carrito"— se convierte en una tarjeta destacada con mensaje personalizado (producto y cantidad incluidos). Por debajo del umbral queda como un enlace sutil, evitando saturar la pantalla. Se evalúa tanto en la página de producto como después de confirmar el pedido.
* **Multiplicador editable:** Nuevo campo en **Admin → Configuración → Envío**.

## 3. Assets e Imágenes
* **Placeholder de marca:** Se generó `assets/img/default_pan.png` (ícono de pan en la paleta de la marca) para reemplazar los cuadros rotos que aparecían en catálogo, carrito, recibo y panel admin cuando falta la foto real de un producto.
* **Fallback corregido:** El `onerror` de la página de inicio apuntaba a un servicio externo (`via.placeholder.com`) que dejó de responder; ahora, como el resto del sitio, usa el placeholder local. Se agregaron fallbacks donde no existían (imagen principal y miniaturas de producto).

## 4. Base de Datos
* **`database/fermento_instalacion_completa.sql` consolidado:** El script de instalación había quedado desactualizado — no incluía las tablas `admin_logs`, `dias_feriados`, `pedido_historial` ni las columnas de envío programado agregadas en v1.1. Ahora es nuevamente la fuente única de verdad: una instalación limpia con este archivo deja la base de datos exactamente en el estado actual (v1.0 + v1.1 + v1.2), sin necesidad de aplicar migraciones sueltas después.
* **Migración incremental:** `database/v1.2_it_mundi_ajustes.sql` — para bases de datos ya existentes que no se pueden recrear desde cero (ej. producción), aplica únicamente el delta de esta versión.

---
*Fuente de los cambios: hoja de cálculo compartida ("Solicitudes antes de entrega final" + "Productos corregidos"), filtrada a filas con Realizado por = "Equipo IT mundi". Implementados: #5, #7, #22. Pendientes de definición del negocio: #8 y #9 (se debaten en reunión del 08/09), #18 y #21 (falta que Sheny defina tiempos/política de horas).*
