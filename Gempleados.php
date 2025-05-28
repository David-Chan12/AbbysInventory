<?php
session_start();
require_once 'conexion.php'; // Conexión a la base de datos

// Obtener empleados
$query = "SELECT id, nombre FROM empleado";
$resultado = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reportes de Personal - Abby's Cookies & Cakes</title>
  <link rel="stylesheet" href="css/Gempleados.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
<!-- Sidebar -->
<div id="sidebar-placeholder"></div>

<script>
  fetch('sidebar.php')
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
      <a href="empleado.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
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
    </header>

    

      <h1 class="page-title">Reportes de Personal</h1>
      
      <div class="report-container">
        <h2 class="section-title">REPORTE DE PERSONAL</h2>

        <div class="report-header">
          <div class="report-info">
            <div class="report-info-item">
              <strong>Empleado:</strong>
              <select id="empleadoSelect" style="padding: 10px; width: auto; border-radius: 8px; border: 1px solid #ccc; font-family: 'Poppins';">
                <option value="">Seleccione un empleado</option>
                <?php while ($empleado = mysqli_fetch_assoc($resultado)): ?>
                  <option value="<?= $empleado['id'] ?>"><?= htmlspecialchars($empleado['nombre']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
        </div>

        <h3 class="section-title">Detalles de Trabajo</h3>
        
        <table>
          <thead>
            <tr>
              <th>No.</th>
              <th>Descripción de trabajo</th>
              <th>Categoría</th>
              <th>Fecha de inicio</th>
              <th>Fecha de finalización</th>
            </tr>
          </thead>
          <tbody id="tabla-detalle">
            <!-- Aquí se cargará dinámicamente el reporte -->
          </tbody>
        </table>
      </div>

    </div> <!-- Cierre main-panel -->
  </div>

<script>
document.getElementById('empleadoSelect').addEventListener('change', function() {
  const empleadoId = this.value;
  if (empleadoId) {
    fetchReporte(empleadoId);
  } else {
    clearTabla();
  }
});

function fetchReporte(empleadoId) {
  fetch('obtener_reportes.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ empleado_id: empleadoId })
  })
  .then(response => response.json())
  .then(data => {
    let tablaDetalle = document.getElementById('tabla-detalle');
    tablaDetalle.innerHTML = '';

    if (data.length > 0) {
      data.forEach((tarea, index) => {
        tablaDetalle.innerHTML += `
          <tr>
            <td>${index + 1}</td>
            <td>${tarea.descripcion}</td>
            <td>${tarea.categoria}</td>
            <td>${tarea.fecha_inicio}</td>
            <td>${tarea.fecha_fin}</td>
          </tr>
        `;
      });
    } else {
      tablaDetalle.innerHTML = `<tr><td colspan="5" style="text-align:center;">No hay reportes para este empleado.</td></tr>`;
    }
  })
  .catch(error => console.error('Error:', error));
}

function clearTabla() {
  document.getElementById('tabla-detalle').innerHTML = '';
}
</script>

</body>
</html>
