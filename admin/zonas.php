<?php
$currentPage = 'zonas';
$pageTitle   = 'Zonas de Envío';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';
include 'includes/admin_nav.php';

$msg = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify('zonas.php');
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $costo       = $_POST['costo_envio'] !== '' ? (float)$_POST['costo_envio'] : null;

        if ($nombre) {
            $stmt = $pdo->prepare("INSERT INTO zonas_envio (nombre, descripcion, costo_envio) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $costo]);
            adminLog('crear_zona', 'zonas_envio', (int)$pdo->lastInsertId(), $nombre);
            $msg = 'creada';
        }
    } elseif ($accion === 'editar') {
        $id          = (int)$_POST['id'];
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $costo       = $_POST['costo_envio'] !== '' ? (float)$_POST['costo_envio'] : null;

        if ($nombre && $id) {
            $stmt = $pdo->prepare("UPDATE zonas_envio SET nombre = ?, descripcion = ?, costo_envio = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $costo, $id]);
            adminLog('editar_zona', 'zonas_envio', $id, $nombre);
            $msg = 'actualizada';
        }
    } elseif ($accion === 'toggle') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE zonas_envio SET activa = NOT activa WHERE id = ?")->execute([$id]);
        adminLog('toggle_zona', 'zonas_envio', $id);
        $msg = 'toggle';
    } elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM zonas_envio WHERE id = ?")->execute([$id]);
        adminLog('eliminar_zona', 'zonas_envio', $id);
        $msg = 'eliminada';
    }
}

