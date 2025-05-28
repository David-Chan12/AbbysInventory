<?php
session_start();
require_once 'conexion.php'; // Asegúrate de que este archivo conecta a tu base de datos correctamente

// Obtener empleados para asignar al pedido
$queryEmpleados = "SELECT id, nombre FROM empleado";
$resultadoEmpleados = $conn->query($queryEmpleados);

// Obtener estados de pedido
$queryEstados = "SELECT id, descripcion FROM estado";
$resultadoEstados = $conn->query($queryEstados);

// Procesar el formulario cuando se envía
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_pedido'])) {
    $fecha = $_POST['fecha'];
    $descripcion = $_POST['descripcion'];
    $cliente = $_POST['cliente']; // Ahora es un texto directo
    $empleado_id = $_POST['empleado'];
    $estado_id = $_POST['estado']; 
    $metodo_pago = $_POST['metodo_pago'];
    
    // Insertar en la tabla pedido
    $query = "INSERT INTO pedido (fecha, productos_solicitados, cliente, metodo_pago, dia_entrega, estado_pedido, descripcion) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssss", $fecha, $descripcion, $cliente, $metodo_pago, $fecha, $estado_id, $descripcion);
    
    if ($stmt->execute()) {
        $pedido_id = $conn->insert_id;
        
        // Asociar el pedido con el empleado
        $queryEmpleadoPedido = "INSERT INTO empelado_pedido (empleado_id, pedido_id) VALUES (?, ?)";
        $stmtEmpleadoPedido = $conn->prepare($queryEmpleadoPedido);
        $stmtEmpleadoPedido->bind_param("ii", $empleado_id, $pedido_id);
        $stmtEmpleadoPedido->execute();
        
        $mensaje = "Pedido guardado correctamente";
    } else {
        $mensaje = "Error al guardar el pedido: " . $conn->error;
    }
}

// Obtener todos los pedidos para el calendario
$queryPedidos = "SELECT p.id, p.fecha, p.productos_solicitados, p.descripcion, e.descripcion as estado, 
                 p.cliente as cliente_nombre, emp.nombre as empleado_nombre
                 FROM pedido p
                 LEFT JOIN estado e ON p.estado_pedido = e.id
                 LEFT JOIN empelado_pedido ep ON p.id = ep.pedido_id
                 LEFT JOIN empleado emp ON ep.empleado_id = emp.id";
$resultadoPedidos = $conn->query($queryPedidos);

