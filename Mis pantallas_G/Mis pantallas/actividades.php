<?php
session_start();
require_once 'conexion.php'; // Asegúrate de que este archivo conecta a tu base de datos correctamente

// Obtener empleados
$query = "SELECT id, nombre FROM empleado";
$resultado = $conn->query($query); 

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar Reporte de Personal - Abby's Cookies & Cakes</title>
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


      <h1 class="page-title">Gestión de Reporte de Personal</h1>

      <div class="report-container">

        <h2 class="section-title">Seleccionar Empleado</h2>
        <div style="margin-bottom: 20px;">
          <select id="empleadoSelect" style="padding: 10px; width: 100%; border-radius: 8px; border: 1px solid #ccc; font-family: 'Poppins';">
            <option value="">Seleccione un empleado</option>
            <?php while ($empleado = mysqli_fetch_assoc($resultado)): ?>
              <option value="<?= $empleado['id'] ?>"><?= htmlspecialchars($empleado['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <h2 class="section-title">Editar Detalles de Trabajo</h2>

        <table>
          <thead>
            <tr>
              <th>No.</th>
              <th>Descripción de trabajo</th>
              <th>Categoría</th>
              <th>Fecha de inicio</th>
              <th>Fecha de finalización</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tabla-detalle">
            <!-- Aquí se cargarán dinámicamente las tareas -->
          </tbody>
        </table>

        <div style="text-align: right; margin-top: 20px;">
         <button onclick="agregarFila()" style="background:#a87165; color:white; border:none; padding:10px 20px; border-radius:8px; font-family: 'Poppins'; font-size: 14px;">Agregar Fila</button>
         <button onclick="guardarCambios()" style="background:#65a871; color:white; border:none; padding:10px 20px; border-radius:8px; font-family: 'Poppins'; font-size: 14px; margin-left: 10px;">Guardar Cambios</button>
        </div>

      </div>

    </div>
  </div>
<!-- Agrega SweetAlert2 en el <head> -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById('empleadoSelect').addEventListener('change', function() {
  const empleadoId = this.value;
  if (empleadoId) {
    fetchTareas(empleadoId);
  } else {
    clearTareas();
  }
});

function fetchTareas(empleadoId) {
  fetch('obtener_reportes.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ empleado_id: empleadoId })
  })
  .then(response => response.json())
  .then(data => {
    let tablaDetalle = document.getElementById('tabla-detalle');
    tablaDetalle.innerHTML = '';
    data.forEach((tarea, index) => {
      tablaDetalle.innerHTML += generarFila(index + 1, tarea);
    });
  })
  .catch(error => {
    console.error('Error:', error);
    Swal.fire('Error', 'No se pudieron cargar los datos.', 'error');
  });
}

function agregarFila() {
  const tabla = document.getElementById('tabla-detalle');
  const filas = tabla.getElementsByTagName('tr').length + 1;
  tabla.insertAdjacentHTML('beforeend', generarFila(filas));
}

function generarFila(numero, tarea = {}) {
  return `
    <tr>
      <td>${numero}</td>
      <td><input type="text" value="${tarea.descripcion || ''}" style="width: 100%;"></td>
      <td><input type="text" value="${tarea.categoria || ''}" style="width: 100%;"></td>
      <td><input type="date" value="${tarea.fecha_inicio || ''}" style="width: 100%;"></td>
      <td><input type="date" value="${tarea.fecha_fin || ''}" style="width: 100%;"></td>
      <td><button onclick="eliminarFila(this)" style="background:#e06666; color:white; border:none; padding:5px 10px; border-radius:5px;">Eliminar</button></td>
    </tr>
  `;
}

function eliminarFila(boton) {
  const fila = boton.parentElement.parentElement;
  fila.remove();
  reenumerarFilas();
}

function reenumerarFilas() {
  const filas = document.querySelectorAll('#tabla-detalle tr');
  filas.forEach((fila, index) => {
    fila.children[0].textContent = index + 1;
  });
}

function guardarCambios() {
  const empleadoId = document.getElementById('empleadoSelect').value;
  if (!empleadoId) {
    Swal.fire('Atención', 'Selecciona un empleado primero.', 'warning');
    return;
  }

  const filas = document.querySelectorAll('#tabla-detalle tr');
  const trabajos = [];

  let camposVacios = false;

  filas.forEach(fila => {
    const inputs = fila.querySelectorAll('input');
    if (inputs.length === 4) {
      const descripcion = inputs[0].value.trim();
      const categoria = inputs[1].value.trim();
      const fecha_inicio = inputs[2].value;
      const fecha_fin = inputs[3].value;
      
      if (!descripcion || !categoria || !fecha_inicio || !fecha_fin) {
        camposVacios = true;
      }

      trabajos.push({ descripcion, categoria, fecha_inicio, fecha_fin });
    }
  });

  if (camposVacios) {
    Swal.fire('Atención', 'Todos los campos deben estar llenos.', 'warning');
    return;
  }

  fetch('guardar_reportes.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ empleado_id: empleadoId, trabajos: trabajos })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      Swal.fire('Éxito', 'Cambios guardados correctamente.', 'success');
      fetchTareas(empleadoId); // Recargar tareas
    } else {
      Swal.fire('Error', 'No se pudieron guardar los cambios.', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    Swal.fire('Error', 'Hubo un problema al guardar.', 'error');
  });
}

function clearTareas() {
  document.getElementById('tabla-detalle').innerHTML = '';
}
</script>

  
</body>
</html>
