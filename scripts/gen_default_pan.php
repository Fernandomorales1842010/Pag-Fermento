<?php
// Genera assets/img/default_pan.png — placeholder de marca para productos sin foto.
// Uso puntual (build-time), no se ejecuta en producción.
$w = 500; $h = 500;
$img = imagecreatetruecolor($w, $h);
imageantialias($img, true);

$cream  = imagecolorallocate($img, 249, 247, 242); // --bg-cream
$toast  = imagecolorallocate($img, 217, 140, 69);  // --accent-toast
$toastD = imagecolorallocate($img, 184, 111, 45);  // sombra del pan
$cream2 = imagecolorallocate($img, 240, 231, 214); // borde circulo

imagefilledrectangle($img, 0, 0, $w, $h, $cream);

// Círculo de fondo suave
imagefilledellipse($img, $w/2, $h/2, 360, 360, $cream2);

// "Pan" estilizado: óvalo principal + sombra inferior + líneas de corteza
imagefilledellipse($img, $w/2, $h/2 + 18, 230, 150, $toastD);
imagefilledellipse($img, $w/2, $h/2, 230, 150, $toast);

// Líneas de corteza (marcas típicas de pan artesanal)
imagesetthickness($img, 6);
for ($i = -1; $i <= 1; $i++) {
    $x = $w/2 + $i * 55;
    imagearc($img, $x, $h/2 - 6, 60, 90, 200, 340, $cream);
}

imagepng($img, __DIR__ . '/../assets/img/default_pan.png');
imagedestroy($img);
echo "OK: assets/img/default_pan.png generado (" . filesize(__DIR__ . '/../assets/img/default_pan.png') . " bytes)\n";
