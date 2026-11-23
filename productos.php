<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

// Desactivar / Activar producto (eliminación lógica)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE producto SET estado = NOT estado WHERE id_producto = ?");
    $stmt->execute([$id]);
    header("Location: productos.php?msg=estado");
    exit();
}

// Calcular indicadores (KPIs) globales de productos activos
$stmtKpi = $pdo->query("
    SELECT 
        COUNT(*) AS total_activos,
        SUM(CASE WHEN stock_actual < stock_minimo THEN 1 ELSE 0 END) AS bajo_stock,
        COALESCE(SUM(precio * stock_actual), 0) AS valor_total
    FROM producto 
    WHERE estado = 1
");
$kpis = $stmtKpi->fetch();

// Filtros de búsqueda
$busqueda = trim($_GET['q'] ?? '');
$cat_filtro = $_GET['cat'] ?? '';
$solo_bajo_stock = isset($_GET['bajo_stock']) ? 1 : 0;

$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM producto p 
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria 
        WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($cat_filtro !== '' && is_numeric($cat_filtro)) {
    $sql .= " AND p.id_categoria = ?";
    $params[] = $cat_filtro;
}
if ($solo_bajo_stock) {
    $sql .= " AND p.stock_actual < p.stock_minimo AND p.estado = 1";
}

$sql .= " ORDER BY p.estado DESC, p.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

$categorias = $pdo->query("SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre")->fetchAll();

require_once 'includes/header.php';
?>

<!-- Encabezado de página -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">Catálogo de Productos</h3>
        <p class="text-muted small mb-0">Gestión de existencias, precios y alertas de reposición</p>
    </div>
    <a href="producto_form.php" class="btn btn-primary rounded-3 shadow-sm fw-semibold">
        <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php
            if ($_GET['msg'] === 'guardado') echo "Producto guardado correctamente.";
            elseif ($_GET['msg'] === 'estado') echo "Estado del producto actualizado.";
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tarjetas KPI -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Productos Activos</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= (int)$kpis['total_activos'] ?></div>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="bi bi-box-seam fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Alertas Stock Bajo</div>
                    <div class="fs-3 fw-bold <?= $kpis['bajo_stock'] > 0 ? 'text-danger' : 'text-dark' ?> mt-1">
                        <?= (int)$kpis['bajo_stock'] ?>
                    </div>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                    <i class="bi bi-exclamation-triangle fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-success border-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Valorización Inventario</div>
                    <div class="fs-3 fw-bold text-dark mt-1">$<?= number_format($kpis['valor_total'], 0, ',', '.') ?></div>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-start-0 ps-0" 
                           placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="cat" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id_categoria'] ?>" <?= $cat_filtro == $c['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="bajo_stock" id="bajo_stock" value="1" <?= $solo_bajo_stock ? 'checked' : '' ?>>
                    <label class="form-check-label text-danger fw-semibold small" for="bajo_stock">
                        <i class="bi bi-funnel-fill me-1"></i>Solo stock bajo mínimo
                    </label>
                </div>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-dark w-100 rounded-3">Filtrar</button>
                <a href="productos.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de productos -->
<div class="card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small text-muted border-bottom">
                <tr>
                    <th class="ps-3">Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock Actual</th>
                    <th>Mínimo</th>
                    <th>Estado</th>
                    <th class="text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No se encontraron productos con los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): 
                        $alerta_stock = ($p['estado'] == 1 && $p['stock_actual'] < $p['stock_minimo']);
                    ?>
                        <tr class="<?= $alerta_stock ? 'table-warning' : ($p['estado'] == 0 ? 'table-secondary text-muted' : '') ?>">
                            <td class="ps-3 fw-bold text-primary"><?= htmlspecialchars($p['codigo']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($p['nombre']) ?></div>
                                <?php if (!empty($p['descripcion'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 240px;">
                                        <?= htmlspecialchars($p['descripcion']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($p['categoria_nombre']) ?>
                                </span>
                            </td>
                            <td class="fw-semibold">$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                            <td>
                                <span class="fw-bold fs-6"><?= $p['stock_actual'] ?></span>
                                <?php if ($alerta_stock): ?>
                                    <span class="badge bg-danger ms-1">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>¡Stock Bajo!
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= $p['stock_minimo'] ?></td>
                            <td>
                                <?php if ($p['estado'] == 1): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i>Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                                        Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <a href="producto_form.php?id=<?= $p['id_producto'] ?>" 
                                   class="btn btn-sm btn-outline-primary rounded-3" title="Editar producto">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <a href="productos.php?toggle=<?= $p['id_producto'] ?>" 
                                   class="btn btn-sm <?= $p['estado'] == 1 ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-3"
                                   onclick="return confirm('¿Confirma cambiar el estado de este producto?')"
                                   title="<?= $p['estado'] == 1 ? 'Desactivar producto' : 'Activar producto' ?>">
                                    <i class="bi <?= $p['estado'] == 1 ? 'bi-slash-circle' : 'bi-check-lg' ?>"></i>
                                    <?= $p['estado'] == 1 ? 'Desactivar' : 'Activar' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>