<?php
session_start();
require_once 'conexion.php';

// Obtener todos los productos disponibles
$query_productos = "SELECT * FROM producto WHERE cantidad_disponible > 0 AND estado = 1";
$resultado_productos = $conn->query($query_productos);

// Inicializar variables
$productos_seleccionados = [];
$total_venta = 0;

// Procesar el formulario cuando se envía
if(isset($_POST['guardarVenta'])){
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Obtener datos del formulario
        $fecha = isset($_POST['fecha']) && $_POST['fecha'] != '' ? $_POST['fecha'] : date('Y-m-d H:i:s');
        $total = $_POST['total'];
        $metodo_pago = $_POST['metodo_pago'];
        $descripcion = $_POST['descripcion'];
        
        // Insertar la venta
        $sql_venta = "INSERT INTO ventas (fecha, total, metodo_pago, descripcion) 
                      VALUES (?, ?, ?, ?)";
        $stmt_venta = $conn->prepare($sql_venta);
        $stmt_venta->bind_param("sdss", $fecha, $total, $metodo_pago, $descripcion);
        $stmt_venta->execute();
        
        // Obtener el ID de la venta insertada
        $venta_id = $conn->insert_id;
        
        // Procesar los productos vendidos
        if (isset($_POST['producto_id']) && isset($_POST['cantidad'])) {
            $producto_ids = $_POST['producto_id'];
            $cantidades = $_POST['cantidad'];
            
            // Preparar la consulta para insertar en detalle_venta
            $sql_detalle = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            
            // Preparar la consulta para actualizar el inventario de productos
            $sql_update_producto = "UPDATE producto SET cantidad_disponible = cantidad_disponible - ? 
                                   WHERE id = ?";
            $stmt_update_producto = $conn->prepare($sql_update_producto);
            
            for ($i = 0; $i < count($producto_ids); $i++) {
                if (!empty($producto_ids[$i]) && !empty($cantidades[$i]) && $cantidades[$i] > 0) {
                    // Obtener el precio del producto
                    $sql_precio = "SELECT precio FROM producto WHERE id = ?";
                    $stmt_precio = $conn->prepare($sql_precio);
                    $stmt_precio->bind_param("i", $producto_ids[$i]);
                    $stmt_precio->execute();
                    $resultado_precio = $stmt_precio->get_result();
                    $precio = $resultado_precio->fetch_assoc()['precio'];
                    
                    // Calcular subtotal
                    $subtotal = $precio * $cantidades[$i];
                    
                    // Insertar detalle de venta
                    $stmt_detalle->bind_param("iiddd", $venta_id, $producto_ids[$i], $cantidades[$i], $precio, $subtotal);
                    $stmt_detalle->execute();
                    
                    // Actualizar inventario de productos
                    $stmt_update_producto->bind_param("ii", $cantidades[$i], $producto_ids[$i]);
                    $stmt_update_producto->execute();
                }
            }
        }
        
        // Confirmar la transacción
        $conn->commit();
        
        // Redirigir con mensaje de éxito
        echo "<script language='JavaScript'>
            alert('¡¡Los datos fueron ingresados correctamente!!');
            location.assign('ventas.php');
            </script>";
        exit();
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        echo "<script language='JavaScript'>
            alert('¡¡ERROR!! Los datos NO fueron ingresados: " . $e->getMessage() . "');
            location.assign('ventas.php');
            </script>";
    }
} 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/agreinsu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Nueva Venta - Abby Cookies & Cakes</title>
    <style>
        .producto-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .btn-add-producto {
            background: rgba(160, 100, 80, 0.8);
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-remove-producto {
            background: #e74c3c;
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .productos-container {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
        }
        .subtotal {
            font-weight: bold;
            margin-left: 10px;
        }
        .total-container {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- sidebar -->
    <div id="sidebar-placeholder"></div>
    <script>
        fetch('sidebar_i.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('sidebar-placeholder').innerHTML = data;
            });
    </script>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="menu-container">
        <header class="menu-header">
            <h1 class="logo">Abby's Cookies & Cakes</h1>
            <div class="user-info">
                <p class="user-email">usuario@abby.com</p>
                <button class="notification-btn" onclick="window.location.href='Alertas_y_notificaciones.php'">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <button class="logout-btn" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </header>

        <h2 class="page-title">Nueva Venta</h2>

        <div class="form-container">
            <div class="form-header">
                <h3 class="form-title">Información de ventas</h3>
            </div>

            <form action="<?=$_SERVER['PHP_SELF']?>" method="post" id="ventaForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id" class="form-label">ID</label>
                        <input type="text" id="id" name="id" class="form-input" disabled placeholder="Asignado automáticamente">
                        <p class="form-note">Este campo se generará automáticamente</p>
                    </div>

                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha de registro</label>
                        <input type="datetime-local" id="fecha" name="fecha" class="form-input" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="metodo_pago" class="form-label">Método de pago*</label>
                        <select id="metodo_pago" name="metodo_pago" class="form-input" required>
                            <option value="">Seleccione un método de pago</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta de crédito/débito</option>
                            <option value="transferencia">Transferencia bancaria</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="4" cols="50" class="form-input" placeholder="Detalles adicionales de la venta"></textarea>
                    </div>
                </div>

                <div class="productos-container">
                    <h3>Productos</h3>
                    <p>Seleccione los productos y las cantidades para esta venta:</p>
                    
                    <div id="productos-list">
                        <div class="producto-row">
                            <select name="producto_id[]" class="producto-select" required style="flex: 2; padding: 8px; border-radius: 4px; border: 1px solid #ccc;" onchange="actualizarPrecio(this)">
                                <option value="">Seleccione un producto</option>
                                <?php 
                                // Reiniciar el puntero del resultado
                                $resultado_productos->data_seek(0);
                                while($producto = $resultado_productos->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $producto['id']; ?>" data-precio="<?php echo $producto['precio']; ?>" data-disponible="<?php echo $producto['cantidad_disponible']; ?>">
                                        <?php echo htmlspecialchars($producto['nombre']); ?> - $<?php echo htmlspecialchars($producto['precio']); ?> (<?php echo htmlspecialchars($producto['cantidad_disponible']); ?> disponibles)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="number" name="cantidad[]" placeholder="Cantidad" min="1" required style="flex: 1; padding: 8px; border-radius: 4px; border: 1px solid #ccc;" onchange="calcularSubtotal(this)" onkeyup="calcularSubtotal(this)">
                            <span class="subtotal">$0.00</span>
                            <button type="button" class="btn-remove-producto" onclick="removeProducto(this)"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-add-producto" onclick="addProducto()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                </div>

                <div class="total-container">
                    <span>Total: $</span>
                    <span id="total-display">0.00</span>
                    <input type="hidden" id="total" name="total" value="0">
                </div>

                <div class="form-actions">
                    <button class="btn btn-secondary" type="button" onclick="window.location.href='ventas.php'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button class="btn btn-primary" type="submit" name="guardarVenta">
                        <i class="fas fa-save"></i> Guardar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función para agregar una nueva fila de producto
        function addProducto() {
            const productosList = document.getElementById('productos-list');
            const productoRow = document.createElement('div');
            productoRow.className = 'producto-row';
            
            // Clonar el primer select de productos
            const firstSelect = document.querySelector('select[name="producto_id[]"]');
            const newSelect = firstSelect.cloneNode(true);
            newSelect.value = ''; // Resetear el valor seleccionado
            
            productoRow.innerHTML = `
                <select name="producto_id[]" class="producto-select" required style="flex: 2; padding: 8px; border-radius: 4px; border: 1px solid #ccc;" onchange="actualizarPrecio(this)">
                    ${newSelect.innerHTML}
                </select>
                <input type="number" name="cantidad[]" placeholder="Cantidad" min="1" required style="flex: 1; padding: 8px; border-radius: 4px; border: 1px solid #ccc;" onchange="calcularSubtotal(this)" onkeyup="calcularSubtotal(this)">
                <span class="subtotal">$0.00</span>
                <button type="button" class="btn-remove-producto" onclick="removeProducto(this)"><i class="fas fa-times"></i></button>
            `;
            
            productosList.appendChild(productoRow);
        }
        
        // Función para eliminar una fila de producto
        function removeProducto(button) {
            const productosList = document.getElementById('productos-list');
            const productoRow = button.parentNode;
            
            // Asegurarse de que siempre quede al menos una fila
            if (productosList.children.length > 1) {
                productosList.removeChild(productoRow);
                calcularTotal();
            }
        }
        
        // Función para actualizar el precio cuando se selecciona un producto
        function actualizarPrecio(selectElement) {
            const row = selectElement.parentNode;
            const cantidadInput = row.querySelector('input[name="cantidad[]"]');
            const subtotalSpan = row.querySelector('.subtotal');
            
            if (selectElement.value) {
                const option = selectElement.options[selectElement.selectedIndex];
                const precio = parseFloat(option.dataset.precio);
                const disponible = parseInt(option.dataset.disponible);
                
                // Actualizar el máximo de cantidad disponible
                cantidadInput.max = disponible;
                
                // Calcular subtotal si ya hay una cantidad
                if (cantidadInput.value) {
                    const cantidad = parseInt(cantidadInput.value);
                    const subtotal = precio * cantidad;
                    subtotalSpan.textContent = '$' + subtotal.toFixed(2);
                }
            } else {
                subtotalSpan.textContent = '$0.00';
            }
            
            calcularTotal();
        }
        
        // Función para calcular el subtotal cuando cambia la cantidad
        function calcularSubtotal(inputElement) {
            const row = inputElement.parentNode;
            const selectElement = row.querySelector('select[name="producto_id[]"]');
            const subtotalSpan = row.querySelector('.subtotal');
            
            if (selectElement.value && inputElement.value) {
                const option = selectElement.options[selectElement.selectedIndex];
                const precio = parseFloat(option.dataset.precio);
                const cantidad = parseInt(inputElement.value);
                const disponible = parseInt(option.dataset.disponible);
                
                // Validar que la cantidad no exceda el disponible
                if (cantidad > disponible) {
                    alert(`Solo hay ${disponible} unidades disponibles de este producto.`);
                    inputElement.value = disponible;
                }
                
                const subtotal = precio * Math.min(cantidad, disponible);
                subtotalSpan.textContent = '$' + subtotal.toFixed(2);
            } else {
                subtotalSpan.textContent = '$0.00';
            }
            
            calcularTotal();
        }
        
        // Función para calcular el total de la venta
        function calcularTotal() {
            const subtotales = document.querySelectorAll('.subtotal');
            let total = 0;
            
            subtotales.forEach(span => {
                const subtotal = parseFloat(span.textContent.replace('$', '')) || 0;
                total += subtotal;
            });
            
            document.getElementById('total-display').textContent = total.toFixed(2);
            document.getElementById('total').value = total;
        }
        
        // Validar el formulario antes de enviar
        document.getElementById('ventaForm').addEventListener('submit', function(e) {
            const total = parseFloat(document.getElementById('total').value);
            
            if (total <= 0) {
                e.preventDefault();
                alert('El total de la venta debe ser mayor a cero. Por favor, agregue productos a la venta.');
            }
        });
    </script>
</body>
</html>