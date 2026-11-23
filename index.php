<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['id_usuario'])) {
    header("Location: productos.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '') {
        $error = "Complete todos los campos.";
    } else {
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, clave, estado FROM usuario WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && $usuario['estado'] == 1 && password_verify($clave, $usuario['clave'])) {
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            header("Location: productos.php");
            exit();
        } else {
            $error = "Credenciales incorrectas o usuario inactivo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border-radius: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background-color: #ffffff;
        }
        .brand-badge {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.35);
        }
    </style>
</head>
<body class="d-flex flex-column justify-content-center align-items-center vh-100 px-3">

    <div class="card login-card shadow-lg p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="brand-badge">
                <i class="bi bi-cpu-fill fs-2"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">TecnoStock</h3>
            <p class="text-muted small mb-0">Sistema de Gestión de Inventario</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center small py-2 rounded-3" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2 fs-6"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="correo" class="form-control border-start-0 ps-0" 
       placeholder="usuario@tecnostock.cl" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-secondary">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="clave" class="form-control border-start-0 ps-0" 
                           placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100 text-white">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
            </button>
        </form>
    </div>

    <div class="text-center mt-4">
        <small class="text-white-50">&copy; <?= date('Y') ?> TecnoStock &bull; Control de Inventario Tecnológico</small>
    </div>

</body>
</html>