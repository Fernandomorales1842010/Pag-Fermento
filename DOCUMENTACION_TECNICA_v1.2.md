# Documentación técnica — `feature/it-mundi-ajustes`

Rama base: `main` (`0d8b1c7`). 5 commits, 17 archivos, +1281/-188 líneas.

```
c7e623e  Implementar puntos IT mundi (#5, #7, #22) y correcciones de catalogo
4e3be40  Agregar carrusel de productos recomendados en la pagina de producto
3a5f443  Agregar selector de lotes y repartidor de sabores en producto.php
6a01dd5  Corregir error fatal al confirmar pedidos con 2 o mas productos distintos
fa14a70  Reemplazar bloque de "Nuestra Filosofia" por franja de confianza
```

Este documento es la referencia técnica. `CHANGELOG_v1.2.md` es el resumen de negocio (léelo si necesitas contexto de por qué se hizo cada cosa); aquí está el cómo y con qué archivos.

---

## 1. Modelo de datos

### 1.1 Columna nueva

```sql
ALTER TABLE productos
  ADD COLUMN unidades_paquete INT(11) NULL DEFAULT NULL
  COMMENT 'Unidades por paquete fisico (informativo para el cliente)'
  AFTER minimo_compra;
```

Puramente informativa. No participa en ninguna validación — solo se usa para renderizar texto ("8 paquetes de 4 uds. c/u"). `minimo_compra` es el campo con peso funcional: representa el tamaño del lote de producción, no un piso mínimo arbitrario.

### 1.2 Redefinición semántica de `productos.minimo_compra`

| Antes | Ahora |
|---|---|
| Piso mínimo de compra (el cliente puede pedir ese número o más, en cualquier incremento) | Tamaño exacto del lote de producción. El total pedido debe ser un **múltiplo exacto** de este valor |

Esto no es un cambio de esquema, es un cambio de contrato — el campo es el mismo, pero el código que lo consume ahora exige múltiplo exacto en vez de "al menos". Ver §3.

### 1.3 Datos

- 20 productos existentes: `minimo_compra`, `unidades_paquete`, `precio_distribuidor` actualizados con datos de la hoja de cálculo de Sheny.
- 21 productos (los mismos 20 + Grissini): `precio` = `precio_distribuidor` (precio de mayoreo, ver CHANGELOG §"Precio de venta").
- Categoría `GOURMET` insertada en `categorias`; 6 productos reclasificados (estaban en `SALADO`).
- `producto_variantes` de "Linea de Donas": las 4 filas de ejemplo originales fueron reemplazadas por las 9 reales del negocio (`DELETE` + `INSERT`, no `UPDATE` — los IDs cambian).
- 2 productos nuevos: `Pan Chapata` (id 42), `Focaccia` (id 43).
- `configuracion`: nueva clave `multiplicador_pedido_grande` (default `'3'`), + `horario_lv_inicio/fin`, `horario_sab_inicio/fin` (ya existían como fallback en PHP, ahora también como filas).

**Excluidos a propósito** — productos 36-39 (Pan hamburguesa/hot dog brioche, Pan de papa hamburguesa/hot dog): usan variantes "Caja 45 U./90 U." con precio propio por tamaño de caja. La hoja de Sheny trae un solo precio de mayoreo por producto; aplicarlo a ambas cajas habría borrado el descuento por volumen que ya existía. `minimo_compra` se quedó en `1` para estos 4 — no participan en la lógica de lote (§3).

### 1.4 Scripts SQL

- **`database/fermento_instalacion_completa.sql`** — reescrito para ser la fuente única de verdad. Antes de esta rama, este script solo tenía el schema v1.0 (le faltaban `admin_logs`, `dias_feriados`, `pedido_historial` y las columnas de envío programado de v1.1). Ahora incluye v1.0 + v1.1 + v1.2 completo. `DROP DATABASE IF EXISTS` + recreación — instalación limpia, sin datos de pedidos.
- **`database/v1.2_it_mundi_ajustes.sql`** — migración incremental idempotente (`ADD COLUMN IF NOT EXISTS`, `ON DUPLICATE KEY UPDATE`, `DELETE`+`INSERT` para variantes) para bases de datos existentes que no se pueden recrear desde cero. 7 secciones numeradas con comentarios explicando cada decisión.

