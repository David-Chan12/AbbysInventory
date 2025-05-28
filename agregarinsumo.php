<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/agreinsu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Nuevo insumo - Abby Cookies & Cakes</title>
</head>
<body>
    <!-- sidebar -->
    <div id="sidebar-placeholder"></div>
    <script>
      fetch('sidebar_i.php')
        .then(response => response.text())
        .then(data => {
          document.getElementById('sidebar-placeholder').innerHTML = data;
        });
    </script>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <?php
    session_start();
    require_once 'conexion.php'; // Asegúrate de que este archivo existe y tiene la conexión correcta

    if(isset($_POST['guardarIn'])) {
        // Recuperar datos del formulario
        $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
        $cantidad_disponible = mysqli_real_escape_string($conn, $_POST['cantidad_disponible']);
        $unidad_medida = mysqli_real_escape_string($conn, $_POST['unidad_medida']);
        $proveedor = mysqli_real_escape_string($conn, $_POST['proveedor']);
        $fecha_ingreso = isset($_POST['fecha_ingreso']) && $_POST['fecha_ingreso'] != '' ? 
                          mysqli_real_escape_string($conn, $_POST['fecha_ingreso']) : 
                          date('Y-m-d H:i:s');
        $fecha_caducidad = mysqli_real_escape_string($conn, $_POST['fecha_caducidad']);
        $costo_unitario = mysqli_real_escape_string($conn, $_POST['costo_unitario']);
        $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);

        // Query SQL con valores escapados
        $sql = "INSERT INTO insumo (nombre, cantidad_disponible, unidad_medida, proveedor, fecha_ingreso, fecha_caducidad, costo_unitario, descripcion) 
                VALUES ('$nombre', '$cantidad_disponible', '$unidad_medida', '$proveedor', '$fecha_ingreso', '$fecha_caducidad', '$costo_unitario', '$descripcion')";
        
        // Ejecutar consulta
        $resultado = $conn->query($sql);
        if($resultado) {
            echo "<script language='JavaScript'>
                    alert('¡¡Los datos fueron ingresados correctamente!!');
                    location.assign('Ginsumos.php');
                  </script>";
        } else {
            echo "<script language='JavaScript'>
                    alert('¡¡ERROR!! Los datos NO fueron ingresados. Error: " . addslashes(mysqli_error($conn)) . "');
                    location.assign('Ginsumos.php');
                  </script>";
        }
        
        // Cerrar conexión (opcional, se cierra automáticamente al final del script)
        mysqli_close($conn);
    } else { 
    ?>

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

        <h2 class="page-title">Nuevo Insumo</h2>
        <p class="slogan">Agrega un nuevo insumo a tu inventario.</p>

        <div class="form-container">
            <div class="form-header">
                <h3 class="form-title">Información del Insumo</h3>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id" class="form-label">ID</label>
                        <input type="text" id="id" name="id" class="form-input" disabled placeholder="Asignado automáticamente">
                        <p class="form-note">Este campo se generará automáticamente</p>
                    </div>

                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre del insumo*</label>
                        <input type="text" id="nombre" name="nombre" class="form-input" required placeholder="Ej. Harina">
                    </div>

                    <div class="form-group">
                        <label for="cantidad_disponible" class="form-label">Cantidad disponible*</label>
                        <input type="number" id="cantidad_disponible" name="cantidad_disponible" class="form-input" min="1" step="1" required placeholder="Ej. 1 - 5">
                    </div>

                    <div class="form-group">
                        <label for="unidad_medida" class="form-label">Unidad de medida*</label>
                        <input type="text" name="unidad_medida" id="unidad_medida" class="form-input" required placeholder="Ej. litro">
                    </div>

                    <div class="form-group">
                        <label for="proveedor" class="form-label">Proveedor*</label>
                        <input type="text" id="proveedor" name="proveedor" class="form-input" required placeholder="Ej. Huevos S.A">
                    </div>

                    <div class="form-group">
                        <label for="fecha_ingreso" class="form-label">Fecha de registro</label>
                        <input type="date" id="fecha_ingreso" name="fecha_ingreso" class="form-input" disabled placeholder="Asignado automáticamente">
                        <p class="form-note">Este campo se generará automáticamente</p>
                    </div>

                    <div class="form-group">
                        <label for="fecha_caducidad" class="form-label">Fecha de expiración*</label>
                        <input type="date" id="fecha_caducidad" name="fecha_caducidad" class="form-input" required placeholder="18/08/2005">
                    </div>

                    <div class="form-group">
                        <label for="costo_unitario" class="form-label">Costo unitario*</label>
                        <input type="number" id="costo_unitario" name="costo_unitario" class="form-input" required step="any" inputmode="decimal">
                    </div>

                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="4" cols="50" class="form-input"></textarea>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-secondary" onclick="window.location.href='Ginsumos.php'">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn btn-primary" type="submit" name="guardarIn">
                            <i class="fas fa-save"></i> Guardar Insumo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php } ?>
</body>
</html>