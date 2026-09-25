# 🗄️ Fermento — Estructura de Base de Datos
> **Versión:** 1.2 | **Motor:** MySQL 8.x | **Charset:** utf8mb4_unicode_ci

---

## Resumen de Tablas

| # | Tabla | Descripción | Registros iniciales |
|---|---|---|---|
| 1 | `categorias` | Categorías de productos | 4 |
| 2 | `configuracion` | Parámetros globales del sistema | 14 |
| 3 | `usuarios` | Clientes y administradores | 3 |
| 4 | `zonas_envio` | Zonas geográficas de entrega | 7 |
| 5 | `cupones` | Códigos de descuento | 2 |
| 6 | `productos` | Catálogo de productos | 27 |
| 7 | `producto_variantes` | Variantes de productos (sabores/tamaños) | 22 |
| 8 | `pedidos` | Órdenes de compra | vacía |
| 9 | `detalles_pedido` | Líneas de cada pedido | vacía |
| 10 | `admin_logs` | Bitácora de actividad admin | vacía |
| 11 | `dias_feriados` | Días bloqueados para entrega | vacía |
| 12 | `pedido_historial` | Historial de modificaciones de pedidos | vacía |

---

## Diagrama de Relaciones (ERD)

```
categorias          configuracion       dias_feriados
   (ref)                (solo)              (solo)

productos ──────────── producto_variantes
    │  (1:N, CASCADE)
    │
    └──── detalles_pedido ──── pedidos ──── usuarios
              (N:1)              (N:1)        (1:N)
                                   │
                            pedido_historial
                            admin_logs (ref usuario_id)
zonas_envio ─── pedidos.zona_envio_id
cupones     ─── pedidos.cupon_id
```

---

## Detalle de cada Tabla

---

### 1. `categorias`
Agrupa los productos en familias. Usada como filtro en el catálogo público.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `nombre` | VARCHAR(80) UNIQUE | Nombre de la categoría |
| `icono` | VARCHAR(60) | Clase CSS de Font Awesome |
| `orden` | INT | Posición en el menú |
| `activa` | TINYINT(1) | 1 = visible, 0 = oculta |
| `creado_en` | TIMESTAMP | Fecha de creación |

**Datos actuales:**
| id | nombre | icono |
|---|---|---|
| 1 | DULCE | `fas fa-cookie` |
| 2 | SALADO | `fas fa-bread-slice` |
| 3 | PANADERIA | `fas fa-birthday-cake` |
| 4 | GOURMET | `fas fa-crown` |

---

### 2. `configuracion`
Parámetros del sistema editables desde el panel admin. Sistema clave-valor.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `clave` | VARCHAR(80) UNIQUE | Nombre del parámetro |
| `valor` | TEXT | Valor actual |
| `descripcion` | VARCHAR(200) | Descripción del parámetro |

**Claves disponibles:**

| Clave | Valor por defecto | Descripción |
|---|---|---|
| `costo_envio` | `30.00` | Costo de envío en Quetzales |
| `envio_gratis_minimo` | `0` | Monto mínimo para envío gratis (0 = desactivado) |
| `whatsapp_numero` | `50249420696` | Número con código de país |
| `nombre_tienda` | `Fermento` | Nombre de la panadería |
| `email_contacto` | `admin@gmail.com` | Email principal |
| `moneda` | `Q` | Símbolo de moneda |
| `zona_predeterminada` | `1` | ID de zona por defecto |
| `pedidos_activos` | `1` | 1 = acepta pedidos, 0 = tienda cerrada |
| `mensaje_cerrado` | *(texto)* | Mensaje cuando la tienda está cerrada |
| `horario_lv_inicio` | `08:00` | Hora inicio entregas Lunes-Viernes |
| `horario_lv_fin` | `17:00` | Hora fin entregas Lunes-Viernes |
| `horario_sab_inicio` | `08:00` | Hora inicio entregas Sábado |
| `horario_sab_fin` | `12:00` | Hora fin entregas Sábado |
| `multiplicador_pedido_grande` | `3` | Múltiplo del mínimo que activa el aviso de pedido especial |

> **Cómo leer un valor:** `getConfig('costo_envio')` → `'30.00'`  
> **Cómo escribir un valor:** `setConfig('pedidos_activos', '0')`

---

### 3. `usuarios`
Clientes y administradores del sistema. Soporta login con email/password y Google OAuth.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `nombre` | VARCHAR(100) | Nombre completo |
| `email` | VARCHAR(100) UNIQUE | Email (login principal) |
| `telefono` | VARCHAR(20) | Teléfono de contacto |
| `direccion` | TEXT | Dirección de entrega |
| `password` | VARCHAR(255) | Hash bcrypt (NULL si login Google) |
| `google_id` | VARCHAR(255) | ID de Google OAuth (NULL si login normal) |
| `avatar` | VARCHAR(255) | Nombre de archivo del avatar |
| `fecha_registro` | DATETIME | Fecha de registro |
| `rol` | ENUM | `cliente` / `admin` / `supervisor` |

