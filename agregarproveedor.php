<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/agreprove.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Nuevo proveedor - Abby Cookies & Cakes</title>
</head>
<body>
    <!-- sidlebar -->
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

<?php
session_start();
require_once 'conexion.php';

if(isset($_POST['guardarProve'])){
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $correo = $_POST['correo'];
     // Verifica si la fecha fue proporcionada. Si no, asigna la fecha actual.
    $fecha_registro = isset($_POST['fecha_registro']) && $_POST['fecha_registro'] != '' ? $_POST['fecha_registro'] : date('Y-m-d H:i:s');


     //insert
    $sql = "INSERT INTO proveedor (nombre, telefono,direccion,correo,fecha_registro) 
VALUES ('$nombre','$telefono','$direccion','$correo','$fecha_registro')";
    
    $resultado = mysqli_query($conn, $sql); // Changed $conexion to $conn
    if($resultado){
        //los resultados ingresaron a la base de datos
        echo " <script language='JavaScript'>
        alert('¡¡Los datos fueron ingresados correctamente!!');
        location.assign('proveedores.php');
        </script>";
        }else{
            //los datos NO se guardaron
            echo " <script language='JavaScript'>
            alert('¡¡ERROR!! Los datos NO fueron ingresados');
            location.assign('proveedores.php');
            </script>";
        }
}  else { 
?>

  <div class="menu-container">

    <header class="menu-header">
      <h1 class="logo">Abby's Cookies & Cakes</h1>
      <div class="user-info">
        <p class="user-email">usuario@abby.com</p>
        <button class="notification-btn" onclick="window.location.href='Alertas y notificaciones.php'">
          <i class="fas fa-bell"></i>
          <span class="notification-badge">3</span>
        </button>
        <button class="logout-btn" onclick="cerrarSesion()">
          <i class="fas fa-sign-out-alt"></i>
          Cerrar Sesión
        </button>
      </div>
    </header>

    <h2 class="page-title">Nuevo Proveedor</h2>
    <p class="slogan">Agrega un nuevo proveedor a tu inventario.</p>

    <div class="form-container">
        <div class="form-header">
            <h3 class="form-title">Información del Proveedor</h3>
        </div>

        <form action="<?=$_SERVER['PHP_SELF']  ?>" method="post">
        <div class="form-grid">

            <div class="form-group">
                <label for="id" class="form-label">ID</label>
                <input type="text" id="id" name="id" class="form-input" disabled placeholder="Asignado automáticamente">
                <p class="form-note">Este campo se generará automáticamente</p>
            </div>

            <div class="form-group">
                <label for="nombre" class="form-label">Nombre del proveedor*</label>
                <input type="text" id="nombre" name="nombre" class="form-input" required
                placeholder="Ej. Harina S.A">
            </div>

            <div class="form-group">
                <label for="telefono" class="form-label">Telefono*</label>
                <input type="text" name="telefono"id="telefono" class="form-input"  required
                placeholder="Ej. 123456789">
            </div>

            <div class="form-group">
                <label for="direccion" class="form-label">Direccion*</label>
                <input type="text" name="direccion" id="direccion" class="form-input"  required
                placeholder="Ej. valladolid, Yucatán, México">
            </div>

            <div class="form-group">
                <label for="correo" class="form-label">Correo electronico*</label>
                <input type="text" id="correo" name="correo" class="form-input"  required
                placeholder="Ej. correo@example.com">
            </div>

            <div class="form-group">
                <label for="fecha_registro" class="form-label">Fecha de registro</label>
                <input type="date" id="fecha_registro" name="fecha_registro" class="form-input" disabled placeholder="Asignado automáticamente">
                <p class="form-note">Este campo se generará automáticamente</p>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" onclick="window.location.href='Ginsumos.php'">
                    <i class="fas fa-times"></i>Cancelar
                </button>
                <button class="btn btn-primary" type="submit" name="guardarProve">
                    <i class="fas fa-save"></i> Guardar Proveedor
                </button>
            </div>

        </div>
    </form>
<?php
}
?>
    </div>
  </div>
</body>
</html>
