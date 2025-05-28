<?php
session_start();
require_once 'conexion.php';

if (isset($_POST['actualizarPr'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $correo = $_POST['correo'];

    $sql = "UPDATE proveedor SET nombre = '$nombre', telefono = '$telefono',
    direccion = '$direccion', correo = '$correo' WHERE id = '$id'";

    $resultado = mysqli_query($conn, $sql);
    if ($resultado) {
        echo "<script>alert('¡¡Los datos fueron actualizados correctamente!!');
        window.location.href='proveedores.php';</script>";
    } else {
        echo "<script>alert('¡¡ERROR!! Los datos NO fueron actualizados');
        window.location.href='proveedores.php';</script>";
    }
    exit();
} elseif (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM proveedor WHERE id = '$id'";
    $resultado = mysqli_query($conn, $sql);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_assoc($resultado);
        $nombre = $fila["nombre"];
        $telefono = $fila["telefono"];
        $direccion = $fila["direccion"];
        $correo = $fila["correo"];
        $fecha_registro = $fila["fecha_registro"];
    } else {
        echo "<script>alert('No se encontró el proveedor con ese ID'); window.location.href='proveedores.php';</script>";
        exit();
    }
    mysqli_close($conn);
} else {
    echo "<script>alert('ID no proporcionado'); window.location.href='proveedores.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/agreprove.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
  <title>Editar Proveedor</title>
</head>
<body>

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
        <button class="notification-btn" onclick="window.location.href='Alertas y notificaciones.php'">
          <i class="fas fa-bell"></i>
          <span class="notification-badge">3</span>
        </button>
        <button class="logout-btn" onclick="cerrarSesion()">
          <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </button>
      </div>
    </header>

    <h2 class="page-title">Editar Proveedor</h2>
    <p class="slogan">Edita los datos del proveedor seleccionado.</p>

    <div class="form-container">
      <div class="form-header">
        <h3 class="form-title">Información del Proveedor</h3>
      </div>

      <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
        <div class="form-grid">

          <div class="form-group">
            <label for="id_visible" class="form-label">ID</label>
            <input type="text" id="id_visible" class="form-input" disabled value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <p class="form-note">Este campo se genera automáticamente</p>
          </div>

          <div class="form-group">
            <label for="nombre" class="form-label">Nombre del proveedor*</label>
            <input type="text" id="nombre" name="nombre" class="form-input" required
                   placeholder="Ej. Harina S.A" value="<?= htmlspecialchars($nombre) ?>">
          </div>

          <div class="form-group">
            <label for="telefono" class="form-label">Teléfono*</label>
            <input type="text" id="telefono" name="telefono" class="form-input" required
                   placeholder="Ej. 123456789" value="<?= htmlspecialchars($telefono) ?>">
          </div>

          <div class="form-group">
            <label for="direccion" class="form-label">Dirección*</label>
            <input type="text" id="direccion" name="direccion" class="form-input" required
                   placeholder="Ej. Valladolid, Yucatán" value="<?= htmlspecialchars($direccion) ?>">
          </div>

          <div class="form-group">
            <label for="correo" class="form-label">Correo electrónico*</label>
            <input type="email" id="correo" name="correo" class="form-input" required
                   placeholder="correo@example.com" value="<?= htmlspecialchars($correo) ?>">
          </div>

          <div class="form-group">
            <label for="fecha_registro" class="form-label">Fecha de registro</label>
            <input type="date" id="fecha_registro" class="form-input" disabled value="<?= htmlspecialchars($fecha_registro) ?>">
            <p class="form-note">Este campo se genera automáticamente</p>
          </div>

          <div class="form-actions">
            <button class="btn btn-secondary" type="button" onclick="window.location.href='proveedores.php'">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn btn-primary" type="submit" name="actualizarPr">
              <i class="fas fa-save"></i> Actualizar Proveedor
            </button>
          </div>

        </div>
      </form>
    </div>
  </div>

</body>
</html>
