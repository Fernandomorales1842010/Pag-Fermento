<?php
$currentPage = 'cupones';
$pageTitle   = 'Cupones y Descuentos';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';
require_can('ver_cupones'); // Solo admin
include 'includes/admin_nav.php';


$msg = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify('cupones.php');
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $codigo       = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo         = in_array($_POST['tipo'], ['porcentaje', 'fijo']) ? $_POST['tipo'] : 'porcentaje';
        $valor        = (float)$_POST['valor'];
        $usos_maximos = !empty($_POST['usos_maximos']) ? (int)$_POST['usos_maximos'] : null;
        $fecha_expira = !empty($_POST['fecha_expira']) ? $_POST['fecha_expira'] : null;

        if ($codigo && $valor > 0) {
            $stmt = $pdo->prepare("INSERT INTO cupones (codigo, tipo, valor, usos_maximos, fecha_expira) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$codigo, $tipo, $valor, $usos_maximos, $fecha_expira]);
                adminLog('crear_cupon', 'cupones', (int)$pdo->lastInsertId(), $codigo);
                $msg = 'creado';
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $msg = 'duplicado';
                }
            }
        }
    } elseif ($accion === 'editar') {
        $id           = (int)$_POST['id'];
        $codigo       = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo         = in_array($_POST['tipo'], ['porcentaje', 'fijo']) ? $_POST['tipo'] : 'porcentaje';
        $valor        = (float)$_POST['valor'];
        $usos_maximos = !empty($_POST['usos_maximos']) ? (int)$_POST['usos_maximos'] : null;
        $fecha_expira = !empty($_POST['fecha_expira']) ? $_POST['fecha_expira'] : null;

        if ($codigo && $id && $valor > 0) {
            $stmt = $pdo->prepare("UPDATE cupones SET codigo = ?, tipo = ?, valor = ?, usos_maximos = ?, fecha_expira = ? WHERE id = ?");
            try {
                $stmt->execute([$codigo, $tipo, $valor, $usos_maximos, $fecha_expira, $id]);
                adminLog('editar_cupon', 'cupones', $id, $codigo);
                $msg = 'actualizado';
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $msg = 'duplicado';
                }
            }
        }
    } elseif ($accion === 'toggle') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE cupones SET activo = NOT activo WHERE id = ?")->execute([$id]);
        adminLog('toggle_cupon', 'cupones', $id);
        $msg = 'toggle';
    } elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM cupones WHERE id = ?")->execute([$id]);
        adminLog('eliminar_cupon', 'cupones', $id);
        $msg = 'eliminado';
    }
}

