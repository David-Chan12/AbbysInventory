<?php
session_start();
require_once 'conexion.php';

// Obtener todos los productos
$query_productos = "SELECT * FROM producto";
$resultado_productos = $conn->query($query_productos);

// Obtener todos los insumos
$query_insumos = "SELECT * FROM insumo";
$resultado_insumos = $conn->query($query_insumos);

// Obtener estados para el select
$query_estados = "SELECT * FROM estadopro";
$resultado_estados = $conn->query($query_estados);

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Recoger datos del formulario para el producto
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $cantidad_disponible = $_POST['cantidad_disponible'];
        $estado = $_POST['estado'];
        $descripcion = $_POST['descripcion'];
        
        // Fecha actual para creación y actualización
        $fecha_actual = date('Y-m-d H:i:s');
        
        // Insertar o actualizar el producto
        if (isset($_POST['producto_id']) && !empty($_POST['producto_id'])) {
            // Actualizar producto existente
            $producto_id = $_POST['producto_id'];
            $sql_producto = "UPDATE producto SET nombre = ?, precio = ?, cantidad_disponible = ?, 
                            fecha_actualizacion = ?, estado = ?, descripcion = ? WHERE id = ?";
            $stmt_producto = $conn->prepare($sql_producto);
            $stmt_producto->bind_param("sddsisi", $nombre, $precio, $cantidad_disponible, $fecha_actual, $estado, $descripcion, $producto_id);
        } else {
            // Insertar nuevo producto
            $sql_producto = "INSERT INTO producto (nombre, precio, cantidad_disponible, fecha_creacion, fecha_actualizacion, estado, descripcion) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_producto = $conn->prepare($sql_producto);
            $stmt_producto->bind_param("sddssis", $nombre, $precio, $cantidad_disponible, $fecha_actual, $fecha_actual, $estado, $descripcion);
        }
        
        $stmt_producto->execute();
        
        // Si es un nuevo producto, obtener el ID generado
        if (!isset($producto_id)) {
            $producto_id = $conn->insert_id;
        } else {
            // Si es un producto existente, eliminar las relaciones anteriores
            $sql_delete = "DELETE FROM producto_insumo WHERE producto_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $producto_id);
            $stmt_delete->execute();
        }
        
        // Procesar los insumos seleccionados
        if (isset($_POST['insumo_id']) && isset($_POST['cantidad'])) {
            $insumo_ids = $_POST['insumo_id'];
            $cantidades = $_POST['cantidad'];
            
            // Preparar la consulta para insertar en producto_insumo
            $sql_rel = "INSERT INTO producto_insumo (producto_id, insumo_id, cantidad_requerida) VALUES (?, ?, ?)";
            $stmt_rel = $conn->prepare($sql_rel);
            
            // Preparar la consulta para actualizar el inventario de insumos
            $sql_update_insumo = "UPDATE insumo SET cantidad_disponible = cantidad_disponible - ? WHERE id = ?";
            $stmt_update_insumo = $conn->prepare($sql_update_insumo);
            
            for ($i = 0; $i < count($insumo_ids); $i++) {
                if (!empty($insumo_ids[$i]) && !empty($cantidades[$i]) && $cantidades[$i] > 0) {
                    // Insertar relación producto-insumo
                    $stmt_rel->bind_param("iid", $producto_id, $insumo_ids[$i], $cantidades[$i]);
                    $stmt_rel->execute();
                    
                    // Actualizar inventario de insumos
                    $stmt_update_insumo->bind_param("di", $cantidades[$i], $insumo_ids[$i]);
                    $stmt_update_insumo->execute();
                }
            }
        }
        
        // Confirmar la transacción
        $conn->commit();
        
        // Redirigir con mensaje de éxito
        header("Location: productos.php?success=1");
        exit();
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        $error = "Error al guardar el producto: " . $e->getMessage();
    }
}

