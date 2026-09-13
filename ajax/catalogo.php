<?php
require '../includes/db.php';
header('Content-Type: application/json');

// ── PARÁMETROS ────────────────────────────────────────────
$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$busqueda  = trim($data['busqueda'] ?? '');
$categoria = $data['categoria'] ?? 'todas';
$pagina    = max(1, (int)($data['pagina'] ?? 1));
$por_pagina = max(1, (int)($data['limite'] ?? 8));

// ── CONSTRUIR WHERE DINÁMICO ──────────────────────────────
$where  = "WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $where   .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if ($categoria !== 'todas') {
    $where   .= " AND p.categoria = ?";
    $params[] = $categoria;
}

// ── CONTAR TOTAL (para paginación) ────────────────────────
$sqlCount = "SELECT COUNT(*) FROM productos p $where";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total_productos = (int)$stmtCount->fetchColumn();
$total_paginas   = max(1, ceil($total_productos / $por_pagina));
$pagina          = min($pagina, $total_paginas);

// ── OBTENER PRODUCTOS DE LA PÁGINA ACTUAL ─────────────────
$offset  = ($pagina - 1) * $por_pagina;
$sqlData = "SELECT 
                p.*,
                (SELECT COUNT(v.id) FROM producto_variantes v WHERE v.producto_id = p.id) AS num_variantes
            FROM productos p
            $where
            ORDER BY p.id DESC
            LIMIT ? OFFSET ?";

$paramsData   = $params;
$paramsData[] = $por_pagina;
$paramsData[] = $offset;

$stmt = $pdo->prepare($sqlData);
$stmt->execute($paramsData);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── GENERAR HTML DE TARJETAS ──────────────────────────────
$html = '';

if (count($productos) > 0) {
    foreach ($productos as $prod) {
        $agotado    = ((int)$prod['stock'] <= 0 && (int)$prod['num_variantes'] === 0);
        $enOferta   = ($prod['oferta'] == 1 && !$agotado);
        $tieneVars  = (int)$prod['num_variantes'] > 0;
        $opacidad   = $agotado ? 'opacity:0.65;filter:grayscale(1);' : '';
        $precioLabel = ($tieneVars ? 'Desde ' : '') . 'Q' . number_format((float)$prod['precio'], 2);
        $img        = htmlspecialchars($prod['imagen'] ?: 'default_pan.png');
        $nombre     = htmlspecialchars($prod['nombre']);
        $desc       = htmlspecialchars(mb_substr($prod['descripcion'], 0, 90)) . (mb_strlen($prod['descripcion']) > 90 ? '…' : '');
        $id         = (int)$prod['id'];

        if ($agotado) {
            $btnHtml = "<a href=\"producto.php?id={$id}\" class=\"btn-icon-add btn-disabled\"><i class=\"fas fa-eye\"></i></a>";
        } else {
            $btnHtml = "<a href=\"producto.php?id={$id}\" class=\"btn-icon-add\"><i class=\"fas fa-arrow-right\"></i></a>";
        }

        $badgeAgotado = $agotado  ? '<div class="badge-overlay badge-agotado">AGOTADO</div>' : '';
        $badgeOferta  = $enOferta ? '<div class="badge-overlay badge-oferta">★ OFERTA</div>'  : '';

        $html .= "
        <div class=\"product-card-simple fade-in\" onclick=\"window.location.href='producto.php?id={$id}'\" style=\"cursor:pointer; {$opacidad}\">
            <a href=\"producto.php?id={$id}\" style=\"display:block;position:relative;\">
                <img src=\"assets/img/{$img}\"
                     class=\"card-img-top\"
                     alt=\"{$nombre}\"
                     onerror=\"this.src='assets/img/default_pan.png'\">
                {$badgeAgotado}
                {$badgeOferta}
            </a>
            <div class=\"card-body-simple\">
                <a href=\"producto.php?id={$id}\" style=\"text-decoration:none;\">
                    <h3 class=\"card-title-simple\">{$nombre}</h3>
                </a>
                <p class=\"card-desc-simple\">{$desc}</p>
                <div class=\"card-footer-simple\">
                    <span class=\"card-price-simple\">{$precioLabel}</span>
                    {$btnHtml}
                </div>
            </div>
        </div>";
    }
} else {
    $html = '
    <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#bbb;">
        <i class="fas fa-search" style="font-size:2.5rem;display:block;margin-bottom:15px;"></i>
        <p style="font-size:1rem;">No encontramos productos con esos filtros.</p>
    </div>';
}

// ── GENERAR PAGINACIÓN ────────────────────────────────────
$pag_html = '';
if ($total_paginas > 1) {
    $rango = 2; // páginas a cada lado de la actual
    if ($pagina > 1) {
        $pag_html .= "<button onclick=\"filtrar(" . ($pagina - 1) . ")\" class=\"page-btn\">&#8592;</button>";
    }

    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i === 1 || $i === $total_paginas || abs($i - $pagina) <= $rango) {
            $active    = ($i === $pagina) ? 'active' : '';
            $pag_html .= "<button onclick=\"filtrar({$i})\" class=\"page-btn {$active}\">{$i}</button>";
        } elseif (abs($i - $pagina) === $rango + 1) {
            $pag_html .= "<span class=\"page-btn\" style=\"cursor:default;\">…</span>";
        }
    }

    if ($pagina < $total_paginas) {
        $pag_html .= "<button onclick=\"filtrar(" . ($pagina + 1) . ")\" class=\"page-btn\">&#8594;</button>";
    }
}

// ── RESPUESTA ─────────────────────────────────────────────
echo json_encode([
    'success'     => true,
    'html'        => $html,
    'paginacion'  => $pag_html,
    'total'       => $total_productos,
    'pagina'      => $pagina,
    'total_paginas' => $total_paginas,
]);