// Obtener cupones
$cupones = $pdo->query("SELECT * FROM cupones ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div class="page-title">
        <h1>Cupones y Descuentos</h1>
        <p>Administra códigos promocionales para tus clientes.</p>
    </div>
    <button onclick="toggleModal('crear')" class="btn-new">
        <i class="fas fa-ticket-alt"></i> Nuevo Cupón
    </button>
</div>

<?php if($msg === 'duplicado'): ?>
<div style="background:#ffebee;border:1px solid #e74c3c;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#e74c3c;font-weight:600;">
    <i class="fas fa-times-circle"></i> Ya existe un cupón con ese código.
</div>
<?php elseif($msg && $msg !== 'toggle'): ?>
<div style="background:#eafaf1;border:1px solid #27ae60;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#27ae60;font-weight:600;">
    <i class="fas fa-check-circle"></i> Cupón <?php echo $msg; ?> correctamente.
</div>
<?php endif; ?>

<div style="background:white; border-radius:20px; padding:25px; box-shadow:var(--shadow); overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead>
            <tr style="background:#f8f9fa;">
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Código</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Descuento</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Usos</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Expira</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700;">Estado</th>
                <th style="padding:15px; font-size:0.75rem; text-transform:uppercase; color:#aaa; font-weight:700; text-align:right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cupones)): ?>
            <tr>
                <td colspan="6" style="padding:30px; text-align:center; color:#888;">No hay cupones registrados.</td>
            </tr>
            <?php else: ?>
            <?php foreach($cupones as $c): ?>
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:15px; font-weight:700; color:var(--accent-toast);">
                    <i class="fas fa-ticket-alt" style="margin-right:5px; color:#D98C45;"></i> <?php echo htmlspecialchars($c['codigo']); ?>
                </td>
                <td style="padding:15px; font-weight:600;">
                    <?php 
                        echo $c['tipo'] === 'porcentaje' 
                            ? number_format($c['valor'], 0) . '%' 
                            : 'Q' . number_format($c['valor'], 2);
                    ?>
                </td>
                <td style="padding:15px; font-size:0.9rem; color:#555;">
                    <?php echo $c['usos_actuales']; ?> / <?php echo $c['usos_maximos'] ? $c['usos_maximos'] : '∞'; ?>
                </td>
                <td style="padding:15px; font-size:0.9rem; color:#555;">
                    <?php echo $c['fecha_expira'] ? date('d/m/Y', strtotime($c['fecha_expira'])) : 'Nunca'; ?>
                </td>
                <td style="padding:15px;">
                    <?php if ($c['activo']): ?>
                        <span style="background:#e8f5e9; color:#2e7d32; padding:5px 10px; border-radius:50px; font-size:0.75rem; font-weight:700;">Activo</span>
                    <?php else: ?>
                        <span style="background:#ffebee; color:#c62828; padding:5px 10px; border-radius:50px; font-size:0.75rem; font-weight:700;">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td style="padding:15px; text-align:right;">
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button onclick='toggleModal("editar", <?php echo json_encode($c); ?>)' title="Editar"
                                style="width:30px;height:30px;border-radius:8px;border:1px solid #eee;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-pencil-alt" style="font-size:0.7rem;color:var(--accent-toast);"></i>
                        </button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                            <button type="submit" style="width:30px;height:30px;border-radius:8px;border:1px solid #eee;background:<?php echo $c['activo']?'#fff3e0':'#e8f5e9'; ?>;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="<?php echo $c['activo']?'Desactivar':'Activar'; ?>">
                                <i class="fas <?php echo $c['activo']?'fa-eye-slash':'fa-eye'; ?>" style="font-size:0.7rem;color:<?php echo $c['activo']?'#f39c12':'#27ae60'; ?>;"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este cupón?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                            <button type="submit" style="width:30px;height:30px;border-radius:8px;border:1px solid #eee;background:#ffebee;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Eliminar">
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
<div id="cuponModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:4000;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:white;border-radius:20px;padding:35px;max-width:480px;width:90%;box-shadow:0 25px 60px rgba(0,0,0,0.25);">
        <h3 style="font-family:'Merriweather';margin-bottom:20px;" id="modalTitle">Nuevo Cupón</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" id="formAccion" value="crear">
            <input type="hidden" name="id" id="formId" value="">
            
            <div style="margin-bottom:18px;">
                <label style="display:block;margin-bottom:8px;font-weight:600;font-size:0.85rem;color:#444;">Código del Cupón *</label>
                <input type="text" name="codigo" id="formCodigo" required
                       style="width:100%;padding:12px 15px;border-radius:10px;border:1.5px solid #ddd;font-family:'Poppins';text-transform:uppercase;"
                       placeholder="Ej: VERANO20">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:18px;">
                <div>
                    <label style="display:block;margin-bottom:8px;font-weight:600;font-size:0.85rem;color:#444;">Tipo de Descuento</label>
                    <select name="tipo" id="formTipo" style="width:100%;padding:12px 15px;border-radius:10px;border:1.5px solid #ddd;font-family:'Poppins';">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="fijo">Monto Fijo (Q)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;font-weight:600;font-size:0.85rem;color:#444;">Valor *</label>
                    <input type="number" name="valor" id="formValor" step="0.01" min="0" required
                           style="width:100%;padding:12px 15px;border-radius:10px;border:1.5px solid #ddd;font-family:'Poppins';"
                           placeholder="Ej: 15">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:25px;">
                <div>
                    <label style="display:block;margin-bottom:8px;font-weight:600;font-size:0.85rem;color:#444;">Usos Máximos</label>
                    <input type="number" name="usos_maximos" id="formUsosMaximos" min="1"
                           style="width:100%;padding:12px 15px;border-radius:10px;border:1.5px solid #ddd;font-family:'Poppins';"
                           placeholder="Ilimitado">
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;font-weight:600;font-size:0.85rem;color:#444;">Fecha de Expiración</label>
                    <input type="date" name="fecha_expira" id="formFechaExpira"
                           style="width:100%;padding:12px 15px;border-radius:10px;border:1.5px solid #ddd;font-family:'Poppins';">
                </div>
            </div>
            
            <div style="display:flex;gap:15px;">
                <button type="button" onclick="toggleModal()" class="btn-new btn-outline" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-new" style="flex:2;">Guardar Cupón</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(accion = null, data = null) {
    const modal = document.getElementById('cuponModal');
    if (accion) {
        modal.style.display = 'flex';
        document.getElementById('formAccion').value = accion === 'crear' ? 'crear' : 'editar';
        document.getElementById('modalTitle').innerText = accion === 'crear' ? 'Nuevo Cupón' : 'Editar Cupón';
        
        if (data && accion === 'editar') {
            document.getElementById('formId').value = data.id;
            document.getElementById('formCodigo').value = data.codigo;
            document.getElementById('formTipo').value = data.tipo;
            document.getElementById('formValor').value = data.valor;
            document.getElementById('formUsosMaximos').value = data.usos_maximos || '';
            document.getElementById('formFechaExpira').value = data.fecha_expira ? data.fecha_expira.substring(0,10) : '';
        } else {
            document.getElementById('formId').value = '';
            document.getElementById('formCodigo').value = '';
            document.getElementById('formTipo').value = 'porcentaje';
            document.getElementById('formValor').value = '';
            document.getElementById('formUsosMaximos').value = '';
            document.getElementById('formFechaExpira').value = '';
        }
    } else {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>
