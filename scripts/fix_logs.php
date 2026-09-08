<?php
require '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT DEFAULT NULL,
    `usuario_nombre` VARCHAR(100) DEFAULT NULL,
    `accion` VARCHAR(50) NOT NULL,
    `entidad` VARCHAR(50) DEFAULT NULL,
    `entidad_id` INT DEFAULT NULL,
    `detalle` TEXT DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $pdo->exec($sql);
    echo "<h3>¡Tabla admin_logs creada con éxito!</h3>";
    echo "<p>Ya puedes volver al panel y ver el registro de actividad sin errores.</p>";
    echo "<a href='admin/index.php' style='padding: 10px 20px; background: black; color: white; text-decoration: none; border-radius: 5px;'>Volver al Panel</a>";
} catch (Exception $e) {
    echo "Error creando la tabla: " . $e->getMessage();
}
?>
