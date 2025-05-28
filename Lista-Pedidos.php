<?php
session_start();
require_once 'conexion.php';

// Obtener estados de pedido
$queryEstados = "SELECT id, descripcion FROM estado";
$resultadoEstados = $conn->query($queryEstados);

// Obtener empleados
$queryEmpleados = "SELECT id, nombre FROM empleado";
$resultadoEmpleados = $conn->query($queryEmpleados);

// Variables para el formulario de edición
$pedido = null;
$mensaje = "";

// Procesar eliminación de pedido
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // Primero eliminar las relaciones en empelado_pedido
    $queryDeleteRelacion = "DELETE FROM empelado_pedido WHERE pedido_id = ?";
    $stmtDeleteRelacion = $conn->prepare($queryDeleteRelacion);
    $stmtDeleteRelacion->bind_param("i", $id);
    $stmtDeleteRelacion->execute();
    
    // Luego eliminar el pedido
    $queryDelete = "DELETE FROM pedido WHERE id = ?";
    $stmtDelete = $conn->prepare($queryDelete);
    $stmtDelete->bind_param("i", $id);
    
    if ($stmtDelete->execute()) {
        $mensaje = "Pedido eliminado correctamente";
    } else {
        $mensaje = "Error al eliminar el pedido: " . $conn->error;
    }
}

// Procesar edición de pedido
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_pedido'])) {
    $id = $_POST['id'];
    $fecha = $_POST['fecha'];
    $descripcion = $_POST['descripcion'];
    $cliente = $_POST['cliente'];
    $empleado_id = $_POST['empleado'];
    $estado_id = $_POST['estado'];
    $metodo_pago = $_POST['metodo_pago'];
    
    // Actualizar pedido
    $queryUpdate = "UPDATE pedido SET fecha = ?, productos_solicitados = ?, cliente = ?, 
                    metodo_pago = ?, dia_entrega = ?, estado_pedido = ?, descripcion = ? 
                    WHERE id = ?";
    
    $stmtUpdate = $conn->prepare($queryUpdate);
    $stmtUpdate->bind_param("sssssssi", $fecha, $descripcion, $cliente, $metodo_pago, $fecha, $estado_id, $descripcion, $id);
    
    if ($stmtUpdate->execute()) {
        // Actualizar relación con empleado
        // Primero eliminar relaciones existentes
        $queryDeleteRelacion = "DELETE FROM empelado_pedido WHERE pedido_id = ?";
        $stmtDeleteRelacion = $conn->prepare($queryDeleteRelacion);
        $stmtDeleteRelacion->bind_param("i", $id);
        $stmtDeleteRelacion->execute();
        
        // Luego crear la nueva relación
        $queryEmpleadoPedido = "INSERT INTO empelado_pedido (empleado_id, pedido_id) VALUES (?, ?)";
        $stmtEmpleadoPedido = $conn->prepare($queryEmpleadoPedido);
        $stmtEmpleadoPedido->bind_param("ii", $empleado_id, $id);
        $stmtEmpleadoPedido->execute();
        
        $mensaje = "Pedido actualizado correctamente";
    } else {
        $mensaje = "Error al actualizar el pedido: " . $conn->error;
    }
}

// Cargar datos del pedido para edición
if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $id = $_GET['editar'];
    
    $queryPedido = "SELECT p.*, e.nombre as empleado_nombre, ep.empleado_id 
                    FROM pedido p
                    LEFT JOIN empelado_pedido ep ON p.id = ep.pedido_id
                    LEFT JOIN empleado e ON ep.empleado_id = e.id
                    WHERE p.id = ?";
    
    $stmtPedido = $conn->prepare($queryPedido);
    $stmtPedido->bind_param("i", $id);
    $stmtPedido->execute();
    $resultadoPedido = $stmtPedido->get_result();
    
    if ($resultadoPedido->num_rows > 0) {
        $pedido = $resultadoPedido->fetch_assoc();
    }
}

// Obtener todos los pedidos para la lista
$queryPedidos = "SELECT p.id, p.fecha, p.productos_solicitados, p.cliente, p.metodo_pago,
                 e.descripcion as estado, emp.nombre as empleado_nombre
                 FROM pedido p
                 LEFT JOIN estado e ON p.estado_pedido = e.id
                 LEFT JOIN empelado_pedido ep ON p.id = ep.pedido_id
                 LEFT JOIN empleado emp ON ep.empleado_id = emp.id
                 ORDER BY p.fecha DESC";
$resultadoPedidos = $conn->query($queryPedidos);

// Filtros
$filtroCliente = isset($_GET['filtro_cliente']) ? $_GET['filtro_cliente'] : '';
$filtroEstado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';

