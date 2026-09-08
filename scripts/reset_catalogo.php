<?php
// reset_catalogo.php - Script para limpiar la BD y cargar el catálogo base
require '../includes/db.php';
session_start();

// Solo admins pueden ejecutar esto
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso Denegado. Solo un administrador puede limpiar la base de datos.");
}

if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'si') {
    echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>";
    echo "<h2 style='color:red;'>¡ADVERTENCIA!</h2>";
    echo "<p>Estás a punto de <b>ELIMINAR TODOS</b> los productos, variantes, pedidos y detalles de pedido actuales en la base de datos.</p>";
    echo "<p>Luego de limpiar, se insertará el catálogo oficial con 29 productos/variantes.</p>";
    echo "<p>¿Estás seguro de continuar?</p>";
    echo "<a href='?confirmar=si' style='background:red; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>SÍ, LIMPIAR BASE DE DATOS</a>";
    echo "</div>";
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Desactivar checks de llaves foráneas para poder hacer TRUNCATE
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 2. Limpiar Tablas
    $pdo->exec("TRUNCATE TABLE producto_variantes;");
    $pdo->exec("TRUNCATE TABLE detalles_pedido;");
    $pdo->exec("TRUNCATE TABLE pedidos;");
    $pdo->exec("TRUNCATE TABLE productos;");

    // Reactivar checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "✅ Tablas limpiadas correctamente.<br>";

    // 3. Insertar Catálogo Base
    $productos = [
        [
            'nombre' => 'Línea de Pies',
            'descripcion' => 'Deliciosa concha de pie horneada a la perfección.',
            'categoria' => 'Dulce',
            'minimo_compra' => 1,
            'variantes' => [
                ['nombre' => 'Queso Tradicional', 'sku' => 'PQ05.1', 'precio' => 8.00, 'precio_distribuidor' => 6.50, 'stock' => 100],
                ['nombre' => 'Mermelada de Piña', 'sku' => 'PP05.2', 'precio' => 10.00, 'precio_distribuidor' => 6.50, 'stock' => 100],
            ]
        ],
        [
            'nombre' => 'Línea de Strudels',
            'descripcion' => 'Delicada y escamosa pasta de hojaldre con cobertura de azúcar.',
            'categoria' => 'Dulce',
            'minimo_compra' => 1,
            'variantes' => [
                ['nombre' => 'Piña', 'sku' => 'S04.H2.1', 'precio' => 7.00, 'precio_distribuidor' => 4.50, 'stock' => 100],
                ['nombre' => 'Fresa', 'sku' => 'C03.1j', 'precio' => 7.00, 'precio_distribuidor' => 4.50, 'stock' => 100],
                ['nombre' => 'Manjar', 'sku' => 'S04.H2.2', 'precio' => 8.00, 'precio_distribuidor' => 4.50, 'stock' => 100],
            ]
        ],
        [
            'nombre' => 'Línea de Donas',
            'descripcion' => 'Suave dona disponible con diferentes coberturas y exquisitas decoraciones.',
            'categoria' => 'Dulce',
            'minimo_compra' => 1,
            'variantes' => [
                ['nombre' => 'Chocolate Clásico', 'sku' => 'DACH12.1', 'precio' => 9.00, 'precio_distribuidor' => 4.50, 'stock' => 50],
                ['nombre' => 'Chocolate Blanco', 'sku' => 'DACH12.1', 'precio' => 9.00, 'precio_distribuidor' => 4.50, 'stock' => 50],
                ['nombre' => 'Glaseado de Fresa', 'sku' => 'DACH12.1', 'precio' => 10.00, 'precio_distribuidor' => 4.50, 'stock' => 50],
            ]
        ],
        [
            'nombre' => 'Croissant Simple',
            'descripcion' => 'Clásico croissant hecho con fina pasta de hojaldre semidulce sin relleno.',
            'categoria' => 'Dulce',
            'precio' => 7.50,
            'precio_distribuidor' => 4.50,
            'sku' => 'CO3',
            'stock' => 50,
            'minimo_compra' => 1,
            'variantes' => []
        ],
        [
            'nombre' => 'Milhojas',
            'descripcion' => 'Postre icónico de capas de hojaldre relleno de abundante manjar.',
            'categoria' => 'Dulce',
            'precio' => 9.50,
            'precio_distribuidor' => 4.00,
            'sku' => 'S04.H2.2',
            'stock' => 40,
            'minimo_compra' => 1,
            'variantes' => []
        ],
        [
            'nombre' => 'Pan de papa hot dog (Caja 45 U.)',
            'descripcion' => 'Pan alargado a base de masa madre que incorpora papa en su formulación.',
            'categoria' => 'Salado',
            'precio' => 9.50,
            'precio_distribuidor' => 5.50,
            'sku' => 'B.HOT.PA 14.2.1',
            'stock' => 20,
            'minimo_compra' => 1,
            'variantes' => []
        ],
        [
            'nombre' => 'Pan de papa hot dog (Caja 90 U.)',
            'descripcion' => 'Pan alargado a base de masa madre que incorpora papa en su formulación.',
            'categoria' => 'Salado',
            'precio' => 8.50,
            'precio_distribuidor' => 4.50,
            'sku' => 'B.HOT.PA 14.2.1',
            'stock' => 20,
            'minimo_compra' => 1,
            'variantes' => []
        ],
        [
            'nombre' => 'Pan de molde brioche',
            'descripcion' => 'Pan artesanal de masa madre en corte rectangular, profundamente enriquecido.',
            'categoria' => 'Salado',
            'precio' => 30.00,
            'precio_distribuidor' => 25.00,
            'sku' => 'B.MOL.14.3',
            'stock' => 30,
            'minimo_compra' => 1,
            'variantes' => []
        ],
        [
            'nombre' => 'Grissini',
            'descripcion' => 'Delgados y apetitosos palitos de pan, perfectamente horneados.',
            'categoria' => 'Salado',
            'precio' => 18.00,
            'precio_distribuidor' => 12.00,
            'sku' => 'B.HOT 14.2',
            'stock' => 60,
            'minimo_compra' => 1,
            'variantes' => []
        ]
    ];

    $stmtInsertProd = $pdo->prepare("INSERT INTO productos (nombre, descripcion, categoria, precio, stock, minimo_compra, sku, precio_distribuidor, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'default.png')");
    $stmtInsertVar = $pdo->prepare("INSERT INTO producto_variantes (producto_id, nombre, sku, precio, stock) VALUES (?, ?, ?, ?, ?)");

    foreach ($productos as $p) {
        $precio_base = isset($p['precio']) ? $p['precio'] : $p['variantes'][0]['precio'];
        $stock_base = isset($p['stock']) ? $p['stock'] : array_sum(array_column($p['variantes'], 'stock'));
        $sku_base = isset($p['sku']) ? $p['sku'] : '';
        $precio_dist_base = isset($p['precio_distribuidor']) ? $p['precio_distribuidor'] : null;

        $stmtInsertProd->execute([
            $p['nombre'], 
            $p['descripcion'], 
            $p['categoria'], 
            $precio_base, 
            $stock_base, 
            $p['minimo_compra'],
            $sku_base,
            $precio_dist_base
        ]);

        $prod_id = $pdo->lastInsertId();

        foreach ($p['variantes'] as $v) {
            $stmtInsertVar->execute([
                $prod_id,
                $v['nombre'],
                $v['sku'],
                $v['precio'],
                $v['stock']
            ]);
        }
    }

    $pdo->commit();
    echo "<br>✅ ¡Catálogo insertado con éxito!<br>";
    echo "<br><a href='admin/index.php'>Ir al Panel de Administración</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<b style='color:red'>Error durante la limpieza/importación:</b> " . $e->getMessage();
}
?>
