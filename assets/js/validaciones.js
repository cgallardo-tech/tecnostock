document.addEventListener('DOMContentLoaded', () => {
    // 1. Validación del formulario de Producto (producto_form.php)
    const formProducto = document.getElementById('formProducto');
    if (formProducto) {
        formProducto.addEventListener('submit', (e) => {
            const precio = parseFloat(document.getElementById('precio').value);
            const stockActual = parseInt(document.getElementById('stock_actual').value, 10);
            const stockMinimo = parseInt(document.getElementById('stock_minimo').value, 10);

            if (isNaN(precio) || precio < 0) {
                e.preventDefault();
                alert('El precio unitario no puede ser negativo.');
                return;
            }
            if (isNaN(stockActual) || stockActual < 0) {
                e.preventDefault();
                alert('El stock inicial no puede ser negativo.');
                return;
            }
            if (isNaN(stockMinimo) || stockMinimo < 0) {
                e.preventDefault();
                alert('El stock mínimo no puede ser negativo.');
                return;
            }
        });
    }

    // 2. Validación del formulario de Entradas y Salidas (movimientos.php)
    const formMovimiento = document.getElementById('formMovimiento');
    if (formMovimiento) {
        formMovimiento.addEventListener('submit', (e) => {
            const selectProducto = document.getElementById('id_producto');
            const tipo = document.getElementById('tipo').value;
            const cantidad = parseInt(document.getElementById('cantidad').value, 10);

            if (!selectProducto.value) {
                e.preventDefault();
                alert('Debe seleccionar un producto.');
                return;
            }

            if (isNaN(cantidad) || cantidad <= 0) {
                e.preventDefault();
                alert('La cantidad del movimiento debe ser mayor a 0.');
                return;
            }

            // Verificar stock disponible en cliente cuando es SALIDA
            if (tipo === 'SALIDA') {
                const opcionSeleccionada = selectProducto.options[selectProducto.selectedIndex];
                const stockDisponible = parseInt(opcionSeleccionada.getAttribute('data-stock'), 10);

                if (!isNaN(stockDisponible) && cantidad > stockDisponible) {
                    e.preventDefault();
                    alert(`Stock insuficiente. Solo hay ${stockDisponible} unidades disponibles para este producto.`);
                    return;
                }
            }
        });
    }
});