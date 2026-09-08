<?php
$currentPage = 'logs';
$pageTitle   = 'Logs de Actividad';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';
include 'includes/admin_nav.php';

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filtros
$where = "1=1";
$params = [];

if (!empty($_GET['usuario'])) {
    $where .= " AND usuario_nombre LIKE ?";
    $params[] = "%" . $_GET['usuario'] . "%";
}
if (!empty($_GET['accion'])) {
    $where .= " AND accion LIKE ?";
    $params[] = "%" . $_GET['accion'] . "%";
}

$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM admin_logs WHERE $where");
$stmt_total->execute($params);
$totalRegistros = $stmt_total->fetchColumn();
$totalPages = ceil($totalRegistros / $limit);

$sql = "SELECT * FROM admin_logs WHERE $where ORDER BY fecha DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="page-header">
    <div class="page-title">
        <h1>Logs de Actividad</h1>
        <p>Registro de auditoría del panel de administración.</p>
    </div>
</div>

<div style="background:white; border-radius:20px; padding:25px; box-shadow:var(--shadow); margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
        <div style="flex:1; min-width:200px;">
            <label style="font-size:0.8rem; font-weight:600; color:#666; margin-bottom:5px; display:block;">Usuario</label>
            <input type="text" name="usuario" value="<?php echo htmlspecialchars($_GET['usuario'] ?? ''); ?>" 
                   style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" placeholder="Buscar usuario...">
        </div>
        <div style="flex:1; min-width:200px;">
            <label style="font-size:0.8rem; font-weight:600; color:#666; margin-bottom:5px; display:block;">Acción</label>
            <input type="text" name="accion" value="<?php echo htmlspecialchars($_GET['accion'] ?? ''); ?>" 
                   style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" placeholder="Ej: crear_producto">
        </div>
        <button type="submit" class="btn-new" style="padding:10px 20px;">
            <i class="fas fa-search"></i> Buscar
        </button>
        <a href="logs.php" class="btn-new btn-outline" style="padding:10px 20px;">Limpiar</a>
    </form>
</div>

<div style="background:white; border-radius:20px; padding:25px; box-shadow:var(--shadow); overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead>
            <tr style="background:#f8f9fa;">
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Fecha</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Usuario</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Acción</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Entidad</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Detalle</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
            <tr>
                <td colspan="6" style="padding:30px; text-align:center; color:#888;">No se encontraron registros.</td>
            </tr>
            <?php else: ?>
            <?php foreach($logs as $l): ?>
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:15px; font-size:0.85rem; color:#555; white-space:nowrap;">
                    <?php echo date('d/m/Y H:i:s', strtotime($l['fecha'])); ?>
                </td>
                <td style="padding:15px; font-weight:600; color:#333;">
                    <?php echo htmlspecialchars($l['usuario_nombre'] ?? 'Sistema'); ?>
                </td>
                <td style="padding:15px; font-size:0.85rem;">
                    <span style="background:#e3f2fd; color:#1565c0; padding:4px 8px; border-radius:4px; font-family:monospace;">
                        <?php echo htmlspecialchars($l['accion']); ?>
                    </span>
                </td>
                <td style="padding:15px; font-size:0.85rem; color:#555;">
                    <?php echo htmlspecialchars($l['entidad'] ?? '-'); ?> 
                    <?php echo $l['entidad_id'] ? '#' . $l['entidad_id'] : ''; ?>
                </td>
                <td style="padding:15px; font-size:0.85rem; color:#555; max-width:250px; overflow:hidden; text-overflow:ellipsis;">
                    <?php echo htmlspecialchars($l['detalle'] ?? '-'); ?>
                </td>
                <td style="padding:15px; font-size:0.8rem; color:#888; font-family:monospace;">
                    <?php echo htmlspecialchars($l['ip'] ?? '-'); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div style="margin-top:20px; display:flex; justify-content:center; gap:5px;">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <a href="logs.php?page=<?php echo $i; ?>&usuario=<?php echo urlencode($_GET['usuario'] ?? ''); ?>&accion=<?php echo urlencode($_GET['accion'] ?? ''); ?>" 
               style="padding:8px 12px; border-radius:6px; text-decoration:none; <?php echo $i==$page ? 'background:var(--text-black);color:white;' : 'background:#f4f4f4;color:#333;'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
