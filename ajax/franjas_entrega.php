<?php
/**
 * ajax/franjas_entrega.php
 * Devuelve las franjas horarias disponibles para una fecha dada,
 * respetando los horarios configurados, domingos bloqueados y días feriados.
 *
 * GET ?fecha=YYYY-MM-DD
 * Responde JSON: { disponible: bool, franjas: ["08:00","09:00",...], mensaje: "" }
 */

require '../includes/db.php';
require '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$fecha = trim($_GET['fecha'] ?? '');

// Validar formato
if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['disponible' => false, 'franjas' => [], 'mensaje' => 'Fecha inválida.']);
    exit;
}

// Se requiere anticipación mínima configurable (default: 42 horas)
$horasAnticipacion = (int)getConfig('horas_anticipacion', 42);
$minDate = date('Y-m-d', strtotime("+{$horasAnticipacion} hours"));
if ($fecha < $minDate) {
    echo json_encode(['disponible' => false, 'franjas' => [], 'mensaje' => "Se requieren mínimo {$horasAnticipacion} horas de anticipación para programar una entrega."]);
    exit;
}

$ts      = strtotime($fecha);
$diaSem  = (int)date('w', $ts); // 0=Dom, 1=Lun ... 6=Sáb

// Domingos siempre bloqueados
if ($diaSem === 0) {
    echo json_encode(['disponible' => false, 'franjas' => [], 'mensaje' => 'No realizamos entregas los domingos.']);
    exit;
}

// Verificar si es feriado
$stmtF = $pdo->prepare("SELECT descripcion FROM dias_feriados WHERE fecha = ?");
$stmtF->execute([$fecha]);
$feriado = $stmtF->fetch(PDO::FETCH_ASSOC);
if ($feriado) {
    echo json_encode([
        'disponible' => false,
        'franjas'    => [],
        'mensaje'    => '📅 Este día es feriado: ' . htmlspecialchars($feriado['descripcion']) . '. Se reprogramará al siguiente día hábil.'
    ]);
    exit;
}

// Obtener horarios según día
if ($diaSem === 6) {
    // Sábado
    $horaInicio = getConfig('horario_sab_inicio', '08:00');
    $horaFin    = getConfig('horario_sab_fin',    '12:00');
} else {
    // Lunes a Viernes
    $horaInicio = getConfig('horario_lv_inicio', '08:00');
    $horaFin    = getConfig('horario_lv_fin',    '17:00');
}

// Generar franjas horarias cada hora entre inicio y fin
$franjas   = [];
$tsInicio  = strtotime("$fecha $horaInicio");
$tsFin     = strtotime("$fecha $horaFin");
$ahora     = time();

// Margen configurable de anticipación
$margen = $horasAnticipacion * 3600;

$tsCurrent = $tsInicio;
while ($tsCurrent < $tsFin) {
    if ($tsCurrent > ($ahora + $margen)) {
        $franjas[] = date('H:i', $tsCurrent);
    }
    $tsCurrent += 3600; // cada hora
}

if (empty($franjas)) {
    echo json_encode([
        'disponible' => false,
        'franjas'    => [],
        'mensaje'    => 'Ya no hay franjas disponibles para este horario con 42 horas de anticipación. Por favor elige un día posterior.'
    ]);
    exit;
}

echo json_encode([
    'disponible' => true,
    'franjas'    => $franjas,
    'mensaje'    => '',
    'horario'    => "$horaInicio – $horaFin"
]);
