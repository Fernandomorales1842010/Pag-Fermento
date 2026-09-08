<?php
function to_iso($str) { return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8'); }
require 'includes/db.php';
// Asegúrate de que la ruta sea correcta según donde pegaste la carpeta
require 'includes/fpdf/fpdf.php'; 

session_start();

// 1. VALIDACIONES DE SEGURIDAD
if (!isset($_GET['id'])) { die("Error: Pedido no especificado."); }
$id_pedido = (int)$_GET['id'];

// Si es usuario registrado, validamos que el pedido sea suyo
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id_pedido, $uid]);
} else {
    // Si es invitado (o admin), por ahora permitimos ver si tiene el ID (en producción se usan tokens)
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$id_pedido]);
}

$pedido = $stmt->fetch();
if (!$pedido) { die("Recibo no encontrado o acceso denegado."); }

// Obtener detalles
$stmt2 = $pdo->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = ?");
$stmt2->execute([$id_pedido]);
$items = $stmt2->fetchAll();

// 2. CONFIGURACIÓN DEL DISEÑO (Clase extendida para Header/Footer)
class PDF_Recibo extends FPDF {
    function Header() {
        // Color Tostado (Accent) para el logo
        $this->SetTextColor(217, 140, 69); // RGB del #D98C45
        $this->SetFont('Times', 'B', 24);
        $this->Cell(0, 10, 'FERMENTO', 0, 1, 'L');
        
        $this->SetFont('Times', 'I', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, to_iso('Panadería de Autor'), 0, 1, 'L');
        
        // Línea divisoria elegante
        $this->SetDrawColor(217, 140, 69);
        $this->SetLineWidth(0.5);
        $this->Line(10, 28, 200, 28);
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, to_iso('Gracias por su preferencia - www.fermentopanaderia.com'), 0, 0, 'C');
    }
}

// 3. GENERAR EL DOCUMENTO
$pdf = new PDF_Recibo();
$pdf->AddPage();
$pdf->SetMargins(20, 20, 20);

// --- DATOS DEL ENCABEZADO ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0);
$pdf->Cell(120, 5, to_iso('DATOS DEL CLIENTE:'), 0, 0);
$pdf->Cell(50, 5, to_iso('RECIBO # ' . str_pad($pedido['id'], 6, "0", STR_PAD_LEFT)), 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(50);
// Cliente
$pdf->Cell(120, 5, to_iso($pedido['nombre_cliente']), 0, 0);
// Fecha
$pdf->Cell(50, 5, 'Fecha: ' . date('d/m/Y', strtotime($pedido['fecha'])), 0, 1);

$pdf->MultiCell(120, 5, to_iso($pedido['direccion_envio']), 0, 'L');
$pdf->Ln(15);

// --- CUERPO TIPO CARTA ---
$pdf->SetFont('Times', '', 12);
$pdf->SetTextColor(0);
$pdf->MultiCell(0, 6, to_iso("Estimado(a) Cliente,\n\nEs un placer confirmarle el detalle de su orden. Agradecemos profundamente su confianza en nuestro trabajo artesanal. A continuación, presentamos el resumen de su pedido:"), 0, 'J');
$pdf->Ln(10);

// --- TABLA DE PRODUCTOS ---
// Encabezado Tabla
$pdf->SetFillColor(245, 245, 245); // Gris muy suave
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(15, 8, 'Cant.', 0, 0, 'C', true);
$pdf->Cell(105, 8, to_iso('Producto / Descripción'), 0, 0, 'L', true);
$pdf->Cell(25, 8, 'Precio U.', 0, 0, 'R', true);
$pdf->Cell(25, 8, 'Subtotal', 0, 1, 'R', true);

// Filas
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(50);

foreach ($items as $item) {
    $subtotal = $item['precio_unitario'] * $item['cantidad'];
    
    $pdf->Cell(15, 8, $item['cantidad'], 'B', 0, 'C');
    $pdf->Cell(105, 8, to_iso($item['nombre_producto']), 'B', 0, 'L');
    $pdf->Cell(25, 8, 'Q' . number_format($item['precio_unitario'], 2), 'B', 0, 'R');
    $pdf->Cell(25, 8, 'Q' . number_format($subtotal, 2), 'B', 1, 'R');
}

// --- TOTALES ---
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(217, 140, 69); // Color Accent
$pdf->Cell(145, 10, 'TOTAL A PAGAR:', 0, 0, 'R');
$pdf->Cell(25, 10, 'Q' . number_format($pedido['total'], 2), 0, 1, 'R');

// --- MENSAJE FINAL ---
$pdf->Ln(20);
$pdf->SetFont('Times', 'I', 11);
$pdf->SetTextColor(0);
$pdf->MultiCell(0, 6, to_iso("Este documento sirve como comprobante de su pedido. Si tiene alguna duda, puede contactarnos directamente a nuestro WhatsApp.\n\nAtentamente,\nEl equipo de Fermento."), 0, 'C');

// 4. SALIDA
// 'D' fuerza la descarga. Si prefieres que se abra en el navegador, usa 'I'.
$pdf->Output('D', 'Recibo_Fermento_' . $pedido['id'] . '.pdf'); 
?>