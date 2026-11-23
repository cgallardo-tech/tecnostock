<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$producto = [
    'codigo' => '', 'nombre' => '', 'descripcion' => '',
    'precio' => '', 'stock_actual' => 0, 'stock_minimo' => 5, 'id_categoria' => ''
];
$errores = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM producto WHERE id_producto = ?");
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if ($encontrado) $producto = $encontrado;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? -1);
    $stock_actual = (int)($_POST['stock_actual'] ?? -1);
    $stock_minimo = (int)($_POST['stock_minimo'] ?? -1);
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);

    if ($codigo === '' || $nombre === '' || $id_categoria <= 0) {
        $errores[] = "Código, nombre y categoría son obligatorios.";
    }
    if ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $errores[] = "El precio, stock actual y stock mínimo no pueden ser negativos.";
    }

    if (empty($errores)) {
        try {
            if ($id > 0) {
                $sql = "UPDATE producto SET codigo=?, nombre=?, descripcion=?, precio=?, stock_minimo=?, id_categoria=? WHERE id_producto=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_minimo, $id_categoria, $id]);
            } else {
                $sql = "INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria]);
            }
            header("Location: productos.php?msg=guardado");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errores[] = "El código de producto '$codigo' ya está registrado.";
            } else {
                $errores[] = "Error al guardar el producto.";
            }
        }
    }
    $producto = compact('codigo', 'nombre', 'descripcion', 'precio', 'stock_actual', 'stock_minimo', 'id_categoria');
}

$categorias = $pdo->query("SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre")->fetchAll();
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="mb-3">
            <a href="productos.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Volver al catálogo
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3">
                    <i class="bi <?= $id > 0 ? 'bi-pencil-square' : 'bi-box-seam' ?> fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0"><?= $id > 0 ? 'Editar Producto' : 'Registrar Nuevo Producto' ?></h5>
                    <small class="text-muted">Complete la ficha técnica y parámetros de stock</small>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger rounded-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errores as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formProducto">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Código SKU *</label>
                            <input type="text" name="codigo" class="form-control text-uppercase" placeholder="Ej: MON-002" required maxlength="30"
                                   value="<?= htmlspecialchars($producto['codigo']) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Nombre del Producto *</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Monitor IPS 27 pulgadas 144Hz" required maxlength="120"
                                   value="<?= htmlspecialchars($producto['nombre']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Descripción / Especificaciones</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles técnicos opcionales..."><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Categoría *</label>
                            <select name="id_categoria" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id_categoria'] ?>" <?= $producto['id_categoria'] == $c['id_categoria'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Precio Unitario ($) *</label>
                            <input type="number" name="precio" id="precio" class="form-control" step="1" min="0" required
                                   value="<?= htmlspecialchars($producto['precio']) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold text-secondary">Stock Inicial *</label>
                            <input type="number" name="stock_actual" id="stock_actual" class="form-control" min="0" required
                                   <?= $id > 0 ? 'readonly title="El stock se modifica desde Entradas y Salidas"' : '' ?>
                                   value="<?= htmlspecialchars($producto['stock_actual']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Stock Mínimo *</label>
                            <input type="number" name="stock_minimo" id="stock_minimo" class="form-control" min="0" required
                                   value="<?= htmlspecialchars($producto['stock_minimo']) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="productos.php" class="btn btn-light border rounded-3 px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Guardar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/validaciones.js"></script>
</body>
</html>