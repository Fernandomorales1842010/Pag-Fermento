<?php
$currentPage = 'configuracion';
$pageTitle   = 'Configuración';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';
require_can('ver_configuracion'); // Solo admin — reemplaza el bloqueo manual anterior
include 'includes/admin_nav.php';

// ── Guardar configuración general ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'config') {
    csrf_verify('configuracion.php');
    $campos = ['costo_envio', 'whatsapp_numero', 'tienda_nombre', 'tienda_descripcion',
               'horario_lv_inicio', 'horario_lv_fin', 'horario_sab_inicio', 'horario_sab_fin',
               'horas_anticipacion', 'multiplicador_pedido_grande'];
    foreach ($campos as $c) {
        if (isset($_POST[$c])) {
            setConfig($c, trim($_POST[$c]));
        }
    }
    adminLog('editar_configuracion', 'configuracion', null, 'Configuración general guardada');
    $guardado = true;
}

// ── Agregar día feriado ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar_feriado') {
    csrf_verify('configuracion.php');
    require_can('gestionar_feriados');
    $fecha = trim($_POST['feriado_fecha'] ?? '');
    $desc  = trim($_POST['feriado_desc']  ?? '');
    if ($fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        try {
            $pdo->prepare("INSERT INTO dias_feriados (fecha, descripcion, creado_por) VALUES (?, ?, ?)")
                ->execute([$fecha, $desc ?: 'Feriado', $_SESSION['user_id']]);
            $msgFeriado = ['type' => 'ok', 'text' => "Feriado del $fecha agregado correctamente."];
            adminLog('agregar_feriado', 'dias_feriados', null, "Feriado: $fecha — $desc");
        } catch (PDOException $e) {
            $msgFeriado = ['type' => 'err', 'text' => 'Esa fecha ya existe como feriado.'];
        }
    } else {
        $msgFeriado = ['type' => 'err', 'text' => 'Fecha inválida.'];
    }
}

// ── Eliminar día feriado ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_feriado') {
    csrf_verify('configuracion.php');
    require_can('gestionar_feriados');
    $fid = (int)$_POST['feriado_id'];
    if ($fid > 0) {
        $pdo->prepare("DELETE FROM dias_feriados WHERE id = ?")->execute([$fid]);
        adminLog('eliminar_feriado', 'dias_feriados', $fid, "Feriado #$fid eliminado");
        $msgFeriado = ['type' => 'ok', 'text' => 'Feriado eliminado.'];
    }
}

$guardado   = $guardado ?? false;
$msgFeriado = $msgFeriado ?? null;

