# 📋 Fermento — Historial de Versiones (CHANGELOG)
> Registro cronológico de todos los cambios del proyecto desde su inicio.

---

## [v1.2] — Septiembre 2026
> **Responsable:** Equipo IT mundi  
> **Fuente:** Solicitudes acordadas con el negocio + correcciones de catálogo (Sheny)

### 🛒 Compra por Lote de Producción
- **Mínimos de compra = tamaño de lote:** El campo `minimo_compra` de cada producto ahora representa el tamaño real del lote de producción (ej. Francés = 126 uds., Baguette = 32 uds.), actualizado para 20 productos con los datos de Sheny.
- **Selector de cantidad por lote:** Los botones `+`/`-` avanzan de lote en lote (no de 1 en 1) en productos sin variantes combinables.
- **Nueva columna `unidades_paquete`:** Dato informativo que muestra cuántas unidades hay por paquete físico, visible junto al selector de cantidad.
- **Productos nuevos:** Se agregaron **Pan Chapata** y **Focaccia** al catálogo.
- **Categoría GOURMET:** La línea de brioches y Grissini fue reclasificada desde SALADO a su propia categoría GOURMET.
- **Precio unificado:** El precio de venta al cliente es ahora igual al precio de mayoreo. Excepción: brioches con variantes "Caja 45/90 U." mantienen precios por volumen.
- **Línea de Donas corregida:** Los 4 sabores de ejemplo fueron reemplazados por los **9 sabores reales** del negocio (Coco, Maní, Capuchino, Anicillo, Pedritos, Blanco liso, Blanco con anicillo, Fresa con anicillo, Azúcar glass).

