<?php
$pageTitle = "Finalizar Compra | Fermento";
require 'includes/db.php';
require 'includes/config.php';
include 'includes/header.php';
include 'includes/nav.php';

if (empty($_SESSION['carrito'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$nombre_pre = "";
$tel_pre = "";
$dir_pre = "";

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if($user) {
        $nombre_pre = $user['nombre'];
        $tel_pre = isset($user['telefono']) ? $user['telefono'] : ''; 
        $dir_pre = isset($user['direccion']) ? $user['direccion'] : ''; 
    }
}

// ── Recalcular subtotal desde la BD (precios en tiempo real) ─────────────
// Importante: NO confiar en los precios guardados en sesión. Si el admin
// actualizó un precio, el cliente debe ver el valor correcto aquí también.
$subtotal = 0;
$minimo_violations = [];
$agrupadoPorPadre = []; // Para validar F4
foreach ($_SESSION['carrito'] as $key => $item) {
    // Obtener precio real desde la BD
    if (!empty($item['variante_id'])) {
        $stmtP = $pdo->prepare("SELECT precio FROM producto_variantes WHERE id = ? AND producto_id = ?");
        $stmtP->execute([$item['variante_id'], $item['id']]);
    } else {
        $stmtP = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
        $stmtP->execute([$item['id']]);
    }
    $precio_real = $stmtP->fetchColumn();

    if ($precio_real !== false) {
        // Actualizar precio en sesión si cambió
        if ((float)$_SESSION['carrito'][$key]['precio'] !== (float)$precio_real) {
            $_SESSION['carrito'][$key]['precio'] = $precio_real;
        }
        $subtotal += $precio_real * $item['cantidad'];
    }

    $min = isset($item['minimo_compra_padre']) ? (int)$item['minimo_compra_padre'] : (isset($item['minimo_compra']) ? (int)$item['minimo_compra'] : 1);
    if ($min < 1) $min = 1;

    if (!isset($agrupadoPorPadre[$item['id']])) {
        $agrupadoPorPadre[$item['id']] = [
            'nombre' => preg_replace('/ \(.*\)$/', '', $item['nombre']), // Quitar nombre de variante para el msj
            'minimo' => $min,
            'cantidad_total' => 0
        ];
    }
    $agrupadoPorPadre[$item['id']]['cantidad_total'] += $item['cantidad'];
}

foreach ($agrupadoPorPadre as $pId => $datos) {
    // Fermento vende por lote de producción completo: el total combinado
    // (sumando todos los sabores de un mismo producto) debe ser un múltiplo
    // exacto del mínimo, no solo "al menos" el mínimo.
    $esMultiplo = $datos['minimo'] > 0 && ($datos['cantidad_total'] % $datos['minimo'] === 0);
    if ($datos['cantidad_total'] < $datos['minimo'] || !$esMultiplo) {
        $minimo_violations[] = [
            'nombre'  => $datos['nombre'],
            'minimo'  => $datos['minimo'],
            'actual'  => $datos['cantidad_total'],
            'parcial' => $datos['cantidad_total'] >= $datos['minimo'], // ya alcanzó el mínimo pero no es lote exacto
        ];
    }
}

$hay_errores_minimo = count($minimo_violations) > 0;


$descuento = 0;
$cupon = $_SESSION['cupon'] ?? null;
if ($cupon) {
    if ($cupon['tipo'] === 'porcentaje') {
        $descuento = $subtotal * ($cupon['valor'] / 100);
    } else {
        $descuento = $cupon['valor'];
    }
    if ($descuento > $subtotal) $descuento = $subtotal;
}
$zonas = getZonasEnvio();
$costoGlobal = (float)getConfig('costo_envio', '30.00');
// Construir mapa de costos por zona (para JS dinámico)
$zonasCostos = [];
foreach ($zonas as $z) {
    $zonasCostos[$z['id']] = ($z['costo_envio'] !== null) ? (float)$z['costo_envio'] : $costoGlobal;
}
$costoEnvio = $costoGlobal; // Valor inicial (se actualizará con JS)

$total_pagar = $subtotal - $descuento + $costoEnvio;
?>

<div class="container section">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1 class="section-title" style="margin:0; text-align:left;">Finalizar Pedido</h1>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div style="font-size:0.9rem;">
                ¿Ya tienes cuenta? <a href="login.php" style="color:var(--accent-toast); font-weight:bold;">Iniciar Sesión</a> para agilizar.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── AVISO DE TIEMPO DE ELABORACIÓN ── -->
    <div style="
        display: flex;
        gap: 16px;
        align-items: flex-start;
        background: linear-gradient(135deg, #fdf8f3 0%, #fef5e7 100%);
        border: 1px solid #D98C45;
        border-left: 5px solid #D98C45;
        border-radius: 10px;
        padding: 18px 22px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(217,140,69,0.10);
    ">
        <div style="font-size:2rem; line-height:1; flex-shrink:0;">🕐</div>
        <div>
            <div style="font-family:'Merriweather',serif; font-weight:700; font-size:1rem; color:#7a4f1e; margin-bottom:6px;">
                Tiempo de preparación artesanal: 24 a 48 horas hábiles
            </div>
            <div style="font-size:0.875rem; color:#8c6a3a; line-height:1.65;">
                Para garantizarte un producto <strong>recién horneado, de calidad impecable</strong>,
                cada pedido pasa por nuestro proceso artesanal completo: horneado, elaboración y empaque.
                Por ello, el tiempo estimado desde que confirmas tu pedido hasta la entrega es de
                <strong>24 a 48 horas hábiles</strong>.
                <br>Te notificaremos por WhatsApp en cuanto esté listo. ¡Gracias por tu paciencia! 🍞✨
            </div>
        </div>
    </div>

    <?php if ($hay_errores_minimo): ?>
    <div style="background:#fff3cd; border:1px solid #f0a500; border-radius:10px; padding:16px 20px; margin-bottom:24px; font-size:0.9rem; color:#856404;">
        <strong>&#9888; No puedes continuar &mdash; hay productos que no completan un lote exacto:</strong>
        <ul style="margin:10px 0 0 18px; line-height:1.9;">
            <?php foreach ($minimo_violations as $v): ?>
                <li>
                    <strong><?php echo htmlspecialchars($v['nombre']); ?></strong>:
                    <?php if ($v['parcial']): ?>
                        se vende en lotes de <strong><?php echo $v['minimo']; ?> unidades</strong>, tienes <?php echo $v['actual']; ?> (te faltan <?php echo $v['minimo'] - ($v['actual'] % $v['minimo']); ?> para completar el siguiente lote, o quita <?php echo $v['actual'] % $v['minimo']; ?> para quedarte en el anterior).
                    <?php else: ?>
                        mínimo requerido <strong><?php echo $v['minimo']; ?> unidades</strong>, tienes <?php echo $v['actual']; ?>.
                    <?php endif; ?>
                    <a href="javascript:history.back();" style="color:#c0392b; font-weight:600; margin-left:6px;">&#8592; Ajustar en el carrito</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="checkout-grid">

        <!-- FORMULARIO -->
        <div class="checkout-form">
            <h3 style="margin-bottom:20px;">Datos de Entrega</h3>
            <form action="procesar_pedido.php" method="POST" id="orderForm">
                <!-- Token CSRF: protege contra envíos fraudulentos desde otros sitios -->
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="form-group">
                    <label>Nombre quien recibe</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre_pre); ?>" required placeholder="Ej: Juan Pérez" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>

                <div class="form-group">
                    <label>Teléfono de contacto</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($tel_pre); ?>" required placeholder="Ej: 5555-1234" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>
                
                <div class="form-group">
                    <label>Zona de Envío *</label>
                    <select name="zona_envio_id" id="zonaSelect" required onchange="actualizarCostoEnvio(this.value)" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; background:white;">
                        <option value="">Selecciona tu zona...</option>
                        <?php foreach($zonas as $z): 
                            $costoZona = ($z['costo_envio'] !== null) ? (float)$z['costo_envio'] : $costoGlobal;
                        ?>
                            <option value="<?php echo $z['id']; ?>">
                                <?php echo htmlspecialchars($z['nombre']); ?> (+Q<?php echo number_format($costoZona, 2); ?>)
                            </option>
                        <?php endforeach; ?>
                        <option value="0">Otra zona / Coordinar por WhatsApp (+Q0.00)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Dirección exacta</label>
                    <textarea name="direccion" rows="3" required placeholder="Zona, Colonia, Casa, Referencias..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"><?php echo htmlspecialchars($dir_pre); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Notas adicionales (Opcional)</label>
                    <textarea name="notas" rows="2" placeholder="Ej: Timbre no sirve, dejar en recepción..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></textarea>
                </div>

                <!-- ── FECHA Y HORA DE ENTREGA (F1) ──────────────────────── -->
                <div class="form-group" id="grupoFechaEntrega" style="margin-top:18px;">
                    <label style="font-weight:700;margin-bottom:6px;display:block;">
                        📅 Fecha de Entrega Deseada <span style="color:#c0392b;">*</span>
                    </label>
                    <input type="date" name="fecha_envio_programada" id="fechaEntrega"
                           min="<?= date('Y-m-d', strtotime('+' . (int)getConfig('horas_anticipacion', 42) . ' hours')) ?>"
                           required
                           onchange="cargarFranjas(this.value)"
                           style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;font-family:'Poppins';font-size:0.9rem;color:#333;">
                    <small id="msgFecha" style="color:#e74c3c;font-size:0.78rem;display:none;margin-top:4px;"></small>
                </div>

                <div class="form-group" id="grupoHoraEntrega" style="display:none;margin-top:4px;">
                    <label style="font-weight:700;margin-bottom:6px;display:block;">
                        🕐 Hora de Entrega <span style="color:#c0392b;">*</span>
                    </label>
                    <select name="hora_envio_programada" id="horaEntrega" required
                            style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;font-family:'Poppins';font-size:0.9rem;background:white;">
                        <option value="">Primero selecciona una fecha...</option>
                    </select>
                    <small id="horarioInfo" style="color:#888;font-size:0.77rem;margin-top:4px;display:block;"></small>
                </div>
                <!-- ──────────────────────────────────────────────────────── -->

            </form>

        </div>

        <!-- RESUMEN -->
        <div class="order-summary" style="background:white; padding:30px; border-radius:10px; border:1px solid #eee; height:fit-content;">
            <h3 style="margin-bottom:20px; border-bottom:2px solid var(--bg-cream); padding-bottom:10px;">Resumen</h3>
            
            <?php
            $cantidadesPorProducto = [];
            foreach ($_SESSION['carrito'] as $cItem) {
                $pId = $cItem['id'];
                $cantidadesPorProducto[$pId] = ($cantidadesPorProducto[$pId] ?? 0) + $cItem['cantidad'];
            }
            
            foreach ($_SESSION['carrito'] as $item):
                $pId = $item['id'];
                $min = isset($item['minimo_compra_padre']) ? (int)$item['minimo_compra_padre'] : (isset($item['minimo_compra']) ? (int)$item['minimo_compra'] : 1);
                if ($min < 1) $min = 1;
                
                $sumaCombinada = $cantidadesPorProducto[$pId];
                $bajo_minimo = ($sumaCombinada < $min) || ($min > 0 && $sumaCombinada % $min !== 0);
            ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:0.95rem; <?php echo $bajo_minimo ? 'border-left:3px solid #e74c3c; padding-left:8px;' : ''; ?>">
                    <div>
                        <strong><?php echo $item['cantidad']; ?>x</strong> <?php echo htmlspecialchars($item['nombre']); ?>
                        <?php if ($bajo_minimo && $sumaCombinada < $min): ?>
                            <div style="font-size:0.75rem; color:#c0392b; font-weight:600; margin-top:2px;">
                                &#9888; Mínimo combinado: <?php echo $min; ?> uds. (Llevas <?php echo $sumaCombinada; ?>)
                            </div>
                        <?php elseif ($bajo_minimo): ?>
                            <div style="font-size:0.75rem; color:#c0392b; font-weight:600; margin-top:2px;">
                                &#9888; Debe ser múltiplo de <?php echo $min; ?> (lote completo). Llevas <?php echo $sumaCombinada; ?>.
                            </div>
                        <?php elseif ($min > 1): ?>
                            <div style="font-size:0.72rem; color:#888;">Mínimo de la categoría: <?php echo $min; ?></div>
                        <?php endif; ?>
                    </div>
                    <div>Q<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></div>
                </div>
            <?php endforeach; ?>
            
            <hr style="margin:20px 0; border:0; border-top:1px dashed #ccc;">
            
            <!-- CÓDIGO DE DESCUENTO -->
            <div style="margin-bottom:20px; border-top:1px solid #eee; padding-top:20px;">
                <label style="display:block; font-weight:700; margin-bottom:10px; font-size:0.9rem;">Cupón de Descuento</label>
                <?php if ($cupon): ?>
                    <div style="background:#eafaf1; border:1px solid #27ae60; padding:12px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-weight:700; color:#27ae60;"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($cupon['codigo']); ?></span>
                            <div style="font-size:0.8rem; color:#2e7d32;">
                                - <?php echo $cupon['tipo'] === 'porcentaje' ? number_format($cupon['valor'], 0).'%' : 'Q'.number_format($cupon['valor'], 2); ?>
                            </div>
                        </div>
                        <button type="button" onclick="removerCupon()" style="background:transparent; border:none; color:#c0392b; cursor:pointer; font-size:0.9rem; font-weight:700;">Remover</button>
                    </div>
                <?php else: ?>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="cuponInput" placeholder="Ej: VERANO20" style="flex:1; padding:10px 15px; border-radius:8px; border:1.5px solid #ddd; font-family:'Poppins'; text-transform:uppercase;">
                        <button type="button" onclick="aplicarCupon()" class="btn-primary" style="padding:10px 20px;">Aplicar</button>
                    </div>
                    <div id="cuponMsg" style="font-size:0.8rem; margin-top:8px; display:none;"></div>
                <?php endif; ?>
            </div>

            <!-- TOTALES -->
            <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:0.95rem; color:#666;">
                <span>Subtotal (<?php echo count($_SESSION['carrito']); ?> prod):</span>
                <span>Q<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($descuento > 0): ?>
            <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:0.95rem; color:#e74c3c; font-weight:600;">
                <span>Descuento (<?php echo htmlspecialchars($cupon['codigo']); ?>):</span>
                <span>-Q<?php echo number_format($descuento, 2); ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-size:0.95rem; color:#666;">
                <span>Envío:</span>
                <span id="costoEnvioDisplay">Selecciona una zona</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:700; color:var(--text-black); border-top:2px solid #eee; padding-top:15px; margin-top:5px;">
                <span>Total a Pagar:</span>
                <span id="totalPagarDisplay" style="color:var(--accent-toast);">Q<?php echo number_format($subtotal - $descuento, 2); ?> + envío</span>
            </div>

            <?php if ($hay_errores_minimo): ?>
            <button type="button" disabled
                style="margin-top:30px; padding:15px; width:100%; background:#ddd; color:#888; border:none; border-radius:8px; font-weight:700; cursor:not-allowed; font-size:0.95rem;">
                &#9888; Ajusta los mínimos para continuar
            </button>
            <p style="font-size:0.78rem; color:#c0392b; text-align:center; margin-top:10px;">
                Regresa al carrito y aumenta la cantidad de los productos marcados.
            </p>
            <?php else: ?>
            <!-- B-01: id único para que JS pueda referenciarlo y deshabilitarlo al submit -->
            <button type="submit" form="orderForm" id="btnConfirmar" class="btn-primary full-width" style="margin-top:30px; padding:15px;">
                Confirmar Pedido <i class="fas fa-check-circle"></i>
            </button>
            <p style="font-size:0.8rem; color:#666; text-align:center; margin-top:15px;">
                <i class="fas fa-lock"></i> Al confirmar, te enviaremos a WhatsApp para coordinar el pago.
            </p>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
/* B-03: Grid responsivo — 2 columnas en escritorio, 1 columna en móvil */
.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}
@media (max-width: 768px) {
    .checkout-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .order-summary {
        order: -1; /* El resumen aparece primero en móvil */
    }
}
</style>

<script>
    const zn = document.getElementById('zonaNombre');
    if (zn && typeof zonaSelect !== 'undefined') {
        zn.value = zonaSelect.options[zonaSelect.selectedIndex].text;
    }

    // Mapa de costos por zona (generado desde PHP)
    const ZONAS_COSTOS = <?php echo json_encode($zonasCostos); ?>;
    const SUBTOTAL_BASE = <?php echo $subtotal - $descuento; ?>;

    function actualizarCostoEnvio(zonaId) {
        const envioEl  = document.getElementById('costoEnvioDisplay');
        const totalEl  = document.getElementById('totalPagarDisplay');
        if (!envioEl || !totalEl) return;

        if (!zonaId || zonaId === '') {
            envioEl.innerText = 'Selecciona una zona';
            totalEl.innerText = 'Q' + SUBTOTAL_BASE.toFixed(2) + ' + envío';
            return;
        }

        const zonaIdInt = parseInt(zonaId);
        let costo = 0;
        if (zonaIdInt > 0 && ZONAS_COSTOS[zonaIdInt] !== undefined) {
            costo = ZONAS_COSTOS[zonaIdInt];
        }

        const total = SUBTOTAL_BASE + costo;

        if (zonaIdInt === 0) {
            envioEl.innerHTML = '<span style="color:#27ae60;font-weight:600;">Q0.00</span> <small style="color:#aaa;">(Coordinar por WhatsApp)</small>';
        } else {
            envioEl.innerHTML = '<strong>Q' + costo.toFixed(2) + '</strong>';
        }
        totalEl.innerHTML = '<strong>Q' + total.toFixed(2) + '</strong>';
    }

    // B-01: Prevenir doble envío deshabilitando el botón al primer submit
    document.getElementById('orderForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnConfirmar');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
        }
    });