// ── Leer feriados para mostrar ────────────────────────────────────────────────
$feriados = $pdo->query("SELECT * FROM dias_feriados ORDER BY fecha ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div class="page-title">
        <h1>Configuración General</h1>
        <p>Administra los parámetros globales de tu tienda.</p>
    </div>
</div>

<?php if ($guardado): ?>
<div style="background:#eafaf1;border:1px solid #27ae60;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#27ae60;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> Configuración guardada correctamente.
</div>
<?php endif; ?>

<?php if ($msgFeriado): ?>
<div style="background:<?= $msgFeriado['type']==='ok' ? '#eafaf1' : '#fdecea' ?>;border:1px solid <?= $msgFeriado['type']==='ok' ? '#27ae60' : '#e74c3c' ?>;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:<?= $msgFeriado['type']==='ok' ? '#27ae60' : '#c0392b' ?>;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-<?= $msgFeriado['type']==='ok' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($msgFeriado['text']) ?>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     SECCIÓN 1: CONFIGURACIÓN GENERAL (Tienda + Envío)
════════════════════════════════════════════════════════════ -->
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
<input type="hidden" name="accion" value="config">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;">

    <!-- TIENDA -->
    <div class="card" style="padding:30px;">
        <h3 style="font-family:'Merriweather';margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-store" style="color:var(--accent-toast);"></i> Tienda
        </h3>
        
        <div class="field-group" style="margin-bottom:18px;">
            <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">Nombre de la Tienda</label>
            <input type="text" name="tienda_nombre" value="<?php echo htmlspecialchars(getConfig('tienda_nombre', 'Fermento')); ?>" 
                   style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;">
        </div>
        
        <div class="field-group" style="margin-bottom:18px;">
            <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">Descripción Corta</label>
            <input type="text" name="tienda_descripcion" value="<?php echo htmlspecialchars(getConfig('tienda_descripcion', 'Casa de Panaderos')); ?>"
                   style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;">
        </div>
    </div>

    <!-- ENVÍO -->
    <div class="card" style="padding:30px;">
        <h3 style="font-family:'Merriweather';margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-truck" style="color:var(--accent-toast);"></i> Envío
        </h3>
        
        <div class="field-group" style="margin-bottom:18px;">
            <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">Costo de Envío (Q)</label>
            <input type="number" name="costo_envio" step="0.01" min="0" value="<?php echo htmlspecialchars(getConfig('costo_envio', '30.00')); ?>"
                   style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;">
            <small style="color:#999;font-size:0.75rem;">Este costo se aplicará a todas las zonas de envío activas.</small>
        </div>
        
        <div class="field-group" style="margin-bottom:18px;">
            <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">WhatsApp (sin +)</label>
            <input type="text" name="whatsapp_numero" value="<?php echo htmlspecialchars(getConfig('whatsapp_numero', '50239754421')); ?>"
                   style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;"
                   placeholder="50212345678">
            <small style="color:#999;font-size:0.75rem;">Número donde se recibirán los pedidos por WhatsApp.</small>
        </div>

        <div class="field-group">
            <label style="font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;display:block;margin-bottom:7px;">Multiplicador de Pedido Grande/Especial</label>
            <input type="number" name="multiplicador_pedido_grande" step="1" min="2" value="<?php echo htmlspecialchars(getConfig('multiplicador_pedido_grande', '3')); ?>"
                   style="width:100%;padding:12px 16px;border:1.5px solid #eee;border-radius:12px;font-family:'Poppins';font-size:0.9rem;">
            <small style="color:#999;font-size:0.75rem;">Cuando un cliente pida esta cantidad de veces (o más) el mínimo de compra (batch) de un producto, se le mostrará un aviso para coordinar un pedido grande/especial por WhatsApp.</small>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECCIÓN 2: LOGÍSTICA DE ENTREGAS — Horarios
════════════════════════════════════════════════════════════ -->
<div class="card" style="padding:30px;margin-top:25px;">
    <h3 style="font-family:'Merriweather';margin-bottom:6px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-clock" style="color:#6C63FF;"></i> Logística de Entregas — Horarios
    </h3>
    <p style="color:#999;font-size:0.83rem;margin-bottom:22px;">Define el horario en que realizas entregas. El cliente solo podrá elegir fechas y horas dentro de estos rangos.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <!-- Lunes a Viernes -->
        <div style="background:#f9f8ff;border-radius:14px;padding:20px;">
            <div style="font-weight:700;font-size:0.85rem;color:#6C63FF;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-calendar-week"></i> Lunes a Viernes
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:0.75rem;color:#888;font-weight:600;display:block;margin-bottom:6px;">Hora Inicio</label>
                    <input type="time" name="horario_lv_inicio" value="<?= htmlspecialchars(getConfig('horario_lv_inicio', '08:00')) ?>"
                           style="width:100%;padding:10px 12px;border:1.5px solid #e0deff;border-radius:10px;font-family:'Poppins';font-size:0.9rem;color:#333;">
                </div>
                <div>
                    <label style="font-size:0.75rem;color:#888;font-weight:600;display:block;margin-bottom:6px;">Hora Fin</label>
                    <input type="time" name="horario_lv_fin" value="<?= htmlspecialchars(getConfig('horario_lv_fin', '17:00')) ?>"
                           style="width:100%;padding:10px 12px;border:1.5px solid #e0deff;border-radius:10px;font-family:'Poppins';font-size:0.9rem;color:#333;">
                </div>
            </div>
        </div>

        <!-- Sábado -->
        <div style="background:#fff8ee;border-radius:14px;padding:20px;">
            <div style="font-weight:700;font-size:0.85rem;color:#D98C45;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-umbrella-beach"></i> Sábado
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:0.75rem;color:#888;font-weight:600;display:block;margin-bottom:6px;">Hora Inicio</label>
                    <input type="time" name="horario_sab_inicio" value="<?= htmlspecialchars(getConfig('horario_sab_inicio', '08:00')) ?>"
                           style="width:100%;padding:10px 12px;border:1.5px solid #f0dfc3;border-radius:10px;font-family:'Poppins';font-size:0.9rem;color:#333;">
                </div>
                <div>
                    <label style="font-size:0.75rem;color:#888;font-weight:600;display:block;margin-bottom:6px;">Hora Fin</label>
                    <input type="time" name="horario_sab_fin" value="<?= htmlspecialchars(getConfig('horario_sab_fin', '12:00')) ?>"
                           style="width:100%;padding:10px 12px;border:1.5px solid #f0dfc3;border-radius:10px;font-family:'Poppins';font-size:0.9rem;color:#333;">
                </div>
            </div>
        </div>
    </div>
    <div style="background:#f0f4ff;border-radius:10px;padding:10px 14px;margin-top:14px;font-size:0.78rem;color:#6C63FF;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-info-circle"></i> <strong>Domingos:</strong> Sin entregas (bloqueado automáticamente en el checkout).
    </div>

    <!-- Anticipación de envío -->
    <div style="margin-top:22px;background:#f9fff9;border:1.5px solid #c8e6c9;border-radius:14px;padding:20px;">
        <div style="font-weight:700;font-size:0.85rem;color:#27ae60;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-hourglass-half"></i> Anticipación mínima para pedidos programados
        </div>
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="flex:0 0 140px;">
                <label style="font-size:0.75rem;color:#888;font-weight:600;display:block;margin-bottom:6px;">Horas mínimas</label>
                <input type="number" name="horas_anticipacion" min="1" max="168" step="1"
                       value="<?= htmlspecialchars(getConfig('horas_anticipacion', '42')) ?>"
                       style="width:100%;padding:10px 12px;border:1.5px solid #c8e6c9;border-radius:10px;font-family:'Poppins';font-size:0.9rem;color:#333;">
            </div>
            <small style="color:#888;font-size:0.78rem;line-height:1.5;">
                El cliente <strong>no podrá</strong> seleccionar fechas de entrega con menos de estas horas de anticipación.<br>
                Valor actual: <strong><?= (int)getConfig('horas_anticipacion', 42) ?> horas</strong> (~<?= round(getConfig('horas_anticipacion', 42) / 24, 1) ?> días).
            </small>
        </div>
    </div>
</div>

<div style="display:flex;justify-content:flex-end;margin-top:25px;">
    <button type="submit" class="btn-new" style="padding:14px 36px;">
        <i class="fas fa-save"></i> Guardar Configuración
    </button>
</div>
</form>

<!-- ═══════════════════════════════════════════════════════════
     SECCIÓN 3: DÍAS FERIADOS
════════════════════════════════════════════════════════════ -->
<div class="card" style="padding:30px;margin-top:25px;">
    <h3 style="font-family:'Merriweather';margin-bottom:6px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-calendar-times" style="color:#e74c3c;"></i> Días Feriados
    </h3>
    <p style="color:#999;font-size:0.83rem;margin-bottom:22px;">
        Agrega los días feriados. El sistema los bloqueará automáticamente en el checkout y reprogramará las entregas al siguiente día hábil disponible.
    </p>

    <!-- Formulario agregar feriado -->
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;margin-bottom:24px;flex-wrap:wrap;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="accion" value="agregar_feriado">
        <div style="flex:0 0 180px;">
            <label style="font-size:0.75rem;color:#888;font-weight:600;display:block;margin-bottom:6px;">Fecha del Feriado</label>
            <input type="date" name="feriado_fecha" required min="<?= date('Y-m-d') ?>"
                   style="width:100%;padding:10px 14px;border:1.5px solid #eee;border-radius:10px;font-family:'Poppins';font-size:0.88rem;">
        </div>
        <div style="flex:1;min-width:200px;">
            <label style="font-size:0.75rem;color:#888;font-weight:600;display:block;margin-bottom:6px;">Descripción (opcional)</label>
            <input type="text" name="feriado_desc" placeholder="Ej: Día de la Independencia"
                   style="width:100%;padding:10px 14px;border:1.5px solid #eee;border-radius:10px;font-family:'Poppins';font-size:0.88rem;">
        </div>
        <button type="submit" style="padding:10px 22px;background:#e74c3c;color:white;border:none;border-radius:10px;font-family:'Poppins';font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:8px;white-space:nowrap;">
            <i class="fas fa-plus"></i> Agregar Feriado
        </button>
    </form>

    <!-- Lista de feriados -->
    <?php if (empty($feriados)): ?>
    <div style="text-align:center;padding:30px;color:#ccc;">
        <i class="fas fa-calendar-check" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
        No hay días feriados registrados.
    </div>
    <?php else: ?>
    <div style="border:1px solid #f0f0f0;border-radius:12px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
            <thead>
                <tr style="background:#fafafa;border-bottom:2px solid #f0f0f0;">
                    <th style="padding:12px 16px;text-align:left;font-weight:700;color:#555;">Fecha</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:700;color:#555;">Día</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:700;color:#555;">Descripción</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:700;color:#555;">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
            foreach ($feriados as $f):
                $ts = strtotime($f['fecha']);
                $diaNum = (int)date('w', $ts);
                $esPasado = $ts < strtotime('today');
            ?>
            <tr style="border-bottom:1px solid #f8f8f8;<?= $esPasado ? 'opacity:0.5;' : '' ?>">
                <td style="padding:12px 16px;font-weight:700;color:#1F1F1F;">
                    <?= date('d/m/Y', $ts) ?>
                    <?php if ($esPasado): ?><small style="color:#bbb;font-weight:400;"> (pasado)</small><?php endif; ?>
                </td>
                <td style="padding:12px 16px;">
                    <span style="background:<?= $diaNum === 0 ? '#fdecea' : ($diaNum === 6 ? '#fff3cd' : '#f0f4ff') ?>;
                                 color:<?= $diaNum === 0 ? '#c0392b' : ($diaNum === 6 ? '#856404' : '#6C63FF') ?>;
                                 padding:3px 10px;border-radius:20px;font-size:0.78rem;font-weight:700;">
                        <?= $diasSemana[$diaNum] ?>
                    </span>
                </td>
                <td style="padding:12px 16px;color:#666;"><?= htmlspecialchars($f['descripcion']) ?></td>
                <td style="padding:12px 16px;text-align:center;">
                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este feriado?')">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="accion" value="eliminar_feriado">
                        <input type="hidden" name="feriado_id" value="<?= $f['id'] ?>">
                        <button type="submit" style="background:none;border:1px solid #e74c3c;color:#e74c3c;padding:5px 12px;border-radius:8px;cursor:pointer;font-size:0.78rem;font-family:'Poppins';">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
    .field-group input:focus,
    input[type="time"]:focus,
    input[type="date"]:focus { outline:none; border-color:var(--accent-toast) !important; box-shadow:0 0 0 3px rgba(217,140,69,.15); }
</style>

<?php include 'includes/admin_footer.php'; ?>