// Si se está editando un producto existente
$producto = null;
$insumos_producto = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $producto_id = $_GET['id'];
    
    // Obtener datos del producto
    $query_producto = "SELECT * FROM producto WHERE id = ?";
    $stmt_producto = $conn->prepare($query_producto);
    $stmt_producto->bind_param("i", $producto_id);
    $stmt_producto->execute();
    $resultado_producto = $stmt_producto->get_result();
    
    if ($resultado_producto->num_rows > 0) {
        $producto = $resultado_producto->fetch_assoc();
        
        // Obtener insumos asociados al producto
        $query_insumos_producto = "SELECT pi.insumo_id, pi.cantidad_requerida, i.nombre 
                                  FROM producto_insumo pi 
                                  JOIN insumo i ON pi.insumo_id = i.id 
                                  WHERE pi.producto_id = ?";
        $stmt_insumos = $conn->prepare($query_insumos_producto);
        $stmt_insumos->bind_param("i", $producto_id);
        $stmt_insumos->execute();
        $resultado_insumos_producto = $stmt_insumos->get_result();
        
        while ($row = $resultado_insumos_producto->fetch_assoc()) {
            $insumos_producto[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Ginsumos.css">
    <title>Preparar Producto - Abby Cookies & Cakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .insumo-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .btn-add-insumo {
            background: rgba(160, 100, 80, 0.8);
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-remove-insumo {
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
        .insumos-container {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
        }
        .producto-select-container {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    
    <div class="panel-background">
        <div class="main-panel">
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

                <div class="top-bar">
                    <a href="ProductoM.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
                </div>
            </header>

            <h1 class="page-title"><?php echo isset($producto) ? 'Editar Producto' : 'Preparar Nuevo Producto'; ?></h1>
            <div class="report-container">
                <h2 class="section-title">FORMULARIO DE PREPARACIÓN</h2>

                <?php if(isset($error)): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="producto-select-container">
                    <h3>Seleccionar Producto Existente</h3>
                    <select id="producto_existente" style="padding: 10px; width: 100%; border-radius: 8px; border: 1px solid #ccc; font-family: 'Poppins';">
                        <option value="">Seleccione un producto o cree uno nuevo</option>
                        <?php while ($prod = mysqli_fetch_assoc($resultado_productos)): ?>
                            <option value="<?= $prod['id'] ?>" <?php echo (isset($producto) && $producto['id'] == $prod['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($prod['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="productoForm">
                    <?php if(isset($producto)): ?>
                        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                    <?php endif; ?>

                    <div style="margin-bottom: 15px;">
                        <label for="nombre" style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre del Producto:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo isset($producto) ? htmlspecialchars($producto['nombre']) : ''; ?>" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="precio" style="display: block; margin-bottom: 5px; font-weight: bold;">Precio:</label>
                        <input type="number" id="precio" name="precio" step="0.01" value="<?php echo isset($producto) ? htmlspecialchars($producto['precio']) : ''; ?>" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="cantidad_disponible" style="display: block; margin-bottom: 5px; font-weight: bold;">Cantidad Disponible:</label>
                        <input type="number" id="cantidad_disponible" name="cantidad_disponible" value="<?php echo isset($producto) ? htmlspecialchars($producto['cantidad_disponible']) : ''; ?>" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="estado" style="display: block; margin-bottom: 5px; font-weight: bold;">Estado:</label>
                        <select id="estado" name="estado" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            <?php 
                            // Reiniciar el puntero del resultado
                            $resultado_estados->data_seek(0);
                            while($estado = $resultado_estados->fetch_assoc()): 
                                $selected = (isset($producto) && $estado['id'] == $producto['estado']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $estado['id']; ?>" <?php echo $selected; ?>><?php echo $estado['descripcion']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="descripcion" style="display: block; margin-bottom: 5px; font-weight: bold;">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="4" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"><?php echo isset($producto) ? htmlspecialchars($producto['descripcion']) : ''; ?></textarea>
                    </div>

                    <div class="insumos-container">
                        <h3>Insumos Necesarios</h3>
                        <p>Seleccione los insumos y las cantidades necesarias para preparar este producto:</p>
                        
                        <div id="insumos-list">
                            <?php if(!empty($insumos_producto)): ?>
                                <?php foreach($insumos_producto as $index => $insumo_prod): ?>
                                    <div class="insumo-row">
                                        <select name="insumo_id[]" required style="flex: 2; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                                            <option value="">Seleccione un insumo</option>
                                            <?php 
                                            // Reiniciar el puntero del resultado
                                            $resultado_insumos->data_seek(0);
                                            while($insumo = $resultado_insumos->fetch_assoc()): 
                                                $selected = ($insumo['id'] == $insumo_prod['insumo_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $insumo['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($insumo['nombre']); ?> (<?php echo htmlspecialchars($insumo['cantidad_disponible']); ?> <?php echo htmlspecialchars($insumo['unidad_medida']); ?> disponibles)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="number" name="cantidad[]" placeholder="Cantidad" value="<?php echo htmlspecialchars($insumo_prod['cantidad_requerida']); ?>" step="0.01" min="0.01" required style="flex: 1; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                                        <button type="button" class="btn-remove-insumo" onclick="removeInsumo(this)"><i class="fas fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="insumo-row">
                                    <select name="insumo_id[]" required style="flex: 2; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                                        <option value="">Seleccione un insumo</option>
                                        <?php 
                                        // Reiniciar el puntero del resultado
                                        $resultado_insumos->data_seek(0);
                                        while($insumo = $resultado_insumos->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $insumo['id']; ?>">
                                                <?php echo htmlspecialchars($insumo['nombre']); ?> (<?php echo htmlspecialchars($insumo['cantidad_disponible']); ?> <?php echo htmlspecialchars($insumo['unidad_medida']); ?> disponibles)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <input type="number" name="cantidad[]" placeholder="Cantidad" step="0.01" min="0.01" required style="flex: 1; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                                    <button type="button" class="btn-remove-insumo" onclick="removeInsumo(this)"><i class="fas fa-times"></i></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="btn-add-insumo" onclick="addInsumo()">
                            <i class="fas fa-plus"></i> Agregar Insumo
                        </button>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" class="mostrardatos">
                            <?php echo isset($producto) ? 'Actualizar Producto' : 'Guardar Producto'; ?>
                        </button>
                        <a href="productos.php" class="mostrardatos" style="margin-left: 10px;">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Función para agregar una nueva fila de insumo
        function addInsumo() {
            const insumosList = document.getElementById('insumos-list');
            const insumoRow = document.createElement('div');
            insumoRow.className = 'insumo-row';
            
            // Clonar el primer select de insumos
            const firstSelect = document.querySelector('select[name="insumo_id[]"]');
            const newSelect = firstSelect.cloneNode(true);
            newSelect.value = ''; // Resetear el valor seleccionado
            
            insumoRow.innerHTML = `
                <select name="insumo_id[]" required style="flex: 2; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    ${newSelect.innerHTML}
                </select>
                <input type="number" name="cantidad[]" placeholder="Cantidad" step="0.01" min="0.01" required style="flex: 1; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                <button type="button" class="btn-remove-insumo" onclick="removeInsumo(this)"><i class="fas fa-times"></i></button>
            `;
            
            insumosList.appendChild(insumoRow);
        }
        
        // Función para eliminar una fila de insumo
        function removeInsumo(button) {
            const insumosList = document.getElementById('insumos-list');
            const insumoRow = button.parentNode;
            
            // Asegurarse de que siempre quede al menos una fila
            if (insumosList.children.length > 1) {
                insumosList.removeChild(insumoRow);
            }
        }
        
        // Cargar producto existente al seleccionarlo
        document.getElementById('producto_existente').addEventListener('change', function() {
            const productoId = this.value;
            if (productoId) {
                window.location.href = `preparar_producto.php?id=${productoId}`;
            } else {
                window.location.href = 'preparar_producto.php';
            }
        });
    </script>
</body>
</html>