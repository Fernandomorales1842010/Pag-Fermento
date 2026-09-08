<?php
$pageTitle = "Mis Pedidos | Fermento";
require 'includes/db.php';
require 'includes/config.php';
include 'includes/header.php';
include 'includes/nav.php';

// 1. Seguridad: Si no está logueado, va al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

// 2. Consultar pedidos DE ESTE USUARIO
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC");
$stmt->execute([$uid]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ── Modal de confirmación de cancelación ── */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 9000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: white;
    border-radius: 16px;
    padding: 36px 32px;
    max-width: 420px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    animation: modalIn 0.25s ease;
}
@keyframes modalIn {
    from { transform: scale(0.9); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
.modal-icon  { font-size: 2.8rem; margin-bottom: 14px; }
.modal-title { font-family:'Merriweather',serif; font-size:1.2rem; font-weight:700; color:#1F1F1F; margin-bottom:10px; }
.modal-desc  { font-size:0.88rem; color:#666; line-height:1.6; margin-bottom:26px; }
.modal-actions { display:flex; gap:12px; justify-content:center; }
.btn-cancel-confirm {
    background:#e74c3c; color:white; border:none;
    padding:11px 24px; border-radius:8px; font-weight:700;
    font-family:'Poppins',sans-serif; font-size:0.9rem;
    cursor:pointer; transition:background 0.2s;
}
.btn-cancel-confirm:hover { background:#c0392b; }
.btn-keep {
    background:#f4f4f4; color:#555; border:none;
    padding:11px 24px; border-radius:8px; font-weight:600;
    font-family:'Poppins',sans-serif; font-size:0.9rem;
    cursor:pointer; transition:background 0.2s;
}
.btn-keep:hover { background:#e8e8e8; }
/* Botón cancelar en la tabla */
.btn-cancelar-pedido {
    background:transparent; border:1.5px solid #e74c3c;
    color:#e74c3c; padding:5px 12px; border-radius:6px;
    font-size:0.78rem; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; transition:all 0.2s;
    margin-left:8px;
}
.btn-cancelar-pedido:hover { background:#e74c3c; color:white; }
</style>

<!-- Modal de confirmación de cancelación -->
<div class="modal-overlay" id="modalCancelar">
    <div class="modal-box">
        <div class="modal-icon">⚠️</div>
        <div class="modal-title">¿Cancelar este pedido?</div>
        <div class="modal-desc">
            Esta acción no se puede deshacer.<br>
            El stock de los productos será restaurado y el cupón
            (si usaste uno) quedará disponible nuevamente.
        </div>
        <div class="modal-actions">
            <button class="btn-keep" onclick="cerrarModal()">Mantener pedido</button>
            <button class="btn-cancel-confirm" id="btnConfirmarCancelacion" onclick="ejecutarCancelacion()">
                Sí, cancelar
            </button>
        </div>
    </div>
</div>

<div class="container section">

    <?php
    // Obtener datos del usuario actualmente logueado
    $stmtUser = $pdo->prepare("SELECT nombre, email, telefono, direccion, rol FROM usuarios WHERE id = ?");
    $stmtUser->execute([$uid]);
    $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $esAdmin  = in_array($userInfo['rol'] ?? 'cliente', ['admin', 'supervisor']);
    ?>

    <!-- ─── TARJETA DE PERFIL ─── -->
    <div style="background:white; border-radius:16px; border:1px solid #eee; padding:28px 30px; margin-bottom:32px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:20px; box-shadow:0 4px 15px rgba(0,0,0,0.04);">
        <div style="display:flex; align-items:center; gap:20px;">
            <div style="width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#1F1F1F,#D98C45); display:flex; align-items:center; justify-content:center; color:white; font-size:1.4rem; flex-shrink:0;">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <div style="font-weight:800; font-size:1.15rem; font-family:'Merriweather',serif;">
                    <?php echo htmlspecialchars($userInfo['nombre']); ?>
                    <?php if($esAdmin): ?>
                        <span style="background:#fff3e0; color:#D98C45; font-size:0.6rem; padding:3px 8px; border-radius:20px; font-family:'Poppins',sans-serif; font-weight:700; margin-left:8px; vertical-align:middle; text-transform:uppercase; letter-spacing:0.5px;"><?php echo strtoupper($userInfo['rol']); ?></span>
                    <?php endif; ?>
                </div>
                <div style="color:#888; font-size:0.85rem; margin-top:3px;"><i class="fas fa-envelope" style="margin-right:5px;"></i><?php echo htmlspecialchars($userInfo['email']); ?></div>
                <?php if(!empty($userInfo['telefono'])): ?>
                <div style="color:#888; font-size:0.85rem; margin-top:2px;"><i class="fas fa-phone" style="margin-right:5px;"></i><?php echo htmlspecialchars($userInfo['telefono']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <?php if($esAdmin): ?>
            <a href="admin/index.php" style="display:inline-flex; align-items:center; gap:8px; background:#1F1F1F; color:white; padding:12px 22px; border-radius:10px; text-decoration:none; font-weight:700; font-family:'Poppins',sans-serif; font-size:0.9rem; transition:background 0.2s;" onmouseover="this.style.background='#D98C45'" onmouseout="this.style.background='#1F1F1F'">
                <i class="fas fa-tools"></i> Panel Administrador
            </a>
            <?php endif; ?>
            <a href="logout.php" style="display:inline-flex; align-items:center; gap:8px; border:1.5px solid #eee; color:#999; padding:11px 20px; border-radius:10px; text-decoration:none; font-weight:600; font-family:'Poppins',sans-serif; font-size:0.85rem; transition:all 0.2s;" onmouseover="this.style.borderColor='#e74c3c';this.style.color='#e74c3c'" onmouseout="this.style.borderColor='#eee';this.style.color='#999'">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </div>

    <h2 class="section-title">Historial de Compras</h2>

    <?php if(count($pedidos) > 0): ?>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr style="background: #f4f4f4; text-align: left;">
                        <th style="padding: 15px;"># Orden</th>
                        <th style="padding: 15px;">Fecha</th>
                        <th style="padding: 15px;">Dirección</th>
                        <th style="padding: 15px;">Total</th>
                        <th style="padding: 15px;">Estado</th>
                        <th style="padding: 15px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pedidos as $p):
                        $est         = strtolower($p['estado']);
                        $color       = ($est=='completado') ? '#d4edda' : (($est=='cancelado') ? '#f8d7da' : '#fff3cd');
                        $txtColor    = ($est=='completado') ? '#155724' : (($est=='cancelado') ? '#721c24' : '#856404');
                        $esPendiente = ($est === 'pendiente');
                    ?>
                        <tr id="fila-pedido-<?php echo $p['id']; ?>" style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px;"><strong>#<?php echo $p['id']; ?></strong></td>
                            <td style="padding: 15px;"><?php echo date('d/m/Y', strtotime($p['fecha'])); ?></td>
                            <td style="padding: 15px; font-size: 0.9rem; color: #666;">
                                <?php
                                    $dir = $p['direccion_envio'];
                                    echo htmlspecialchars(strlen($dir) > 30 ? substr($dir, 0, 30) . '...' : $dir);
                                ?>
                            </td>
                            <td style="padding: 15px; font-weight: bold; color: var(--accent-toast);">
                                Q<?php echo number_format($p['total'], 2); ?>
                            </td>
                            <td style="padding: 15px;">
                                <span id="badge-<?php echo $p['id']; ?>"
                                      style="background: <?php echo $color; ?>; color: <?php echo $txtColor; ?>; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-transform: capitalize;">
                                    <?php echo $est; ?>
                                </span>
                            </td>
                            <td style="padding: 15px; text-align: right; white-space: nowrap;">
                                <a href="detalle_orden.php?id=<?php echo $p['id']; ?>" class="btn-mini">
                                    Ver Detalle
                                </a>
                                <?php if($esPendiente): ?>
                                <!-- Gap 1: Botón cancelar — solo visible en pedidos pendientes -->
                                <button class="btn-cancelar-pedido"
                                        id="btn-cancelar-<?php echo $p['id']; ?>"
                                        onclick="abrirModal(<?php echo $p['id']; ?>)">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div style="text-align: center; padding: 60px; background: #fff; border-radius: 10px; border: 1px solid #eee;">
            <i class="fas fa-shopping-basket" style="font-size: 3rem; color: #ddd; margin-bottom: 20px;"></i>
            <p style="color: #666; font-size: 1.1rem;">Aún no has realizado ninguna compra.</p>
            <a href="index.php" class="btn-primary" style="margin-top: 20px;">Ir a la Tienda</a>
        </div>
    <?php endif; ?>
</div>

<script>
// ── Lógica del modal de cancelación ─────────────────────────────────────────
let pedidoAcancelar = null;

function abrirModal(pedidoId) {
    pedidoAcancelar = pedidoId;
    document.getElementById('modalCancelar').classList.add('active');
}

function cerrarModal() {
    pedidoAcancelar = null;
    document.getElementById('modalCancelar').classList.remove('active');
}

// Cerrar modal al hacer clic fuera del cuadro
document.getElementById('modalCancelar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

// Gap 1+2: Envía la cancelación al endpoint AJAX con CSRF token
function ejecutarCancelacion() {
    if (!pedidoAcancelar) return;

    const btn = document.getElementById('btnConfirmarCancelacion');
    btn.disabled    = true;
    btn.textContent = 'Cancelando...';

    fetch('ajax/cancelar_pedido.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            pedido_id:  pedidoAcancelar,
            csrf_token: '<?php echo csrf_token(); ?>'
        })
    })
    .then(r => r.json())
    .then(data => {
        cerrarModal();
        btn.disabled    = false;
        btn.textContent = 'Sí, cancelar';

        if (data.success) {
            // Actualizar la fila en tiempo real sin recargar la página
            const badge = document.getElementById('badge-' + pedidoAcancelar);
            if (badge) {
                badge.style.background = '#f8d7da';
                badge.style.color      = '#721c24';
                badge.textContent      = 'cancelado';
            }
            // Quitar el botón de cancelar (ya no aplica para este pedido)
            const btnCancelar = document.getElementById('btn-cancelar-' + pedidoAcancelar);
            if (btnCancelar) btnCancelar.remove();

            mostrarToast('✅ ' + data.msg, 'success');
        } else {
            mostrarToast('❌ ' + data.error, 'error');
        }
    })
    .catch(() => {
        cerrarModal();
        btn.disabled    = false;
        btn.textContent = 'Sí, cancelar';
        mostrarToast('❌ Error de conexión. Intenta de nuevo.', 'error');
    });
}

// Toast de notificación
function mostrarToast(mensaje, tipo) {
    const t = document.createElement('div');
    t.textContent = mensaje;
    t.style.cssText = `
        position:fixed; bottom:24px; right:24px; z-index:9999;
        background:${tipo === 'success' ? '#27ae60' : '#e74c3c'};
        color:white; padding:14px 22px; border-radius:10px;
        font-family:'Poppins',sans-serif; font-size:0.9rem; font-weight:600;
        box-shadow:0 8px 24px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}
</script>

<?php include 'includes/footer.php'; ?>