<?php
// admin/usuarios.php
$currentPage = 'usuarios';
$pageTitle   = 'Gestión de Usuarios';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';

// Solo el rol 'admin' puede acceder
if ($_SESSION['user_rol'] !== 'admin') {
    echo "<div style='padding:50px;text-align:center;'><h2>Acceso Denegado</h2><p>Solo los administradores pueden gestionar usuarios.</p><a href='index.php'>Volver al inicio</a></div>";
    exit;
}

include 'includes/admin_nav.php';

// Parámetros de búsqueda
$search = trim($_GET['buscar'] ?? '');
$rol    = trim($_GET['rol'] ?? '');

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($rol !== '') {
    $sql .= " AND rol = ?";
    $params[] = $rol;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas rápidas
$total_admins     = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
$total_supervisores = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'supervisor'")->fetchColumn();
$total_clientes   = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente'")->fetchColumn();

// Mensajes
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>

<style>
.usuarios-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 25px;
}
.stat-card-user {
    background: white;
    border-radius: 16px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow);
    transition: transform 0.2s;
}
.stat-card-user:hover { transform: translateY(-2px); }
.stat-icon-user {
    width: 50px; height: 50px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.stat-info-user .num  { font-size: 1.8rem; font-weight: 800; line-height: 1; }
.stat-info-user .lbl  { font-size: 0.78rem; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }

/* Tabla premium */
.usuarios-table { width: 100%; border-collapse: collapse; }
.usuarios-table thead th {
    padding: 12px 16px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #aaa;
    font-weight: 700;
    background: #fafafa;
    border-bottom: 1px solid #f0f0f0;
}
.usuarios-table tbody tr {
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.15s;
}
.usuarios-table tbody tr:hover { background: #fafbff; }
.usuarios-table td { padding: 14px 16px; }

/* Avatar inicial */
.avatar-circle {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem;
    flex-shrink: 0;
    color: white;
}

/* Badges de rol */
.rol-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filtros */
.filtros-card {
    background: white;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: var(--shadow);
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filtros-card .f-label {
    font-size: 0.73rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #888;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 6px;
}
.filtros-card input, .filtros-card select {
    padding: 10px 14px;
    border: 1.5px solid #eee;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
    background: white;
}
.filtros-card input:focus, .filtros-card select:focus {
    border-color: var(--accent-toast);
    box-shadow: 0 0 0 3px rgba(217,140,69,0.12);
}

/* Modal premium */
.modal-overlay-u {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(5px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.modal-box-u {
    background: white;
    border-radius: 24px;
    width: 90%;
    max-width: 520px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: slideUp 0.3s ease;
}
@keyframes slideUp {
    from { transform: translateY(40px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.modal-header-u {
    padding: 28px 32px 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex; justify-content: space-between; align-items: center;
}
.modal-header-u h2 {
    font-family: 'Merriweather', serif;
    font-size: 1.2rem; margin: 0;
}
.modal-close-btn {
    width: 34px; height: 34px;
    border-radius: 50%; border: none;
    background: #f5f5f5; cursor: pointer;
    font-size: 1.1rem; color: #666;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.modal-close-btn:hover { background: #fee; color: #e74c3c; }
.modal-body-u { padding: 24px 32px 32px; }

.u-field { margin-bottom: 18px; }
.u-field label {
    font-size: 0.73rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #666;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 7px;
}
.u-field input, .u-field select {
    width: 100%;
    padding: 11px 16px;
    border: 1.5px solid #e8e8e8;
    border-radius: 12px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.2s;
    background: #fafafa;
    box-sizing: border-box;
}
.u-field input:focus, .u-field select:focus {
    border-color: var(--accent-toast);
    background: white;
    box-shadow: 0 0 0 3px rgba(217,140,69,0.12);
}
.role-selector { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
.role-option {
    border: 2px solid #eee;
    border-radius: 12px;
    padding: 12px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.8rem;
    font-weight: 600;
}
.role-option:hover { border-color: #ccc; background: #fafafa; }
.role-option.selected { border-color: var(--accent-toast); background: rgba(217,140,69,0.07); }
.role-option .role-icon { font-size: 1.5rem; margin-bottom: 5px; display: block; }
.role-option .role-name { display: block; color: #333; }
.role-option .role-desc { display: block; font-size: 0.65rem; color: #aaa; margin-top: 2px; font-weight: 400; }
</style>

<div class="page-header">
    <div class="page-title">
        <h1>Gestión de Usuarios</h1>
        <p>Administra clientes, supervisores y administradores del sistema.</p>
    </div>
    <div class="page-actions">
        <button class="btn-new" onclick="abrirModalCrear()">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </button>
    </div>
</div>

<?php if($msg == 'creado'): ?>
<div style="background:#eafaf1;border:1px solid #27ae60;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#27ae60;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> Usuario creado exitosamente.
</div>
<?php elseif($msg == 'actualizado'): ?>
<div style="background:#eafaf1;border:1px solid #27ae60;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#27ae60;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> Usuario actualizado correctamente.
</div>
<?php elseif($msg == 'eliminado'): ?>
<div style="background:#fdf5e8;border:1px solid #D98C45;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#b56f30;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-trash-alt"></i> Usuario eliminado correctamente.
</div>
<?php elseif($err == 'bd'): ?>
<div style="background:#ffebee;border:1px solid #e74c3c;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#e74c3c;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-exclamation-triangle"></i> Error en la base de datos.
</div>
<?php elseif($err == 'email_existe'): ?>
<div style="background:#ffebee;border:1px solid #e74c3c;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#e74c3c;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-exclamation-triangle"></i> El correo electrónico ya está registrado.
</div>
<?php endif; ?>

<!-- Stats rápidas -->
<div class="usuarios-stats">
    <div class="stat-card-user">
        <div class="stat-icon-user" style="background:rgba(52,152,219,0.12); color:#2980b9;">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="stat-info-user">
            <div class="num" style="color:#2980b9;"><?php echo $total_admins; ?></div>
            <div class="lbl">Administradores</div>
        </div>
    </div>
    <div class="stat-card-user">
        <div class="stat-icon-user" style="background:rgba(155,89,182,0.12); color:#8e44ad;">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="stat-info-user">
            <div class="num" style="color:#8e44ad;"><?php echo $total_supervisores; ?></div>
            <div class="lbl">Supervisores</div>
        </div>
    </div>
    <div class="stat-card-user">
        <div class="stat-icon-user" style="background:rgba(46,204,113,0.12); color:#27ae60;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info-user">
            <div class="num" style="color:#27ae60;"><?php echo $total_clientes; ?></div>
            <div class="lbl">Clientes</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filtros-card">
    <form method="GET" style="display:flex; gap:15px; width:100%; flex-wrap:wrap; align-items:flex-end;">
        <div style="flex:1; min-width:220px;">
            <span class="f-label">Buscar</span>
            <input type="text" name="buscar" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nombre o email..." style="width:100%;">
        </div>
        <div style="min-width:180px;">
            <span class="f-label">Rol</span>
            <select name="rol" style="width:100%;">
                <option value="">Todos los roles</option>
                <option value="admin" <?php echo $rol == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                <option value="supervisor" <?php echo $rol == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                <option value="cliente" <?php echo $rol == 'cliente' ? 'selected' : ''; ?>>Cliente</option>
            </select>
        </div>
        <div style="display:flex; gap:10px; align-items:flex-end;">
            <button type="submit" class="btn-new" style="padding:10px 20px; height:43px;">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <?php if($search !== '' || $rol !== ''): ?>
                <a href="usuarios.php" style="padding:10px 16px; height:43px; border:1.5px solid #ddd; border-radius:10px; display:flex; align-items:center; text-decoration:none; color:#666; font-size:0.85rem; transition:0.2s;" onmouseover="this.style.borderColor='#D98C45'" onmouseout="this.style.borderColor='#ddd'">
                    <i class="fas fa-times"></i>&nbsp;Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla -->
<div style="background:white; border-radius:20px; box-shadow:var(--shadow); overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="usuarios-table">
            <thead>
                <tr>
                    <th style="text-align:left; padding-left:20px;">Usuario</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Registro</th>
                    <th style="text-align:right; padding-right:20px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($usuarios) > 0): ?>
                    <?php 
                    $avatarColors = ['#6C63FF','#FF6B6B','#FFD93D','#6BCB77','#4D96FF','#F4A261','#C77DFF'];
                    foreach($usuarios as $i => $u): 
                        $initial = strtoupper(mb_substr($u['nombre'], 0, 1));
                        $bgColor = $avatarColors[$i % count($avatarColors)];
                        
                        // Config de rol
                        $rolConfig = [
                            'admin'      => ['bg'=>'#EBF5FB','color'=>'#2980b9','icon'=>'fas fa-shield-alt','label'=>'Admin'],
                            'supervisor' => ['bg'=>'#F5EEF8','color'=>'#8e44ad','icon'=>'fas fa-user-tie','label'=>'Supervisor'],
                            'cliente'    => ['bg'=>'#EAFAF1','color'=>'#27ae60','icon'=>'fas fa-user','label'=>'Cliente'],
                        ];
                        $rc = $rolConfig[$u['rol']] ?? ['bg'=>'#eee','color'=>'#666','icon'=>'fas fa-user','label'=>$u['rol']];
                    ?>
                    <tr>
                        <td style="padding-left:20px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="avatar-circle" style="background:<?php echo $bgColor; ?>;">
                                    <?php echo $initial; ?>
                                </div>
                                <div>
                                    <div style="font-weight:700; color:#1F1F1F;"><?php echo htmlspecialchars($u['nombre']); ?></div>
                                    <div style="font-size:0.75rem; color:#aaa;">#<?php echo $u['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" style="color:#555; text-decoration:none; font-size:0.88rem;" onmouseover="this.style.color='var(--accent-toast)'" onmouseout="this.style.color='#555'">
                                <i class="fas fa-envelope" style="margin-right:5px; opacity:0.4;"></i><?php echo htmlspecialchars($u['email']); ?>
                            </a>
                        </td>
                        <td style="color:#666; font-size:0.88rem;">
                            <?php if(!empty($u['telefono'])): ?>
                                <i class="fas fa-phone" style="margin-right:5px; opacity:0.4;"></i><?php echo htmlspecialchars($u['telefono']); ?>
                            <?php else: ?>
                                <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="rol-badge" style="background:<?php echo $rc['bg']; ?>; color:<?php echo $rc['color']; ?>;">
                                <i class="<?php echo $rc['icon']; ?>"></i> <?php echo $rc['label']; ?>
                            </span>
                        </td>
                        <td style="font-size:0.83rem; color:#999;">
                            <i class="fas fa-calendar-alt" style="margin-right:5px; opacity:0.4;"></i>
                            <?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?>
                        </td>
                        <td style="padding-right:20px; text-align:right;">
                            <div style="display:flex; gap:8px; justify-content:flex-end;">
                                <button onclick='abrirModalEditar(<?php echo json_encode($u); ?>)' 
                                        style="width:34px;height:34px;border-radius:10px;border:1.5px solid #eee;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
                                        onmouseover="this.style.borderColor='var(--accent-toast)';this.style.background='#fdf5e8';"
                                        onmouseout="this.style.borderColor='#eee';this.style.background='white';"
                                        title="Editar usuario">
                                    <i class="fas fa-pencil-alt" style="font-size:0.7rem;color:var(--accent-toast);"></i>
                                </button>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <button onclick="eliminarUsuario(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['nombre'])); ?>')"
                                        style="width:34px;height:34px;border-radius:10px;border:1.5px solid #fee;background:#fff9f9;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
                                        onmouseover="this.style.background='#ffebee';this.style.borderColor='#e74c3c';"
                                        onmouseout="this.style.background='#fff9f9';this.style.borderColor='#fee';"
                                        title="Eliminar usuario">
                                    <i class="fas fa-trash" style="font-size:0.7rem;color:#e74c3c;"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:50px; color:#bbb;">
                            <i class="fas fa-search" style="font-size:2rem; display:block; margin-bottom:10px; opacity:0.3;"></i>
                            No se encontraron usuarios con esos filtros.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear/Editar -->
<div id="modalUsuario" class="modal-overlay-u">
    <div class="modal-box-u">
        <div class="modal-header-u">
            <h2 id="modalTitle"><i class="fas fa-user-plus" style="color:var(--accent-toast);margin-right:10px;"></i>Nuevo Usuario</h2>
            <button class="modal-close-btn" onclick="cerrarModal()">×</button>
        </div>
        <div class="modal-body-u">
            <form id="formUsuario" action="procesar_usuario.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="usuario_id" value="">
                <input type="hidden" name="rol" id="u_rol_hidden" value="cliente">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="u-field" style="grid-column:1/-1;">
                        <label>Nombre Completo *</label>
                        <input type="text" name="nombre" id="u_nombre" required placeholder="Ej: Juan Pérez García">
                    </div>
                    <div class="u-field">
                        <label>Correo Electrónico *</label>
                        <input type="email" name="email" id="u_email" required placeholder="correo@ejemplo.com">
                    </div>
                    <div class="u-field">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="u_telefono" placeholder="5555-5555">
                    </div>
                    <div class="u-field" style="grid-column:1/-1;">
                        <label>
                            Contraseña <span id="passHint" style="color:#aaa; font-size:0.75rem; font-weight:400; text-transform:none; letter-spacing:0;">* Obligatoria</span>
                        </label>
                        <input type="password" name="password" id="u_password" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>

                <!-- Selector de rol visual -->
                <div class="u-field">
                    <label>Rol en el Sistema *</label>
                    <div class="role-selector">
                        <div class="role-option" id="role-cliente" onclick="selectRole('cliente')">
                            <span class="role-icon">👤</span>
                            <span class="role-name">Cliente</span>
                            <span class="role-desc">Solo compras</span>
                        </div>
                        <div class="role-option" id="role-supervisor" onclick="selectRole('supervisor')">
                            <span class="role-icon">🛠️</span>
                            <span class="role-name">Supervisor</span>
                            <span class="role-desc">Catálogo y pedidos</span>
                        </div>
                        <div class="role-option" id="role-admin" onclick="selectRole('admin')">
                            <span class="role-icon">🛡️</span>
                            <span class="role-name">Admin</span>
                            <span class="role-desc">Acceso total</span>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:12px; margin-top:8px;">
                    <button type="button" onclick="cerrarModal()" 
                            style="flex:1; padding:12px; border:1.5px solid #eee; border-radius:12px; background:white; cursor:pointer; font-family:'Poppins'; font-weight:600; color:#666; transition:0.2s;"
                            onmouseover="this.style.borderColor='#ccc'" onmouseout="this.style.borderColor='#eee'">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-new" style="flex:2; padding:12px; border-radius:12px;">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectRole(rol) {
    document.getElementById('u_rol_hidden').value = rol;
    document.querySelectorAll('.role-option').forEach(el => el.classList.remove('selected'));
    document.getElementById('role-' + rol).classList.add('selected');
}

function abrirModalCrear() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus" style="color:var(--accent-toast);margin-right:10px;"></i>Nuevo Usuario';
    document.getElementById('accion').value = 'crear';
    document.getElementById('usuario_id').value = '';
    document.getElementById('u_nombre').value = '';
    document.getElementById('u_email').value = '';
    document.getElementById('u_telefono').value = '';
    document.getElementById('u_password').required = true;
    document.getElementById('passHint').innerText = '* Obligatoria';
    selectRole('cliente');
    document.getElementById('modalUsuario').style.display = 'flex';
}

function abrirModalEditar(u) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit" style="color:var(--accent-toast);margin-right:10px;"></i>Editar Usuario';
    document.getElementById('accion').value = 'editar';
    document.getElementById('usuario_id').value = u.id;
    document.getElementById('u_nombre').value = u.nombre;
    document.getElementById('u_email').value = u.email;
    document.getElementById('u_telefono').value = u.telefono || '';
    document.getElementById('u_password').required = false;
    document.getElementById('u_password').value = '';
    document.getElementById('passHint').innerText = '(Dejar en blanco para no cambiarla)';
    selectRole(u.rol || 'cliente');
    document.getElementById('modalUsuario').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalUsuario').style.display = 'none';
}

// Cerrar al click fuera
document.getElementById('modalUsuario').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

function eliminarUsuario(id, nombre) {
    if(confirm(`¿Eliminar a "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'procesar_usuario.php';
        const fields = {
            csrf_token: '<?php echo csrf_token(); ?>',
            accion: 'eliminar',
            id: id
        };
        Object.entries(fields).forEach(([k,v]) => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = k; i.value = v;
            form.appendChild(i);
        });
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>
