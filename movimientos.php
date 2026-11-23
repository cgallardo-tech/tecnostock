<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = (int)($_POST['id_producto'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $id_usuario = $_SESSION['id_usuario'];

    if ($id_producto <= 0 || !in_array($tipo, ['ENTRADA', 'SALIDA']) || $cantidad <= 0) {
        $mensaje = "Complete correctamente el producto, tipo de movimiento y una cantidad mayor a 0.";
        $tipo_alerta = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT nombre, stock_actual FROM producto WHERE id_producto = ? AND estado = 1 FOR UPDATE");
            $stmt->execute([$id_producto]);
            $prod = $stmt->fetch();

            if (!$prod) {
                throw new Exception("El producto seleccionado no existe o está inactivo.");
            }

            if ($tipo === 'SALIDA' && $cantidad > $prod['stock_actual']) {
                throw new Exception("Stock insuficiente. Disponible actual de '{$prod['nombre']}': {$prod['stock_actual']} unidades.");
            }

            $nuevo_stock = ($tipo === 'ENTRADA') ? ($prod['stock_actual'] + $cantidad) : ($prod['stock_actual'] - $cantidad);

            $stmtUpd = $pdo->prepare("UPDATE producto SET stock_actual = ? WHERE id_producto = ?");
            $stmtUpd->execute([$nuevo_stock, $id_producto]);

            $stmtMov = $pdo->prepare("INSERT INTO movimiento (tipo, cantidad, motivo, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)");
            $stmtMov = $pdo->prepare("INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)");
            $pdo->commit();
            $mensaje = "Movimiento registrado. Nuevo stock de '{$prod['nombre']}': {$nuevo_stock} unidades.";
            $tipo_alerta = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = $e->getMessage();
            $tipo_alerta = "danger";
        }
    }
}

$productos = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM producto WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();

// Filtro opcional de historial
$filtro_tipo = $_GET['tipo'] ?? '';
$sqlMov = "SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
           FROM movimiento m
           INNER JOIN producto p ON m.id_producto = p.id_producto
           INNER JOIN usuario u ON m.id_usuario = u.id_usuario
           WHERE 1=1";
$paramsMov = [];

if (in_array($filtro_tipo, ['ENTRADA', 'SALIDA'])) {
    $sqlMov .= " AND m.tipo = ?";
    $paramsMov[] = $filtro_tipo;
}
$sqlMov .= " ORDER BY m.id_movimiento DESC LIMIT 50";

$stmtHist = $pdo->prepare($sqlMov);
$stmtHist->execute($paramsMov);
$movimientos = $stmtHist->fetchAll();

require_once 'includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold text-dark mb-0">Entradas y Salidas de Inventario</h3>
    <p class="text-muted small mb-0">Registro de ingresos por compra, ajustes y despachos con trazabilidad</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show rounded-3 shadow-sm">
        <i class="bi <?= $tipo_alerta === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario de Movimiento -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-arrow-left-right text-primary me-2"></i>Registrar Movimiento
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" id="formMovimiento">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Producto *</label>
                        <select name="id_producto" id="id_producto" class="form-select" required>
                            <option value="">Seleccione un producto...</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id_producto'] ?>" data-stock="<?= $p['stock_actual'] ?>">
                                    [<?= htmlspecialchars($p['codigo']) ?>] <?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tipo de Movimiento *</label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="ENTRADA">ENTRADA (+ Aumentar Stock)</option>
                            <option value="SALIDA">SALIDA (- Descontar Stock)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Cantidad *</label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" placeholder="Ej: 5" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Motivo / Referencia</label>
                        <input type="text" name="motivo" class="form-control" placeholder="Ej: Compra proveedor, Venta #104..." maxlength="200">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Procesar Movimiento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Historial de Movimientos -->
    <div class="col-lg-8">
        <div class="card shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>Historial Reciente
                </h6>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="movimientos.php" class="btn <?= $filtro_tipo === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">Todos</a>
                    <a href="movimientos.php?tipo=ENTRADA" class="btn <?= $filtro_tipo === 'ENTRADA' ? 'btn-success' : 'btn-outline-success' ?>">Entradas</a>
                    <a href="movimientos.php?tipo=SALIDA" class="btn <?= $filtro_tipo === 'SALIDA' ? 'btn-danger' : 'btn-outline-danger' ?>">Salidas</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted border-bottom">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th>Motivo</th>
                            <th class="pe-3">Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                    No hay movimientos registrados para este filtro.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <?php $fecha_reg = $m['fecha'] ?? $m['fecha_movimiento'] ?? $m['fecha_hora'] ?? $m['created_at'] ?? null; ?>
<td class="ps-3 small text-muted"><?= $fecha_reg ? date('d/m/Y H:i', strtotime($fecha_reg)) : '#' . $m['id_movimiento'] ?></td>
                                    <td>
                                        <?php if ($m['tipo'] === 'ENTRADA'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="bi bi-arrow-down-left me-1"></i>ENTRADA
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                                <i class="bi bi-arrow-up-right me-1"></i>SALIDA
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary small"><?= htmlspecialchars($m['codigo']) ?></span>
                                        <div class="fw-semibold text-dark small"><?= htmlspecialchars($m['producto_nombre']) ?></div>
                                    </td>
                                    <td class="text-center fw-bold <?= $m['tipo'] === 'ENTRADA' ? 'text-success' : 'text-danger' ?>">
                                        <?= $m['tipo'] === 'ENTRADA' ? '+' : '-' ?><?= $m['cantidad'] ?>
                                    </td>
                                   <?php $texto_motivo = $m['motivo'] ?? $m['observacion'] ?? $m['detalle'] ?? $m['descripcion'] ?? ''; ?>
<td class="small text-muted"><?= htmlspecialchars($texto_motivo ?: '—') ?></td>
                                    <td class="pe-3 small text-secondary">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($m['usuario_nombre']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/validaciones.js"></script>
</body>
</html>