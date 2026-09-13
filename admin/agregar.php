<?php
$currentPage = 'productos';
$pageTitle   = 'Nuevo Producto';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';
require_can('editar_productos'); // Solo admin
include 'includes/admin_nav.php';


$err = $_GET['err'] ?? '';
$errMsg = [
    'datos' => 'El nombre y precio son obligatorios.',
    'bd'    => 'Error al guardar en la base de datos. Intenta de nuevo.',
];
?>

<style>
.form-card {
    max-width: 860px; margin: 0 auto;
    background: white; border-radius: 24px;
    box-shadow: var(--shadow); overflow: hidden;
}
.form-header {
    background: var(--text-black);
    padding: 30px 40px; color: white;
    display: flex; align-items: center; gap: 15px;
}
.form-header i { color: var(--accent-toast); font-size: 1.5rem; }
.form-header h2 { font-family: 'Merriweather'; font-size: 1.3rem; margin-bottom: 3px; }
.form-header p  { color: #888; font-size: 0.85rem; }
.form-body { padding: 40px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
.col-full  { grid-column: 1 / -1; }

.field-group { display: flex; flex-direction: column; gap: 7px; }
.field-group label {
    font-weight: 700; font-size: 0.8rem;
    text-transform: uppercase; letter-spacing: 0.5px; color: #555;
}
.field-group input,
.field-group textarea,
.field-group select {
    padding: 12px 16px;
    border: 1.5px solid #eee; border-radius: 12px;
    font-family: 'Poppins'; font-size: 0.9rem; color: #111;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: white; width: 100%;
}
.field-group input:focus,
.field-group textarea:focus,
.field-group select:focus {
    outline: none; border-color: var(--accent-toast);
    box-shadow: 0 0 0 3px rgba(217,140,69,0.15);
}
.field-group textarea { resize: vertical; min-height: 100px; }

.price-wrap { position: relative; }
.price-wrap span {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%); font-weight: 700; color: var(--accent-toast);
}
.price-wrap input { padding-left: 32px; }

/* PREVIEWS DE IMÁGENES */
.img-upload-zone {
    border: 2px dashed #eee; border-radius: 14px;
    padding: 16px; text-align: center; cursor: pointer;
    transition: border-color 0.2s ease, background 0.2s ease;
    position: relative; overflow: hidden;
}
.img-upload-zone:hover { border-color: var(--accent-toast); background: #fdf8f2; }
.img-upload-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%;
}
.img-preview {
    width: 100%; height: 120px; object-fit: cover;
    border-radius: 10px; display: none; margin-top: 8px;
}
.img-upload-zone .upload-placeholder i { font-size: 1.8rem; color: #ddd; display: block; margin-bottom: 6px; }
.img-upload-zone .upload-placeholder span { font-size: 0.8rem; color: #bbb; }

/* TOGGLE SWITCHES */
.toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    background: #f9f9f9; border-radius: 12px; padding: 14px 18px;
}
.toggle-label { font-weight: 600; font-size: 0.9rem; }
.toggle-label small { display: block; color: #999; font-size: 0.75rem; font-weight: 400; }
.toggle-switch { position: relative; width: 46px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute; inset: 0; background: #ddd;
    border-radius: 50px; cursor: pointer;
    transition: background 0.2s ease;
}
.slider::before {
    content: ''; position: absolute;
    width: 18px; height: 18px; border-radius: 50%;
    background: white; left: 4px; top: 4px;
    transition: transform 0.2s ease;
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
.toggle-switch input:checked + .slider { background: var(--accent-toast); }
.toggle-switch input:checked + .slider::before { transform: translateX(20px); }

.form-actions {
    display: flex; justify-content: flex-end; gap: 12px;
    padding: 25px 40px; border-top: 1px solid #f0f0f0;
    background: #fdfdfd;
}

.alert-inline {
    background: #fff3cd; border: 1px solid #ffc107;
    border-radius: 12px; padding: 12px 18px;
    font-size: 0.9rem; color: #856404;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
</style>

<div class="page-header">
    <div class="page-title">
        <a href="productos.php" style="text-decoration:none;color:#888;font-size:0.85rem;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-arrow-left"></i> Volver al Inventario
        </a>
        <h1 style="margin-top:8px;">Nuevo Producto</h1>
    </div>
</div>

<div class="form-card">
    <div class="form-header">
        <i class="fas fa-bread-slice"></i>
        <div>
            <h2>Añadir Pan Artesanal</h2>
            <p>Completa los datos para publicar el producto en el catálogo.</p>
        </div>
    </div>

    <form action="procesar.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-body">

        <?php if($err && isset($errMsg[$err])): ?>
        <div class="alert-inline"><i class="fas fa-exclamation-triangle"></i> <?php echo $errMsg[$err]; ?></div>
        <?php endif; ?>

        <div class="form-grid">

            <div class="field-group col-full">
                <label>Nombre del Producto *</label>
                <input type="text" name="nombre" required placeholder="Ej. Croissant de Almendras con Masa Madre">
            </div>

            <div class="field-group col-full">
                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Descripción del producto, textura, presentación..."></textarea>
            </div>

            <div class="field-group col-full">
                <label>Perfecto para acompañar <span style="font-weight:400;color:#aaa;font-size:0.8rem;">(opcional)</span></label>
                <textarea name="maridaje" rows="3" placeholder="Ej: Ideal con café, vinos tintos o como base para sándwich gourmet..."></textarea>
            </div>

            <div class="field-group" id="mainSkuDiv">
                <label>SKU / Código (Opcional)</label>
                <input type="text" name="sku" placeholder="Ej: PQ05.1">
            </div>

            <div class="field-group" id="mainPrecioDiv">
                <label>Precio Base de Venta (Q) *</label>
                <div class="price-wrap">
                    <span>Q</span>
                    <input type="number" name="precio" id="mainPrecio" step="0.01" min="0" required placeholder="0.00">
                </div>
            </div>

            <div class="field-group">
                <label>Precio Distribuidor (Q) (Interno)</label>
                <div class="price-wrap">
                    <span>Q</span>
                    <input type="number" name="precio_distribuidor" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>

            <div class="field-group" id="mainStockDiv">
                <label>Stock Inicial</label>
                <input type="number" name="stock" min="0" value="0" placeholder="0">
            </div>

            <div class="field-group">
                <label>Mínimo de Compra *</label>
                <input type="number" name="minimo_compra" min="1" value="1" required>
            </div>

            <div class="field-group col-full">
                <label>Categoría</label>
                <select name="categoria">
                    <?php
                    $cats = getCategorias();
                    if(empty($cats)) echo '<option value="Sin Categoría">Sin Categoría (Crea una en Categorías)</option>';
                    foreach($cats as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['nombre']); ?>">
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
                        <input type="checkbox" id="tieneVariantes" onchange="toggleVariantes()">
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div id="variantesContainer" style="display:none; flex-direction:column; gap:15px;">
                    <div id="listaVariantes" style="display:flex; flex-direction:column; gap:10px;">
                        <!-- Las variantes se añadirán aquí dinámicamente -->
                    </div>
                    <button type="button" class="btn-new btn-outline" style="align-self:flex-start; padding: 8px 16px; font-size: 0.85rem;" onclick="addVariante()">+ Añadir Otra Variante</button>
                </div>
            </div>

            <!-- IMÁGENES con preview -->
            <div class="field-group">
                <label><i class="fas fa-camera" style="color:var(--accent-toast);"></i> Imagen Principal *</label>
                <div class="img-upload-zone" onclick="this.querySelector('input').click()">
                    <input type="file" name="imagen" accept="image/*" required onchange="previewImg(this,'prev1')">
                    <div class="upload-placeholder" id="ph1">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Haz clic o arrastra una imagen</span>
                    </div>
                    <img id="prev1" class="img-preview">
                </div>
            </div>

            <div class="field-group">
                <label><i class="fas fa-camera" style="color:#ccc;"></i> Imagen 2 (Opcional)</label>
                <div class="img-upload-zone" onclick="this.querySelector('input').click()">
                    <input type="file" name="imagen_2" accept="image/*" onchange="previewImg(this,'prev2')">
                    <div class="upload-placeholder" id="ph2">
                        <i class="fas fa-plus"></i>
                        <span>Foto adicional</span>
                    </div>
                    <img id="prev2" class="img-preview">
                </div>
            </div>

            <div class="field-group">
                <label><i class="fas fa-camera" style="color:#ccc;"></i> Imagen 3 (Opcional)</label>
                <div class="img-upload-zone" onclick="this.querySelector('input').click()">
                    <input type="file" name="imagen_3" accept="image/*" onchange="previewImg(this,'prev3')">
                    <div class="upload-placeholder" id="ph3">
                        <i class="fas fa-plus"></i>
                        <span>Foto adicional</span>
                    </div>
                    <img id="prev3" class="img-preview">
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
                        <input type="checkbox" name="destacado" value="1" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="toggle-row">
                    <div class="toggle-label">
                        🔥 Marcar como Oferta
                        <small>Mostrará una etiqueta de oferta en el catálogo.</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="oferta" value="1">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

        </div>
    </div>

    <div class="form-actions">
        <a href="productos.php" class="btn-new btn-outline" style="padding:12px 28px;">Cancelar</a>
        <button type="submit" class="btn-new" style="padding:12px 32px;">
            <i class="fas fa-save"></i> Guardar Producto
        </button>
    </div>
    </form>
</div>

<script>
function previewImg(input, previewId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    const preview = document.getElementById(previewId);
    // Identificar placeholder hermano
    const ph = input.parentElement.querySelector('.upload-placeholder');
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (ph) ph.style.display = 'none';
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
        <div class="field-group"><label>Tamaño</label><input type="text" name="var_tamano[]" placeholder="Ej: Pequeño, Mediano, Grande"></div>
        <div class="field-group"><label>Sabor</label><input type="text" name="var_sabor[]" placeholder="Ej: Chocolate, Vainilla"></div>
        <div class="field-group"><label>SKU</label><input type="text" name="var_sku[]" placeholder="Opcional"></div>
        <div class="field-group"><label>Precio (Q) *</label><input type="number" name="var_precio[]" step="0.01" min="0" placeholder="0.00" required></div>
        <div class="field-group"><label>Stock</label><input type="number" name="var_stock[]" min="0" placeholder="0"></div>
        <div class="field-group"><label>Mínimo</label><input type="number" name="var_minimo[]" min="1" placeholder="Heredar"></div>
        <button type="button" class="btn-new btn-outline" style="padding:10px 15px; border-color:#e74c3c; color:#e74c3c; background:white;" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
    `;
    list.appendChild(row);
}
</script>

<?php include 'includes/admin_footer.php'; ?>