### 📱 WhatsApp — Avisos Automáticos
- **Recordatorio post-pedido (#5):** Popup automático al confirmar una orden explicando que el pedido no llega a la panadería hasta que el cliente lo envíe por WhatsApp.
- **Aviso de pedido grande/especial (#7):** Se detectan automáticamente pedidos que superan `multiplicador_pedido_grande × minimo_compra`. Cuando se cruza el umbral, el enlace WhatsApp se convierte en una tarjeta destacada con mensaje personalizado.
- **Multiplicador editable:** Nuevo campo en Admin → Configuración → Envío (por defecto: **3×**).

### 🖼️ Assets e Imágenes
- **Placeholder de marca:** Se creó `assets/img/default_pan.png` para reemplazar imágenes rotas cuando falta la foto real de un producto.
- **Fallback corregido:** El `onerror` en `index.php` fue actualizado desde el servicio externo `via.placeholder.com` (caído) al placeholder local.

### 🗄️ Base de Datos
- **`fermento_instalacion_completa.sql` consolidado:** Ahora es la fuente única de verdad: una instalación desde cero queda en estado v1.0 + v1.1 + v1.2.
- **Nueva migración incremental:** `v1.2_it_mundi_ajustes.sql` para bases existentes en producción.

---

## [v1.1] — Julio 2026
> **Enfoque:** Robustecimiento de reglas de negocio, nuevo sistema de roles y mejoras al flujo operativo.

### 🛒 Reglas de Negocio y Carrito
- **Mínimos combinados (F4):** El sistema agrupa automáticamente las cantidades de variantes del mismo producto. Ejemplo: 3 empanadas de carne + 3 de pollo = mínimo de 6 superado.
- **Restricción de compra rápida:** Se eliminó el botón "Añadir al carrito" rápido desde el catálogo. Las tarjetas redirigen obligatoriamente a la página de detalle del producto.

### 👥 Gestión Operativa y Panel Admin
- **Nuevo rol Supervisor (F3):** Puede gestionar pedidos e inventario, pero no puede modificar configuración del sistema, usuarios ni roles.
- **Motor de permisos granulares:** Implementado en `auth_admin.php` para proteger todas las rutas del panel admin.
- **Modificación de Pedidos (F2):** Admin y supervisor pueden editar un pedido existente. Al hacerlo, el cliente recibe una alerta "Cambio propuesto en tu pedido" para que apruebe o rechace.
- **Tiempos de anticipación dinámicos (F1):** Campo configurable "Horas de anticipación requeridas". El calendario de entrega en `checkout.php` bloquea dinámicamente los días no válidos (por defecto: 42 horas).

### 📱 Notificaciones y Comunicación
- **WhatsApp enriquecido:** El mensaje automático incluye resumen completo del pedido, total y link de seguimiento. El botón fue renombrado a "Continuar en WhatsApp".
- **Notificación interna al admin:** Botón "Enviar a WhatsApp" en `ver_pedido.php` para generar reporte interno al dueño.

### 📊 Analítica y Dashboard
- **Producto Estrella:** Cálculo del producto más vendido en el periodo actual.
- **Ventas por Año:** Gráfico de rendimiento anual.
- **Rendimiento por Horarios:** Gráfica de horas de mayor tráfico y conversión.

### 🎨 UI/UX y Responsividad
- **Tracking dinámico de pedidos:** El cliente ve el flujo real: `Recibido → Preparando → En Camino → Completado` (se eliminó el estado ambiguo "Entregado").
- **Panel admin modernizado:** Menú lateral colapsable en escritorio, scroll independiente estilizado, tablas que se convierten en tarjetas en móvil.
- **Eliminación del sistema de reseñas/estrellas** por decisión del negocio.

### 🗄️ Base de Datos
- Nueva tabla `admin_logs` — bitácora de actividad del panel admin.
- Nueva tabla `dias_feriados` — días bloqueados para programar entregas.
- Nueva tabla `pedido_historial` — registro de modificaciones a pedidos.
- Nuevas columnas en `pedidos`: `fecha_envio_programada`, `hora_envio_programada`.
- Script de migración: `migration_fase1_supervisor.sql`.

---

## [v1.0] — Enero 2026
> **Lanzamiento inicial** de la plataforma de e-commerce Fermento.

### ✅ Funcionalidades Base
- Catálogo de productos con categorías (DULCE, SALADO, PANADERIA).
- Página de detalle de producto con galería de imágenes.
- Sistema de carrito de compras con sesiones.
- Checkout completo con selección de zona de envío.
- Sistema de cupones de descuento (porcentaje y monto fijo).
- Registro y login de usuarios (email/password y Google OAuth).
- Panel de administración para gestión de:
  - Productos e inventario
  - Pedidos
  - Usuarios
  - Zonas de envío
  - Cupones
  - Configuración general
- Generación de recibo PDF con FPDF.
- Integración WhatsApp para confirmación de pedidos.
- Envío programado con selección de fecha y hora.
- Historial de pedidos del cliente con tracking de estado.
- Protección CSRF en todos los formularios.
- Headers de seguridad HTTP via `.htaccess`.
- Bloqueo de acceso a archivos sensibles (`.env`, `.sql`, `.csv`, `.log`).

### 🗄️ Base de Datos inicial
Tablas: `categorias`, `configuracion`, `usuarios`, `zonas_envio`, `cupones`, `productos`, `producto_variantes`, `pedidos`, `detalles_pedido`.

---

## Pendientes / Roadmap

| # | Feature | Estado | Notas |
|---|---|---|---|
| #8 | Política de precios para brioches con variantes | 🟡 Pendiente | Esperando decisión del negocio |
| #9 | Unificación de precios mayoreo | 🟡 Pendiente | Debate en reunión 08/09 |
| #18 | Política de horas de entrega | 🟡 Pendiente | Sheny debe definir tiempos |
| #21 | Horarios especiales | 🟡 Pendiente | Sheny debe definir política |
| — | Dominio personalizado + HTTPS | 🔵 En progreso | Conectar GoDaddy + Certbot |
| — | Elastic IP en AWS | 🔵 Pendiente | Para IP fija en producción |
| — | `mysql_secure_installation` | 🔵 Pendiente | Hardening de seguridad MySQL |

---

*Ver scripts de migración en la carpeta `database/` para aplicar cambios incrementales a bases de datos existentes.*
