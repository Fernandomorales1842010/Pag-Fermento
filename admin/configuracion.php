<?php
$currentPage = 'configuracion';
$pageTitle   = 'Configuración';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';

if ($_SESSION['user_rol'] === 'supervisor') {
    die("<div style='padding:50px;text-align:center;'><h2>Acceso Denegado</h2><p>Los supervisores no tienen permiso para ver o modificar la configuración del sistema.</p><a href='index.php'>Volver al inicio</a></div>");
}

include 'includes/admin_nav.php';

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify('configuracion.php');
    $campos = ['costo_envio', 'whatsapp_numero', 'tienda_nombre', 'tienda_descripcion'];
    foreach ($campos as $c) {
        if (isset($_POST[$c])) {
            setConfig($c, trim($_POST[$c]));
        }
    }
    adminLog('editar_configuracion', 'configuracion', null, 'Configuración general guardada');
    $guardado = true;
}

$guardado = $guardado ?? false;
?>

<div class="page-header">
    <div class="page-title">
        <h1>Configuración General</h1>
        <p>Administra los parámetros globales de tu tienda.</p>
    </div>
</div>

<?php if($guardado): ?>
<div style="background:#eafaf1;border:1px solid #27ae60;border-radius:12px;padding:14px 20px;margin-bottom:20px;color:#27ae60;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> Configuración guardada correctamente.
</div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
    </div>


</div>

<div style="display:flex;justify-content:flex-end;margin-top:25px;">
    <button type="submit" class="btn-new" style="padding:14px 36px;">
        <i class="fas fa-save"></i> Guardar Configuración
    </button>
</div>
</form>

<style>
    .field-group input:focus { outline:none; border-color:var(--accent-toast); box-shadow:0 0 0 3px rgba(217,140,69,.15); }
</style>

<?php include 'includes/admin_footer.php'; ?>
