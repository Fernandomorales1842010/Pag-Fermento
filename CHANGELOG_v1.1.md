# Documentación de Actualización - Fermento V1.1

La versión 1.1 de Fermento se enfoca en robustecer las reglas de negocio, optimizar el flujo de trabajo operativo mediante nuevos roles de usuario, mejorar las estadísticas del negocio y perfeccionar la experiencia del usuario tanto en el panel de administración como en la tienda pública.

## 1. Reglas de Negocio y Carrito
* **Mínimos de Compra Combinados (F4):** El sistema ahora agrupa automáticamente las cantidades de las variantes de un mismo producto en el carrito. Esto permite que el cliente alcance el mínimo de compra sumando diferentes sabores o tamaños de una misma familia de productos (ej. 3 empanadas de carne + 3 de pollo = mínimo de 6 superado).
* **Restricción de Compra Rápida:** Se eliminó el botón de añadir al carrito rápido desde el catálogo (`index.php` y vistas de categorías). Las tarjetas ahora redirigen obligatoriamente a la página de detalle del producto para garantizar que el cliente visualice y respete las restricciones y mínimos de compra.

## 2. Gestión Operativa y Panel de Administración
* **Nuevo Sistema de Roles y Permisos (F3):** Se introdujo el rol de **Supervisor**. Se implementó un motor de permisos granulares (`auth_admin.php`) que protege las rutas del sistema. El supervisor puede gestionar pedidos e inventario, pero tiene restringida la modificación de configuraciones del sistema, usuarios y roles.
* **Modificación de Pedidos (F2):** Los administradores y supervisores ahora pueden editar un pedido existente desde el panel, lo cual genera una alerta visual ("Cambio propuesto en tu pedido") en el historial del cliente para que apruebe o rechace las modificaciones.
* **Tiempos de Anticipación Dinámicos (F1):** Se agregó un campo en la configuración del sistema para definir las "Horas de anticipación requeridas". El calendario de entrega (`checkout.php`) bloquea dinámicamente los días no válidos basándose en este valor (por defecto, 42 horas).

## 3. Notificaciones y Comunicación
* **WhatsApp Enriquecido:** El mensaje automático enviado al cliente incluye un resumen exhaustivo del pedido, total, y link de seguimiento seguro. El botón en el checkout fue renombrado a "Continuar en WhatsApp" para evitar confusión.
* **Notificación al Administrador:** Se integró un botón rápido "Enviar a WhatsApp" en la vista de detalle del pedido (`ver_pedido.php`) que redacta un reporte interno instantáneo para el dueño del negocio.

## 4. Analítica y Dashboard
Se añadieron 3 nuevos widgets interactivos al panel principal impulsados por consultas optimizadas (`api_stats.php`):
* **Producto Estrella:** Cálculo del producto más vendido en el periodo actual.
* **Ventas por Año:** Gráfico de rendimiento general anual.
* **Rendimiento por Horarios:** Gráfica para analizar las horas de mayor tráfico y conversión de ventas.

## 5. UI/UX y Responsividad
* **Tracking Dinámico de Pedidos:** El cliente ahora visualiza el flujo real del pedido en su perfil: `Recibido ➔ Preparando ➔ En Camino ➔ Completado`. (Se eliminó el estado ambiguo "Entregado").
* **Panel de Admin Modernizado:** El menú lateral de administración ahora es colapsable en versión escritorio (Toggle) para ganar espacio de trabajo, cuenta con scroll independiente (barra estilizada) y las tablas se transforman inteligentemente en tarjetas en dispositivos móviles.
* **Limpieza Visual:** Se erradicó por completo el sistema de reseñas/estrellas de la página de productos por decisión de negocio.

---
*Base de datos actualizada con el script `migration_fase1_supervisor.sql` para soportar el nuevo rol estructural de supervisor.*
