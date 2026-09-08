<?php
$currentPage = 'categorias';
$pageTitle   = 'Categorías';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';
include 'includes/admin_nav.php';

$msg = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify('categorias.php');
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $icono = trim($_POST['icono'] ?? 'fas fa-bread-slice');
        $orden = (int)($_POST['orden'] ?? 0);
        if ($nombre) {
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre, icono, orden) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$nombre, $icono, $orden]);
                adminLog('crear_categoria', 'categorias', (int)$pdo->lastInsertId(), $nombre);
                $msg = 'creada';
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $msg = 'duplicada';
                }
            }
        }
    } elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre'] ?? '');
        $icono = trim($_POST['icono'] ?? 'fas fa-bread-slice');
        $orden = (int)($_POST['orden'] ?? 0);
        if ($nombre && $id) {
            $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, icono = ?, orden = ? WHERE id = ?");
            $stmt->execute([$nombre, $icono, $orden, $id]);
            adminLog('editar_categoria', 'categorias', $id, $nombre);
            $msg = 'actualizada';
        }
    } elseif ($accion === 'toggle') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE categorias SET activa = NOT activa WHERE id = ?")->execute([$id]);
        adminLog('toggle_categoria', 'categorias', $id);
        $msg = 'toggle';
    } elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        // Verificar que no haya productos con esta categoría
        $stmt = $pdo->prepare("SELECT c.nombre, COUNT(p.id) as total FROM categorias c LEFT JOIN productos p ON p.categoria = c.nombre WHERE c.id = ? GROUP BY c.id");
        $stmt->execute([$id]);
        $info = $stmt->fetch();
        if ($info && $info['total'] > 0) {
            $msg = 'tiene_productos';
        } else {
            $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
            adminLog('eliminar_categoria', 'categorias', $id);
            $msg = 'eliminada';
        }
    }
}