Ambos scripts requieren `SET NAMES utf8mb4;` al inicio — sin eso, `mysql -u root < archivo.sql` corrompe caracteres acentuados en INSERTs nuevos (se detectó y corrigió durante esta rama: "Maní"/"Azúcar" salían con mojibake).

---

## 2. Assets

- **`assets/img/default_pan.png`** (nuevo, 4.6 KB) — placeholder de marca generado con GD (`scripts/gen_default_pan.php`, no se ejecuta en producción, es una herramienta de build puntual). Reemplaza el ícono de imagen rota cuando falta la foto real de un producto.
- Todos los `<img>` de producto ahora tienen `onerror="this.onerror=null;this.src='assets/img/default_pan.png';"` — el `onerror=null` previo al reasignar `src` evita un loop infinito si el placeholder también fallara. Archivos tocados: `index.php`, `ajax/catalogo.php`, `ajax/carrito.php`, `producto.php` (imagen principal + miniaturas — antes no tenían fallback), `recibo.php`, `admin/productos.php`.
- `index.php` tenía un fallback a `https://via.placeholder.com/...` — servicio externo caído. Se reemplazó por el fallback local.
- **Nota de compatibilidad con `main`:** ningún archivo `assets/img/pan_*.png` ha estado nunca trackeado en git (siempre estuvieron en `.gitignore`). El merge no puede tocar ni borrar fotos reales de producto en ningún checkout — solo agrega `default_pan.png` como archivo nuevo.

---

## 3. Lógica de venta por lote

### 3.1 Regla

El total combinado de un producto en el carrito debe ser un múltiplo exacto de `minimo_compra` (o `minimo_compra_padre` para productos con variantes combinables). No basta con "al menos el mínimo".

### 3.2 Dónde se aplica

| Archivo | Qué hace |
|---|---|
| `producto.php` | UI: fuerza que la cantidad seleccionada solo pueda ser un múltiplo (stepper por lote, o repartidor con validación de suma exacta — ver §4) |
| `ajax/carrito.php` | Paso de los botones +/- del carrito lateral = `minimo_compra` del ítem (no 1). Validación server-side: `$sumaCombinada % $min !== 0` marca `bajo_minimo = true` |
| `checkout.php` | Misma validación server-side antes de permitir confirmar el pedido. Mensaje distingue "no alcanza el mínimo" (`actual < minimo`) de "no es múltiplo" (`actual >= minimo` pero no exacto, campo `parcial` en el array de violaciones) |
| `assets/js/main.js` | `updateCartUI()` — mensaje de alerta en el carrito usa el flag `parcial` para mostrar el texto correcto |

### 3.3 Por qué la validación server-side importa

La UI (producto.php) hace prácticamente imposible generar una cantidad inválida por accionar los controles normales. La validación en `ajax/carrito.php`/`checkout.php` es defensa en profundidad: cualquier código que llegue a mutar `$_SESSION['carrito']` por otra vía (debugging, un endpoint futuro, manipulación directa del POST) sigue quedando bloqueado en el checkout. No confíes solo en el cliente.

### 3.4 Excepción

Productos 36-39 (`minimo_compra = 1`) no entran en esta regla — se compran en unidades libres, como siempre.

---

## 4. Componentes de UI nuevos (`producto.php`)

Todo lo de esta sección vive en un único archivo grande; la tabla de funciones JS (§5) es la referencia rápida.

### 4.1 Selector de lotes (productos sin variantes, `minimo_compra > 1`)

Reemplaza el stepper de unidades sueltas. Campo `#lotesInput` (editable, tipeable) representa **número de lotes**, no unidades. `#qty` (hidden) siempre = `lotes × minimo_compra` — es el campo que de verdad se envía al agregar al carrito.

```
lotesInput (visible) → recalcularLotesSimple() → qty (hidden) → addCurrentToCart()
```

### 4.2 Repartidor de sabores (productos con variantes combinables: Linea de Pies, Strudels, Donas)

