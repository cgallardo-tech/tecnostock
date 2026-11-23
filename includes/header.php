<?php $pagina_actual = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Gestión de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .navbar-tecno {
            background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }
        .nav-link {
            font-weight: 500;
            border-radius: 0.5rem;
            padding: 0.45rem 0.85rem !important;
            transition: all 0.15s ease;
        }
        .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.08);
        }
        .nav-link.active-tab {
            color: #ffffff !important;
            background-color: rgba(37, 99, 235, 0.35);
            font-weight: 600;
        }
        .card {
            border: none;
            border-radius: 0.85rem;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-tecno mb-4 py-2.5">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="productos.php">
            <span class="bg-primary text-white rounded-3 px-2 py-1 me-2 d-inline-flex align-items-center">
                <i class="bi bi-cpu-fill"></i>
            </span>
            TecnoStock
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-lg-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= in_array($pagina_actual, ['productos.php', 'producto_form.php']) ? 'active-tab' : 'text-white-50' ?>" href="productos.php">
                        <i class="bi bi-box-seam me-1"></i> Catálogo
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $pagina_actual === 'categorias.php' ? 'active-tab' : 'text-white-50' ?>" href="categorias.php">
                        <i class="bi bi-tags me-1"></i> Categorías
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $pagina_actual === 'movimientos.php' ? 'active-tab' : 'text-white-50' ?>" href="movimientos.php">
                        <i class="bi bi-arrow-left-right me-1"></i> Entradas y Salidas
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                <span class="navbar-text text-light small me-2">
                    <i class="bi bi-person-circle me-1 text-primary"></i>
                    <?= htmlspecialchars($_SESSION['nombre']) ?>
                </span>
                <a href="cambiar_clave.php" class="btn btn-outline-warning btn-sm rounded-3 <?= $pagina_actual === 'cambiar_clave.php' ? 'active' : '' ?>" title="Configurar correo y contraseña">
    <i class="bi bi-person-gear"></i> Cuenta
</a>
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-3">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </div>
</nav>
<div class="container pb-5">