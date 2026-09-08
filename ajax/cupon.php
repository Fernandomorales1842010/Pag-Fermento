<?php
session_start();
require '../includes/db.php';
header('Content-Type: application/json');

// Recibir datos POST JSON o form-data
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$accion = $data['accion'] ?? '';

if ($accion === 'remover') {
    unset($_SESSION['cupon']);
    echo json_encode(['success' => true, 'msg' => 'Cupón removido']);
    exit;
}

if ($accion === 'aplicar') {
    $codigo = strtoupper(trim($data['codigo'] ?? ''));
    if (empty($codigo)) {
        echo json_encode(['success' => false, 'error' => 'Ingresa un código de cupón']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM cupones WHERE codigo = ? AND activo = 1");
    $stmt->execute([$codigo]);
    $cupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cupon) {
        echo json_encode(['success' => false, 'error' => 'Cupón inválido o inactivo']);
        exit;
    }

    if (!empty($cupon['fecha_expira']) && $cupon['fecha_expira'] < date('Y-m-d')) {
        echo json_encode(['success' => false, 'error' => 'Este cupón ha expirado']);
        exit;
    }

    if (!empty($cupon['usos_maximos']) && $cupon['usos_actuales'] >= $cupon['usos_maximos']) {
        echo json_encode(['success' => false, 'error' => 'Este cupón ha superado el límite de usos']);
        exit;
    }

    $_SESSION['cupon'] = [
        'id'     => $cupon['id'],
        'codigo' => $cupon['codigo'],
        'tipo'   => $cupon['tipo'],
        'valor'  => $cupon['valor']
    ];

    echo json_encode([
        'success' => true,
        'msg'     => 'Cupón aplicado con éxito',
        'cupon'   => $_SESSION['cupon']
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción inválida']);