**Roles:**
- `cliente` — Solo puede ver sus pedidos
- `supervisor` — Gestiona pedidos e inventario, no puede modificar configuración ni usuarios
- `admin` — Acceso total al sistema

---

### 4. `zonas_envio`
Define las áreas geográficas a las que se realiza entrega. Puede tener costo propio o usar el global.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `nombre` | VARCHAR(100) UNIQUE | Nombre de la zona |
| `descripcion` | TEXT | Descripción/restricciones |
| `activa` | TINYINT(1) | 1 = activa |
| `costo_envio` | DECIMAL(10,2) | Costo específico (NULL = usar costo global) |
| `creado_en` | TIMESTAMP | Fecha de creación |

---

### 5. `cupones`
Códigos de descuento aplicables en el checkout.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `codigo` | VARCHAR(50) UNIQUE | Código a ingresar por el cliente |
| `tipo` | ENUM | `porcentaje` / `fijo` |
| `valor` | DECIMAL(10,2) | Monto o porcentaje del descuento |
| `fecha_expira` | DATE | Fecha de expiración |
| `usos_maximos` | INT | Máximo de usos permitidos |
| `usos_actuales` | INT | Contador de usos actuales |
| `activo` | TINYINT(1) | 1 = activo |

**Cupones iniciales:**
| Código | Tipo | Valor |
|---|---|---|
| `BIENVENIDA10` | porcentaje | 10% |
| `DESCUENTOQ50` | fijo | Q50.00 |

---

### 6. `productos`
Catálogo principal. Los productos sin variantes venden directamente; los que tienen variantes (`tiene_variantes = 1`) requieren selección.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `nombre` | VARCHAR(100) | Nombre del producto |
| `descripcion` | TEXT | Descripción larga |
| `maridaje` | TEXT | Sugerencia de maridaje |
| `precio` | DECIMAL(10,2) | Precio de venta (= precio mayoreo) |
| `categoria` | VARCHAR(50) | Nombre de categoría (DULCE/SALADO/PANADERIA/GOURMET) |
| `imagen` | VARCHAR(255) | Imagen principal |
| `imagen_2` | VARCHAR(255) | Segunda imagen |
| `imagen_3` | VARCHAR(255) | Tercera imagen |
| `destacado` | TINYINT(1) | 1 = aparece en sección destacados |
| `stock` | INT | Stock disponible |
| `oferta` | TINYINT(1) | 1 = tiene descuento de oferta |
| `tiene_variantes` | TINYINT(1) | 1 = usa tabla `producto_variantes` |
| `sku` | VARCHAR(60) | Código interno de producto |
| `precio_distribuidor` | DECIMAL(10,2) | Precio de mayoreo (referencia interna) |
| `minimo_compra` | INT | Mínimo de unidades por pedido (= tamaño de lote) |
| `unidades_paquete` | INT | Unidades por paquete físico (informativo) |
| `fecha_creacion` | DATETIME | Fecha de alta |

> **Nota v1.2:** `minimo_compra` = tamaño del lote de producción. El cliente avanza de lote en lote, no de 1 en 1.

---

### 7. `producto_variantes`
Variantes de un producto (sabores, tamaños). Se activan cuando `productos.tiene_variantes = 1`.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `producto_id` | INT FK | Referencia a `productos.id` (CASCADE DELETE) |
| `tamano` | VARCHAR(80) | Tamaño (ej: "Caja 45 U.") |
| `sabor` | VARCHAR(80) | Sabor (ej: "Coco", "Fresa") |
| `nombre` | VARCHAR(200) | Nombre completo mostrado al cliente |
| `precio` | DECIMAL(10,2) | Precio de esta variante |
| `stock` | INT | Stock de esta variante |
| `sku` | VARCHAR(60) | Código interno de variante |
| `minimo_compra` | INT | Mínimo por variante |

**FK:** `producto_id → productos.id` (ON DELETE CASCADE, ON UPDATE CASCADE)

---