function aplicarCupon() {
    const code = document.getElementById('cuponInput').value.trim();
    const msg = document.getElementById('cuponMsg');
    if(!code) return;
    
    fetch('ajax/cupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'aplicar', codigo: code })
    })
    .then(r => r.json())
    .then(data => {
        msg.style.display = 'block';
        if(data.success) {
            msg.style.color = '#27ae60';
            msg.innerText = data.msg + ' Recargando...';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            msg.style.color = '#e74c3c';
            msg.innerText = data.error;
        }
    });
}

function removerCupon() {
    fetch('ajax/cupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'remover' })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) window.location.reload();
    });
}

// ── F1: Cargar franjas horarias disponibles para la fecha elegida ────────────
function cargarFranjas(fecha) {
    const msgFecha    = document.getElementById('msgFecha');
    const grupoHora   = document.getElementById('grupoHoraEntrega');
    const selectHora  = document.getElementById('horaEntrega');
    const horarioInfo = document.getElementById('horarioInfo');

    if (!fecha) return;

    // Reset visual
    msgFecha.style.display = 'none';
    grupoHora.style.display = 'none';
    selectHora.innerHTML = '<option value="">Cargando...</option>';
    selectHora.removeAttribute('required');

    fetch('ajax/franjas_entrega.php?fecha=' + encodeURIComponent(fecha))
        .then(r => r.json())
        .then(data => {
            if (!data.disponible) {
                msgFecha.textContent = data.mensaje;
                msgFecha.style.display = 'block';
                grupoHora.style.display = 'none';
                selectHora.innerHTML = '<option value="">No disponible</option>';
            } else {
                // Rellenar horas
                selectHora.innerHTML = '<option value="">Elige tu hora de entrega...</option>';
                data.franjas.forEach(h => {
                    const op = document.createElement('option');
                    op.value = h;
                    const [hh, mm] = h.split(':');
                    const hora12 = ((parseInt(hh) % 12) || 12) + ':' + mm + (parseInt(hh) < 12 ? ' AM' : ' PM');
                    op.textContent = hora12;
                    selectHora.appendChild(op);
                });
                selectHora.setAttribute('required', 'required');
                horarioInfo.textContent = '⏰ Horario de atención: ' + data.horario;
                grupoHora.style.display = 'block';
                msgFecha.style.display = 'none';
            }
        })
        .catch(() => {
            msgFecha.textContent = 'Error al cargar horarios. Recarga la página.';
            msgFecha.style.display = 'block';
        });
}
</script>

<?php include 'includes/footer.php'; ?>