Condición: `$esLoteCombinable = $tieneVariantes && $min_padre > 1` (calculado en PHP, línea ~45 de `producto.php`).

Flujo:
1. `#lotesInput` fija el total (`lotes × minimo_padre`).
2. Reparto automático parejo entre sabores al cambiar el número de lotes (`recalcularReparto()`).
3. Cada sabor tiene su propio stepper (`ajustarReparto(varianteId, dir)`), independiente.
4. `recalcularContador()` corre después de cualquier cambio: suma todas las filas, actualiza el contador visual (verde si cuadra, rojo si no) y **deshabilita el botón "Añadir al Carrito" si la suma no es exacta**.
5. `addLoteCombinado(prodId)` — hace un `fetch` a `ajax/carrito.php` por cada sabor con cantidad > 0, secuencial (`await` en loop), y al final refresca el carrito una sola vez. No hay endpoint batch nuevo; reutiliza `ajax/carrito.php?accion=agregar` tal cual ya existía.

Reparto inicial calculado en PHP (`$repartoInicial`, línea ~48), no en JS — evita un flash de "0/0" antes de que corra el JS.

### 4.3 Carrusel "También te puede interesar"

Query en PHP (líneas ~68-100 de `producto.php`): productos de la misma categoría, excluyendo el actual y los agotados; si la categoría tiene menos de 8 productos disponibles, rellena con destacados de otras categorías.

Carrusel = `overflow-x: auto` + `scroll-snap-type` nativo, sin librería. `desplazarRecomendados(dir)` mueve el scroll con `scrollBy()`; `actualizarFlechasRecomendados()` oculta la flecha correspondiente al llegar a un extremo (clases `.recomendados-wrapper--inicio` / `--fin`).

### 4.4 CTA de WhatsApp contextual ("pedido grande o especial")

Un solo elemento (`#cajaPedidoEspecial`) con dos estados visuales, no dos elementos separados. Por defecto es un link sutil; `actualizarInfoLote()` le agrega la clase `.caja-pedido-especial--grande` cuando `cantidad >= minimo_padre × multiplicador` (multiplicador viene de `configuracion.multiplicador_pedido_grande`, editable en `admin/configuracion.php`). El mensaje de WhatsApp precargado cambia según el estado.

### 4.5 Fallback de imágenes

Ver §2.

---

## 5. Referencia de funciones JS (`producto.php`)

| Función | Rama que la usa | Qué hace |
|---|---|---|
| `updateQty(dir)` | Sin variantes / Caja 45-90 U. | +/- de la cantidad libre; usa `data-minimo` como tamaño de paso |
| `validarQtyLibre(input)` | Caja 45-90 U. | Clamp on-change a `[min, max]` cuando el usuario escribe directo |
| `cambiarLotes(dir)` / `onLotesInputChange()` | Simple y combinable | +/- o edición directa de `#lotesInput`; delega a `recalcularLotesSimple()` o `recalcularReparto()` según exista `#repartidorLista` |
| `recalcularLotesSimple()` | Simple (sin variantes) | `qty.value = lotes × minimo_padre`, actualiza texto y llama `actualizarInfoLote()` |
| `recalcularReparto()` | Combinable | Recalcula el total y **resetea** el reparto parejo entre sabores |
| `ajustarReparto(varianteId, dir)` | Combinable | +/- de un sabor individual, sin tocar los demás |
| `recalcularContador()` | Combinable | Suma, pinta el contador, sincroniza `#qty`, habilita/deshabilita el botón de agregar |
| `addLoteCombinado(prodId)` | Combinable | `async` — agrega cada sabor al carrito vía `fetch` secuencial |
| `actualizarInfoLote()` | Todas las que venden por lote | Texto "N lotes (X uds.) — Y paquetes..." + toggle del CTA de pedido grande |
| `desplazarRecomendados(dir)` / `actualizarFlechasRecomendados()` | Carrusel | Scroll nativo + estado de flechas |
| `addCurrentToCart(prodId)` | Simple, Caja 45-90 U. | Sin cambios de comportamiento — sigue leyendo `#qty` y la variante seleccionada (si aplica) |

---

