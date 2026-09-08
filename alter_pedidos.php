<?php
require 'includes/db.php';
try {
    $pdo->exec('ALTER TABLE pedidos ADD COLUMN descuento DECIMAL(10,2) DEFAULT 0.00;');
    echo 'descuento added. ';
} catch(Exception $e) { echo $e->getMessage() . ' '; }
try {
    $pdo->exec('ALTER TABLE pedidos ADD COLUMN cupon_id INT(11) DEFAULT NULL;');
    echo 'cupon_id added. ';
} catch(Exception $e) { echo $e->getMessage() . ' '; }
try {
    $pdo->exec('ALTER TABLE pedidos ADD COLUMN cupon_codigo VARCHAR(50) DEFAULT NULL;');
    echo 'cupon_codigo added.';
} catch(Exception $e) { echo $e->getMessage(); }
?>
