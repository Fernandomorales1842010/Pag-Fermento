<?php
require '../includes/db.php';

// Leer el CSV
$csvFile = 'inventario.csv';
if (!file_exists($csvFile)) {
    die("Archivo CSV no encontrado.\n");
}

$lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (count($lines) < 2) {
    die("Archivo vacío o sin suficientes datos.\n");
}

// Variables para agrupar productos por nombre base
// Esto sirve para agrupar "Pan de hamburguesa brioche (Caja 45 U.)"
// y "Pan de hamburguesa brioche (Caja 90 U.)" en el mismo producto.
$productos_agrupados = [];

function parsePrecio($precioStr) {
    // Busca todos los números con decimales
    preg_match_all('/[\d\.]+/', str_replace(',', '', $precioStr), $matches);
    if (empty($matches[0])) return 0.00;
    
    // Si hay un rango, tomar el valor MÁS ALTO según requerimiento del usuario
    $precios = array_map('floatval', $matches[0]);
    return max($precios);
}

// Empezar en la línea 1 para saltar encabezados
for ($i = 1; $i < count($lines); $i++) {
    // str_getcsv es útil para manejar comillas
    $data = str_getcsv($lines[$i]);
    if (count($data) < 5) continue;

    $categoria = trim($data[0]);
    $nombreRaw = trim($data[1]);
    $sku       = trim($data[2]);
    $desc      = trim($data[3]);
    $precioRaw = trim($data[4]);

    $precio = parsePrecio($precioRaw);
    
    $nombreBase = $nombreRaw;
    $variantesSabor = [];
    $tamano = null;

    // Detectar si hay paréntesis (variantes)
    if (preg_match('/^(.*?)\s*\((.*?)\)$/', $nombreRaw, $matches)) {
        $nombreBase = trim($matches[1]);
        $contenidoParentesis = trim($matches[2]);
        
        // Determinar si es tamaño (ej. Caja 45 U.) o sabores (ej. Queso y Piña)
        if (stripos($contenidoParentesis, 'Caja') !== false || stripos($contenidoParentesis, 'U.') !== false) {
            // Es un tamaño
            $tamano = $contenidoParentesis;
        } else {
            // Son sabores (separar por " y ", ",", o " o ")
            $saboresRaw = preg_split('/(,| y | o )/i', $contenidoParentesis);
            foreach ($saboresRaw as $sabor) {
                $sabor = trim($sabor);
                if (!empty($sabor) && strtolower($sabor) !== 'múltiples variedades') {
                    $variantesSabor[] = ucfirst($sabor);
                }
            }
        }
    }

    // Limpiar 'Múltiples variedades'
    if (stripos($nombreBase, 'Múltiples variedades') !== false) {
        $nombreBase = trim(str_ireplace('Múltiples variedades', '', $nombreBase), "() \t\n\r\0\x0B");
    }

    $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $nombreBase));

    if (!isset($productos_agrupados[$key])) {
        $productos_agrupados[$key] = [
            'nombre'      => $nombreBase,
            'descripcion' => $desc,
            'categoria'   => $categoria,
            'precio'      => $precio, // Precio más alto encontrado
            'sku'         => $sku,
            'variantes'   => []
        ];
    } else {
        // Actualizar precio base si el de la variante es mayor
        if ($precio > $productos_agrupados[$key]['precio']) {
            $productos_agrupados[$key]['precio'] = $precio;
        }
    }

    // Agregar variantes encontradas
    if ($tamano !== null) {
        $productos_agrupados[$key]['variantes'][] = [
            'tamano' => $tamano,
            'sabor'  => null,
            'precio' => $precio,
            'sku'    => $sku
        ];
    } elseif (!empty($variantesSabor)) {
        foreach ($variantesSabor as $sab) {
            $productos_agrupados[$key]['variantes'][] = [
                'tamano' => null,
                'sabor'  => $sab,
                'precio' => $precio,
                'sku'    => $sku
            ];
        }
    }
}

// Inserción en BD
try {
    $pdo->beginTransaction();

    $stmtProd = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria, imagen, destacado, oferta, sku, stock, minimo_compra, tiene_variantes) VALUES (?, ?, ?, ?, 'default_pan.png', 0, 0, ?, 0, 1, ?)");
    
    $stmtVar = $pdo->prepare("INSERT INTO producto_variantes (producto_id, tamano, sabor, nombre, precio, stock, sku) VALUES (?, ?, ?, ?, ?, 0, ?)");

    foreach ($productos_agrupados as $p) {
        $tieneVariantes = count($p['variantes']) > 0 ? 1 : 0;
        
        $stmtProd->execute([
            $p['nombre'], 
            $p['descripcion'], 
            $p['precio'], 
            $p['categoria'], 
            $p['sku'], 
            $tieneVariantes
        ]);
        
        $producto_id = $pdo->lastInsertId();

        if ($tieneVariantes) {
            foreach ($p['variantes'] as $v) {
                // Nombre combinado de la variante
                $partes = array_filter([$v['tamano'], $v['sabor']]);
                $varNombre = !empty($partes) ? implode(' / ', $partes) : "Opción estándar";
                
                $stmtVar->execute([
                    $producto_id,
                    $v['tamano'],
                    $v['sabor'],
                    $varNombre,
                    $v['precio'],
                    $v['sku']
                ]);
            }
        }
    }

    $pdo->commit();
    echo "¡Importación completada con éxito!\n";
    echo "Se insertaron " . count($productos_agrupados) . " productos principales.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error en la importación: " . $e->getMessage() . "\n";
}
?>
