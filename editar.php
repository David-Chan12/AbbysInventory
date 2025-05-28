<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/agreinsu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Editar Insumos</title>
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

if(isset($_POST['actualizarIn'])){
    $id = $_POST['id']; // Added this line to get the ID from the form
    $nombre = $_POST['nombre'];
    $cantidad_disponible = $_POST['cantidad_disponible'];
    $unidad_medida = $_POST['unidad_medida'];
    $proveedor = $_POST['proveedor'];

    // Verifica si la fecha de caducidad fue proporcionada
    $fecha_caducidad = $_POST['fecha_caducidad'];
    $costo_unitario = $_POST['costo_unitario'];
    $descripcion = $_POST['descripcion'];

    //Update
    $sql = "UPDATE insumo SET nombre ='".$nombre."', cantidad_disponible = '".$cantidad_disponible."',
        unidad_medida = '".$unidad_medida."', proveedor = '".$proveedor."',
        fecha_caducidad = '".$fecha_caducidad."',
        costo_unitario = '".$costo_unitario."', descripcion = '".$descripcion."' WHERE id = '".$id."'";
    
    $resultado = mysqli_query($conn,$sql);
    if($resultado){
        //los resultados ingresaron a la base de datos
        echo " <script language='JavaScript'>
        alert('¡¡Los datos fueron ingresados correctamente!!');
        location.assign('Ginsumos.php');
        </script>";
        }else{
            //los datos NO se guardaron
            echo " <script language='JavaScript'>
            alert('¡¡ERROR!! Los datos NO fueron ingresados');
            location.assign('Ginsumos.php');
            </script>";
        }
}  else { 
    //aqui entra si no ha presionado el boton
        $id = $_GET['id'];
        $sql = "select * from insumo where id='".$id."'";

        $resultado = mysqli_query($conn,$sql);

        $fila = mysqli_fetch_assoc($resultado);
        $nombre = $fila["nombre"];
        $cantidad_disponible = $fila["cantidad_disponible"];
        $unidad_medida = $fila["unidad_medida"];
        $proveedor = $fila["proveedor"];
        $fecha_ingreso = $fila["fecha_ingreso"];
        $fecha_caducidad = $fila["fecha_caducidad"];
        $costo_unitario = $fila["costo_unitario"];
        $descripcion = $fila["descripcion"];

        mysqli_close($conn);
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

    <h2 class="page-title">Editar Insumo</h2>
    <p class="slogan">Edita datos de tus insumos del inventario.</p>

    <div class="form-container">
        <div class="form-header">
            <h3 class="form-title">Información del Insumo</h3>
        </div>

        <form action="<?=$_SERVER['PHP_SELF']  ?>" method="post">
        <!-- Add hidden input for ID -->
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-grid">

            <div class="form-group">
                <label for="id" class="form-label">ID</label>
                <input type="text" id="id" name="id_display" class="form-input" disabled value="<?php echo $id; ?>" placeholder="Asignado automáticamente">
                <p class="form-note">Este campo se generará automáticamente</p>
            </div>

            <div class="form-group">
                <label for="nombre" class="form-label">Nombre del insumo*</label>
                <input type="text" id="nombre" name="nombre" class="form-input" required
                placeholder="Ej. Harina"
                value="<?php echo $nombre; ?>">
            </div>

            <div class="form-group">
                <label for="cantidad_disponible" class="form-label">Cantidad disponible*</label>
                <input type="number" id="cantidad_disponible" name="cantidad_disponible" class="form-input"  min="1" step="1"required
                placeholder="Ej. 1 - 5" value="<?php echo $cantidad_disponible; ?>">
            </div>

            <div class="form-group">
                <label for="unidad_medida" class="form-label">Unidad de medida*</label>
                <input type="text" name="unidad_medida" id="unidad_medida" class="form-input"  required
                placeholder="Ej. litro" value="<?php echo $unidad_medida; ?>">
            </div>

            <div class="form-group">
                <label for="proveedor" class="form-label">Proveedor*</label>
                <input type="text" id="proveedor" name="proveedor" class="form-input"  required
                placeholder="Ej. Huevos S.A" value="<?php echo $proveedor; ?>">
            </div>

            <div class="form-group">
                <label for="fecha_ingreso" class="form-label">Fecha de registro</label>
                <input type="date" id="fecha_ingreso" name="fecha_ingreso" class="form-input" disabled value="<?php echo $fecha_ingreso; ?>" placeholder="Asignado automáticamente">
                <p class="form-note">Este campo se generará automáticamente</p>
            </div>

            <div class="form-group">
                <label for="fecha_caducidad" class="form-label">Fecha de expiracion*</label>
                <input type="date" id="fecha_caducidad" name="fecha_caducidad" class="form-input"  required
                placeholder="18/08/2005" value="<?php echo $fecha_caducidad; ?>">
            </div>

            <div class="form-group">
                <label for="costo_unitario" class="form-label">Costo unitario*</label>
                <input type="number" id="costo_unitario" name="costo_unitario" class="form-input"  required
                step="any" inputmode="decimal" value="<?php echo $costo_unitario; ?>">
            </div>

            <div class="form-group">
                <label for="descripcion" class="form-label">Descripcion</label>
                <textarea name="descripcion" id="descripcion" rows="4" cols="50" class="form-input"><?php echo $descripcion; ?></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='Ginsumos.php'">
                    <i class="fas fa-times"></i>Cancelar
                </button>
                <button class="btn btn-primary" type="submit" name="actualizarIn">
                    <i class="fas fa-save"></i> Actualizar Insumo
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