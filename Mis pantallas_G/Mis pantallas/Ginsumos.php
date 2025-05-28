<?php
    session_start();
    require_once 'conexion.php'; // Conexión a la base de datos

    // Obtener empleados
$query = "SELECT * FROM insumo"; 
$resultado = $conn->query($query); 
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Ginsumos.css">
    <title>Tabla de insumos- Abby Cookies & Cakes</title>
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
        <a href="insumos.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
      </div>
    </header>

      <h1 class="page-title">Reporte de  insumos</h1>
      <div class="report-container">
        <h2 class="section-title">TABLA DE INSUMOS</h2>

        <div class="report-header">
            <div class="report-info">
                <div class="report-info-item">
                    <strong>Insumos: </strong>
                    <select id="insumoSelect" style="padding: 10px; width: auto; border-radius: 8px; border: 1px solid #ccc; font-family: 'Poppins';">
                        <option value="">Seleccione un insumo</option>
                        <?php while ($insumo = mysqli_fetch_assoc($resultado)): ?>
                        <option value="<?= $insumo['id'] ?>"><?= htmlspecialchars($insumo['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <form action="<?=$_SERVER['PHP_SELF']?>">
                      <br>
                        <button class="mostrardatos" onclick="fetchTodosInsumos()">Mostrar todos los datos</button>
                        <a href="agregarinsumo.php" class="mostrardatos"><i class="fa-solid fa-plus"></i></a>
                    </form>
                </div>
            </div>
        </div>
        
        <h3 class="section-title">Detalles de los insumos</h3>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cantidad disponible</th>
                    <th>Unidad de medida</th>
                    <th>Proveedor</th>
                    <th>Fecha de ingreso</th>
                    <th>Fecha de caducidad</th>
                    <th>Costo unitario</th>
                    <th>Descripción</th>
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
window.addEventListener('DOMContentLoaded', fetchTodosInsumos);
function fetchTodosInsumos() {
  fetch('obtener_todos_insumos.php')
    .then(response => response.json())
    .then(data => {
      const tabla = document.getElementById('tabla-detalle');
      tabla.innerHTML = '';

      if (data.length > 0) {
        data.forEach(insumo => {
          tabla.innerHTML += `
            <tr>
              <td>${insumo.id}</td>
              <td>${insumo.nombre}</td>
              <td>${insumo.cantidad_disponible}</td>
              <td>${insumo.unidad_medida}</td>
              <td>${insumo.proveedor}</td>
              <td>${insumo.fecha_ingreso}</td>
              <td>${insumo.fecha_caducidad}</td>
              <td>${insumo.costo_unitario}</td>
              <td>${insumo.descripcion}</td>
              <td>
                <a href='editar.php?id=${insumo.id}'>EDITAR</a><br>
                <a href='eliminar.php?id=${insumo.id}' onclick='return confirmar()'>ELIMINAR</a>
              </td>
            </tr>`;
        });
      } else {
        tabla.innerHTML = `<tr><td colspan="10" style="text-align:center;">No hay insumos registrados.</td></tr>`;
      }
    })
    .catch(error => console.error('Error:', error));
}
document.getElementById('insumoSelect').addEventListener('change', function(){
  const insumoID =this.value;

if (insumoId) {
    fetch('obtener_insumo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ insumo_id: insumoId })
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
            <td>${data.cantidad_disponible}</td>
            <td>${data.unidad_medida}</td>
            <td>${data.proveedor}</td>
            <td>${data.fecha_ingreso}</td>
            <td>${data.fecha_caducidad}</td>
            <td>${data.costo_unitario}</td>
            <td>${data.descripcion}</td>
            <td>
              <a href='editar.php?id=${data.id}'>EDITAR</a><br>
              <a href='eliminar.php?id=${data.id}' onclick='return confirmar()'>ELIMINAR</a>
            </td>
          </tr>`;
      } else {
        tabla.innerHTML = `<tr><td colspan="10">No se encontró el insumo.</td></tr>`;
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