<?php
$currentPage = 'productos';
require '../includes/db.php';
require '../includes/config.php';

if (!isset($_GET['id'])) { header("Location: productos.php"); exit; }
$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { header("Location: productos.php?msg=error"); exit; }

$stmtVar = $pdo->prepare("SELECT * FROM producto_variantes WHERE producto_id = ?");
$stmtVar->execute([$id]);
$variantes = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
$tieneVariantes = count($variantes) > 0;

$pageTitle = 'Editar: ' . $p['nombre'];
include 'includes/admin_header.php';
include 'includes/admin_nav.php';

// Helper para imagen actual
function imgSrc($name) { return $name ? "../assets/img/" . htmlspecialchars($name) : null; }
?>

<style>
/* Se reutilizan los estilos de agregar.php (ya en el CSS global) */
.form-card { max-width:860px;margin:0 auto;background:white;border-radius:24px;box-shadow:var(--shadow);overflow:hidden; }
.form-header { background:var(--text-black);padding:30px 40px;color:white;display:flex;align-items:center;gap:15px; }
.form-header i { color:var(--accent-toast);font-size:1.5rem; }
.form-header h2 { font-family:'Merriweather';font-size:1.3rem;margin-bottom:3px; }
.form-header p  { color:#888;font-size:0.85rem; }
.form-body { padding:40px; }
.form-grid { display:grid;grid-template-columns:1fr 1fr;gap:22px; }
.col-full { grid-column:1/-1; }
.field-group { display:flex;flex-direction:column;gap:7px; }
.field-group label { font-weight:700;font-size:0.8rem;text-transform:uppercase;letter-spacing:.5px;color:#555; }
.field-group input,.field-group textarea,.field-group select {
    padding:12px 16px;border:1.5px solid #eee;border-radius:12px;
    font-family:'Poppins';font-size:.9rem;color:#111;
    transition:border-color .2s,box-shadow .2s;background:white;width:100%;
}
.field-group input:focus,.field-group textarea:focus,.field-group select:focus {
    outline:none;border-color:var(--accent-toast);box-shadow:0 0 0 3px rgba(217,140,69,.15);
}
.field-group textarea { resize:vertical;min-height:100px; }
.price-wrap { position:relative; }
.price-wrap span { position:absolute;left:14px;top:50%;transform:translateY(-50%);font-weight:700;color:var(--accent-toast); }
.price-wrap input { padding-left:32px; }

/* ZONA IMAGEN EDITABLE */
.img-slot {
    border:2px dashed #eee;border-radius:14px;padding:12px;
    cursor:pointer;transition:border-color .2s,background .2s;position:relative;
}
.img-slot:hover { border-color:var(--accent-toast);background:#fdf8f2; }
.img-slot input[type=file] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%; }
.img-slot .img-current {
    display:flex;align-items:center;gap:12px;
}
.img-slot .img-thumb {
    width:70px;height:70px;object-fit:cover;border-radius:10px;flex-shrink:0;
}
.img-slot .img-thumb-placeholder {
    width:70px;height:70px;border-radius:10px;
    background:#f5f5f5;display:flex;align-items:center;justify-content:center;
    color:#ccc;font-size:1.5rem;flex-shrink:0;
}
.img-slot .slot-info strong { display:block;font-size:0.85rem;font-weight:600; }
.img-slot .slot-info span { color:#999;font-size:0.75rem; }
.img-slot .new-preview { width:100%;max-height:140px;object-fit:cover;border-radius:10px;display:none;margin-top:8px; }

/* TOGGLES */
.toggle-row { display:flex;align-items:center;justify-content:space-between;background:#f9f9f9;border-radius:12px;padding:14px 18px; }
.toggle-label { font-weight:600;font-size:.9rem; }
.toggle-label small { display:block;color:#999;font-size:.75rem;font-weight:400; }
.toggle-switch { position:relative;width:46px;height:26px; }
.toggle-switch input { opacity:0;width:0;height:0; }
.slider { position:absolute;inset:0;background:#ddd;border-radius:50px;cursor:pointer;transition:background .2s; }
.slider::before { content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:white;left:4px;top:4px;transition:transform .2s;box-shadow:0 1px 4px rgba(0,0,0,.2); }
.toggle-switch input:checked + .slider { background:var(--accent-toast); }
.toggle-switch input:checked + .slider::before { transform:translateX(20px); }

.form-actions { display:flex;justify-content:flex-end;gap:12px;padding:25px 40px;border-top:1px solid #f0f0f0;background:#fdfdfd; }
</style>

<div class="page-header">
    <div class="page-title">
        <a href="productos.php" style="text-decoration:none;color:#888;font-size:0.85rem;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-arrow-left"></i> Volver al Inventario
        </a>
        <h1 style="margin-top:8px;">Editar Producto</h1>
    </div>
</div>

<div class="form-card">
    <div class="form-header">
        <i class="fas fa-pencil-alt"></i>
        <div>
            <h2><?php echo htmlspecialchars($p['nombre']); ?></h2>
            <p>Modifica los datos y guarda los cambios.</p>
        </div>
    </div>

    <form action="actualizar.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
    <input type="hidden" name="imagen_actual"   value="<?php echo $p['imagen']; ?>">
    <input type="hidden" name="imagen_actual_2"  value="<?php echo $p['imagen_2']; ?>">
    <input type="hidden" name="imagen_actual_3"  value="<?php echo $p['imagen_3']; ?>">

    <div class="form-body">
        <div class="form-grid">

            <div class="field-group col-full">
                <label>Nombre del Producto *</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($p['nombre']); ?>" required>
            </div>

            <div class="field-group col-full">
                <label>Descripción</label>
                <textarea name="descripcion"><?php echo htmlspecialchars($p['descripcion']); ?></textarea>
            </div>

            <div class="field-group col-full">
                <label>Perfecto para acompañar <span style="font-weight:400;color:#aaa;font-size:0.8rem;">(opcional)</span></label>
                <textarea name="maridaje" rows="3" placeholder="Ej: Ideal con café, vinos tintos o como base para sándwich gourmet..."><?php echo htmlspecialchars($p['maridaje'] ?? ''); ?></textarea>
            </div>

            <div class="field-group" id="mainSkuDiv" style="<?php echo $tieneVariantes ? 'display:none;' : ''; ?>">
                <label>SKU / Código (Opcional)</label>
                <input type="text" name="sku" value="<?php echo htmlspecialchars($p['sku'] ?? ''); ?>" placeholder="Ej: PQ05.1">
            </div>

            <div class="field-group" id="mainPrecioDiv" style="<?php echo $tieneVariantes ? 'display:none;' : ''; ?>">
                <label>Precio Base de Venta (Q) *</label>
                <div class="price-wrap">
                    <span>Q</span>
                    <input type="number" name="precio" id="mainPrecio" step="0.01" min="0" value="<?php echo $p['precio']; ?>" <?php echo $tieneVariantes ? '' : 'required'; ?>>
                </div>
            </div>

            <div class="field-group">
                <label>Precio Distribuidor (Q) (Interno)</label>
                <div class="price-wrap">
                    <span>Q</span>
                    <input type="number" name="precio_distribuidor" step="0.01" min="0" value="<?php echo htmlspecialchars($p['precio_distribuidor'] ?? ''); ?>" placeholder="0.00">
                </div>
            </div>

            <div class="field-group" id="mainStockDiv" style="<?php echo $tieneVariantes ? 'display:none;' : ''; ?>">
                <label>Stock Inicial</label>
                <input type="number" name="stock" min="0" value="<?php echo $p['stock']; ?>">
            </div>

            <div class="field-group">
                <label>Mínimo de Compra *</label>
                <input type="number" name="minimo_compra" min="1" value="<?php echo isset($p['minimo_compra']) ? $p['minimo_compra'] : 1; ?>" required>
            </div>

            <div class="field-group col-full">
                <label>Categoría</label>
                <select name="categoria">
                    <?php
                    $cats = getCategorias();
                    if(empty($cats)) echo '<option value="Sin Categoría">Sin Categoría</option>';
                    foreach($cats as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['nombre']); ?>" <?php echo ($p['categoria'] == $c['nombre']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- TOGGLE VARIANTES -->
            <div class="col-full" style="background:#f9f9f9; padding:20px; border-radius:12px; margin-top:10px; border: 1px solid #eee;">
                <div class="toggle-row" style="background:transparent; padding:0; margin-bottom:15px;">
                    <div class="toggle-label">
                        ¿Este producto tiene múltiples opciones?
                        <small>Activa esto si el producto tiene variantes como sabores, tamaños o empaques diferentes.</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="tieneVariantes" onchange="toggleVariantes()" <?php echo $tieneVariantes ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div id="variantesContainer" style="display:<?php echo $tieneVariantes ? 'flex' : 'none'; ?>; flex-direction:column; gap:15px;">
                    <div id="listaVariantes" style="display:flex; flex-direction:column; gap:10px;">
                        <?php if ($tieneVariantes): ?>
                            <?php foreach ($variantes as $v): ?>
                            <div class="variante-row" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr 80px auto; gap:10px; align-items:end; background:white; padding:15px; border-radius:8px; border:1px solid #ddd;">
                                <input type="hidden" name="var_id[]" value="<?php echo $v['id']; ?>">
                                <div class="field-group"><label>Tamaño</label><input type="text" name="var_tamano[]" value="<?php echo htmlspecialchars($v['tamano'] ?? ''); ?>" placeholder="Ej: Grande, Familiar"></div>
                                <div class="field-group"><label>Sabor</label><input type="text" name="var_sabor[]" value="<?php echo htmlspecialchars($v['sabor'] ?? ''); ?>" placeholder="Ej: Chocolate"></div>
                                <div class="field-group"><label>SKU</label><input type="text" name="var_sku[]" value="<?php echo htmlspecialchars($v['sku'] ?? ''); ?>" placeholder="Opcional"></div>
                                <div class="field-group"><label>Precio (Q) *</label><input type="number" name="var_precio[]" step="0.01" min="0" value="<?php echo $v['precio']; ?>" required></div>
                                <div class="field-group"><label>Stock</label><input type="number" name="var_stock[]" min="0" value="<?php echo $v['stock']; ?>"></div>
                                <div class="field-group"><label>Mínimo</label><input type="number" name="var_minimo[]" min="1" placeholder="Heredar" value="<?php echo !empty($v['minimo_compra']) ? (int)$v['minimo_compra'] : ''; ?>"></div>
                                <button type="button" class="btn-new btn-outline" style="padding:10px 15px; border-color:#e74c3c; color:#e74c3c; background:white;" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-new btn-outline" style="align-self:flex-start; padding: 8px 16px; font-size: 0.85rem;" onclick="addVariante()">+ Añadir Otra Variante</button>
                </div>
            </div>

            <!-- IMAGEN 1 -->
            <div class="field-group">
                <label><i class="fas fa-camera" style="color:var(--accent-toast);"></i> Imagen Principal</label>
                <div class="img-slot" onclick="this.querySelector('input').click()">
                    <input type="file" name="imagen" accept="image/*" onchange="previewSlot(this,'newPrev1','thumb1','ph1')">
                    <div class="img-current">
                        <?php if(imgSrc($p['imagen'])): ?>
                            <img src="<?php echo imgSrc($p['imagen']); ?>" class="img-thumb" id="thumb1">
                        <?php else: ?>
                            <div class="img-thumb-placeholder" id="thumb1"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                        <div class="slot-info" id="ph1">
                            <strong>Imagen actual</strong>
                            <span>Haz clic para cambiar</span>
                        </div>
                    </div>
                    <img id="newPrev1" class="new-preview">
                </div>
            </div>

            <!-- IMAGEN 2 -->
            <div class="field-group">
                <label><i class="fas fa-camera" style="color:#bbb;"></i> Imagen 2</label>
                <div class="img-slot" onclick="this.querySelector('input').click()">
                    <input type="file" name="imagen_2" accept="image/*" onchange="previewSlot(this,'newPrev2','thumb2','ph2')">
                    <div class="img-current">
                        <?php if(imgSrc($p['imagen_2'])): ?>
                            <img src="<?php echo imgSrc($p['imagen_2']); ?>" class="img-thumb" id="thumb2">
                        <?php else: ?>
                            <div class="img-thumb-placeholder" id="thumb2"><i class="fas fa-plus"></i></div>
                        <?php endif; ?>
                        <div class="slot-info" id="ph2">
                            <strong><?php echo $p['imagen_2'] ? 'Foto 2 actual' : 'Sin imagen'; ?></strong>
                            <span>Haz clic para <?php echo $p['imagen_2'] ? 'cambiar' : 'añadir'; ?></span>
                        </div>
                    </div>
                    <img id="newPrev2" class="new-preview">
                </div>
            </div>

            <!-- IMAGEN 3 -->
            <div class="field-group">
                <label><i class="fas fa-camera" style="color:#bbb;"></i> Imagen 3</label>
                <div class="img-slot" onclick="this.querySelector('input').click()">
                    <input type="file" name="imagen_3" accept="image/*" onchange="previewSlot(this,'newPrev3','thumb3','ph3')">
                    <div class="img-current">
                        <?php if(imgSrc($p['imagen_3'])): ?>
                            <img src="<?php echo imgSrc($p['imagen_3']); ?>" class="img-thumb" id="thumb3">
                        <?php else: ?>
                            <div class="img-thumb-placeholder" id="thumb3"><i class="fas fa-plus"></i></div>
                        <?php endif; ?>
                        <div class="slot-info" id="ph3">
                            <strong><?php echo $p['imagen_3'] ? 'Foto 3 actual' : 'Sin imagen'; ?></strong>
                            <span>Haz clic para <?php echo $p['imagen_3'] ? 'cambiar' : 'añadir'; ?></span>
                        </div>
                    </div>
                    <img id="newPrev3" class="new-preview">
                </div>
            </div>

            <!-- TOGGLES -->
            <div class="col-full" style="display:flex;flex-direction:column;gap:10px;">
                <div class="toggle-row">
                    <div class="toggle-label">
                        ⭐ Destacar en Inicio
                        <small>Aparecerá en la sección principal de la web.</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="destacado" value="1" <?php echo $p['destacado'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="toggle-row">
                    <div class="toggle-label">
                        🔥 Marcar como Oferta
                        <small>Mostrará una etiqueta de oferta en el catálogo.</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="oferta" value="1" <?php echo (!empty($p['oferta'])) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

        </div>
    </div>

    <div class="form-actions">
        <a href="productos.php" class="btn-new btn-outline" style="padding:12px 28px;">Cancelar</a>
        <button type="submit" class="btn-new" style="padding:12px 32px;">
            <i class="fas fa-save"></i> Guardar Cambios
        </button>
    </div>
    </form>
</div>

<script>
function previewSlot(input, newPrevId, thumbId, phId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    const newPrev = document.getElementById(newPrevId);
    const thumb   = document.getElementById(thumbId);
    const ph      = document.getElementById(phId);
    reader.onload = e => {
        newPrev.src = e.target.result;
        newPrev.style.display = 'block';
        if (thumb) thumb.style.opacity = '0.4';
        if (ph) { ph.querySelector('strong').innerText = '✓ Nueva imagen seleccionada'; ph.querySelector('span').innerText = file.name.slice(0,30); }
        input.parentElement.style.borderColor = 'var(--accent-toast)';
    };
    reader.readAsDataURL(file);
}

function toggleVariantes() {
    const isChecked = document.getElementById('tieneVariantes').checked;
    const container = document.getElementById('variantesContainer');
    const mainPrecioDiv = document.getElementById('mainPrecioDiv');
    const mainPrecioInput = document.getElementById('mainPrecio');
    const mainStockDiv = document.getElementById('mainStockDiv');
    const mainSkuDiv = document.getElementById('mainSkuDiv');

    if (isChecked) {
        container.style.display = 'flex';
        mainPrecioDiv.style.display = 'none';
        mainPrecioInput.removeAttribute('required');
        mainStockDiv.style.display = 'none';
        mainSkuDiv.style.display = 'none';
        if(document.querySelectorAll('.variante-row').length === 0) {
            addVariante();
        }
    } else {
        container.style.display = 'none';
        mainPrecioDiv.style.display = 'flex';
        mainPrecioInput.setAttribute('required', 'required');
        mainStockDiv.style.display = 'flex';
        mainSkuDiv.style.display = 'flex';
    }
}

function addVariante() {
    const list = document.getElementById('listaVariantes');
    const row = document.createElement('div');
    row.className = 'variante-row';
    row.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr 80px auto; gap:10px; align-items:end; background:white; padding:15px; border-radius:8px; border:1px solid #ddd;';
    
    row.innerHTML = `
        <input type="hidden" name="var_id[]" value="0">
        <div class="field-group"><label>Tamaño</label><input type="text" name="var_tamano[]" placeholder="Ej: Pequeño, Mediano, Grande"></div>
        <div class="field-group"><label>Sabor</label><input type="text" name="var_sabor[]" placeholder="Ej: Chocolate, Vainilla"></div>
        <div class="field-group"><label>SKU</label><input type="text" name="var_sku[]" placeholder="Opcional"></div>
        <div class="field-group"><label>Precio (Q) *</label><input type="number" name="var_precio[]" step="0.01" min="0" placeholder="0.00" required></div>
        <div class="field-group"><label>Stock</label><input type="number" name="var_stock[]" min="0" placeholder="0"></div>
        <div class="field-group"><label>Mínimo</label><input type="number" name="var_minimo[]" min="1" placeholder="1" value="1"></div>
        <button type="button" class="btn-new btn-outline" style="padding:10px 15px; border-color:#e74c3c; color:#e74c3c; background:white;" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
    `;
    list.appendChild(row);
}
</script>

<?php include 'includes/admin_footer.php'; ?>