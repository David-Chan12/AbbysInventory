<?php
    session_start();
    require_once 'conexion.php'; // Conexión a la base de datos

    // Obtener proveedores
$query = "SELECT * FROM proveedor";
$resultado = $conn->query($query);     
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Ginsumos.css">
    <title>Tabla de proveedores - Abby Cookies & Cakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <script type="text/javascript">
        function confirmar(){
            return confirm('¿Estas seguro?, se eliminaran los datos de manera permanente.');
        }
    </script>
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
  <div class="panel-background">
    <div class="main-panel">

    <header class="menu-header">
      <h1 class="logo">Abby's Cookies & Cakes</h1>
      <div class="user-info">
        <p class="user-email">usuario@abby.com</p>
        <button class="notification-btn" onclick="window.location.href='Alertas y notificaciones.html'">
          <i class="fas fa-bell"></i>
          <span class="notification-badge">3</span>
        </button>
        <button class="logout-btn" onclick="cerrarSesion()">
          <i class="fas fa-sign-out-alt"></i>
          Cerrar Sesión
        </button>
      </div>

      <div class="top-bar">
        <a href="insumos.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
      </div>
    </header>

      <h1 class="page-title">Reporte de proveedores</h1>
      <div class="report-container">
        <h2 class="section-title">TABLA DE PROVEEDORES</h2>

        <div class="report-header">
            <div class="report-info">
                <div class="report-info-item">
                    <strong>Proveedor: </strong>
                    <select id="proveSelect" style="padding: 10px; width: auto; border-radius: 8px; border: 1px solid #ccc; font-family: 'Poppins';">
                        <option value="">Seleccione un proveedor</option>
                        <?php while ($proveedor = mysqli_fetch_assoc($resultado)): ?>
                        <option value="<?= $proveedor['id'] ?>"><?= htmlspecialchars($proveedor['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <form action="<?=$_SERVER['PHP_SELF']?>">
                      <br>
                        <button class="mostrardatos" onclick="fetchTodosProveedores()">Mostrar todos los datos</button>
                        <a href="agregarproveedor.php" class="mostrardatos"><i class="fa-solid fa-plus"></i></a>
                    </form>
                </div>
            </div>
        </div>
        
        <h3 class="section-title">Detalles de los proveedores</h3>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Telefono</th>
                    <th>Dirección</th>
                    <th>Correo electronico</th>
                    <th>Fecha de registro</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody id="tabla-detalle">
              <!-- Aquí se cargará dinámicamente el reporte -->
            </tbody>
        </table>
      </div>
            </div>
    </div><!-- Cierre del main panel -->
<script>
  // Mostrar todos los insumos al cargar la página
window.addEventListener('DOMContentLoaded', fetchTodosProveedores);
function fetchTodosProveedores() {
  fetch('obtener_todos_proveedores.php')
    .then(response => response.json())
    .then(data => {
      const tabla = document.getElementById('tabla-detalle');
      tabla.innerHTML = '';

      if (data.length > 0) {
        data.forEach(proveedor => {
          tabla.innerHTML += `
            <tr>
              <td>${proveedor.id}</td>
              <td>${proveedor.nombre}</td>
              <td>${proveedor.telefono}</td>
              <td>${proveedor.direccion}</td>
              <td>${proveedor.correo}</td>
              <td>${proveedor.fecha_registro}</td>
              <td>
                <a href='editarprove.php?id=${proveedor.id}'>EDITAR</a><br>
                <a href='eliminarprove.php?id=${proveedor.id}' onclick='return confirmar()'>ELIMINAR</a>
              </td>
            </tr>`;
        });
      } else {
        tabla.innerHTML = `<tr><td colspan="7" style="text-align:center;">No hay registros disponibles.</td></tr>`;
      }
    })
    .catch(error => console.error('Error:', error));
}
document.getElementById('proveSelect').addEventListener('change', function () {
  const proveedorId = this.value;

  if (proveedorId) {
    fetch('obtener_proveedor.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ proveedor_id: proveedorId })
    })
      .then(response => response.json())
      .then(data => {
        const tabla = document.getElementById('tabla-detalle');
        tabla.innerHTML = '';

        if (data) {
          tabla.innerHTML += `
            <tr>
              <td>${data.id}</td>
              <td>${data.nombre}</td>
              <td>${data.telefono}</td>
              <td>${data.direccion}</td>
              <td>${data.correo}</td>
              <td>${data.fecha_registro}</td>
              <td>
                <a href='editarprove.php?id=${data.id}'>EDITAR</a><br>
                <a href='eliminarprove.php?id=${data.id}' onclick='return confirmar()'>ELIMINAR</a>
              </td>
            </tr>`;
        } else {
          tabla.innerHTML = `<tr><td colspan="7">No se encontró el proveedor.</td></tr>`;
        }
      })
      .catch(error => console.error('Error:', error));
  } else {
    document.getElementById('tabla-detalle').innerHTML = '';
  }
});
</script>
</body>
</html>