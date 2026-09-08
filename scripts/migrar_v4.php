<?php
// migrar_v4.php - Configuración de Roles y Tablas Faltantes
require '../includes/db.php';

try {
    $pdo->beginTransaction();

    // 1. Agregar columna 'rol' a 'usuarios'
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN rol ENUM('admin', 'supervisor', 'cliente') NOT NULL DEFAULT 'cliente'");
        echo "Columna 'rol' añadida a 'usuarios'.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Columna 'rol' ya existe en 'usuarios'.<br>";
        } else {
            throw $e;
        }
    }

    // 2. Dar rol de 'admin' al primer usuario para evitar quedarse sin acceso
    $pdo->exec("UPDATE usuarios SET rol = 'admin' ORDER BY id ASC LIMIT 1");
    echo "Rol de 'admin' otorgado al primer usuario registrado.<br>";

    // 3. Crear tabla 'categorias' si no existe
    $sql_categorias = "CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        icono VARCHAR(100) DEFAULT 'fas fa-bread-slice',
        orden INT DEFAULT 0,
        activa TINYINT(1) DEFAULT 1,
        UNIQUE (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_categorias);
    echo "Tabla 'categorias' creada o ya existía.<br>";

    // 4. Crear tabla 'zonas_envio' si no existe
    $sql_zonas = "CREATE TABLE IF NOT EXISTS zonas_envio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT,
        activa TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_zonas);
    echo "Tabla 'zonas_envio' creada o ya existía.<br>";

    // 5. Crear tabla 'configuracion' si no existe
    $sql_config = "CREATE TABLE IF NOT EXISTS configuracion (
        llave VARCHAR(100) PRIMARY KEY,
        valor TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_config);
    echo "Tabla 'configuracion' creada o ya existía.<br>";

    $pdo->commit();
    echo "<br><b style='color:green'>Migración V4 (Roles y Tablas) completada con éxito.</b>";
    echo "<br><a href='index.php'>Volver al inicio</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<b style='color:red'>Error durante la migración:</b> " . $e->getMessage();
}
?>
