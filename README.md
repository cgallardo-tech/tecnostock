# TecnoStock - Sistema de Control de Inventario

## 1. Análisis y Requerimientos
* **Problema:** TecnoStock gestiona su inventario de accesorios tecnológicos mediante planillas manuales, generando códigos duplicados y desfase en el stock disponible.
* **Alcance y Actor:** Sistema web local operado por el **Encargado de Inventario** para administrar el catálogo de productos y registrar movimientos de entrada y salida.
* **Requerimientos Funcionales (RF):**
  * RF1: Autenticación de usuarios registrados.
  * RF2: CRUD de productos con código único y eliminación lógica (desactivación).
  * RF3: Búsqueda y filtrado por nombre, código o categoría.
  * RF4: Registro de entradas y salidas con actualización automática de stock.
  * RF5: Alerta visual cuando `stock_actual < stock_minimo`.

## 2. Casos de Prueba Verificados
| ID | Escenario | Datos de Entrada | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- |
| CP-01 | Login válido | `admin@tecnostock.cl` / `admin123` | Redirección al catálogo | Aprobado |
| CP-02 | Código duplicado | Código `TEC-001` en producto nuevo | Bloqueo y mensaje de error | Aprobado |
| CP-03 | Valores negativos | Precio `-500` o Stock `-2` | Validación JS/PHP impide guardar | Aprobado |
| CP-04 | Salida mayor a stock | Salida de `50` u. cuando hay `3` u. | Transacción revertida (Rollback) | Aprobado |
| CP-05 | Alerta stock bajo | Stock actual `3`, Stock mínimo `5` | Fila resaltada + badge `¡Stock Bajo!` | Aprobado |
| CP-06 | Desactivación | Clic en Desactivar producto | `estado = 0`, conserva historial | Aprobado |