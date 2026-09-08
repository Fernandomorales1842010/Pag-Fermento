<?php 
    $pageTitle = "Catálogo Completo | Fermento";
    require 'includes/db.php';
    require 'includes/config.php';
    include 'includes/header.php'; 
    include 'includes/nav.php'; 
?>

<div class="hero" style="height: 300px; min-height: 300px;">
    <div class="hero-content">
        <h1 style="font-size: 2.5rem;">Nuestro Catálogo</h1>
        <p>Explora toda nuestra variedad de panes artesanales</p>
    </div>
</div>

<section class="section container" style="background: var(--bg-cream); margin-top: -50px; border-radius: 20px; position: relative; z-index: 2;">
    
    <div class="catalog-controls">
        
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Buscar por nombre (ej: Croissant)..." onkeyup="buscarTiempoReal()">
            <button type="button" id="btnClearSearch" onclick="limpiarBusqueda()" style="display:none; position:absolute; right:15px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#999;"><i class="fas fa-times-circle"></i></button>
        </div>

        <div class="filter-group">
            <button class="filter-btn active" onclick="cambiarCategoria('todas', this)">Todos</button>
            <?php 
            $categorias = getCategorias();
            foreach($categorias as $cat): 
            ?>
            <button class="filter-btn" onclick="cambiarCategoria('<?php echo htmlspecialchars($cat['nombre']); ?>', this)">
                <?php echo htmlspecialchars($cat['nombre']); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; font-size:0.9rem; color:#666; flex-wrap:wrap; gap:10px;">
        <span id="resultadosContador">Mostrando resultados...</span>
        <div style="display:flex; align-items:center; gap:10px;">
            <label style="font-weight:600;">Mostrar:</label>
            <select id="limiteSelect" onchange="cambiarLimite(this.value)" style="padding:6px 12px; border-radius:8px; border:1px solid #ddd; font-family:'Poppins'; outline:none;">
                <option value="8">8 por página</option>
                <option value="16">16 por página</option>
                <option value="24">24 por página</option>
            </select>
        </div>
    </div>

    <div id="gridProductos" class="catalog-grid">
        <p style="text-align:center; grid-column: 1/-1;">Cargando productos...</p>
    </div>

    <div id="paginacionContainer" class="pagination-container">
        </div>

</section>

<script>
    // Variables de estado
    let estado = {
        busqueda: '',
        categoria: 'todas',
        pagina: 1,
        limite: 8
    };

    let timeoutBusqueda = null;

    // 1. Cargar inicial
    document.addEventListener("DOMContentLoaded", () => {
        filtrar(1);
    });

    // 2. Función principal AJAX
    function filtrar(pag = 1) {
        estado.pagina = pag;
        const grid = document.getElementById('gridProductos');
        
        fetch('ajax/catalogo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(estado)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                grid.innerHTML = data.html;
                document.getElementById('paginacionContainer').innerHTML = data.paginacion;
                document.getElementById('resultadosContador').innerHTML = `<b>${data.total}</b> resultados encontrados`;
            } else {
                grid.innerHTML = "<p style='text-align:center; grid-column:1/-1;'>Error al cargar productos.</p>";
            }
        })
        .catch(err => {
            console.error(err);
            grid.innerHTML = "<p style='text-align:center; grid-column:1/-1;'>Ocurrió un error en la conexión.</p>";
        });
    }

    // 3. Cambiar Categoría
    function cambiarCategoria(cat, btn) {
        estado.categoria = cat;
        estado.pagina = 1;
        
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        filtrar(1);
    }

    // 4. Buscador en Tiempo Real
    function buscarTiempoReal() {
        const val = document.getElementById('searchInput').value;
        const btnClear = document.getElementById('btnClearSearch');
        btnClear.style.display = val.length > 0 ? 'block' : 'none';
        
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
            estado.busqueda = val;
            estado.pagina = 1;
            filtrar(1);
        }, 300);
    }

    // 5. Limpiar Búsqueda
    function limpiarBusqueda() {
        const input = document.getElementById('searchInput');
        input.value = '';
        document.getElementById('btnClearSearch').style.display = 'none';
        estado.busqueda = '';
        estado.pagina = 1;
        filtrar(1);
    }

    // 6. Cambiar Limite por página
    function cambiarLimite(lim) {
        estado.limite = parseInt(lim);
        estado.pagina = 1;
        filtrar(1);
    }
</script>

<?php include 'includes/footer.php'; ?>