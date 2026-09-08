<?php
/**
 * MIGRACIÓN DE BASE DE DATOS - Fermento E-commerce
 * Ejecutar UNA VEZ desde phpMyAdmin o directamente en el navegador
 * 
 * Agrega: configuracion, zonas_envio, categorias
 * Modifica: productos (minimo_compra), pedidos (zona, envio, subtotal, notas)
 */
require '../includes/db.php';

$queries = [];
$results = [];

// ============================================================
// 1. TABLA DE CONFIGURACIÓN GENERAL
// ============================================================
$queries[] = "CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    tipo ENUM('text','number','boolean') DEFAULT 'text'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Configuraciones iniciales
$configs = [
    ['costo_envio', '30.00', 'Costo de envío estándar en Quetzales', 'number'],
    ['whatsapp_numero', '50239754421', 'Número de WhatsApp para pedidos (sin +)', 'text'],
    ['moneda_simbolo', 'Q', 'Símbolo de moneda', 'text'],
    ['google_client_id', '', 'Google OAuth Client ID', 'text'],
    ['google_client_secret', '', 'Google OAuth Client Secret', 'text'],
    ['google_redirect_uri', '', 'Google OAuth Redirect URI (ej: https://tudominio.com/google_callback.php)', 'text'],
    ['tienda_nombre', 'Fermento', 'Nombre de la tienda', 'text'],
    ['tienda_descripcion', 'Casa de Panaderos', 'Descripción corta', 'text'],
];

foreach ($configs as $c) {
    $queries[] = "INSERT IGNORE INTO configuracion (clave, valor, descripcion, tipo) VALUES ('{$c[0]}', '{$c[1]}', '{$c[2]}', '{$c[3]}')";
}

// ============================================================
// 2. TABLA DE ZONAS DE ENVÍO
// ============================================================
$queries[] = "CREATE TABLE IF NOT EXISTS zonas_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Zonas iniciales
$queries[] = "INSERT IGNORE INTO zonas_envio (id, nombre, descripcion, activa) VALUES 
    (1, 'Zona Metropolitana', 'Ciudad de Guatemala, zonas 1-21', 1),
    (2, 'Mixco / San Lucas', 'Mixco, San Lucas Sacatepéquez y alrededores', 1),
    (3, 'Carretera a El Salvador', 'KM 1 a KM 25 Carretera a El Salvador', 1)";

// ============================================================
// 3. TABLA DE CATEGORÍAS
// ============================================================
$queries[] = "CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    icono VARCHAR(50) DEFAULT 'fas fa-bread-slice',
    orden INT DEFAULT 0,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Categorías iniciales (las que ya existen en productos)
$queries[] = "INSERT IGNORE INTO categorias (nombre, icono, orden) VALUES 
    ('Salado', 'fas fa-bread-slice', 1),
    ('Dulce', 'fas fa-cookie', 2),
    ('Temporada', 'fas fa-snowflake', 3)";

// ============================================================
// 4. AGREGAR COLUMNAS A PRODUCTOS
// ============================================================
$queries[] = "ALTER TABLE productos ADD COLUMN IF NOT EXISTS minimo_compra INT DEFAULT 1 COMMENT 'Cantidad mínima de compra por producto'";

// ============================================================
// 5. AGREGAR COLUMNAS A PEDIDOS
// ============================================================
$queries[] = "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS zona_envio_id INT DEFAULT NULL";
$queries[] = "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS costo_envio DECIMAL(10,2) DEFAULT 0.00";
$queries[] = "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) DEFAULT 0.00";
$queries[] = "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS notas TEXT DEFAULT NULL";
$queries[] = "ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS metodo_contacto ENUM('normal','whatsapp') DEFAULT 'normal'";

// ============================================================
// EJECUTAR TODAS LAS CONSULTAS
// ============================================================
echo "<h2>Migración Fermento E-commerce</h2>";
echo "<pre>";

foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        $shortSql = substr(trim($sql), 0, 80);
        echo "✅ [{$i}] OK: {$shortSql}...\n";
        $results[] = ['ok', $shortSql];
    } catch (Exception $e) {
        $msg = $e->getMessage();
        // Ignorar errores de "ya existe"
        if (strpos($msg, 'Duplicate') !== false || strpos($msg, 'already exists') !== false) {
            echo "⚠️ [{$i}] YA EXISTE (OK): " . substr($sql, 0, 60) . "...\n";
        } else {
            echo "❌ [{$i}] ERROR: {$msg}\n";
        }
    }
}

echo "\n\n✅ Migración completada. Puedes eliminar este archivo.\n";
echo "</pre>";
?>
