# ⚠️ Fermento — Guía de Errores Comunes
> Referencia rápida para diagnosticar y resolver los problemas más frecuentes.

---

## Errores de Base de Datos

---

### ❌ `FERMENTO DB Connection failed: SQLSTATE[HY000] [1045] Access denied`

**Causa:** Credenciales incorrectas o usuario MySQL no existe.

**Solución:**
```bash
# Verificar si el usuario existe
sudo mysql -u root -e "SELECT user, host FROM mysql.user WHERE user='fermento_user';"

# Recrear el usuario
sudo mysql -u root <<EOF
DROP USER IF EXISTS 'fermento_user'@'localhost';
CREATE USER 'fermento_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'FermentoPass2026!';
GRANT ALL PRIVILEGES ON \`DB_fermento\`.* TO 'fermento_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

---

### ❌ `FERMENTO DB Connection failed: SQLSTATE[HY000] [2002] No such file or directory`

**Causa:** MySQL no está corriendo, o `DB_HOST` apunta a `localhost` con socket en lugar de TCP.

**Solución:**
```bash
# Verificar estado de MySQL
sudo systemctl status mysql

# Reiniciar si está detenido
sudo systemctl restart mysql

# Cambiar en .env de localhost a 127.0.0.1 (fuerza TCP)
# DB_HOST=127.0.0.1
```

---

### ❌ `Table 'DB_fermento.configuracion' doesn't exist`

**Causa:** La base de datos no fue instalada correctamente o está incompleta.

**Solución:**
```bash
# Reinstalar desde cero (¡BORRA todos los datos!)
sudo mysql -u root < /var/www/html/fermento/database/fermento_instalacion_completa.sql
```

---

### ❌ `CSRF token inválido` / Redirige con `?err=csrf`

**Causa:** El formulario expiró, el cliente usó el botón "Atrás", o doble clic en submit.

**Solución para el usuario:** Recargar la página e intentar de nuevo.  
**Solución para el developer:** Es comportamiento esperado de seguridad (token de un solo uso).

---

## Errores del Servidor Web

---

### ❌ Página en blanco / Error 500

**Causa:** Error PHP sin mostrar (modo producción).

**Diagnóstico:**
```bash
sudo tail -50 /var/log/apache2/fermento_error.log
```