if (!empty($filtroCliente) || !empty($filtroEstado)) {
    $queryPedidos = "SELECT p.id, p.fecha, p.productos_solicitados, p.cliente, p.metodo_pago,
                     e.descripcion as estado, emp.nombre as empleado_nombre
                     FROM pedido p
                     LEFT JOIN estado e ON p.estado_pedido = e.id
                     LEFT JOIN empelado_pedido ep ON p.id = ep.pedido_id
                     LEFT JOIN empleado emp ON ep.empleado_id = emp.id
                     WHERE 1=1";
    
    if (!empty($filtroCliente)) {
        $queryPedidos .= " AND p.cliente LIKE '%" . $conn->real_escape_string($filtroCliente) . "%'";
    }
    
    if (!empty($filtroEstado)) {
        $queryPedidos .= " AND p.estado_pedido = " . $conn->real_escape_string($filtroEstado);
    }
    
    $queryPedidos .= " ORDER BY p.fecha DESC";
    $resultadoPedidos = $conn->query($queryPedidos);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Lista de Pedidos</title>
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
      overflow-y: auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      gap: 15px;
    }

    /* Estilos para la tabla */
    .tabla-container {
      background: rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .tabla-container h2 {
      text-align: center;
      color: white;
      margin-bottom: 20px;
    }

    .filtros {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .filtro-item {
      flex: 1;
      min-width: 200px;
    }

    .filtro-item label {
      display: block;
      margin-bottom: 8px;
      color: white;
    }

    .filtro-item input,
    .filtro-item select {
      width: 100%;
      padding: 0.8rem;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 6px;
      color: white;
    }

    .filtro-item input:focus,
    .filtro-item select:focus {
      outline: none;
      border-color: rgba(160, 100, 80, 0.8);
    }

    .filtro-item select option {
      background-color: #5a4a42;
      color: white;
    }

    .tabla {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .tabla th,
    .tabla td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .tabla th {
      background-color: rgba(160, 100, 80, 0.8);
      color: white;
      font-weight: 500;
    }

    .tabla tr:hover {
      background-color: rgba(255, 255, 255, 0.05);
    }

    .tabla .acciones {
      display: flex;
      gap: 10px;
    }

    .tabla .acciones a {
      padding: 5px 10px;
      border-radius: 4px;
      text-decoration: none;
      color: white;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .tabla .acciones .editar {
      background-color: rgba(160, 100, 80, 0.8);
    }

    .tabla .acciones .eliminar {
      background-color: rgba(220, 53, 69, 0.8);
    }

    .tabla .acciones a:hover {
      opacity: 0.9;
    }

    /* Estilos para el formulario */
    .formulario {
      background: rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
      margin-top: 20px;
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

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .botones {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
    }

    .botones .cancelar {
      background-color: rgba(108, 117, 125, 0.8);
    }

    .paginacion {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 10px;
    }

    .paginacion a {
      padding: 8px 12px;
      background-color: rgba(160, 100, 80, 0.8);
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }

    .paginacion a:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    .paginacion .active {
      background-color: rgba(255, 255, 255, 0.2);
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

      .tabla {
        display: block;
        overflow-x: auto;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="sidebar">
      <h2 class="sidebar-title">Menú</h2>
      <div class="sidebar-menu">
        <a href="Pedidos.php" class="menu-item">
          <i class="fas fa-calendar-alt"></i> Calendario
        </a>
        <a href="Lista-Pedidos.php" class="menu-item active">
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

      <?php if (!empty($mensaje)): ?>
        <div id="mensajes" style="margin-bottom: 20px;"><?php echo $mensaje; ?></div>
      <?php endif; ?>

      <div class="tabla-container">
        <h2>Lista de Pedidos</h2>
        
        <form action="" method="GET" class="filtros">
          <div class="filtro-item">
            <label for="filtro_cliente">Filtrar por cliente:</label>
            <input type="text" id="filtro_cliente" name="filtro_cliente" value="<?php echo $filtroCliente; ?>">
          </div>
          <div class="filtro-item">
            <label for="filtro_estado">Filtrar por estado:</label>
            <select id="filtro_estado" name="filtro_estado">
              <option value="">Todos los estados</option>
              <?php 
              if ($resultadoEstados && $resultadoEstados->num_rows > 0) {
                $resultadoEstados->data_seek(0); // Reiniciar el puntero
                while ($row = $resultadoEstados->fetch_assoc()) {
                  $selected = ($filtroEstado == $row['id']) ? 'selected' : '';
                  echo "<option value='" . $row['id'] . "' $selected>" . $row['descripcion'] . "</option>";
                }
              }
              ?>
            </select>
          </div>
          <div class="filtro-item" style="display: flex; align-items: flex-end;">
            <button type="submit">Filtrar</button>
          </div>
        </form>
        
        <table class="tabla">
          <thead>
            <tr>
              <th>ID</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Empleado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($resultadoPedidos && $resultadoPedidos->num_rows > 0) {
              while ($row = $resultadoPedidos->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
                echo "<td>" . $row['cliente'] . "</td>";
                echo "<td>" . (strlen($row['productos_solicitados']) > 30 ? substr($row['productos_solicitados'], 0, 30) . '...' : $row['productos_solicitados']) . "</td>";
                echo "<td>" . $row['estado'] . "</td>";
                echo "<td>" . ($row['empleado_nombre'] ?? 'No asignado') . "</td>";
                echo "<td class='acciones'>";
                echo "<a href='?editar=" . $row['id'] . "' class='editar'><i class='fas fa-edit'></i> Editar</a>";
                echo "<a href='?eliminar=" . $row['id'] . "' class='eliminar' onclick='return confirm(\"¿Estás seguro de eliminar este pedido?\")'><i class='fas fa-trash'></i> Eliminar</a>";
                echo "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='7' style='text-align: center;'>No hay pedidos disponibles</td></tr>";
            }
            ?>
          </tbody>
        </table>
        
        <div class="paginacion">
          <a href="#" class="active">1</a>
          <a href="#">2</a>
          <a href="#">3</a>
          <a href="#"><i class="fas fa-chevron-right"></i></a>
        </div>
      </div>

      <?php if ($pedido): ?>
      <div class="formulario">
        <h2>Editar Pedido #<?php echo $pedido['id']; ?></h2>
        <form method="POST" action="">
          <input type="hidden" name="id" value="<?php echo $pedido['id']; ?>">
          
          <div class="form-row">
            <div>
              <label for="fecha">Fecha del pedido:</label>
              <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d', strtotime($pedido['fecha'])); ?>" required>
            </div>
            <div>
              <label for="cliente">Cliente:</label>
              <input type="text" id="cliente" name="cliente" value="<?php echo $pedido['cliente']; ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div>
              <label for="empleado">Empleado asignado:</label>
              <select id="empleado" name="empleado" required>
                <option value="">Seleccione un empleado</option>
                <?php 
                if ($resultadoEmpleados && $resultadoEmpleados->num_rows > 0) {
                  $resultadoEmpleados->data_seek(0); // Reiniciar el puntero
                  while ($row = $resultadoEmpleados->fetch_assoc()) {
                    $selected = ($pedido['empleado_id'] == $row['id']) ? 'selected' : '';
                    echo "<option value='" . $row['id'] . "' $selected>" . $row['nombre'] . "</option>";
                  }
                }
                ?>
              </select>
            </div>
            <div>
              <label for="estado">Estado:</label>
              <select id="estado" name="estado" required>
                <?php 
                if ($resultadoEstados && $resultadoEstados->num_rows > 0) {
                  $resultadoEstados->data_seek(0); // Reiniciar el puntero
                  while ($row = $resultadoEstados->fetch_assoc()) {
                    $selected = ($pedido['estado_pedido'] == $row['id']) ? 'selected' : '';
                    echo "<option value='" . $row['id'] . "' $selected>" . $row['descripcion'] . "</option>";
                  }
                }
                ?>
              </select>
            </div>
          </div>

          <div>
            <label for="metodo_pago">Método de pago:</label>
            <select id="metodo_pago" name="metodo_pago" required>
              <option value="Efectivo" <?php echo ($pedido['metodo_pago'] == 'Efectivo') ? 'selected' : ''; ?>>Efectivo</option>
              <option value="Tarjeta" <?php echo ($pedido['metodo_pago'] == 'Tarjeta') ? 'selected' : ''; ?>>Tarjeta</option>
              <option value="Transferencia" <?php echo ($pedido['metodo_pago'] == 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
            </select>
          </div>

          <label for="descripcion">Productos solicitados:</label>
          <textarea id="descripcion" name="descripcion" rows="4" required><?php echo $pedido['productos_solicitados']; ?></textarea>

          <div class="botones">
            <a href="Lista-Pedidos.php" class="menu-item cancelar" style="justify-content: center;">Cancelar</a>
            <button type="submit" name="actualizar_pedido"><i class="fas fa-save"></i> Actualizar Pedido</button>
          </div>
        </form>
      </div>
      <?php endif; ?>

    </div>
  </div>

</body>
</html>