### 8. `pedidos`
Registro de cada orden realizada.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Número de pedido |
| `usuario_id` | INT | Referencia a `usuarios.id` (puede ser NULL) |
| `nombre_cliente` | VARCHAR(100) | Nombre del comprador |
| `direccion_envio` | TEXT | Dirección de entrega |
| `telefono` | VARCHAR(20) | Teléfono de contacto |
| `subtotal` | DECIMAL(10,2) | Subtotal sin envío ni descuento |
| `costo_envio` | DECIMAL(10,2) | Costo de envío aplicado |
| `descuento` | DECIMAL(10,2) | Descuento de cupón aplicado |
| `total` | DECIMAL(10,2) | Total final a pagar |
| `estado` | VARCHAR(20) | `pendiente` / `preparando` / `en_camino` / `completado` / `cancelado` |
| `fecha` | DATETIME | Fecha y hora del pedido |
| `notas` | TEXT | Notas adicionales del cliente |
| `zona_envio_id` | INT FK | Zona de entrega seleccionada |
| `cupon_id` | INT FK | Cupón usado (NULL si ninguno) |
| `cupon_codigo` | VARCHAR(50) | Código del cupón (texto plano, referencia histórica) |
| `metodo_contacto` | VARCHAR(20) | `normal` / `whatsapp` |
| `fecha_envio_programada` | DATE | Fecha elegida de entrega |
| `hora_envio_programada` | TIME | Hora elegida de entrega |

---

### 9. `detalles_pedido`
Líneas de productos de cada pedido.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `pedido_id` | INT FK | Referencia a `pedidos.id` (CASCADE DELETE) |
| `producto_id` | INT FK | Referencia a `productos.id` (RESTRICT DELETE) |
| `nombre_producto` | VARCHAR(100) | Snapshot del nombre al momento de la compra |
| `precio_unitario` | DECIMAL(10,2) | Snapshot del precio al momento de la compra |
| `cantidad` | INT | Cantidad ordenada |
| `variante_id` | INT | ID de variante (NULL si sin variante) |
| `variante_nombre` | VARCHAR(200) | Snapshot del nombre de variante |

**FKs:**
- `pedido_id → pedidos.id` (ON DELETE CASCADE)
- `producto_id → productos.id` (ON DELETE RESTRICT)

---

### 10. `admin_logs`
Bitácora de todas las acciones realizadas desde el panel admin.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `usuario_id` | INT | Quién realizó la acción |
| `usuario_nombre` | VARCHAR(100) | Nombre (snapshot) |
| `accion` | VARCHAR(50) | Código de acción (ej: `crear_producto`) |
| `entidad` | VARCHAR(50) | Tabla afectada (ej: `productos`) |
| `entidad_id` | INT | ID del registro afectado |
| `detalle` | TEXT | Descripción legible |
| `ip` | VARCHAR(45) | IP del admin |
| `fecha` | TIMESTAMP | Fecha y hora |

> **Uso:** `adminLog('editar_producto', 'productos', $id, 'Baguette');`

---

### 11. `dias_feriados`
Días bloqueados para programar entregas. Gestionados desde Admin → Configuración.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `fecha` | DATE UNIQUE | Fecha del feriado |
| `descripcion` | VARCHAR(100) | Nombre del feriado |
| `creado_por` | INT | `usuario_id` que lo registró |
| `fecha_registro` | DATETIME | Cuándo se registró |

---

### 12. `pedido_historial`
Registro de modificaciones hechas por admin/supervisor a un pedido existente.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK AI | Identificador único |
| `pedido_id` | INT FK | Referencia a `pedidos.id` (CASCADE DELETE) |
| `usuario_id` | INT | Quién hizo el cambio |
| `campo_modificado` | VARCHAR(100) | Campo que se cambió |
| `valor_anterior` | TEXT | Valor antes del cambio |
| `valor_nuevo` | TEXT | Valor después del cambio |
| `motivo` | TEXT | Razón del cambio |
| `fecha` | DATETIME | Cuándo se hizo el cambio |

---

## Scripts SQL disponibles

| Archivo | Propósito |
|---|---|
| `database/fermento_instalacion_completa.sql` | ⭐ Instalación completa desde cero (v1.0 + v1.1 + v1.2) |
| `database/schema.sql` | Estructura sola (sin datos) |
| `database/v1.2_it_mundi_ajustes.sql` | Migración incremental para bases existentes → v1.2 |
| `database/migration_fase1_supervisor.sql` | Migración v1.0 → v1.1 (rol supervisor) |
| `database/migration_fk.sql` | Agrega llaves foráneas faltantes |
| `fix_zonas_costo.sql` | Corrección puntual de costos de zona |
| `datos_prueba_dashboard.sql` | Datos de prueba para el dashboard |

### Instalación limpia
```bash
mysql -u root -p < database/fermento_instalacion_completa.sql
```

### Verificación post-instalación
```sql
USE DB_fermento;
SELECT tabla, filas FROM (
  SELECT 'productos' AS tabla, COUNT(*) AS filas FROM productos
  UNION ALL SELECT 'categorias', COUNT(*) FROM categorias
  UNION ALL SELECT 'usuarios', COUNT(*) FROM usuarios
) t;
```
