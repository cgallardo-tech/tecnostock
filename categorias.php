<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '') {
        $mensaje = "El nombre de la categoría es obligatorio.";
        $tipo_alerta = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categoria (nombre, descripcion) VALUES (?, ?)");
            $stmt->execute([$nombre, $descripcion]);
            $mensaje = "Categoría agregada correctamente.";
            $tipo_alerta = "success";
        } catch (PDOException $e) {
            $mensaje = "Ya existe una categoría con ese nombre.";
            $tipo_alerta = "danger";
        }
    }
}

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE categoria SET estado = NOT estado WHERE id_categoria = ?");
    $stmt->execute([$id]);
    header("Location: categorias.php?msg=estado");
    exit();
}

$categorias = $pdo->query("
    SELECT c.*, COUNT(p.id_producto) AS total_productos 
    FROM categoria c 
    LEFT JOIN producto p ON c.id_categoria = p.id_categoria AND p.estado = 1
    GROUP BY c.id_categoria 
    ORDER BY c.estado DESC, c.nombre ASC
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold text-dark mb-0">Categorías de Inventario</h3>
    <p class="text-muted small mb-0">Organización y clasificación de líneas de productos</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show rounded-3 shadow-sm">
        <i class="bi <?= $tipo_alerta === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'estado'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i>Estado de la categoría actualizado.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario Nueva Categoría -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Nueva Categoría
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Redes y Conectividad" required maxlength="80">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3" placeholder="Breve detalle de los artículos..." maxlength="255"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">
                        <i class="bi bi-save me-1"></i> Guardar Categoría
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Listado de Categorías -->
    <div class="col-md-8">
        <div class="card shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-tags text-primary me-2"></i>Categorías Registradas
                </h6>
                <span class="badge bg-light text-dark border"><?= count($categorias) ?> en total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted border-bottom">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center">Productos</th>
                            <th>Estado</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $c): ?>
                            <tr class="<?= $c['estado'] == 0 ? 'table-secondary text-muted' : '' ?>">
                                <td class="ps-3 fw-semibold text-dark"><?= htmlspecialchars($c['nombre']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['descripcion'] ?: '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5">
                                        <?= (int)$c['total_productos'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($c['estado'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                                            Inactiva
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="categorias.php?toggle=<?= $c['id_categoria'] ?>" 
                                       class="btn btn-sm <?= $c['estado'] == 1 ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-3"
                                       onclick="return confirm('¿Confirma cambiar el estado de esta categoría?')">
                                        <i class="bi <?= $c['estado'] == 1 ? 'bi-slash-circle' : 'bi-check-lg' ?>"></i>
                                        <?= $c['estado'] == 1 ? 'Desactivar' : 'Activar' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>