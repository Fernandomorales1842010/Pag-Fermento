/**
 * hoja_produccion.js
 * Genera la Hoja de Producción FERMENTO en una ventana limpia, tamaño Carta.
 *
 * Requiere window.FERMENTO_LOGO (data:image/png;base64,... inyectado por PHP)
 *
 * Cada pedido en el array:
 * { id, fecha, impreso, items:[{nombre,cantidad,fecha,hora}], total_uds }
 */
function imprimirHoja(pedidos) {
    if (!pedidos || pedidos.length === 0) return;

    const logo = (typeof window.FERMENTO_LOGO !== 'undefined' && window.FERMENTO_LOGO)
        ? `<img src="${window.FERMENTO_LOGO}" alt="Logo Fermento"
               style="height:56px;width:auto;object-fit:contain;display:block;">`
        : `<div style="height:56px;display:flex;align-items:center;">
               <svg viewBox="0 0 40 40" width="44" height="44">
                   <circle cx="20" cy="20" r="18" fill="#D98C45"/>
                   <text x="20" y="26" font-size="18" font-family="Georgia,serif"
                         text-anchor="middle" fill="white" font-weight="bold">F</text>
               </svg>
           </div>`;

    function esc(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function tarjeta(p, first) {
        const filas = p.items.map((it, i) => `
            <tr>
                <td style="padding:11px 15px;border-bottom:1px solid #f0f0f0;
                           color:#ccc;font-size:.76rem;font-weight:600;">${i+1}</td>
                <td style="padding:11px 15px;border-bottom:1px solid #f0f0f0;
                           font-weight:700;font-size:.9rem;">${esc(it.nombre)}</td>
                <td style="padding:11px 15px;border-bottom:1px solid #f0f0f0;text-align:center;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;
                                 background:#1F1F1F;color:#fff;border-radius:30px;
                                 padding:4px 14px;font-weight:900;font-size:.95rem;min-width:38px;
                                 -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                        ${it.cantidad}
                    </span>
                </td>
                <td style="padding:11px 15px;border-bottom:1px solid #f0f0f0;
                           text-align:center;font-size:.76rem;">
                    <strong>${it.fecha}</strong><br>
                    <span style="color:#bbb;">${it.hora} hrs</span>
                </td>
            </tr>`).join('');

        const pb = !first ? 'page-break-before:always;' : '';

        return `
<div style="border:1.5px solid #ddd;border-radius:10px;overflow:hidden;
            background:#fff;margin-bottom:20px;${pb}">

    <!-- FRANJA DORADA TOP -->
    <div style="height:9px;
                background:linear-gradient(90deg,#c47a2e 0%,#f0b76a 50%,#c47a2e 100%);
                -webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>

    <!-- MEMBRETE -->
    <div style="background:#1a1a1a;padding:22px 30px 18px;
                -webkit-print-color-adjust:exact;print-color-adjust:exact;">

        <!-- Fila 1: Logo + nombre -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;">
            <div style="background:rgba(255,255,255,.92);border-radius:8px;
                        padding:5px 8px;display:flex;align-items:center;justify-content:center;
                        -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                ${logo}
            </div>
            <div style="width:1px;height:44px;background:#D98C45;opacity:.6;flex-shrink:0;
                        -webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>
            <div>
                <div style="font-family:Georgia,'Times New Roman',serif;font-size:1.55rem;
                            font-weight:700;color:#fff;letter-spacing:2px;line-height:1;">
                    FERMENTO
                </div>
                <div style="font-size:.62rem;color:#D98C45;text-transform:uppercase;
                            letter-spacing:3px;margin-top:5px;">
                    Panadería Artesanal
                </div>
            </div>
        </div>

        <!-- Fila 2: Título centrado -->
        <div style="text-align:center;margin-bottom:18px;">
            <div style="font-family:Georgia,'Times New Roman',serif;font-size:.9rem;
                        font-weight:700;letter-spacing:5px;text-transform:uppercase;color:#fff;">
                HOJA DE PRODUCCIÓN
            </div>
            <div style="height:2px;width:60px;margin:8px auto 0;
                        background:linear-gradient(90deg,transparent,#D98C45,transparent);
                        -webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>
        </div>

        <!-- Fila 3: Pills de metadatos -->
        <div style="display:flex;gap:10px;">
            <div style="flex:1;background:rgba(255,255,255,.06);
                        border:1px solid rgba(217,140,69,.35);
                        border-radius:8px;padding:9px 12px;
                        -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                <div style="font-size:.56rem;text-transform:uppercase;letter-spacing:1px;
                            color:#777;margin-bottom:4px;">Pedido</div>
                <div style="font-size:.95rem;font-weight:900;color:#D98C45;">#${p.id}</div>
            </div>
            <div style="flex:2.5;background:rgba(255,255,255,.06);
                        border:1px solid rgba(217,140,69,.35);
                        border-radius:8px;padding:9px 12px;
                        -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                <div style="font-size:.56rem;text-transform:uppercase;letter-spacing:1px;
                            color:#777;margin-bottom:4px;">Entrada del pedido</div>
                <div style="font-size:.82rem;font-weight:700;color:#fff;">${p.fecha} hrs</div>
            </div>
            <div style="flex:2.5;background:rgba(255,255,255,.06);
                        border:1px solid rgba(217,140,69,.35);
                        border-radius:8px;padding:9px 12px;
                        -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                <div style="font-size:.56rem;text-transform:uppercase;letter-spacing:1px;
                            color:#777;margin-bottom:4px;">Impreso el</div>
                <div style="font-size:.82rem;font-weight:700;color:#fff;">${p.impreso} hrs</div>
            </div>
        </div>
    </div>

    <!-- FRANJA INFERIOR -->
    <div style="height:4px;
                background:linear-gradient(90deg,#2a2a2a 0%,#D98C45 40%,#2a2a2a 100%);
                -webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>

    <!-- TABLA PRODUCTOS -->
    <div style="padding:0;">
        <div style="padding:9px 16px;font-size:.6rem;text-transform:uppercase;letter-spacing:1px;
                    color:#999;font-weight:700;background:#f7f7f7;border-bottom:1px solid #eee;">
            🍞 Productos a Elaborar &mdash; ${p.total_uds} unidades
        </div>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:8px 15px;font-size:.6rem;text-transform:uppercase;color:#bbb;
                               font-weight:700;text-align:left;border-bottom:1px solid #eee;width:30px;">#</th>
                    <th style="padding:8px 15px;font-size:.6rem;text-transform:uppercase;color:#bbb;
                               font-weight:700;text-align:left;border-bottom:1px solid #eee;">Producto</th>
                    <th style="padding:8px 15px;font-size:.6rem;text-transform:uppercase;color:#bbb;
                               font-weight:700;text-align:center;border-bottom:1px solid #eee;width:100px;">Cantidad</th>
                    <th style="padding:8px 15px;font-size:.6rem;text-transform:uppercase;color:#bbb;
                               font-weight:700;text-align:center;border-bottom:1px solid #eee;width:120px;">Fecha Pedido</th>
                </tr>
            </thead>
            <tbody>${filas}</tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="padding:10px 15px;background:#f7f7f7;border-top:2px solid #eee;
                                          text-align:right;font-weight:700;color:#888;font-size:.78rem;">
                        TOTAL UNIDADES:
                    </td>
                    <td style="padding:10px 15px;background:#f7f7f7;border-top:2px solid #eee;text-align:center;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;
                                     background:#D98C45;color:#fff;border-radius:30px;
                                     padding:4px 14px;font-weight:900;font-size:.95rem;min-width:38px;
                                     -webkit-print-color-adjust:exact;print-color-adjust:exact;">
                            ${p.total_uds}
                        </span>
                    </td>
                    <td style="background:#f7f7f7;border-top:2px solid #eee;"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- CHECKLIST -->
    <div style="border-top:1px solid #eee;">
        <div style="padding:9px 16px;font-size:.6rem;text-transform:uppercase;letter-spacing:1px;
                    color:#999;font-weight:700;background:#f7f7f7;border-bottom:1px solid #eee;">
            ✅ Control de Proceso
        </div>
        <div style="display:flex;">
            ${['Elaborado','Empacado','Enviado'].map((s,i) => {
                const descs = ['Preparado y listo','Envuelto y embalado','Entregado al cliente'];
                const br = i < 2 ? 'border-right:1px solid #eee;' : '';
                return `<div style="flex:1;display:flex;flex-direction:column;align-items:center;
                                    padding:16px 10px;gap:6px;text-align:center;${br}">
                    <div style="width:36px;height:36px;border:2.5px solid #ccc;border-radius:8px;
                                background:#fff;flex-shrink:0;
                                -webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>
                    <div>
                        <strong style="display:block;font-size:.82rem;margin-bottom:2px;">${s}</strong>
                        <span style="font-size:.68rem;color:#bbb;">${descs[i]}</span>
                    </div>
                    <div style="width:100%;margin-top:5px;">
                        <div style="border-bottom:1.5px dashed #ddd;height:22px;"></div>
                        <div style="font-size:.56rem;color:#ccc;text-align:center;margin-top:3px;">Firma / Hora</div>
                    </div>
                </div>`;
            }).join('')}
        </div>
    </div>

    <!-- PIE -->
    <div style="padding:8px 16px;font-size:.62rem;color:#888;text-align:center;
                background:#1a1a1a;letter-spacing:.5px;
                -webkit-print-color-adjust:exact;print-color-adjust:exact;">
        ■ FERMENTO Panadería Artesanal &nbsp;·&nbsp; Pedido #${p.id} &nbsp;·&nbsp; ${p.impreso}
    </div>

</div>`;
    } // fin tarjeta()

    // ── HTML completo con @page carta ────────────────────────────────────
    const tarjetas = pedidos.map((p, i) => tarjeta(p, i === 0)).join('');

    const html = `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hoja de Producción — FERMENTO</title>
<style>
  @page {
      size: letter;
      margin: 14mm 14mm 14mm 14mm;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: white;
      color: #111;
      padding: 0;
  }
  @media screen {
      body { padding: 24px; background: #f0f0f0; }
      .hoja-wrap { max-width: 720px; margin: 0 auto; }
  }
  @media print {
      body { padding: 0; background: white; }
      .hoja-wrap { max-width: 100%; }
      * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  }
  .no-print { }
  @media print { .no-print { display: none !important; } }
</style>
</head>
<body>
<div class="hoja-wrap">
${tarjetas}
</div>

<!-- Botón de imprimir visible en pantalla -->
<div class="no-print" style="position:fixed;bottom:24px;right:24px;">
  <button onclick="window.print()"
          style="background:#1F1F1F;color:white;border:none;border-radius:12px;
                 padding:12px 24px;font-size:.95rem;font-weight:700;cursor:pointer;
                 box-shadow:0 8px 24px rgba(0,0,0,.3);display:flex;align-items:center;gap:10px;">
      🖨️ Imprimir / Guardar PDF
  </button>
</div>

<script>
  window.onload = function() { setTimeout(function(){ window.print(); }, 500); };
<\/script>
</body>
</html>`;

    // ── Abrir ventana emergente ───────────────────────────────────────────
    const win = window.open('', '_blank',
        'width=820,height=950,scrollbars=yes,toolbar=no,menubar=no,location=no,status=no');
    if (!win) {
        alert('⚠️ Permite las ventanas emergentes de este sitio para imprimir la hoja.');
        return;
    }
    win.document.open();
    win.document.write(html);
    win.document.close();
}

/**
 * imprimirReporte(pedidos) – Reporte completo de pedidos seleccionados.
 * Cada elemento: { id, cliente, telefono, direccion, total, estado,
 *                  fecha, hora, impreso, items:[{nombre,cantidad,precio,subtotal}], total_uds }
 */
function imprimirReporte(pedidos) {
    if (!pedidos || pedidos.length === 0) return;

    const logo = (typeof window.FERMENTO_LOGO !== 'undefined' && window.FERMENTO_LOGO)
        ? `<img src="${window.FERMENTO_LOGO}" alt="Logo" style="height:46px;width:auto;object-fit:contain;display:block;">`
        : `<svg viewBox="0 0 40 40" width="40" height="40"><circle cx="20" cy="20" r="18" fill="#D98C45"/><text x="20" y="26" font-size="18" font-family="Georgia,serif" text-anchor="middle" fill="white" font-weight="bold">F</text></svg>`;

    const eLabel={pendiente:'⏳ Pendiente',completado:'✅ Completado',cancelado:'❌ Cancelado',en_camino:'🚚 En Camino'};
    const eColor={pendiente:'#c0980a',completado:'#27ae60',cancelado:'#e74c3c',en_camino:'#3498db'};
    const eBg   ={pendiente:'#fff8e1',completado:'#eafaf1',cancelado:'#ffebee',en_camino:'#ebf5fb'};
    const esc=s=>String(s||'—').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const fmtQ=n=>'Q'+parseFloat(n||0).toFixed(2);

    const granTotal=pedidos.reduce((a,p)=>a+p.total,0);
    const granUds  =pedidos.reduce((a,p)=>a+p.total_uds,0);
    const impreso  =pedidos[0]?.impreso||'—';

    // ── Portada resumen ─────────────────────────────────────────────────
    const membrete=(titulo)=>`
    <div style="height:8px;background:linear-gradient(90deg,#c47a2e,#f0b76a,#c47a2e);-webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>
    <div style="background:#1a1a1a;padding:18px 26px 14px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">
      <div style="display:flex;align-items:center;gap:13px;margin-bottom:13px;">
        <div style="background:rgba(255,255,255,.92);border-radius:7px;padding:4px 6px;display:inline-flex;-webkit-print-color-adjust:exact;print-color-adjust:exact;">${logo}</div>
        <div style="width:1px;height:36px;background:#D98C45;opacity:.6;flex-shrink:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>
        <div>
          <div style="font-family:Georgia,serif;font-size:1.3rem;font-weight:700;color:#fff;letter-spacing:2px;">FERMENTO</div>
          <div style="font-size:.58rem;color:#D98C45;text-transform:uppercase;letter-spacing:3px;margin-top:3px;">Panadería Artesanal</div>
        </div>
        <div style="margin-left:auto;text-align:right;">
          <div style="font-family:Georgia,serif;font-size:.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#fff;">${titulo}</div>
          <div style="font-size:.58rem;color:#D98C45;margin-top:2px;">${impreso} hrs</div>
        </div>
      </div>
    </div>
    <div style="height:4px;background:linear-gradient(90deg,#2a2a2a,#D98C45,#2a2a2a);-webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>`;

    const portada = pedidos.length < 2 ? '' : `
<div style="border:1.5px solid #ddd;border-radius:10px;overflow:hidden;background:white;margin-bottom:20px;page-break-after:always;">
  ${membrete('REPORTE GENERAL DE PEDIDOS')}
  <div>
    <div style="padding:8px 15px;font-size:.58rem;text-transform:uppercase;letter-spacing:1px;color:#999;font-weight:700;background:#f7f7f7;border-bottom:1px solid #eee;">Pedidos Incluidos — ${pedidos.length} total</div>
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr>
        <th style="padding:7px 13px;font-size:.58rem;text-transform:uppercase;color:#bbb;font-weight:700;border-bottom:1px solid #eee;text-align:left;width:36px;">#</th>
        <th style="padding:7px 13px;font-size:.58rem;text-transform:uppercase;color:#bbb;font-weight:700;border-bottom:1px solid #eee;text-align:left;">Cliente</th>
        <th style="padding:7px 13px;font-size:.58rem;text-transform:uppercase;color:#bbb;font-weight:700;border-bottom:1px solid #eee;text-align:center;width:95px;">Fecha</th>
        <th style="padding:7px 13px;font-size:.58rem;text-transform:uppercase;color:#bbb;font-weight:700;border-bottom:1px solid #eee;text-align:center;width:50px;">Uds.</th>
        <th style="padding:7px 13px;font-size:.58rem;text-transform:uppercase;color:#bbb;font-weight:700;border-bottom:1px solid #eee;text-align:right;width:80px;">Total</th>
        <th style="padding:7px 13px;font-size:.58rem;text-transform:uppercase;color:#bbb;font-weight:700;border-bottom:1px solid #eee;text-align:center;width:92px;">Estado</th>
      </tr></thead>
      <tbody>${pedidos.map((p,i)=>{
        const ec=eColor[p.estado]||'#888',eb=eBg[p.estado]||'#eee',el=eLabel[p.estado]||p.estado;
        return `<tr style="${i%2?'background:#fafafa;':''}">
          <td style="padding:8px 13px;border-bottom:1px solid #f2f2f2;font-weight:800;color:#D98C45;">${p.id}</td>
          <td style="padding:8px 13px;border-bottom:1px solid #f2f2f2;"><strong style="font-size:.84rem;">${esc(p.cliente)}</strong><br><span style="font-size:.68rem;color:#aaa;">${esc(p.telefono)}</span></td>
          <td style="padding:8px 13px;border-bottom:1px solid #f2f2f2;text-align:center;font-size:.74rem;"><strong>${p.fecha}</strong><br><span style="color:#aaa;">${p.hora}</span></td>
          <td style="padding:8px 13px;border-bottom:1px solid #f2f2f2;text-align:center;font-weight:700;">${p.total_uds}</td>
          <td style="padding:8px 13px;border-bottom:1px solid #f2f2f2;text-align:right;font-weight:800;color:#D98C45;">${fmtQ(p.total)}</td>
          <td style="padding:8px 13px;border-bottom:1px solid #f2f2f2;text-align:center;"><span style="font-size:.66rem;font-weight:700;padding:3px 8px;border-radius:20px;color:${ec};background:${eb};-webkit-print-color-adjust:exact;print-color-adjust:exact;">${el}</span></td>
        </tr>`;
      }).join('')}</tbody>
      <tfoot><tr>
        <td colspan="3" style="padding:9px 13px;background:#f7f7f7;border-top:2px solid #eee;font-weight:700;font-size:.76rem;text-align:right;color:#888;">TOTALES:</td>
        <td style="padding:9px 13px;background:#f7f7f7;border-top:2px solid #eee;text-align:center;font-weight:900;">${granUds}</td>
        <td style="padding:9px 13px;background:#f7f7f7;border-top:2px solid #eee;text-align:right;font-weight:900;color:#D98C45;">${fmtQ(granTotal)}</td>
        <td style="background:#f7f7f7;border-top:2px solid #eee;"></td>
      </tr></tfoot>
    </table>
  </div>
  <div style="padding:7px 15px;font-size:.58rem;color:#888;text-align:center;background:#1a1a1a;letter-spacing:.4px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">■ FERMENTO &nbsp;·&nbsp; ${pedidos.length} pedido(s) &nbsp;·&nbsp; ${impreso}</div>
</div>`;

    // ── Tarjeta por pedido ──────────────────────────────────────────────
    const tarjetas = pedidos.map((p,idx)=>{
        const pb=idx>0||pedidos.length>1?'page-break-before:always;':'';
        const ec=eColor[p.estado]||'#888',eb=eBg[p.estado]||'#f5f5f5',el=eLabel[p.estado]||p.estado;
        const filas=p.items.map((it,i)=>`
          <tr>
            <td style="padding:9px 13px;border-bottom:1px solid #f2f2f2;color:#ccc;font-size:.72rem;font-weight:600;">${i+1}</td>
            <td style="padding:9px 13px;border-bottom:1px solid #f2f2f2;font-weight:700;font-size:.84rem;">${esc(it.nombre)}</td>
            <td style="padding:9px 13px;border-bottom:1px solid #f2f2f2;text-align:center;"><span style="display:inline-flex;align-items:center;justify-content:center;background:#1F1F1F;color:#fff;border-radius:30px;padding:3px 11px;font-weight:900;min-width:32px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">${it.cantidad}</span></td>
            <td style="padding:9px 13px;border-bottom:1px solid #f2f2f2;text-align:right;font-size:.8rem;">${fmtQ(it.precio)}</td>
            <td style="padding:9px 13px;border-bottom:1px solid #f2f2f2;text-align:right;font-weight:700;font-size:.8rem;">${fmtQ(it.subtotal)}</td>
          </tr>`).join('');
        return `
<div style="border:1.5px solid #ddd;border-radius:10px;overflow:hidden;background:white;margin-bottom:20px;${pb}">
  ${membrete('DETALLE DE PEDIDO')}
  <!-- Pills pedido -->
  <div style="background:#1a1a1a;padding:0 26px 14px;display:flex;gap:8px;flex-wrap:wrap;-webkit-print-color-adjust:exact;print-color-adjust:exact;">
    ${[['Pedido','#'+p.id,'#D98C45'],['Fecha',p.fecha+' '+p.hora,'#fff'],['Estado',el,ec],['Total',fmtQ(p.total),'#D98C45']].map(([k,v,c])=>`
    <div style="flex:1;min-width:85px;background:rgba(255,255,255,.06);border:1px solid rgba(217,140,69,.3);border-radius:7px;padding:7px 10px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">
      <div style="font-size:.52rem;text-transform:uppercase;color:#777;margin-bottom:2px;">${k}</div>
      <div style="font-size:.84rem;font-weight:900;color:${c};-webkit-print-color-adjust:exact;print-color-adjust:exact;">${v}</div>
    </div>`).join('')}
  </div>
  <div style="height:4px;background:linear-gradient(90deg,#2a2a2a,#D98C45,#2a2a2a);-webkit-print-color-adjust:exact;print-color-adjust:exact;"></div>
  <!-- Cliente -->
  <div style="display:flex;gap:0;border-bottom:1px solid #eee;">
    <div style="flex:1;padding:11px 15px;border-right:1px solid #eee;">
      <div style="font-size:.54rem;text-transform:uppercase;color:#bbb;font-weight:700;letter-spacing:1px;margin-bottom:4px;">👤 Cliente</div>
      <div style="font-weight:700;font-size:.86rem;margin-bottom:2px;">${esc(p.cliente)}</div>
      <div style="font-size:.74rem;color:#888;">📞 ${esc(p.telefono)}</div>
    </div>
    <div style="flex:1.5;padding:11px 15px;">
      <div style="font-size:.54rem;text-transform:uppercase;color:#bbb;font-weight:700;letter-spacing:1px;margin-bottom:4px;">📍 Dirección de envío</div>
      <div style="font-size:.8rem;color:#333;">${esc(p.direccion)}</div>
    </div>
  </div>
  <!-- Productos -->
  <div>
    <div style="padding:8px 15px;font-size:.57rem;text-transform:uppercase;letter-spacing:1px;color:#999;font-weight:700;background:#f7f7f7;border-bottom:1px solid #eee;">🍞 Productos — ${p.total_uds} unidades</div>
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr>
        <th style="padding:7px 13px;font-size:.57rem;text-transform:uppercase;color:#bbb;font-weight:700;text-align:left;border-bottom:1px solid #eee;width:26px;">#</th>
        <th style="padding:7px 13px;font-size:.57rem;text-transform:uppercase;color:#bbb;font-weight:700;text-align:left;border-bottom:1px solid #eee;">Producto</th>
        <th style="padding:7px 13px;font-size:.57rem;text-transform:uppercase;color:#bbb;font-weight:700;text-align:center;border-bottom:1px solid #eee;width:72px;">Cant.</th>
        <th style="padding:7px 13px;font-size:.57rem;text-transform:uppercase;color:#bbb;font-weight:700;text-align:right;border-bottom:1px solid #eee;width:78px;">P. Unit.</th>
        <th style="padding:7px 13px;font-size:.57rem;text-transform:uppercase;color:#bbb;font-weight:700;text-align:right;border-bottom:1px solid #eee;width:78px;">Subtotal</th>
      </tr></thead>
      <tbody>${filas}</tbody>
      <tfoot><tr>
        <td colspan="3" style="padding:9px 13px;background:#f7f7f7;border-top:2px solid #eee;text-align:right;font-weight:700;color:#888;font-size:.74rem;">TOTAL DEL PEDIDO:</td>
        <td colspan="2" style="padding:9px 13px;background:#f7f7f7;border-top:2px solid #eee;text-align:right;font-weight:900;font-size:.93rem;color:#D98C45;-webkit-print-color-adjust:exact;print-color-adjust:exact;">${fmtQ(p.total)}</td>
      </tr></tfoot>
    </table>
  </div>
  <div style="padding:7px 15px;font-size:.58rem;color:#888;text-align:center;background:#1a1a1a;letter-spacing:.4px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">■ FERMENTO &nbsp;·&nbsp; Pedido #${p.id} &nbsp;·&nbsp; ${esc(p.cliente)} &nbsp;·&nbsp; ${p.impreso}</div>
</div>`;
    }).join('');

    const html=`<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><title>Reporte — FERMENTO</title>
<style>
  @page{size:letter;margin:12mm}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',Arial,sans-serif;color:#111;background:white}
  @media screen{body{padding:24px;background:#e8e8e8}.rw{max-width:760px;margin:0 auto}}
  @media print{.rw{max-width:100%}.np{display:none !important}*{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important}}
</style></head><body>
<div class="rw">${portada}${tarjetas}</div>
<div class="np" style="position:fixed;bottom:22px;right:22px;">
  <button onclick="window.print()" style="background:#1F1F1F;color:white;border:none;border-radius:11px;padding:11px 22px;font-size:.88rem;font-weight:700;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.3);">🖨️ Imprimir / PDF</button>
</div>
<script>window.onload=function(){setTimeout(function(){window.print();},600);};<\/script>
</body></html>`;

    const win=window.open('','_blank','width=840,height=960,scrollbars=yes,toolbar=no,menubar=no,location=no,status=no');
    if(!win){alert('⚠️ Permite las ventanas emergentes de este sitio.');return;}
    win.document.open();
    win.document.write(html);
    win.document.close();
}
