# 🍞 Fermento — Guía de Servidor y Deploy
> **Última actualización:** 25 de Septiembre 2026  
> **Estado del servidor:** ✅ En producción

---

## 🖥️ Información del Servidor (AWS EC2)

| Campo | Valor |
|---|---|
| **Proveedor** | AWS EC2 |
| **IP Pública** | `3.145.33.230` |
| **IP Privada** | `172.31.6.141` |
| **SO** | Ubuntu 24.04.4 LTS |
| **Zona** | us-east-2a |
| **URL actual** | http://3.145.33.230 |
| **Región** | us-east-2 (Ohio) |

---

## 🔑 Conectarse al Servidor por SSH

### Requisito
El archivo de llave está en:
```
C:\Users\ferna\Downloads\llave-fermento.pem
```

### Comando para conectarse (desde PowerShell o CMD)
```powershell
ssh -i C:\Users\ferna\Downloads\llave-fermento.pem ubuntu@3.145.33.230
```

> ⚠️ **Importante:** Si la IP cambia (al detener/reiniciar la instancia), verifica la nueva IP en la consola AWS EC2.  
> Para evitar esto, considera asignar una **Elastic IP** en AWS.

---

## 🗄️ Base de Datos MySQL

| Campo | Valor |
|---|---|
| **Host** | `127.0.0.1` |
| **Base de datos** | `DB_fermento` |
| **Usuario** | `fermento_user` |
| **Contraseña** | `FermentoPass2026!` |
| **Usuario root** | Acceso vía `sudo mysql -u root` |

### Entrar a MySQL desde el servidor
```bash
# Como usuario de la app
mysql -u fermento_user -p'FermentoPass2026!' DB_fermento

# Como root (sin contraseña)
sudo mysql -u root
```

---

## 📁 Estructura en el Servidor

| Ruta | Descripción |
|---|---|
| `/var/www/html/fermento` | Código de la aplicación |
| `/etc/apache2/sites-available/fermento.conf` | Config de Apache |
| `/var/www/html/fermento/.env` | Variables de entorno (DB credentials) |
| `/var/log/apache2/fermento_error.log` | Log de errores |

---

## 🚀 Deploy — Actualizar el Código

Cuando hagas cambios en el código local y los subas a GitHub:

```bash
# 1. Conectarse al servidor
ssh -i C:\Users\ferna\Downloads\llave-fermento.pem ubuntu@3.145.33.230

# 2. En el servidor, hacer pull y reiniciar
git -C /var/www/html/fermento pull origin main
sudo systemctl restart apache2
```

### O usando el alias (si ya está configurado)
```bash
deploy-fermento
```

---

## 🛠️ Comandos Útiles en el Servidor

```bash
# Ver estado de Apache
sudo systemctl status apache2

# Reiniciar Apache
sudo systemctl restart apache2

# Ver errores en tiempo real
sudo tail -f /var/log/apache2/fermento_error.log

# Ver estado de MySQL
sudo systemctl status mysql

# Ver el .env actual
cat /var/www/html/fermento/.env

# Editar el .env
sudo nano /var/www/html/fermento/.env
```

---

## 🔐 Credenciales de la Aplicación

| Usuario | Email | Contraseña | Rol |
|---|---|---|---|
| Carlos Morales | `admin@gmail.com` | (ver credentials.txt) | Admin |
| Fernando Morales | `fernandomorales1842010@gmail.com` | (ver credentials.txt) | Cliente |

> 📄 Ver archivo local: `credentials.txt` en la raíz del proyecto

---

## 📋 Pendientes

- [ ] Conectar dominio de GoDaddy → apuntar registro `A` a `3.145.33.230`
- [ ] Actualizar Apache VirtualHost con el nombre del dominio
- [ ] Instalar certificado SSL (HTTPS) con Let's Encrypt
- [ ] Asignar una **Elastic IP** en AWS para que la IP no cambie
- [ ] Ejecutar `sudo mysql_secure_installation` para asegurar MySQL
- [ ] Cambiar contraseña del admin desde el panel web

---

## ⚙️ Stack Técnico Instalado

| Tecnología | Versión |
|---|---|
| Ubuntu | 24.04.4 LTS |
| Apache | 2.x |
| PHP | 8.3 |
| MySQL | 8.x |
| Git | instalado |

**Módulos Apache activos:** `mod_rewrite`, `mod_headers`, `mod_php8.3`

**Extensiones PHP instaladas:** `php8.3-mysql`, `php8.3-mbstring`, `php8.3-curl`, `php8.3-gd`, `php8.3-xml`, `php8.3-zip`, `php8.3-intl`

---

## 🌐 Consola AWS

Accede a tu instancia desde:  
👉 https://console.aws.amazon.com/ec2/

- **Security Group:** `sg-0b8a019399e3f82b3` (launch-wizard-2)
- **Puertos abiertos:** 22 (SSH), 80 (HTTP), 443 (HTTPS)

---

*Documento generado al finalizar la jornada del 25 Sep 2026. ¡Buen trabajo hoy! 🎉*
