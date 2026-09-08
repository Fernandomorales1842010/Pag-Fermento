<?php
require '../includes/db.php';

try {
    $pdo->beginTransaction();

    // 1. Tabla de Variantes
    $sql_variantes = "CREATE TABLE IF NOT EXISTS producto_variantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        sku VARCHAR(100) DEFAULT NULL,
        nombre VARCHAR(255) NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        stock INT DEFAULT 0,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_variantes);
    echo "Tabla 'producto_variantes' creada o ya existía.<br>";

    // 2. Columnas en Productos
    $columnas_prod = [
        "sku" => "VARCHAR(100) DEFAULT NULL",
        "precio_distribuidor" => "DECIMAL(10,2) DEFAULT NULL",
        "minimo_compra" => "INT DEFAULT 1"
    ];

    foreach ($columnas_prod as $col => $tipo) {
        try {
            $pdo->exec("ALTER TABLE productos ADD COLUMN $col $tipo");
            echo "Columna '$col' añadida a 'productos'.<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "Columna '$col' ya existe en 'productos'.<br>";
            } else {
                throw $e;
            }
        }
    }

    // 3. Columnas en Detalles Pedido
    $columnas_det = [
        "variante_id" => "INT DEFAULT NULL",
        "variante_nombre" => "VARCHAR(255) DEFAULT NULL"
    ];

    foreach ($columnas_det as $col => $tipo) {
        try {
            $pdo->exec("ALTER TABLE detalles_pedido ADD COLUMN $col $tipo");
            echo "Columna '$col' añadida a 'detalles_pedido'.<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "Columna '$col' ya existe en 'detalles_pedido'.<br>";
            } else {
                throw $e;
            }
        }
    }

    $pdo->commit();
    echo "<br><b style='color:green'>Migración V3 (Variantes) completada con éxito.</b>";
    echo "<br><a href='index.php'>Volver al inicio</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<b style='color:red'>Error durante la migración:</b> " . $e->getMessage();
}
?>
