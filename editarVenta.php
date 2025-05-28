<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/agreinsu.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
  <title>Editar Venta</title>
</head>
<body>

  <!-- Sidebar -->
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardarVenta'])) {
    $id = $_POST['id'];
    $fecha = (!empty($_POST['fecha'])) ? $_POST['fecha'] : date('Y-m-d H:i:s');
    $total = $_POST['total'];
    $metodo_pago = $_POST['metodo_pago'];
    $descripcion = $_POST['descripcion'];

    $sql = "UPDATE ventas 
            SET fecha = '$fecha', total = '$total', metodo_pago = '$metodo_pago', descripcion = '$descripcion'
            WHERE id = '$id'";

    $resultado = mysqli_query($conn, $sql);

    if ($resultado) {
        echo "<script>
            alert('¡¡Los datos fueron actualizados correctamente!!');
            location.assign('ventas.php');
        </script>";
    } else {
        echo "<script>
            alert('¡¡ERROR!! Los datos NO fueron actualizados');
            location.assign('ventas.php');
        </script>";
    }

    mysqli_close($conn);
    exit();
} else {
    if (!isset($_GET['id'])) {
        echo "<script>
            alert('ID no especificado');
            location.assign('ventas.php');
        </script>";
        exit();
    }

    $id = $_GET['id'];
    $sql = "SELECT * FROM ventas WHERE id = '$id'";
    $resultado = mysqli_query($conn, $sql);

    if (!$resultado || mysqli_num_rows($resultado) === 0) {
        echo "<script>
            alert('Venta no encontrada');
            location.assign('ventas.php');
        </script>";
        exit();
    }

    $fila = mysqli_fetch_assoc($resultado);
    $fecha = $fila['fecha'];
    $total = $fila['total'];
    $metodo_pago = $fila['metodo_pago'];
    $descripcion = $fila['descripcion'];

    mysqli_close($conn);
}
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

    <h2 class="page-title">Editar Venta</h2>
    <p class="slogan">Edita los datos de una venta registrada.</p>

    <div class="form-container">
      <div class="form-header">
        <h3 class="form-title">Información de la Venta</h3>
      </div>

      <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
        <div class="form-grid">

          <!-- Campo oculto para enviar el ID real -->
          <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

          <div class="form-group">
            <label for="id_mostrar" class="form-label">ID</label>
            <input type="text" id="id_mostrar" class="form-input" disabled 
              value="<?= htmlspecialchars($id) ?>">
            <p class="form-note">Este campo se genera automáticamente.</p>
          </div>

          <div class="form-group">
            <label for="fecha" class="form-label">Fecha de registro</label>
            <input type="date" id="fecha" name="fecha" class="form-input"
              value="<?= htmlspecialchars(date('Y-m-d', strtotime($fecha))) ?>">
            <p class="form-note">Puedes modificar la fecha de la venta si lo deseas.</p>
          </div>

          <div class="form-group">
            <label for="total" class="form-label">Total de la venta*</label>
            <input type="text" id="total" name="total" class="form-input" required
              placeholder="Ej. 120.00" value="<?= htmlspecialchars($total) ?>">
          </div>

          <div class="form-group">
            <label for="metodo_pago" class="form-label">Método de pago*</label>
            <input type="text" id="metodo_pago" name="metodo_pago" class="form-input" required
              placeholder="Ej. Efectivo" value="<?= htmlspecialchars($metodo_pago) ?>">
          </div>

          <div class="form-group">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="4" cols="50" class="form-input"><?= htmlspecialchars($descripcion) ?></textarea>
          </div>

          <div class="form-actions">
            <button class="btn btn-secondary" onclick="window.location.href='ventas.php'" type="button">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn btn-primary" type="submit" name="guardarVenta">
              <i class="fas fa-save"></i> Actualizar Venta
            </button>
          </div>

        </div>
      </form>
    </div>
  </div>

</body>
</html>
