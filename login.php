<?php 
    require 'includes/db.php';
    require_once 'includes/config.php';
    session_start();

    $mensaje = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = $_POST['email'];
        $pass = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            // Prevenir Session Fixation: regenerar ID de sesión al autenticar
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_rol'] = isset($user['rol']) ? $user['rol'] : 'cliente';
            header("Location: index.php");
            exit;
        } else {
            $mensaje = "Credenciales incorrectas.";
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Fermento</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- ESTILOS CRÍTICOS DEL LOGIN --- */
        :root {
            --bg-cream: #F9F7F2;
            --text-black: #1F1F1F;
            --accent-toast: #D98C45; /* Naranja Tostado */
            --grey-light: #e9ecef;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-cream);
            color: var(--text-black);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* La Tarjeta Central */
        .login-card {
            background: var(--white);
            width: 100%;
            max-width: 400px; /* Ancho fijo como la referencia */
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); /* Sombra suave */
            text-align: center;
        }

        /* Encabezado */
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

        /* Inputs Estilizados (Caja Icono + Input) */
        .input-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }
        
        .input-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #555;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            display: flex;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            transition: 0.3s;
        }
        
        .input-wrapper:focus-within {
            border-color: var(--text-black);
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }

        .input-icon {
            background-color: var(--grey-light);
            width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            border-right: 1px solid #ddd;
        }

        .input-field {
            width: 100%;
            border: none;
            padding: 12px 15px;
            outline: none;
            font-size: 0.95rem;
            color: #333;
        }

        /* Botón Principal */
        .btn-login {
            width: 100%;
            background-color: var(--text-black); /* NEGRO DE MARCA */
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background-color: var(--accent-toast); /* NARANJA TOSTADO AL HOVER */
        }

        /* Separador */
        .divider {
            margin: 1.5rem 0;
            font-size: 0.8rem;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .divider::before, .divider::after {
            content: "";
            height: 1px;
            background: #eee;
            flex: 1;
        }

        /* Redes Sociales */
        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 2rem;
        }
        .social-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            border-radius: 4px; /* Cuadrado redondeado minimalista */
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            background: white;
            font-size: 0.9rem;
        }
        .social-btn:hover { border-color: var(--text-black); color: var(--text-black); }

        /* Links Footer */
        .footer-links {
            font-size: 0.85rem;
            color: #666;
        }
        .footer-links a {
            color: var(--text-black);
            font-weight: 600;
            text-decoration: none;
        }
        .footer-links a:hover { text-decoration: underline; }
        
        .alert {
            background: #ffecec;
            color: #d63031;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <div class="brand-title">FERMENTO.</div>
        <div class="brand-subtitle">Panadería Artesanal</div>

        <?php if($mensaje): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label class="input-label">Usuario / Correo</label>
                <div class="input-wrapper">
                    <div class="input-icon"><i class="fas fa-user"></i></div>
                    <input type="email" name="email" class="input-field" placeholder="ejemplo@correo.com" required>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Contraseña</label>
                <div class="input-wrapper">
                    <div class="input-icon"><i class="fas fa-key"></i></div>
                    <input type="password" name="password" class="input-field" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                Iniciar Sesión <i class="fas fa-arrow-right"></i>
            </button>
        </form>


        <div class="footer-links">
            <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
            <div style="margin-top: 10px;">
                <a href="index.php" style="color: #999; font-weight: normal; font-size: 0.8rem;">
                    <i class="fas fa-arrow-left"></i> Volver a la tienda
                </a>
            </div>
        </div>

    </div>

</body>
</html>