// Obtener zonas
$zonas = $pdo->query("SELECT * FROM zonas_envio ORDER BY activa DESC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$costoGlobal = getConfig('costo_envio', '30.00');
?>

<div class="page-header">
    <div class="page-title">
        <h1>Zonas de Envío</h1>
        <p>Administra las zonas y sus costos. El costo global por defecto es <strong>Q<?php echo number_format($costoGlobal, 2); ?></strong> — puedes sobreescribirlo por zona.</p>
    </div>
    <button onclick="toggleModal('crear')" class="btn-new">
        <i class="fas fa-plus"></i> Nueva Zona
    </button>
</div>

<?php if($msg && $msg !== 'toggle'): ?>
<div style="background:#eafaf1;border:1px solid #27ae60;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#27ae60;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> Zona <?php echo htmlspecialchars($msg); ?> correctamente.
</div>
<?php endif; ?>

<!-- Aviso del costo global -->
<div style="background:linear-gradient(135deg,#fdf5e8,#fff9f0);border:1px solid #D98C45;border-radius:14px;padding:16px 22px;margin-bottom:20px;display:flex;align-items:center;gap:14px;">
    <i class="fas fa-info-circle" style="color:#D98C45;font-size:1.3rem;flex-shrink:0;"></i>
    <div style="font-size:0.88rem;color:#7a4f1e;">
        <strong>Costo global de envío:</strong> Q<?php echo number_format($costoGlobal, 2); ?> — se aplica a las zonas que no tengan un costo propio definido.
        <a href="configuracion.php" style="color:var(--accent-toast);font-weight:600;margin-left:6px;">Cambiar costo global →</a>
    </div>
</div>

<div style="background:white;border-radius:20px;box-shadow:var(--shadow);overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#fafafa;border-bottom:2px solid #f0f0f0;">
                <th style="padding:14px 20px;text-align:left;font-size:0.75rem;text-transform:uppercase;color:#aaa;font-weight:700;letter-spacing:0.5px;">Zona</th>
                <th style="padding:14px 20px;text-align:left;font-size:0.75rem;text-transform:uppercase;color:#aaa;font-weight:700;letter-spacing:0.5px;">Descripción</th>
                <th style="padding:14px 20px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:#aaa;font-weight:700;letter-spacing:0.5px;">Costo de Envío</th>
                <th style="padding:14px 20px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:#aaa;font-weight:700;letter-spacing:0.5px;">Estado</th>
                <th style="padding:14px 20px;text-align:right;font-size:0.75rem;text-transform:uppercase;color:#aaa;font-weight:700;letter-spacing:0.5px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($zonas)): ?>
            <tr><td colspan="5" style="padding:50px;text-align:center;color:#ccc;"><i class="fas fa-map-marker-alt" style="font-size:2rem;display:block;margin-bottom:10px;opacity:0.3;"></i>No hay zonas configuradas. Crea la primera.</td></tr>
            <?php else: ?>
            <?php foreach($zonas as $z): 
                $costoZona = (isset($z['costo_envio']) && $z['costo_envio'] !== null) ? (float)$z['costo_envio'] : (float)$costoGlobal;
                $esPropio  = (isset($z['costo_envio']) && $z['costo_envio'] !== null);
            ?>
            <tr style="border-bottom:1px solid #f4f4f4;<?php echo !$z['activa'] ? 'opacity:0.5;' : ''; ?>transition:background 0.15s;" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background=''">
                <td style="padding:14px 20px;font-weight:700;color:#1F1F1F;"><?php echo htmlspecialchars($z['nombre']); ?></td>
                <td style="padding:14px 20px;color:#888;font-size:0.88rem;"><?php echo htmlspecialchars($z['descripcion'] ?: '—'); ?></td>
                <td style="padding:14px 20px;text-align:center;">
                    <span style="font-weight:800;font-size:1.05rem;color:<?php echo $esPropio ? '#6C63FF' : 'var(--accent-toast)'; ?>;">
                        Q<?php echo number_format($costoZona, 2); ?>
                    </span>
                    <?php if($esPropio): ?>
                        <span style="display:block;font-size:0.68rem;color:#6C63FF;font-weight:600;margin-top:2px;background:#f0efff;padding:2px 7px;border-radius:20px;display:inline-block;">Propio</span>
                    <?php else: ?>
                        <span style="display:block;font-size:0.68rem;color:#aaa;margin-top:2px;">Global</span>
                    <?php endif; ?>
                </td>
                <td style="padding:14px 20px;text-align:center;">
                    <?php if($z['activa']): ?>
                        <span style="background:#eafaf1;color:#27ae60;padding:5px 12px;border-radius:50px;font-size:0.72rem;font-weight:700;text-transform:uppercase;">Activa</span>
                    <?php else: ?>
                        <span style="background:#ffebee;color:#e74c3c;padding:5px 12px;border-radius:50px;font-size:0.72rem;font-weight:700;text-transform:uppercase;">Inactiva</span>
                    <?php endif; ?>
                </td>
                <td style="padding:14px 20px;text-align:right;">
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="editarZona(<?php echo htmlspecialchars(json_encode($z)); ?>)" 
                                title="Editar"
                                style="width:32px;height:32px;border-radius:9px;border:1.5px solid #eee;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
                                onmouseover="this.style.borderColor='var(--accent-toast)';this.style.background='#fdf5e8'" onmouseout="this.style.borderColor='#eee';this.style.background='white'">
                            <i class="fas fa-pencil-alt" style="font-size:0.7rem;color:var(--accent-toast);"></i>
                        </button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $z['id']; ?>">
                            <button type="submit" title="<?php echo $z['activa'] ? 'Desactivar' : 'Activar'; ?>"
                                    style="width:32px;height:32px;border-radius:9px;border:1.5px solid #eee;background:<?php echo $z['activa'] ? '#fff3e0' : '#e8f5e9'; ?>;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                                <i class="fas <?php echo $z['activa'] ? 'fa-eye-slash' : 'fa-eye'; ?>" style="font-size:0.7rem;color:<?php echo $z['activa'] ? '#f39c12' : '#27ae60'; ?>;"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar la zona «<?php echo htmlspecialchars(addslashes($z['nombre'])); ?>»?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $z['id']; ?>">
                            <button type="submit" title="Eliminar" style="width:32px;height:32px;border-radius:9px;border:1.5px solid #fee;background:#fff9f9;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                                    onmouseover="this.style.background='#ffebee';this.style.borderColor='#e74c3c'" onmouseout="this.style.background='#fff9f9';this.style.borderColor='#fee'">
                                <i class="fas fa-trash" style="font-size:0.7rem;color:#e74c3c;"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL CREAR/EDITAR -->
<div id="zonaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:4000;align-items:center;justify-content:center;backdrop-filter:blur(5px);">
    <div style="background:white;border-radius:22px;padding:35px;max-width:500px;width:90%;box-shadow:0 30px 70px rgba(0,0,0,0.25);animation:modalIn 0.3s ease;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
            <h3 style="font-family:'Merriweather';margin:0;" id="modalTitle">Nueva Zona</h3>
            <button onclick="closeModal()" style="width:32px;height:32px;border-radius:50%;border:none;background:#f5f5f5;cursor:pointer;font-size:1.1rem;color:#666;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.background='#fee'" onmouseout="this.style.background='#f5f5f5'">×</button>
        </div>
        <form method="POST" id="zonaForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" id="formAccion" value="crear">
            <input type="hidden" name="id" id="formId" value="">
            
            <div style="margin-bottom:18px;">
                <label style="font-weight:700;font-size:0.73rem;text-transform:uppercase;letter-spacing:0.5px;color:#666;display:block;margin-bottom:7px;">Nombre de la Zona *</label>
                <input type="text" name="nombre" id="formNombre" required placeholder="Ej: Zona Metropolitana"
                       style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;box-sizing:border-box;">
            </div>
            
            <div style="margin-bottom:18px;">
                <label style="font-weight:700;font-size:0.73rem;text-transform:uppercase;letter-spacing:0.5px;color:#666;display:block;margin-bottom:7px;">Descripción</label>
                <textarea name="descripcion" id="formDesc" rows="2" placeholder="Ej: Ciudad de Guatemala, zonas 1-21"
                          style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;resize:vertical;box-sizing:border-box;"></textarea>
            </div>

            <div style="margin-bottom:25px;">
                <label style="font-weight:700;font-size:0.73rem;text-transform:uppercase;letter-spacing:0.5px;color:#666;display:block;margin-bottom:7px;">
                    Costo de Envío (Q)
                    <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#aaa;font-size:0.75rem;margin-left:5px;">— Déjalo en blanco para usar el costo global (Q<?php echo number_format($costoGlobal, 2); ?>)</span>
                </label>
                <div style="position:relative;">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-weight:700;color:#888;">Q</span>
                    <input type="number" name="costo_envio" id="formCosto" step="0.01" min="0" placeholder="<?php echo number_format($costoGlobal, 2); ?>"
                           style="width:100%;padding:12px 16px 12px 30px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;box-sizing:border-box;">
                </div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:0.8rem;color:#888;">
                    <i class="fas fa-lightbulb" style="color:#FFD93D;"></i>
                    Puedes diferenciarlo: cobrar más por zonas lejanas o menos por zonas prioritarias.
                </div>
            </div>
            
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" onclick="closeModal()" 
                        style="padding:11px 24px;border:1.5px solid #eee;border-radius:12px;background:white;cursor:pointer;font-family:'Poppins';font-weight:600;color:#666;transition:0.2s;"
                        onmouseover="this.style.borderColor='#ccc'" onmouseout="this.style.borderColor='#eee'">
                    Cancelar
                </button>
                <button type="submit" class="btn-new" style="padding:11px 30px;">
                    <i class="fas fa-save"></i> Guardar Zona
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes modalIn { from { opacity:0; transform:scale(0.88) translateY(20px); } to { opacity:1; transform:scale(1) translateY(0); } }
#zonaModal input:focus, #zonaModal textarea:focus { outline:none; border-color:var(--accent-toast) !important; box-shadow:0 0 0 3px rgba(217,140,69,.15); }
</style>

<script>
function toggleModal(mode) {
    document.getElementById('zonaModal').style.display = 'flex';
    if (mode === 'crear') {
        document.getElementById('modalTitle').innerText = 'Nueva Zona';
        document.getElementById('formAccion').value = 'crear';
        document.getElementById('formId').value = '';
        document.getElementById('formNombre').value = '';
        document.getElementById('formDesc').value = '';
        document.getElementById('formCosto').value = '';
    }
}

function editarZona(zona) {
    document.getElementById('zonaModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Editar Zona';
    document.getElementById('formAccion').value = 'editar';
    document.getElementById('formId').value = zona.id;
    document.getElementById('formNombre').value = zona.nombre;
    document.getElementById('formDesc').value = zona.descripcion || '';
    // Mostrar costo propio si lo tiene, o dejar vacío para indicar "usa global"
    document.getElementById('formCosto').value = zona.costo_envio !== null ? zona.costo_envio : '';
}

function closeModal() {
    document.getElementById('zonaModal').style.display = 'none';
}

document.getElementById('zonaModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });
</script>

<?php include 'includes/admin_footer.php'; ?>
