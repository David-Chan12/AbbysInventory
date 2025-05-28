<?php
session_start();
require_once 'conexion.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: productos.php");
    exit();
}

$id = $_GET['id'];

// Obtener estados para el select
$query_estados = "SELECT * FROM estadopro";
$resultado_estados = $conn->query($query_estados);

// Obtener datos del producto
$query_producto = "SELECT * FROM producto WHERE id = ?";
$stmt = $conn->prepare($query_producto);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado_producto = $stmt->get_result();

if ($resultado_producto->num_rows == 0) {
    header("Location: productos.php");
    exit();
}

$producto = $resultado_producto->fetch_assoc();

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $cantidad_disponible = $_POST['cantidad_disponible'];
    $estado = $_POST['estado'];
    $descripcion = $_POST['descripcion'];
    
    // Fecha actual para actualización
    $fecha_actual = date('Y-m-d H:i:s');
    
    // Preparar la consulta SQL
    $sql = "UPDATE producto SET nombre = ?, precio = ?, cantidad_disponible = ?, 
            fecha_actualizacion = ?, estado = ?, descripcion = ? WHERE id = ?";
    
    // Preparar la sentencia
    $stmt = $conn->prepare($sql);
    
    // Vincular parámetros
    $stmt->bind_param("sddsisi", $nombre, $precio, $cantidad_disponible, $fecha_actual, $estado, $descripcion, $id);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Redirigir a la página de productos con mensaje de éxito
        header("Location: productos.php?success=2");
        exit();
    } else {
        $error = "Error al actualizar el producto: " . $stmt->error;
    }
    
    // Cerrar la sentencia
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Ginsumos.css">
    <title>Editar Producto - Abby Cookies & Cakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                    <a href="productos.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
                </div>
            </header>

            <h1 class="page-title">Editar Producto</h1>
            <div class="report-container">
                <h2 class="section-title">FORMULARIO DE EDICIÓN</h2>

                <?php if(isset($error)): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" style="max-width: 600px; margin: 0 auto;">
                    <div style="margin-bottom: 15px;">
                        <label for="nombre" style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre del Producto:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="precio" style="display: block; margin-bottom: 5px; font-weight: bold;">Precio:</label>
                        <input type="number" id="precio" name="precio" step="0.01" value="<?php echo htmlspecialchars($producto['precio']); ?>" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="cantidad_disponible" style="display: block; margin-bottom: 5px; font-weight: bold;">Cantidad Disponible:</label>
                        <input type="number" id="cantidad_disponible" name="cantidad_disponible" value="<?php echo htmlspecialchars($producto['cantidad_disponible']); ?>" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="estado" style="display: block; margin-bottom: 5px; font-weight: bold;">Estado:</label>
                        <select id="estado" name="estado" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            <?php 
                            // Reiniciar el puntero del resultado
                            $resultado_estados->data_seek(0);
                            while($estado = $resultado_estados->fetch_assoc()): 
                                $selected = ($estado['id'] == $producto['estado']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $estado['id']; ?>" <?php echo $selected; ?>><?php echo $estado['descripcion']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="descripcion" style="display: block; margin-bottom: 5px; font-weight: bold;">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="4" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" class="mostrardatos">Actualizar Producto</button>
                        <a href="productos.php" class="mostrardatos" style="margin-left: 10px;">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>