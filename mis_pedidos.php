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

// Precargar historiales para los pedidos en pendiente_confirmacion
$historiales = [];
foreach ($pedidos as $p) {
    if ($p['estado'] === 'pendiente_confirmacion') {
        $st_h = $pdo->prepare("SELECT * FROM pedido_historial WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
        $st_h->execute([$p['id']]);
        $historiales[$p['id']] = $st_h->fetch(PDO::FETCH_ASSOC);
    }
}
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

        <div style="display:flex; flex-direction:column; gap:20px;">
            <?php foreach($pedidos as $p):
                $est = strtolower($p['estado']);
                $esPendiente = ($est === 'pendiente');
                
                // Mapeo del flujo para el timeline
                // Pasos: pendiente -> preparando -> en_camino -> completado
                $pasos = [
                    'pendiente'  => ['label' => '📋 Recibido', 'idx' => 0],
                    'preparando' => ['label' => '🧑‍🍳 Preparando', 'idx' => 1],
                    'en_camino'  => ['label' => '🚚 En Camino', 'idx' => 2],
                    'completado' => ['label' => '✅ Completado', 'idx' => 3],
                ];
                $pasoActualIdx = isset($pasos[$est]) ? $pasos[$est]['idx'] : -1;
            ?>
                <div id="card-pedido-<?php echo $p['id']; ?>" style="background: white; border-radius: 12px; border: 1px solid #eee; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #f4f4f4; padding-bottom: 15px; margin-bottom: 15px; flex-wrap:wrap; gap:10px;">
                        <div>
                            <h3 style="margin:0; font-family:'Merriweather'; color:var(--accent-toast);">Pedido #<?php echo $p['id']; ?></h3>
                            <div style="font-size:0.85rem; color:#888; margin-top:5px;">
                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($p['fecha'])); ?>
                                &nbsp;|&nbsp;
                                <strong>Total:</strong> Q<?php echo number_format($p['total'], 2); ?>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <a href="detalle_orden.php?id=<?php echo $p['id']; ?>" class="btn-mini" style="text-decoration:none;">Ver Detalle</a>
                            <?php if($esPendiente): ?>
                            <button class="btn-cancelar-pedido" id="btn-cancelar-<?php echo $p['id']; ?>" onclick="abrirModal(<?php echo $p['id']; ?>)">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($est === 'pendiente_confirmacion'): 
                        $hist = $historiales[$p['id']] ?? null;
                    ?>
                        <!-- F2: Banner de pendiente confirmación -->
                        <div style="background:#fff3e0; border:2px solid #ffb74d; border-radius:10px; padding:15px; margin-bottom:15px;">
                            <h4 style="margin:0 0 10px 0; color:#e65100; font-family:'Merriweather';"><i class="fas fa-exclamation-circle"></i> Cambio propuesto en tu pedido</h4>
                            <p style="font-size:0.9rem; margin:0 0 5px 0;"><strong>Motivo:</strong> <?php echo htmlspecialchars($hist['motivo'] ?? 'Sin motivo especificado.'); ?></p>
                            <pre style="font-family:inherit; font-size:0.85rem; background:#ffe0b2; padding:10px; border-radius:5px; margin-bottom:15px; white-space:pre-wrap;"><?php echo htmlspecialchars($hist['valor_nuevo'] ?? ''); ?></pre>
                            
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button onclick="responderCambio(<?php echo $p['id']; ?>, 'confirmar')" style="background:#27ae60; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:700;"><i class="fas fa-check"></i> Confirmar Cambio</button>
                                <button onclick="responderCambio(<?php echo $p['id']; ?>, 'rechazar')" style="background:#e74c3c; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:700;"><i class="fas fa-times"></i> Rechazar y Cancelar</button>
                            </div>
                        </div>
                    <?php elseif ($est === 'cancelado'): ?>
                        <div style="background:#f8d7da; color:#721c24; padding:10px 15px; border-radius:8px; font-weight:bold;"><i class="fas fa-ban"></i> Este pedido fue cancelado.</div>
                    <?php else: ?>
                        <!-- F3: Timeline Visual -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; position:relative;">
                            <div style="position:absolute; top:50%; left:0; width:100%; height:4px; background:#f0f0f0; z-index:0; transform:translateY(-50%);"></div>
                            
                            <?php foreach($pasos as $k => $v): 
                                $isCompleted = ($v['idx'] < $pasoActualIdx);
                                $isActive = ($v['idx'] === $pasoActualIdx);
                                $color = $isCompleted ? '#27ae60' : ($isActive ? 'var(--accent-toast)' : '#dcdcdc');
                                $textColor = $isActive ? '#111' : '#888';
                                $bg = $isCompleted ? '#27ae60' : ($isActive ? 'var(--accent-toast)' : '#fff');
                                $border = $isCompleted ? '#27ae60' : ($isActive ? 'var(--accent-toast)' : '#dcdcdc');
                                $iconColor = ($isCompleted || $isActive) ? '#fff' : '#aaa';
                            ?>
                            <div style="z-index:1; display:flex; flex-direction:column; align-items:center; gap:8px; width:25%;">
                                <div style="width:30px; height:30px; border-radius:50%; background:<?php echo $bg; ?>; border:2px solid <?php echo $border; ?>; display:flex; align-items:center; justify-content:center; color:<?php echo $iconColor; ?>; <?php if($isActive) echo 'box-shadow: 0 0 0 4px rgba(217, 140, 69, 0.2); animation: pulse 2s infinite;'; ?>">
                                    <?php if($isCompleted): ?><i class="fas fa-check" style="font-size:0.7rem;"></i><?php else: ?><div style="width:8px;height:8px;background:<?php echo $iconColor; ?>;border-radius:50%;"></div><?php endif; ?>
                                </div>
                                <div style="font-size:0.75rem; font-weight:<?php echo $isActive ? '700' : '600'; ?>; color:<?php echo $textColor; ?>; text-align:center;">
                                    <?php echo $v['label']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if(!empty($p['fecha_envio_programada'])): 
                            $diasSem = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                            $ts = strtotime($p['fecha_envio_programada']);
                            $fechaFormateada = $diasSem[date('w',$ts)] . ', ' . date('d/m/Y', $ts);
                            $horaFormateada = '';
                            if(!empty($p['hora_envio_programada'])) {
                                list($hh,$mm) = explode(':', $p['hora_envio_programada']);
                                $horaFormateada = ' a las ' . (((int)$hh % 12 ?: 12) . ':' . $mm . ((int)$hh < 12 ? ' AM' : ' PM'));
                            }
                        ?>
                        <div style="background:#f4f9f4; border-radius:8px; padding:10px; font-size:0.85rem; color:#2c3e50; display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-truck" style="color:#27ae60;"></i> 
                            <strong>Entrega programada:</strong> <?php echo $fechaFormateada . $horaFormateada; ?>
                        </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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
            // Recargar la página para reflejar el estado cancelado en la tarjeta
            window.location.reload();
        } else {
            alert(data.error || 'Error al cancelar el pedido.');
        }
    })
    .catch(e => {
        cerrarModal();
        btn.disabled    = false;
        btn.textContent = 'Sí, cancelar';
        alert('Ocurrió un error de conexión.');
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