## 6. Bug corregido: `pedido_confirmado.php`

**Síntoma:** `Fatal error: Uncaught PDOException: SQLSTATE[HY093]: Invalid parameter number` al confirmar cualquier pedido con 2+ productos distintos.

**Causa:**
```php
$prodIds = array_unique(array_column($detalles, 'producto_id'));
// $detalles = [{producto_id:17}, {producto_id:17}, {producto_id:35}]
// array_column → [0=>17, 1=>17, 2=>35]
// array_unique → [0=>17, 2=>35]   ← llave 1 desaparece, quedan huecos
$stmtMin->execute($prodIds); // PDO con EMULATE_PREPARES=false exige llaves 0,1,2... consecutivas
```

**Fix:** `array_values(array_unique(...))` — reindexar antes de pasar a `execute()`. Mismo patrón que ya usaba correctamente el código original en `producto.php` (`array_values(array_unique(array_filter(...)))`) para las listas de tamaño/sabor.

**Alcance del bug:** solo `pedido_confirmado.php`. Se revisó `admin/ver_pedido.php` (lógica similar de WhatsApp) — no tiene este patrón, no está afectado.

---

## 7. Homepage (`index.php`)

Sección "Nuestra Filosofía" (h2 centrado + párrafo, `.section` con 80px de padding arriba y abajo) eliminada. Reemplazada por `.trust-strip` — franja de 36px de padding con 3 `.trust-item` (ícono + título + subtítulo), ubicada **después** de la cuadrícula de productos destacados en vez de antes. Objetivo: que el usuario llegue a productos comprables inmediatamente después del hero.

---

## 8. Cómo probar

```powershell
# 1. Levantar MySQL y el servidor PHP (ver database/fermento_instalacion_completa.sql)
& "C:\xampp\mysql\bin\mysqld.exe" --standalone --console
& "C:\xampp\php\php.exe" -S localhost:8010 -t .
```

Casos a validar manualmente:

| Caso | URL | Qué verificar |
|---|---|---|
| Lote simple | `producto.php?id=32` (Baguette) | Stepper de lotes, texto "N lotes (X uds.)", CTA de WhatsApp se activa a partir de 3 lotes |
| Lote combinable | `producto.php?id=17` (Linea de Pies) | Reparto parejo inicial, contador rojo si desbalanceas, botón deshabilitado hasta que cuadre, se agregan ambos sabores al carrito en un clic |
| Caja 45/90 U. | `producto.php?id=36` | Selector clásico intacto, cantidad libre editable con clamp |
| Múltiplo exacto en checkout | Agregar 31 uds. combinadas de un producto de lote 30 | Carrito y checkout bloquean con mensaje "debe ser múltiplo de 30" |
| Carrusel | Cualquier `producto.php?id=X` | 8 recomendados, flechas se ocultan en los extremos |
| Confirmación con 2+ productos | Completar checkout con 2 productos distintos | Ya no debe dar fatal error (regresión del bug §6) |
| Imágenes | Cualquier página con productos sin foto real | Ícono de pan placeholder, no cuadro roto |

---

## 9. Migrar a producción

```bash
# Base de datos existente (no se puede recrear desde cero):
mysql -u root -p tu_bd < database/v1.2_it_mundi_ajustes.sql

# Instalación nueva / entorno de staging:
mysql -u root -p < database/fermento_instalacion_completa.sql
```

No requiere cambios en `.env`, no requiere reiniciar el servidor web, no toca fotos de producto (§2).

---

## 10. Pendiente / fuera de alcance de esta rama

- Productos 36-39: definir si el precio de mayoreo colapsa el descuento por caja de 45/90 U., o si se mantiene el esquema actual.
- Puntos del listado de Equipo IT mundi #8, #9 (pendientes de reunión del 08/09), #18 y #21 (esperando que Sheny defina tiempos/política de horas) — no implementados.
- El CSS de `.trust-strip` quedó en el commit del selector de lotes (`3a5f443`) en vez del commit de homepage (`fa14a70`) — error de reconstrucción de commits durante el split, documentado en el mensaje del commit `fa14a70`. No afecta funcionalidad, solo prolijidad del historial.