**Activar errores temporalmente para debug:**
```php
// Al inicio del archivo PHP problemático
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
> ⚠️ Desactivar en producción después.

---

### ❌ Error 403 Forbidden

**Causa:** Permisos incorrectos en los archivos o `.htaccess` bloqueando acceso.

**Solución:**
```bash
# Corregir permisos
sudo chown -R ubuntu:www-data /var/www/html/fermento
sudo chmod -R 755 /var/www/html/fermento
sudo chmod 640 /var/www/html/fermento/.env
sudo systemctl restart apache2
```

---

### ❌ Error 404 en rutas / `.htaccess` no funciona

**Causa:** `mod_rewrite` deshabilitado o `AllowOverride None` en Apache.

**Solución:**
```bash
sudo a2enmod rewrite headers
sudo nano /etc/apache2/sites-available/fermento.conf
# Verificar que tenga: AllowOverride All
sudo systemctl restart apache2
```

---

### ❌ Imágenes de productos no cargan (ícono roto)

**Causa:** La imagen del producto no existe en `assets/img/`.

**Solución:** El sistema usa automáticamente `assets/img/default_pan.png` como fallback. Si ese archivo tampoco existe:
```bash
# Verificar que existe
ls /var/www/html/fermento/assets/img/default_pan.png
```

---

### ❌ Archivos subidos (imágenes admin) no aparecen

**Causa:** El directorio `assets/img/` no tiene permisos de escritura para Apache.

**Solución:**
```bash
sudo chown -R www-data:www-data /var/www/html/fermento/assets/img/
sudo chmod -R 775 /var/www/html/fermento/assets/img/
```

---

## Errores de Deploy / Git

---

### ❌ `fatal: detected dubious ownership in repository`

**Causa:** El directorio es de `www-data` pero Git corre como `ubuntu` o `root`.

**Solución:**
```bash
sudo git config --global --add safe.directory /var/www/html/fermento
# Y cambiar el propietario correctamente:
sudo chown -R ubuntu:www-data /var/www/html/fermento
```

---

### ❌ `Permission denied (publickey)` al conectar SSH

**Causa A:** Estás en el directorio incorrecto y el archivo `.pem` no se encuentra.  
**Causa B:** El archivo `.pem` tiene permisos muy abiertos en Windows.

**Solución A:** Usar la ruta completa al `.pem`:
```powershell
ssh -i C:\Users\ferna\Downloads\llave-fermento.pem ubuntu@3.145.33.230
```

**Solución B:** Restringir permisos:
```powershell
icacls C:\Users\ferna\Downloads\llave-fermento.pem /inheritance:r /grant:r "$($env:USERNAME):(R)"
```

---

### ❌ `Connection timed out` al conectar SSH

**Posibles causas y soluciones:**

| Causa | Solución |
|---|---|
| Usando IP privada (172.31.x.x) | Usar la IP pública (ver consola AWS EC2) |
| Puerto 22 bloqueado en Security Group | Agregar regla SSH en Inbound Rules de AWS |
| Instancia detenida | Iniciar instancia en consola AWS |
| IP pública cambió (reinicio sin Elastic IP) | Verificar nueva IP en consola AWS |

---

## Errores de la Aplicación

---

### ❌ Tienda muestra "Estamos preparando tu próximo pedido"

**Causa:** La configuración `pedidos_activos` está en `0`.

**Solución:** En Admin → Configuración → cambiar "Acepta pedidos" a Sí.  
O directamente en MySQL:
```sql
UPDATE configuracion SET valor = '1' WHERE clave = 'pedidos_activos';
```

---

### ❌ El cupón dice "inválido" pero existe en la BD

**Causas posibles:**
- El cupón expiró (`fecha_expira` pasada)
- Se agotó (`usos_actuales >= usos_maximos`)
- El cupón está inactivo (`activo = 0`)

**Diagnóstico:**
```sql
SELECT codigo, activo, fecha_expira, usos_maximos, usos_actuales
FROM cupones WHERE codigo = 'TU_CUPON';
```

---

### ❌ El PDF del recibo no genera / Error con FPDF

**Causa:** La librería FPDF está en `includes/fpdf/` y no se encuentra.

**Verificar:**
```bash
ls /var/www/html/fermento/includes/fpdf/
```

**Causa adicional:** PHP no tiene extensión GD habilitada.
```bash
php -m | grep gd
# Si no aparece:
sudo apt install php8.3-gd
sudo systemctl restart apache2
```

---

### ❌ Login con Google no funciona

**Causa:** Las credenciales de Google OAuth no están configuradas o el dominio/IP cambió.

**Solución:**
1. Ir a [console.cloud.google.com](https://console.cloud.google.com)
2. APIs → Credenciales → OAuth 2.0
3. Actualizar **Orígenes autorizados** y **URIs de redireccionamiento** con la nueva IP/dominio
4. Actualizar `includes/google_auth.php` con las credenciales correctas

---

## Comandos de Diagnóstico Rápido

```bash
# Estado general del servidor
sudo systemctl status apache2 mysql

# Últimos 50 errores PHP/Apache
sudo tail -50 /var/log/apache2/fermento_error.log

# Verificar conexión a MySQL
mysql -u fermento_user -p'FermentoPass2026!' DB_fermento -e "SELECT COUNT(*) FROM productos;"

# Verificar PHP y extensiones
php -v
php -m | grep -E "pdo|mysql|curl|gd|mbstring"

# Verificar que Apache lee el .htaccess
apache2ctl -t
```
