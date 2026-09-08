<?php
// admin/index.php
$currentPage = 'dashboard';
$pageTitle = 'Dashboard Inteligente';
require '../includes/db.php';
include 'includes/admin_header.php';
include 'includes/admin_nav.php';

// KPIs Rápidos vía PHP (No filtrables para ver inventario total)
$agotados = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT id FROM productos WHERE stock <= 0
        UNION ALL
        SELECT id FROM producto_variantes WHERE stock <= 0
    ) as t
")->fetchColumn();

$pendingO = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Centro de Operaciones <span class="badge" style="background:#e8f5e9; color:#27ae60; font-size:0.6rem; vertical-align:middle; margin-left:10px;"><i class="fas fa-circle" style="font-size:0.4rem;"></i> EN VIVO</span></h1>
        <p>Analizando rendimiento en tiempo real. Próxima actualización en: <span id="next-refresh">60</span>s</p>
    </div>
</div>

<style>
/* --- BARRA DE FILTROS --- */
.filter-bar {
    background: var(--text-black);
    border-radius: 16px;
    padding: 18px 25px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    box-shadow: var(--shadow);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-label {
    color: #aaa;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.date-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.date-text-input {
    background: #2a2a2a;
    border: 1px solid #444;
    color: white;
    padding: 9px 40px 9px 15px;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    width: 140px;
    cursor: text;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.date-text-input:focus {
    outline: none;
    border-color: var(--accent-toast);
    box-shadow: 0 0 0 3px rgba(217,140,69,0.25);
}

.date-cal-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: var(--accent-toast);
    cursor: pointer;
    font-size: 0.9rem;
    padding: 0;
    line-height: 1;
    transition: transform 0.2s ease;
}

.date-cal-btn:hover { transform: scale(1.2); }

/* Input date real - oculto, solo sirve para el picker nativo */
.date-native-hidden {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}

.filter-separator { color: #555; font-size: 0.9rem; }

/* ATAJOS RÁPIDOS */
.shortcut-btn {
    background: #2a2a2a;
    color: #aaa;
    border: 1px solid #444;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.shortcut-btn:hover, .shortcut-btn.active {
    background: var(--accent-toast);
    color: white;
    border-color: var(--accent-toast);
}
</style>

<div class="filter-bar">
    <div class="filter-group">
        <span class="filter-label"><i class="fas fa-calendar-alt" style="color: var(--accent-toast);"></i> Periodo:</span>

        <!-- DESDE -->
        <div class="date-input-wrapper">
            <span class="filter-label">Desde:</span>
            <input type="text" id="fecha_desde_text" class="date-text-input" readonly 
                   value="<?php echo date('d/m/Y', strtotime('-30 days')); ?>"
                   oninput="syncFromText('desde')">
            <button class="date-cal-btn" onclick="openPicker('desde')" title="Abrir calendario"><i class="fas fa-calendar"></i></button>
            <input type="date" id="fecha_desde" class="date-native-hidden" 
                   value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>"
                   onchange="syncFromPicker('desde')">
        </div>

        <div class="date-input-wrapper">
            <span class="filter-label">Hasta:</span>
            <input type="text" id="fecha_hasta_text" class="date-text-input" readonly 
                   value="<?php echo date('d/m/Y'); ?>"
                   oninput="syncFromText('hasta')">
            <button class="date-cal-btn" onclick="openPicker('hasta')" title="Abrir calendario"><i class="fas fa-calendar"></i></button>
            <input type="date" id="fecha_hasta" class="date-native-hidden"
                   value="<?php echo date('Y-m-d'); ?>"
                   onchange="syncFromPicker('hasta')">
        </div>

        <!-- FILTROS DE COSTO -->
        <div class="date-input-wrapper" style="margin-left: 10px;">
            <span class="filter-label" style="color:#d98c45;"><i class="fas fa-money-bill-wave"></i> Q:</span>
            <input type="number" id="min_costo" class="date-text-input" style="width:90px; margin-right:5px; padding-left:10px;" placeholder="Mín">
            <span style="color:#666;">-</span>
            <input type="number" id="max_costo" class="date-text-input" style="width:90px; margin-left:5px; padding-left:10px;" placeholder="Máx">
        </div>

        <button onclick="updateStats()" class="btn-new" style="padding: 9px 18px;" id="applyBtn">
            <i class="fas fa-sync" id="applyIcon"></i> Aplicar
        </button>
    </div> 

    <div class="filter-group">
        <span class="filter-label">Accesos rápidos:</span>
        <button class="shortcut-btn" onclick="setShortcut(1)">Hoy</button>
        <button class="shortcut-btn" onclick="setShortcut(7)">7 días</button>
        <button class="shortcut-btn active" onclick="setShortcut(30)">30 días</button>
        <button class="shortcut-btn" onclick="setShortcut(90)">3 meses</button>
        <a href="agregar.php" class="btn-new" style="padding: 9px 18px; background: white; color: var(--text-black); margin-left: 10px;">
            <i class="fas fa-plus"></i> Nuevo Pan
        </a>
    </div>
</div>

<style>
/* Hacer que los KPIs sean clickeables */
.kpi-card { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
.kpi-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
</style>

<div class="kpi-grid">
    <div class="kpi-card" onclick="openDrilldown('ventas_diarias', 'Periodo Completo')">
        <div class="kpi-icon"><i class="fas fa-coins"></i></div>
        <div class="kpi-info" style="gap:0;">
            <h3>Ventas en Rango</h3>
            <p id="kpiVentas">Q0.00</p>
        </div>
    </div>
    <div class="kpi-card" onclick="openDrilldown('ventas_diarias', 'Periodo Completo')">
        <div class="kpi-icon" style="color:#2ecc71; background: #eafaf1;"><i class="fas fa-shopping-cart"></i></div>
        <div class="kpi-info" style="gap:0;">
            <h3>Pedidos en Rango</h3>
            <p id="kpiPedidos">0</p>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="color:#9b59b6; background: #f5eef8;"><i class="fas fa-receipt"></i></div>
        <div class="kpi-info" style="gap:0;">
            <h3>Ticket Promedio</h3>
            <p id="kpiTicket">Q0.00</p>
        </div>
    </div>
    <div class="kpi-card" style="<?php echo $agotados > 0 ? 'background:#ffebee;' : ''; ?>">
        <div class="kpi-icon" style="color: #e74c3c; background: #fadbd8;"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="kpi-info" style="gap:0;">
            <h3>Productos Agotados</h3>
            <p style="<?php echo $agotados > 0 ? 'color:#c0392b;' : ''; ?>"><?php echo $agotados; ?></p>
        </div>
    </div>
    <div class="kpi-card" onclick="openDrilldown('pedidos_pendientes', '')" style="<?php echo $pendingO > 0 ? 'background:#fff8e1;' : ''; ?>">
        <div class="kpi-icon" style="color: #f39c12; background: #fef5e7;"><i class="fas fa-clock"></i></div>
        <div class="kpi-info" style="gap:0;">
            <h3>Pedidos Pendientes</h3>
            <p style="<?php echo $pendingO > 0 ? 'color:#d35400;' : ''; ?>"><?php echo $pendingO; ?></p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    
    <div class="card">
        <h2 style="font-family: 'Merriweather'; font-size: 1.1rem; margin-bottom: 20px;">Tendencia de Ventas (Diario)</h2>
        <canvas id="chartVentasLine" height="150"></canvas>
    </div>

    <div class="card">
        <h2 style="font-family: 'Merriweather'; font-size: 1.1rem; margin-bottom: 20px;">Categorías más Vendidas</h2>
        <canvas id="chartCatPie" height="250"></canvas>
    </div>

</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
    
    <div class="card">
        <h2 style="font-family: 'Merriweather'; font-size: 1.1rem; margin-bottom: 20px;">Top Panes Populares del Periodo</h2>
        <canvas id="chartTopBar"></canvas>
    </div>

    <div class="card">
        <h2 style="font-family: 'Merriweather'; font-size: 1.1rem; margin-bottom: 20px;">Alertas de Stock (Mínimos)</h2>
        <canvas id="chartStockAlert"></canvas>
    </div>

</div>

<!-- ====== MODAL PREMIUM ====== -->
<style>
@keyframes modalIn {
    from { opacity: 0; transform: translate(-50%, -50%) scale(0.85); }
    to   { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}
@keyframes overlayFade {
    from { opacity: 0; backdrop-filter: blur(0px); }
    to   { opacity: 1; backdrop-filter: blur(6px); }
}

.detail-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: 3000;
    display: none;
    animation: overlayFade 0.3s ease forwards;
}

.detail-panel {
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.85);
    width: min(92vw, 800px);
    max-height: 90vh;
    background: var(--white);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.35);
    display: flex;
    flex-direction: column;
    animation: modalIn 0.4s cubic-bezier(0.34, 1.26, 0.64, 1) forwards;
    z-index: 3001;
}

.detail-header {
    padding: 28px 30px 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid #f0f0f0;
}

.detail-header-left h2 {
    font-family: 'Merriweather', serif;
    font-size: 1.3rem;
    margin-bottom: 4px;
}

.detail-header-left p {
    font-size: 0.8rem;
    color: #999;
}

.detail-close {
    background: #f4f4f4;
    border: none;
    width: 36px; height: 36px;
    border-radius: 50%;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s ease, transform 0.2s ease;
    flex-shrink: 0;
}
.detail-close:hover { background: #e74c3c; color: white; transform: rotate(90deg); }

.detail-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    overflow-y: auto;
    flex: 1;
}

.detail-chart-panel {
    padding: 25px;
    background: var(--bg-cream);
    display: flex;
    flex-direction: column;
    justify-content: center;
    border-right: 1px solid #f0f0f0;
}

.detail-chart-panel h4 {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #999;
    margin-bottom: 15px;
}

.detail-stat-box {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.detail-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.detail-stat-info small { color: #999; font-size: 0.7rem; display: block; }
.detail-stat-info strong { font-size: 1.2rem; }

.detail-table-panel {
    padding: 25px;
    overflow-y: auto;
}

.detail-table-panel h4 {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #999;
    margin-bottom: 15px;
}

.detail-rows table { width: 100%; border-collapse: collapse; }
.detail-rows th {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #bbb;
    padding: 8px 10px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}
.detail-rows td {
    padding: 10px;
    font-size: 0.85rem;
    border-bottom: 1px solid #f8f8f8;
    vertical-align: middle;
}
.detail-rows tr:hover td { background: #fdf8f2; }

/* Etiqueta de rank */
.rank-chip {
    background: var(--bg-cream);
    color: var(--accent-toast);
    font-weight: 700;
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 20px;
    display: inline-block;
}

@media (max-width: 600px) {
    .detail-body { grid-template-columns: 1fr; }
    .detail-chart-panel { border-right: none; border-bottom: 1px solid #f0f0f0; }
}
</style>

<div class="detail-overlay" id="detailOverlay" onclick="closeModal()"></div>
<div class="detail-panel" id="detailPanel" style="display:none;">
    <div class="detail-header">
        <div class="detail-header-left">
            <h2 id="dpTitle">Detalle</h2>
            <p id="dpSubtitle">Cargando información...</p>
        </div>
        <button class="detail-close" onclick="closeModal()">✕</button>
    </div>
    <div class="detail-body">
        <div class="detail-chart-panel" id="dpLeft"></div>
        <div class="detail-table-panel" id="dpRight"><div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#ddd;"></i></div></div>
    </div>
</div>

<script>
    let charts = {};
    let refreshTimer = 60;
    const REFRESH_INTERVAL = 60;

    // Función para animar números (Efecto contador)
    function animateValue(id, value, isCurrency = false) {
        const obj = document.getElementById(id);
        const current = parseFloat(obj.innerText.replace(/[Q,]/g, '')) || 0;
        const target = parseFloat(value);
        const duration = 1000;
        let startTimestamp = null;

        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const currentVal = (progress * (target - current)) + current;
            
            if (isCurrency) {
                obj.innerHTML = `<span style="opacity:0.3; font-size: 0.8em;">Q</span>${currentVal.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            } else {
                obj.innerText = Math.floor(currentVal);
            }

            if (progress < 1) window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
    }

    // ─── FUNCIONES DE FILTRO DE FECHAS ──────────────────────────────
    // Convierte YYYY-MM-DD a DD/MM/YYYY para mostrar en el campo de texto
    function toDisplay(isoDate) {
        const [y, m, d] = isoDate.split('-');
        return `${d}/${m}/${y}`;
    }

    // Convierte DD/MM/AAAA a YYYY-MM-DD para el input oculto
    function toISO(display) {
        const parts = display.split('/');
        if (parts.length !== 3) return null;
        const [d, m, y] = parts;
        if (d.length !== 2 || m.length !== 2 || y.length !== 4) return null;
        return `${y}-${m}-${d}`;
    }

    // Abre el date-picker nativo del navegador
    function openPicker(field) {
        const input = document.getElementById(`fecha_${field}`);
        // Temporalmente lo hacemos visible y lo triggereamos
        input.style.position = 'fixed';
        input.style.opacity = '0.01';
        input.style.width = '1px';
        input.style.height = '1px';
        input.style.pointerEvents = 'auto';
        input.style.top = '0';
        input.style.left = '0';
        input.showPicker ? input.showPicker() : input.click();
        setTimeout(() => {
            input.style.position = 'absolute';
            input.style.opacity = '0';
            input.style.width = '0';
            input.style.height = '0';
            input.style.pointerEvents = 'none';
        }, 200);
    }

    // Cuando el usuario escribe en el text-input → sincroniza al hidden date
    function syncFromText(field) {
        const textInput = document.getElementById(`fecha_${field}_text`);
        const hiddenInput = document.getElementById(`fecha_${field}`);
        const iso = toISO(textInput.value);
        if (iso) {
            hiddenInput.value = iso;
            textInput.style.borderColor = '#444'; // válido
        } else {
            textInput.style.borderColor = textInput.value.length > 0 ? '#e74c3c' : '#444';
        }
    }

    // Cuando el picker nativo cambia → sincroniza al campo de texto visible
    function syncFromPicker(field) {
        const hiddenInput = document.getElementById(`fecha_${field}`);
        const textInput = document.getElementById(`fecha_${field}_text`);
        if (hiddenInput.value) {
            textInput.value = toDisplay(hiddenInput.value);
            textInput.style.borderColor = '#444';
        }
    }

    // Atajos rápidos: Hoy, 7 días, 30 días, 3 meses
    function setShortcut(days) {
        const today = new Date();
        const from = new Date();
        from.setDate(today.getDate() - (days - 1));

        const fmt = (d) => d.toISOString().split('T')[0];
        const fmtD = (d) => toDisplay(fmt(d));

        document.getElementById('fecha_desde').value = fmt(from);
        document.getElementById('fecha_hasta').value = fmt(today);
        document.getElementById('fecha_desde_text').value = fmtD(from);
        document.getElementById('fecha_hasta_text').value = fmtD(today);

        // Marcar botón activo
        document.querySelectorAll('.shortcut-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');

        updateStats();
    }
    // ─────────────────────────────────────────────────────────────────

    // Temporizador Real-Time
    setInterval(() => {
        refreshTimer--;
        if (refreshTimer <= 0) {
            updateStats();
            refreshTimer = REFRESH_INTERVAL;
        }
        document.getElementById('next-refresh').innerText = refreshTimer;
    }, 1000);

    function updateStats() {
        const desde = document.getElementById('fecha_desde').value;
        const hasta = document.getElementById('fecha_hasta').value;
        const minCosto = document.getElementById('min_costo').value;
        const maxCosto = document.getElementById('max_costo').value;
        
        let url = `api_stats.php?desde=${desde}&hasta=${hasta}`;
        if (minCosto !== '') url += `&min_costo=${minCosto}`;
        if (maxCosto !== '') url += `&max_costo=${maxCosto}`;

        fetch(url)
        .then(res => res.json())
        .then(data => {
            animateValue('kpiVentas', data.kpi_ventas, true);
            animateValue('kpiPedidos', data.kpi_pedidos, false);
            animateValue('kpiTicket', data.kpi_ticket ?? 0, true);

            // --- GRÁFICA DE VENTAS LINEA ---
            renderChart('chartVentasLine', 'line', {
                labels: data.ventas_diarias.map(x => x.dia),
                datasets: [{ 
                    label: 'Ventas (Q)', 
                    data: data.ventas_diarias.map(x => x.total), 
                    borderColor: '#6C63FF', 
                    backgroundColor: 'rgba(108, 99, 255, 0.15)', 
                    fill: true, 
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#6C63FF',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8
                }]
            }, {
                onClick: (e, activeEls) => handleChartClick(e, activeEls, 'ventas_diarias', data.ventas_diarias.map(x => x.dia))
            });

            // --- GRÁFICA CATEGORÍAS PIE ---
            renderChart('chartCatPie', 'pie', {
                labels: data.categorias.map(x => x.categoria),
                datasets: [{ 
                    data: data.categorias.map(x => x.cantidad), 
                    backgroundColor: ['#FF6B6B', '#FFD93D', '#6BCB77', '#4D96FF', '#C77DFF', '#F4A261'],
                    hoverOffset: 10
                }]
            }, {
                onClick: (e, activeEls) => handleChartClick(e, activeEls, 'categoria', data.categorias.map(x => x.categoria))
            });

            // --- GRÁFICA TOP BAR ---
            const topColors = ['#6C63FF','#FF6B6B','#FFD93D','#6BCB77','#4D96FF','#F4A261','#C77DFF','#FF9F45','#48CAE4','#2EC4B6'];
            renderChart('chartTopBar', 'bar', {
                labels: data.top_productos.map(x => x.nombre_producto),
                datasets: [{ 
                    label: 'Vendidos', 
                    data: data.top_productos.map(x => x.cantidad), 
                    backgroundColor: data.top_productos.map((_, i) => topColors[i % topColors.length]),
                    borderRadius: 10
                }]
            }, { 
                indexAxis: 'y',
                onClick: (e, activeEls) => handleChartClick(e, activeEls, 'top_producto', data.top_productos.map(x => x.nombre_producto))
            });

            // --- GRÁFICA STOCK ALERT ---
            renderChart('chartStockAlert', 'bar', {
                labels: data.stock.map(x => x.nombre),
                datasets: [{ 
                    label: 'Unidades', 
                    data: data.stock.map(x => x.stock), 
                    backgroundColor: data.stock.map(x => x.stock < 5 ? '#FF6B6B' : x.stock < 20 ? '#FFD93D' : '#6BCB77'),
                    borderRadius: 6
                }]
            }, {
                onClick: (e, activeEls) => handleChartClick(e, activeEls, 'stock', data.stock.map(x => x.nombre))
            });
        });
    }

    function renderChart(id, type, data, options = {}) {
        if (charts[id]) charts[id].destroy(); 
        
        const config = {
            type: type,
            data: data,
            options: {
                responsive: true,
                animation: {
                    duration: 1200,
                    easing: 'easeOutBack',
                    delay: (context) => {
                        let delay = 0;
                        if (context.type === 'data' && context.mode === 'default') {
                            delay = context.dataIndex * 150 + context.datasetIndex * 100;
                        }
                        return delay;
                    }
                },
                plugins: { legend: { position: 'bottom' } },
                ...options
            }
        };
        const ctx = document.getElementById(id).getContext('2d');
        charts[id] = new Chart(ctx, config);
    }

    function handleChartClick(evt, activeElements, tipo, labels) {
        if (activeElements.length > 0) {
            const index = activeElements[0].index;
            abrirModalDetalle(tipo, labels[index]);
        }
    }

    // Alias para KPIs
    function openDrilldown(tipo, valor) {
        abrirModalDetalle(tipo, valor);
    }

    // Configs visuales por tipo
    const TIPO_CONFIG = {
        top_producto: {
            icon: 'fas fa-bread-slice',
            color: '#D98C45',
            bg: '#fdf5e8',
            title: (v) => `🏆 ${v}`,
            subtitle: 'Pedidos que incluyeron este producto',
            leftLabel: 'Resumen del Producto',
            chartType: 'bar',
            buildStats: (data) => [
                { icon: 'fas fa-shopping-cart', bg:'#eaf6ff', color:'#3498db',  label:'Pedidos totales',   value: data.length },
                { icon: 'fas fa-boxes',         bg:'#fff8e1', color:'#f39c12',  label:'Unidades vendidas', value: data.reduce((s,r)=>s+parseInt(r[Object.keys(r)[3]]||0),0) },
            ],
            chartData: (data) => ({
                labels: data.slice(0,6).map(r => r[Object.keys(r)[1]]),
                datasets: [{ label:'Cant.', data: data.slice(0,6).map(r => r[Object.keys(r)[3]]), backgroundColor:['#6C63FF','#FF6B6B','#FFD93D','#6BCB77','#4D96FF','#F4A261'], borderRadius:6 }]
            }),
        },
        stock: {
            icon: 'fas fa-box-open',
            color: '#3498db',
            bg: '#ebf5fb',
            title: (v) => `📦 ${v}`,
            subtitle: 'Información actual del inventario',
            leftLabel: 'Estado del Producto',
            chartType: null,
            buildStats: (data) => [
                { icon: 'fas fa-tag',    bg:'#fdf5e8', color:'#D98C45', label:'Precio unitario', value: data[0] ? 'Q'+parseFloat(data[0][Object.keys(data[0])[1]]).toFixed(2) : '-' },
                { icon: 'fas fa-th-large', bg:'#eaf6ff', color:'#3498db', label:'Categoría',       value: data[0] ? data[0][Object.keys(data[0])[2]] : '-' },
            ],
        },
        categoria: {
            icon: 'fas fa-layer-group',
            color: '#27ae60',
            bg: '#eafaf1',
            title: (v) => `📂 Categoría: ${v}`,
            subtitle: 'Ventas desglosadas por producto en esta categoría',
            leftLabel: 'Resumen de Categoría',
            chartType: 'doughnut',
            buildStats: (data) => [
                { icon: 'fas fa-shopping-bag', bg:'#eafaf1', color:'#27ae60', label:'Productos vendidos', value: data.length },
                { icon: 'fas fa-coins',        bg:'#fdf5e8', color:'#D98C45', label:'Total recaudado',    value: 'Q'+data.reduce((s,r)=>s+parseFloat(r[Object.keys(r)[2]]||0),0).toFixed(2) },
            ],
            chartData: (data) => ({
                labels: data.map(r => r[Object.keys(r)[0]]),
                datasets: [{ data: data.map(r => r[Object.keys(r)[1]]), backgroundColor:['#FF6B6B','#FFD93D','#6BCB77','#4D96FF','#C77DFF','#F4A261'], borderWidth:0 }]
            }),
        },
        ventas_diarias: {
            icon: 'fas fa-coins',
            color: '#D98C45',
            bg: '#fdf5e8',
            title: (v) => `📅 Ventas del día: ${v}`,
            subtitle: 'Detalle de transacciones en esta fecha',
            leftLabel: 'Resumen Diario',
            chartType: null,
            buildStats: (data) => [
                { icon: 'fas fa-shopping-cart', bg:'#eafaf1', color:'#27ae60', label:'Total de pedidos', value: data.length },
                { icon: 'fas fa-money-bill',    bg:'#fdf5e8', color:'#D98C45', label:'Ingresos diarios', value: 'Q'+data.reduce((s,r)=>s+parseFloat(r[Object.keys(r)[3]]||0),0).toFixed(2) },
            ],
        },
        pedidos_pendientes: {
            icon: 'fas fa-clock',
            color: '#e67e22',
            bg: '#fdf5e6',
            title: (v) => `⏳ Pedidos Pendientes`,
            subtitle: 'Órdenes que requieren atención inmediata',
            leftLabel: 'Estado Actual',
            chartType: null,
            buildStats: (data) => [
                { icon: 'fas fa-exclamation-triangle', bg:'#ffebee', color:'#e74c3c', label:'Por procesar', value: data.length },
                { icon: 'fas fa-coins',                bg:'#fdf5e8', color:'#D98C45', label:'Valor retenido', value: 'Q'+data.reduce((s,r)=>s+parseFloat(r[Object.keys(r)[2]]||0),0).toFixed(2) },
            ],
        }
    };

    let detailMiniChart = null;

    function abrirModalDetalle(tipo, valor) {
        const desde = document.getElementById('fecha_desde').value;
        const hasta = document.getElementById('fecha_hasta').value;
        const min_costo = document.getElementById('min_costo').value;
        const max_costo = document.getElementById('max_costo').value;
        
        const cfg = TIPO_CONFIG[tipo];
        if (!cfg) return;

        // Mostrar overlay + panel con loading
        document.getElementById('detailOverlay').style.display = 'block';
        const panel = document.getElementById('detailPanel');
        panel.style.display = 'flex';
        panel.style.animation = 'none';
        setTimeout(() => panel.style.animation = '', 10);

        document.getElementById('dpTitle').innerText = cfg.title(valor);
        document.getElementById('dpSubtitle').innerText = cfg.subtitle;
        document.getElementById('dpRight').innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#ddd;"></i></div>';
        document.getElementById('dpLeft').innerHTML = '<div style="color:#bbb;text-align:center;">Cargando...</div>';

        fetch('api_detalle_stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo, valor, desde, hasta, min_costo, max_costo })
        })
        .then(res => res.json())
        .then(data => {
            // ── PANEL IZQUIERDO: Stats + Mini-gráfica ──
            const stats = cfg.buildStats(data);
            let leftHTML = `<h4>${cfg.leftLabel}</h4>`;
            stats.forEach(s => {
                leftHTML += `
                <div class="detail-stat-box">
                    <div class="detail-stat-icon" style="background:${s.bg}; color:${s.color};"><i class="${s.icon}"></i></div>
                    <div class="detail-stat-info">
                        <small>${s.label}</small>
                        <strong>${s.value}</strong>
                    </div>
                </div>`;
            });

            if (cfg.chartType && data.length > 0) {
                leftHTML += `<canvas id="detailMiniCanvas" height="160" style="margin-top:15px;"></canvas>`;
            }
            document.getElementById('dpLeft').innerHTML = leftHTML;

            // Dibujar mini-gráfica
            if (cfg.chartType && cfg.chartData && data.length > 0) {
                if (detailMiniChart) detailMiniChart.destroy();
                detailMiniChart = new Chart(
                    document.getElementById('detailMiniCanvas').getContext('2d'),
                    {
                        type: cfg.chartType,
                        data: cfg.chartData(data),
                        options: {
                            responsive: true,
                            animation: { duration: 900, easing: 'easeOutBack' },
                            plugins: { legend: { display: cfg.chartType === 'doughnut', position: 'bottom', labels: { font: { size: 10 } } } },
                            scales: cfg.chartType === 'bar' ? { y: { beginAtZero: true } } : undefined
                        }
                    }
                );
            }

            // ── PANEL DERECHO: Tabla de datos ──
            if (!data || data.length === 0) {
                document.getElementById('dpRight').innerHTML = '<div style="text-align:center;padding:60px;color:#ccc;"><i class="fas fa-inbox" style="font-size:2rem;"></i><p style="margin-top:10px;">Sin datos en este periodo</p></div>';
                return;
            }

            const keys = Object.keys(data[0]);
            const headers = {
                top_producto: ['#', 'Cliente', 'Fecha', 'Unidades'],
                stock:        ['Producto', 'Precio', 'Categoría'],
                categoria:    ['Producto', 'Vendidos', 'Total (Q)'],
            };
            const cols = headers[tipo] || keys;

            let tableHTML = `<h4>Registro detallado (${data.length} ítems)</h4><div class="detail-rows"><table><thead><tr>`;
            cols.forEach(c => tableHTML += `<th>${c}</th>`);
            tableHTML += '</tr></thead><tbody>';

            data.forEach((row, i) => {
                tableHTML += '<tr>';
                if (tipo === 'top_producto') {
                    tableHTML += `<td data-label="${cols[0]}"><span class="rank-chip">${i+1}</span></td>`;
                }
                const vals = Object.values(row);
                const start = tipo === 'top_producto' ? 1 : 0;
                vals.slice(start).forEach((v, idx) => {
                    const colIndex = tipo === 'top_producto' ? idx + 1 : idx;
                    tableHTML += `<td data-label="${cols[colIndex]}">${v ?? '-'}</td>`;
                });
                tableHTML += '</tr>';
            });

            tableHTML += '</tbody></table></div>';
            document.getElementById('dpRight').innerHTML = tableHTML;
        });
    }

    function closeModal() {
        document.getElementById('detailOverlay').style.display = 'none';
        document.getElementById('detailPanel').style.display = 'none';
    }
    window.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // Init al cargar
    updateStats();
</script>

<?php include 'includes/admin_footer.php'; ?>