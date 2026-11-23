<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

$mensaje = '';
$tipo_alerta = '';
$id_usuario = $_SESSION['id_usuario'];

// Obtener datos actuales del usuario
$stmtUser = $pdo->prepare("SELECT nombre, correo, clave FROM usuario WHERE id_usuario = ?");
$stmtUser->execute([$id_usuario]);
$usuario_actual = $stmtUser->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_correo = trim($_POST['correo'] ?? '');
    $clave_actual = $_POST['clave_actual'] ?? '';
    $clave_nueva = $_POST['clave_nueva'] ?? '';
    $clave_confirmar = $_POST['clave_confirmar'] ?? '';

    if ($nuevo_correo === '' || $clave_actual === '') {
        $mensaje = "El correo electrónico y la contraseña actual son obligatorios.";
        $tipo_alerta = "danger";
    } elseif (!filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingrese un formato de correo electrónico válido.";
        $tipo_alerta = "danger";
    } elseif (!$usuario_actual || !password_verify($clave_actual, $usuario_actual['clave'])) {
        $mensaje = "La contraseña actual ingresada es incorrecta.";
        $tipo_alerta = "danger";
    } elseif ($clave_nueva !== '' && strlen($clave_nueva) < 6) {
        $mensaje = "Si desea cambiar la contraseña, debe tener al menos 6 caracteres.";
        $tipo_alerta = "danger";
    } elseif ($clave_nueva !== $clave_confirmar) {
        $mensaje = "La nueva contraseña y su confirmación no coinciden.";
        $tipo_alerta = "danger";
    } else {
        try {
            if ($clave_nueva !== '') {
                // Actualizar correo y contraseña
                $nuevo_hash = password_hash($clave_nueva, PASSWORD_DEFAULT);
                $stmtUpd = $pdo->prepare("UPDATE usuario SET correo = ?, clave = ? WHERE id_usuario = ?");
                $stmtUpd->execute([$nuevo_correo, $nuevo_hash, $id_usuario]);
                $mensaje = "Correo electrónico y contraseña actualizados correctamente.";
            } else {
                // Actualizar solo el correo electrónico
                $stmtUpd = $pdo->prepare("UPDATE usuario SET correo = ? WHERE id_usuario = ?");
                $stmtUpd->execute([$nuevo_correo, $id_usuario]);
                $mensaje = "Correo electrónico actualizado correctamente.";
            }

            $tipo_alerta = "success";
            $usuario_actual['correo'] = $nuevo_correo;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "Ese correo electrónico ya está registrado por otro usuario.";
            } else {
                $mensaje = "Ocurrió un error al actualizar los datos.";
            }
            $tipo_alerta = "danger";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="mb-3">
            <a href="productos.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Volver al catálogo
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-2 me-3">
                    <i class="bi bi-person-gear fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Configuración de Cuenta</h5>
                    <small class="text-muted">Actualiza tu correo de acceso o contraseña</small>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_alerta ?> rounded-3">
                        <i class="bi <?= $tipo_alerta === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <?= htmlspecialchars($mensaje) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Correo Electrónico *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="correo" class="form-control" required 
                                   value="<?= htmlspecialchars($usuario_actual['correo'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Contraseña Actual (para confirmar) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" name="clave_actual" class="form-control" placeholder="Ingresa tu clave actual" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle me-1"></i>Deja los siguientes campos en blanco si solo deseas cambiar tu correo:
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nueva Contraseña (opcional)</label>
                        <input type="password" name="clave_nueva" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Confirmar Nueva Contraseña</label>
                        <input type="password" name="clave_confirmar" class="form-control" minlength="6" placeholder="Repite la nueva contraseña">
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="productos.php" class="btn btn-light border rounded-3 px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>