// Obtener categorías con conteo de productos
$categorias = $pdo->query("
    SELECT c.*, COUNT(p.id) as total_productos 
    FROM categorias c 
    LEFT JOIN productos p ON p.categoria = c.nombre 
    GROUP BY c.id 
    ORDER BY c.orden ASC, c.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Iconos sugeridos
$iconos = [
    'fas fa-bread-slice' => '🍞 Pan',
    'fas fa-cookie' => '🍪 Dulce',
    'fas fa-birthday-cake' => '🎂 Pastel',
    'fas fa-snowflake' => '❄️ Temporada',
    'fas fa-star' => '⭐ Especial',
    'fas fa-fire' => '🔥 Popular',
    'fas fa-leaf' => '🌿 Integral',
    'fas fa-coffee' => '☕ Café',
    'fas fa-gift' => '🎁 Regalo',
    'fas fa-heart' => '❤️ Favoritos',
];
?>

<div class="page-header">
    <div class="page-title">
        <h1>Categorías</h1>
        <p>Administra las categorías de productos de tu catálogo.</p>
    </div>
    <button onclick="toggleModal('crear')" class="btn-new">
        <i class="fas fa-plus"></i> Nueva Categoría
    </button>
</div>

<?php if($msg === 'tiene_productos'): ?>
<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#856404;font-weight:600;">
    <i class="fas fa-exclamation-triangle"></i> No se puede eliminar: hay productos asignados a esta categoría.
</div>
<?php elseif($msg === 'duplicada'): ?>
<div style="background:#ffebee;border:1px solid #e74c3c;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#e74c3c;font-weight:600;">
    <i class="fas fa-times-circle"></i> Ya existe una categoría con ese nombre.
</div>
<?php elseif($msg && $msg !== 'toggle'): ?>
<div style="background:#eafaf1;border:1px solid #27ae60;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#27ae60;font-weight:600;">
    <i class="fas fa-check-circle"></i> Categoría <?php echo $msg; ?> correctamente.
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:20px;">
    <?php foreach($categorias as $cat): ?>
    <div class="card" style="padding:20px;display:flex;align-items:center;gap:15px;<?php echo !$cat['activa'] ? 'opacity:0.5;' : ''; ?>">
        <div style="width:50px;height:50px;border-radius:14px;background:var(--bg-cream);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--accent-toast);flex-shrink:0;">
            <i class="<?php echo htmlspecialchars($cat['icono']); ?>"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <h3 style="font-family:'Merriweather';font-size:1rem;margin-bottom:3px;"><?php echo htmlspecialchars($cat['nombre']); ?></h3>
            <span style="font-size:0.8rem;color:#999;"><?php echo $cat['total_productos']; ?> producto(s) · Orden: <?php echo $cat['orden']; ?></span>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;">
            <button onclick='editarCat(<?php echo json_encode($cat); ?>)' 
                    style="width:30px;height:30px;border-radius:8px;border:1px solid #eee;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Editar">
                <i class="fas fa-pencil-alt" style="font-size:0.7rem;color:var(--accent-toast);"></i>
            </button>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="accion" value="toggle">
                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                <button type="submit" style="width:30px;height:30px;border-radius:8px;border:1px solid #eee;background:<?php echo $cat['activa']?'#fff3e0':'#e8f5e9'; ?>;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="<?php echo $cat['activa']?'Desactivar':'Activar'; ?>">
                    <i class="fas <?php echo $cat['activa']?'fa-eye-slash':'fa-eye'; ?>" style="font-size:0.7rem;color:<?php echo $cat['activa']?'#f39c12':'#27ae60'; ?>;"></i>
                </button>
            </form>
            <?php if($cat['total_productos'] == 0): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta categoría?');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                <button type="submit" style="width:30px;height:30px;border-radius:8px;border:1px solid #eee;background:#ffebee;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Eliminar">
                    <i class="fas fa-trash" style="font-size:0.7rem;color:#e74c3c;"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if(empty($categorias)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:60px;color:#ccc;">
        <i class="fas fa-tags" style="font-size:3rem;display:block;margin-bottom:15px;"></i>
        No hay categorías. Crea la primera.
    </div>
    <?php endif; ?>
</div>

<!-- MODAL -->
<div id="catModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:4000;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:white;border-radius:20px;padding:35px;max-width:480px;width:90%;box-shadow:0 25px 60px rgba(0,0,0,0.25);">
        <h3 style="font-family:'Merriweather';margin-bottom:20px;" id="catModalTitle">Nueva Categoría</h3>
        <form method="POST" id="catForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" id="catAccion" value="crear">
            <input type="hidden" name="id" id="catId" value="">
            
            <div style="margin-bottom:18px;">
                <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">Nombre *</label>
                <input type="text" name="nombre" id="catNombre" required placeholder="Ej: Repostería"
                       style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;">
            </div>
            
            <div style="margin-bottom:18px;">
                <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">Ícono</label>
                <select name="icono" id="catIcono" style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;">
                    <?php foreach($iconos as $clase => $label): ?>
                    <option value="<?php echo $clase; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom:25px;">
                <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">Orden de Aparición</label>
                <input type="number" name="orden" id="catOrden" min="0" value="0"
                       style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;">
                <small style="color:#999;font-size:0.75rem;">Menor número = aparece primero.</small>
            </div>
            
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" onclick="closeCatModal()" class="btn-new btn-outline" style="padding:10px 22px;">Cancelar</button>
                <button type="submit" class="btn-new" style="padding:10px 28px;"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<style>
    #catModal input:focus, #catModal select:focus { outline:none; border-color:var(--accent-toast); box-shadow:0 0 0 3px rgba(217,140,69,.15); }
</style>

<script>
function toggleModal(mode) {
    document.getElementById('catModal').style.display = 'flex';
    document.getElementById('catModalTitle').innerText = 'Nueva Categoría';
    document.getElementById('catAccion').value = 'crear';
    document.getElementById('catId').value = '';
    document.getElementById('catNombre').value = '';
    document.getElementById('catIcono').value = 'fas fa-bread-slice';
    document.getElementById('catOrden').value = '0';
}

function editarCat(cat) {
    document.getElementById('catModal').style.display = 'flex';
    document.getElementById('catModalTitle').innerText = 'Editar Categoría';
    document.getElementById('catAccion').value = 'editar';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catNombre').value = cat.nombre;
    document.getElementById('catIcono').value = cat.icono;
    document.getElementById('catOrden').value = cat.orden;
}

function closeCatModal() {
    document.getElementById('catModal').style.display = 'none';
}

document.getElementById('catModal').addEventListener('click', function(e) {
    if (e.target === this) closeCatModal();
});
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeCatModal(); });
</script>

<?php include 'includes/admin_footer.php'; ?>