$pedidos = [];
if ($resultadoPedidos && $resultadoPedidos->num_rows > 0) {
    while ($row = $resultadoPedidos->fetch_assoc()) {
        $fecha = date('Y-m-d', strtotime($row['fecha']));
        if (!isset($pedidos[$fecha])) {
            $pedidos[$fecha] = [];
        }
        $pedidos[$fecha][] = [
            'id' => $row['id'],
            'descripcion' => $row['productos_solicitados'],
            'cliente' => $row['cliente_nombre'],
            'empleado' => $row['empleado_nombre'],
            'estado' => $row['estado']
        ];
    }
}
$pedidosJSON = json_encode($pedidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Calendario de Pedidos</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Montserrat', 'Segoe UI', sans-serif;
    }

    body {
      background-color: #fff9f7;
      color: #5a4a42;
      min-height: 100vh;
      background-image: url('Imagenes/fe09f794b5cf5833818976f9fd1e3522.jpg');
      background-size: cover;
      background-position: center center;
      background-attachment: fixed;
      display: flex;
      padding: 20px;
      position: relative;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: -1;
      pointer-events: none;
    }

    .container {
      display: flex;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      background-color: rgba(60, 40, 35, 0.4);
      border-radius: 12px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      overflow: hidden;
      color: #fff;
    }

    .sidebar {
      width: 250px;
      background-color: rgba(80, 50, 45, 0.4);
      padding: 30px 20px;
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      flex-direction: column;
    }

    .sidebar-title {
      color: #fff;
      font-size: 22px;
      font-weight: 500;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
      text-align: center;
    }

    .sidebar-menu {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .menu-item {
      background: rgba(76, 51, 47, 0.914);
      border: none;
      color: rgba(255, 255, 255, 0.786);
      cursor: pointer;
      font-size: 14px;
      padding: 12px 15px;
      border-radius: 6px;
      transition: all 0.3s;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .menu-item:hover {
      background-color: rgba(160, 100, 80, 0.8);
    }

    .menu-item.active {
      background-color: rgba(160, 100, 80, 0.8);
      font-weight: 500;
    }

    .main-content {
      flex: 1;
      padding: 30px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      gap: 15px;
    }

    /* Estilos para el calendario */
    .calendario-container {
      width: 100%;
    }

    .calendario {
      background: rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .calendario h2 {
      text-align: center;
      color: white;
      margin-bottom: 20px;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .calendar-header h3 {
      color: white;
      font-weight: 500;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
      margin-top: 1rem;
    }

    .day {
      padding: 10px;
      text-align: center;
      border-radius: 5px;
      cursor: default;
      background: rgba(255, 255, 255, 0.1);
      color: white;
      transition: all 0.3s;
    }

    .day:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .day-header {
      font-weight: bold;
      background: rgba(160, 100, 80, 0.8);
      color: white;
    }

    .inactive {
      color: rgba(255, 255, 255, 0.3);
      background: rgba(255, 255, 255, 0.05);
    }

    .has-event {
      background-color: rgba(160, 100, 80, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.3);
      font-weight: bold;
      cursor: pointer;
    }

    /* Estilos para el formulario */
    .formulario {
      background: rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .formulario h2 {
      color: white;
      margin-bottom: 20px;
      text-align: center;
    }

    .formulario label {
      display: block;
      margin-bottom: 8px;
      color: white;
    }

    .formulario textarea, 
    .formulario input,
    .formulario select {
      width: 100%;
      padding: 0.8rem;
      margin-bottom: 1rem;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 6px;
      color: white;
    }

    .formulario textarea:focus, 
    .formulario input:focus,
    .formulario select:focus {
      outline: none;
      border-color: rgba(160, 100, 80, 0.8);
    }

    .formulario select option {
      background-color: #5a4a42;
      color: white;
    }

    button {
      padding: 0.8rem 1.5rem;
      background-color: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      cursor: pointer;
      border-radius: 6px;
      font-weight: 500;
      transition: all 0.3s;
    }

    button:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    .settings-button {
      background: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      cursor: pointer;
      font-size: 16px;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .settings-button:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    .header .settings-button {
      padding: 0;
      width: 36px;
      height: 36px;
      border-radius: 50%;
    }

    #mensajes {
      margin-top: 15px;
      color: white;
      text-align: center;
      font-style: italic;
    }

    /* Estilos para el modal de detalles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
    }

    .modal-content {
      background-color: rgba(80, 50, 45, 0.95);
      margin: 10% auto;
      padding: 20px;
      border-radius: 10px;
      width: 80%;
      max-width: 600px;
      color: white;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .close {
      color: white;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: rgba(160, 100, 80, 0.8);
    }

    .pedido-item {
      background: rgba(255, 255, 255, 0.1);
      padding: 15px;
      margin-bottom: 10px;
      border-radius: 6px;
    }

    .pedido-item h4 {
      margin-bottom: 10px;
      color: rgba(160, 100, 80, 1);
    }

    .pedido-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .pedido-info p {
      margin: 5px 0;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }

      .sidebar-menu {
        flex-direction: row;
        flex-wrap: wrap;
      }

      .menu-item {
        flex: 1 0 calc(50% - 5px);
      }
      
      .main-content {
        padding: 20px;
      }

      .form-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="sidebar">
      <h2 class="sidebar-title">Menú</h2>
      <div class="sidebar-menu">
        <a href="Pedidos.php" class="menu-item active">
          <i class="fas fa-calendar-alt"></i> Calendario
        </a>
        <a href="Lista-Pedidos.php" class="menu-item">
          <i class="fas fa-list"></i> Pedidos
        </a>
        <a href="Reportes-pedidos.php" class="menu-item">
          <i class="fas fa-chart-bar"></i> Reportes
        </a>
         <a href="Menu.php" class="menu-item">
          <i class="fas fa-home"></i> Menu
        </a>
      </div>
    </div>

    <div class="main-content">
      <div class="header">
        <p class="user-email"><i class="fas fa-user-circle"></i> usuario@abby.com</p>
        <button class="settings-button">
          <i class="fas fa-cog"></i>
        </button>
      </div>

      <div class="calendario-container">
        <div class="calendario">
          <h2>Calendario de Pedidos</h2>
          <div class="calendar-header">
            <button onclick="cambiarMes(-1)"><i class="fas fa-chevron-left"></i></button>
            <h3 id="mes-actual"></h3>
            <button onclick="cambiarMes(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
          <div class="calendar-grid" id="grid">
            <!-- Días del calendario -->
          </div>
        </div>

        <div class="formulario">
          <h2>Agregar Pedido</h2>
          <form id="pedidoForm" method="POST" action="">
            <div class="form-row">
              <div>
                <label for="fecha">Fecha del pedido:</label>
                <input type="date" id="fecha" name="fecha" required>
              </div>
              <div>
                <label for="cliente">Cliente:</label>
                <input type="text" id="cliente" name="cliente" placeholder="Nombre del cliente" required>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label for="empleado">Empleado asignado:</label>
                <select id="empleado" name="empleado" required>
                  <option value="">Seleccione un empleado</option>
                  <?php 
                  if ($resultadoEmpleados && $resultadoEmpleados->num_rows > 0) {
                    while ($row = $resultadoEmpleados->fetch_assoc()) {
                      echo "<option value='" . $row['id'] . "'>" . $row['nombre'] . "</option>";
                    }
                  } else {
                    echo "<option value=''>No hay empleados disponibles</option>";
                  }
                  ?>
                </select>
              </div>
              <div>
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" required>
                  <?php 
                  if ($resultadoEstados && $resultadoEstados->num_rows > 0) {
                    while ($row = $resultadoEstados->fetch_assoc()) {
                      echo "<option value='" . $row['id'] . "'>" . $row['descripcion'] . "</option>";
                    }
                  } else {
                    echo "<option value=''>No hay estados disponibles</option>";
                  }
                  ?>
                </select>
              </div>
            </div>

            <div>
              <label for="metodo_pago">Método de pago:</label>
              <select id="metodo_pago" name="metodo_pago" required>
                <option value="Efectivo">Efectivo</option>
                <option value="Tarjeta">Tarjeta</option>
                <option value="Transferencia">Transferencia</option>
              </select>
            </div>

            <label for="descripcion">Productos solicitados:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required></textarea>

            <button type="submit" name="guardar_pedido"><i class="fas fa-save"></i> Guardar Pedido</button>
          </form>
          <div id="mensajes"><?php echo $mensaje; ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para mostrar detalles de pedidos -->
  <div id="pedidosModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Pedidos para <span id="modal-fecha"></span></h2>
      <div id="pedidos-container">
        <!-- Aquí se mostrarán los pedidos -->
      </div>
    </div>
  </div>

  <script>
    let fechaActual = new Date();
    // Cargar pedidos desde PHP
    const pedidos = <?php echo $pedidosJSON ?: '{}'; ?>;
    
    // Modal
    const modal = document.getElementById("pedidosModal");
    const span = document.getElementsByClassName("close")[0];
    
    span.onclick = function() {
      modal.style.display = "none";
    }
    
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    function mostrarPedidos(fecha, pedidosDelDia) {
      document.getElementById("modal-fecha").textContent = new Date(fecha).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      });
      
      const container = document.getElementById("pedidos-container");
      container.innerHTML = '';
      
      if (pedidosDelDia && pedidosDelDia.length > 0) {
        pedidosDelDia.forEach(pedido => {
          const pedidoElement = document.createElement('div');
          pedidoElement.className = 'pedido-item';
          
          pedidoElement.innerHTML = `
            <h4>Pedido #${pedido.id}</h4>
            <div class="pedido-info">
              <p><strong>Cliente:</strong> ${pedido.cliente}</p>
              <p><strong>Empleado:</strong> ${pedido.empleado || 'No asignado'}</p>
              <p><strong>Estado:</strong> ${pedido.estado}</p>
              <p><strong>Descripción:</strong> ${pedido.descripcion}</p>
            </div>
            <div style="margin-top: 10px; text-align: right;">
              <a href="Lista-Pedidos.php?editar=${pedido.id}" class="menu-item" style="display: inline-block; padding: 5px 10px;">
                <i class="fas fa-edit"></i> Editar
              </a>
            </div>
          `;
          
          container.appendChild(pedidoElement);
        });
      } else {
        container.innerHTML = '<p>No hay detalles adicionales para este día.</p>';
      }
      
      modal.style.display = "block";
    }

    function renderCalendario() {
      const grid = document.getElementById('grid');
      const encabezado = document.getElementById('mes-actual');
      grid.innerHTML = '';

      const year = fechaActual.getFullYear();
      const month = fechaActual.getMonth();
      const primerDia = new Date(year, month, 1);
      const ultimoDia = new Date(year, month + 1, 0);

      encabezado.textContent = primerDia.toLocaleDateString('es-ES', {
        month: 'long',
        year: 'numeric'
      });

      const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
      diasSemana.forEach(d => {
        const cell = document.createElement('div');
        cell.className = 'day day-header';
        cell.textContent = d;
        grid.appendChild(cell);
      });

      let inicio = primerDia.getDay(); // 0 = domingo, 1 = lunes, ...
      inicio = (inicio === 0) ? 6 : inicio - 1; // Ajustar para que empiece en lunes

      for (let i = 0; i < inicio; i++) {
        const empty = document.createElement('div');
        empty.className = 'day inactive';
        grid.appendChild(empty);
      }

      for (let i = 1; i <= ultimoDia.getDate(); i++) {
        const cell = document.createElement('div');
        cell.className = 'day';

        const fechaStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        if (pedidos[fechaStr]) {
          cell.classList.add('has-event');
          
          // Crear tooltip con información resumida
          const pedidosCount = pedidos[fechaStr].length;
          cell.title = `${pedidosCount} pedido(s)`;
          
          // Agregar evento click para mostrar detalles
          cell.addEventListener('click', () => {
            mostrarPedidos(fechaStr, pedidos[fechaStr]);
          });
        }

        cell.textContent = i;
        grid.appendChild(cell);
      }
    }

    function cambiarMes(direccion) {
      fechaActual.setMonth(fechaActual.getMonth() + direccion);
      renderCalendario();
    }

    // Inicializar calendario
    renderCalendario();
  </script>

</body>
</html>