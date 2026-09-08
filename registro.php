<?php 
    require 'includes/db.php';
    require_once 'includes/config.php';
    session_start();

    $mensaje = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verificar token CSRF antes de procesar el formulario
        csrf_verify('registro.php');

        $nombre = htmlspecialchars(trim($_POST['nombre']), ENT_QUOTES, 'UTF-8');
        $email = $_POST['email'];
        $telefono = $_POST['telefono'];
        $direccion = $_POST['direccion'];
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // 1. Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $mensaje = "Este correo ya está registrado.";
        } else {
            // 2. Insertar nuevo usuario con teléfono y dirección
            $sql = "INSERT INTO usuarios (nombre, email, telefono, direccion, password, rol) VALUES (?, ?, ?, ?, ?, 'cliente')";
            $stmt = $pdo->prepare($sql);
            
            if($stmt->execute([$nombre, $email, $telefono, $direccion, $pass])) {
                // Prevenir Session Fixation al registrarse
                session_regenerate_id(true);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_nombre'] = $nombre;
                $_SESSION['user_rol'] = 'cliente';
                header("Location: index.php");
                exit;
            } else {
                $mensaje = "Ocurrió un error al registrarse.";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | Fermento</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- ESTILOS CRÍTICOS (IDÉNTICOS A LOGIN) --- */
        :root {
            --bg-cream: #F9F7F2;
            --text-black: #1F1F1F;
            --accent-toast: #D98C45;
            --grey-light: #e9ecef;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-cream);
            color: var(--text-black);
            min-height: 100vh; /* Usamos min-height para permitir scroll si la pantalla es pequeña */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px; /* Un poco de espacio arriba y abajo */
        }

        .login-card {
            background: var(--white);
            width: 100%;
            max-width: 450px; /* Un poco más ancho para acomodar mejor la info */
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
        }

        .brand-title {
            font-family: 'Merriweather', serif;
            font-size: 1.8rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }
        .brand-subtitle {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 2rem;
        }

        /* Inputs */
        .input-group { text-align: left; margin-bottom: 1.2rem; }
        
        .input-label {
            display: block; font-size: 0.75rem; font-weight: 700;
            color: #555; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;
        }

        .input-wrapper {
            display: flex; border: 1px solid #ddd; border-radius: 6px;
            overflow: hidden; transition: 0.3s;
        }
        
        .input-wrapper:focus-within {
            border-color: var(--text-black);
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }

        .input-icon {
            background-color: var(--grey-light); width: 45px;
            display: flex; align-items: center; justify-content: center;
            color: #666; border-right: 1px solid #ddd;
        }

        .input-field {
            width: 100%; border: none; padding: 10px 15px; /* Padding un poco menor para ahorrar espacio vertical */
            outline: none; font-size: 0.95rem; color: #333;
        }

        /* Botón */
        .btn-login {
            width: 100%; background-color: var(--text-black); color: white;
            padding: 12px; border: none; border-radius: 6px;
            font-weight: 600; cursor: pointer; transition: 0.3s;
            text-transform: uppercase; letter-spacing: 1px; margin-top: 10px;
        }
        .btn-login:hover { background-color: var(--accent-toast); }

        /* Social & Footer */
        .divider {
            margin: 1.5rem 0; font-size: 0.8rem; color: #999;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .divider::before, .divider::after { content: ""; height: 1px; background: #eee; flex: 1; }

        .social-buttons { display: flex; justify-content: center; gap: 15px; margin-bottom: 1.5rem; }
        .social-btn {
            width: 35px; height: 35px; border: 1px solid #ddd; border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.2s; background: white; font-size: 0.9rem;
        }
        .social-btn:hover { border-color: var(--text-black); color: var(--text-black); }

        .footer-links { font-size: 0.85rem; color: #666; }
        .footer-links a { color: var(--text-black); font-weight: 600; text-decoration: none; }
        .footer-links a:hover { text-decoration: underline; }

        .alert {
            background: #ffecec; color: #d63031; padding: 10px;
            border-radius: 4px; font-size: 0.85rem; margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <div class="brand-title">FERMENTO.</div>
        <div class="brand-subtitle">Únete a nuestra comunidad</div>

        <?php if($mensaje): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- Token CSRF: protege el registro contra envíos fraudulentos -->
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="input-group">
                <label class="input-label">Nombre Completo</label>
                <div class="input-wrapper">
                    <div class="input-icon"><i class="fas fa-user"></i></div>
                    <input type="text" name="nombre" class="input-field" placeholder="Juan Pérez" required>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Correo Electrónico</label>
                <div class="input-wrapper">
                    <div class="input-icon"><i class="fas fa-envelope"></i></div>
                    <input type="email" name="email" class="input-field" placeholder="ejemplo@correo.com" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="input-group">
                    <label class="input-label">Teléfono</label>
                    <div class="input-wrapper">
                        <div class="input-icon"><i class="fas fa-phone"></i></div>
                        <input type="tel" name="telefono" class="input-field" placeholder="5555-5555" required>
                    </div>
                </div>
                <div class="input-group">
                    <label class="input-label">Dirección</label>
                    <div class="input-wrapper">
                        <div class="input-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <input type="text" name="direccion" class="input-field" placeholder="Zona 10..." required>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Crear Contraseña</label>
                <div class="input-wrapper">
                    <div class="input-icon"><i class="fas fa-lock"></i></div>
                    <input type="password" name="password" class="input-field" placeholder="********" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                Registrarse <i class="fas fa-user-plus" style="margin-left: 8px;"></i>
            </button>
        </form>


        <div class="footer-links">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
            <div style="margin-top: 10px;">
                <a href="index.php" style="color: #999; font-weight: normal; font-size: 0.8rem;">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
            </div>
        </div>

    </div>

</body>
</html>