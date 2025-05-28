<?php
// turnos.php
require_once 'conexion.php';
session_start();

// Obtener empleados para el select
$empleados = [];
$sql_empleado = "SELECT id, nombre FROM empleado";
$result_empleado = $conn->query($sql_empleado);

if ($result_empleado->num_rows > 0) {
    while($row = $result_empleado->fetch_assoc()) {
        $empleados[] = $row;
    }
}

// Procesar formulario para agregar turno
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_turno'])) {
    $empleado_id = $_POST['employee'];
    $fecha = $_POST['date'];
    $hora_inicio = $_POST['start-time'];
    $hora_fin = $_POST['end-time'];
    
    $sql = "INSERT INTO turnos (empleado_id, fecha, hora_inicio, hora_fin) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $empleado_id, $fecha, $hora_inicio, $hora_fin);
    
    if ($stmt->execute()) {
        $mensaje = "Turno agregado correctamente";
    } else {
        $error = "Error al agregar turno: " . $conn->error;
    }
}

// Obtener mes y año actual (o seleccionado)
$mes_actual = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$anio_actual = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Calcular primer día del mes y cantidad de días
$primer_dia = date('N', strtotime("$anio_actual-$mes_actual-01"));
$dias_en_mes = date('t', strtotime("$anio_actual-$mes_actual-01"));

// Obtener turnos para el mes seleccionado (para el calendario)
$sql_turnos_calendario = "SELECT t.*, e.nombre as empleado_nombre 
               FROM turnos t 
               JOIN empleado e ON t.empleado_id = e.id 
               WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?
               ORDER BY fecha, hora_inicio";
$stmt = $conn->prepare($sql_turnos_calendario);
$stmt->bind_param("ii", $mes_actual, $anio_actual);
$stmt->execute();
$result_turnos_calendario = $stmt->get_result();
$turnos_calendario = [];

if ($result_turnos_calendario->num_rows > 0) {
    while($row = $result_turnos_calendario->fetch_assoc()) {
        $dia = date('j', strtotime($row['fecha']));
        $turnos_calendario[$dia][] = $row;
    }
}

// Obtener todos los turnos para la lista de turnos programados
$sql_turnos_lista = "SELECT t.id, t.fecha, t.hora_inicio, t.hora_fin, e.nombre as empleado_nombre 
                     FROM turnos t 
                     JOIN empleado e ON t.empleado_id = e.id 
                     ORDER BY fecha DESC, hora_inicio ASC
                     LIMIT 20"; // Limitamos a 20 para no sobrecargar la página
$result_turnos_lista = $conn->query($sql_turnos_lista);
$turnos_lista = [];

if ($result_turnos_lista->num_rows > 0) {
    while($row = $result_turnos_lista->fetch_assoc()) {
        $turnos_lista[] = $row;
    }
}

// Cerrar conexión
$conn->close();

// Función para cambiar de mes
function cambiarMes($cambio) {
    global $mes_actual, $anio_actual;
    
    $mes_nuevo = $mes_actual + $cambio;
    $anio_nuevo = $anio_actual;
    
    if ($mes_nuevo > 12) {
        $mes_nuevo = 1;
        $anio_nuevo++;
    } elseif ($mes_nuevo < 1) {
        $mes_nuevo = 12;
        $anio_nuevo--;
    }
    
    return "Gturnos.php?mes=$mes_nuevo&anio=$anio_nuevo";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestión de Turnos - Abby's</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      background-image: url('Imagenes/sweet-composition-with-breakfast-blank-space-father-s-day.jpg');
      background-size: cover;
      background-position: center center;
      background-attachment: fixed;
      display: flex;
      padding: 0;
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

    /* Sidebar styles */
    .sidebar {
      width: 60px;
      height: 100vh;
      background-color: rgba(60, 40, 35, 0.9);
      transition: all 0.3s ease;
      overflow: hidden;
      position: fixed;
      z-index: 100;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
    }

    .sidebar:hover, .sidebar.open {
      width: 250px;
    }

    .sidebar-toggle {
      display: none;
      position: fixed;
      left: 10px;
      top: 10px;
      z-index: 101;
      background: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      font-size: 20px;
      cursor: pointer;
    }

    .sidebar-menu {
      padding-top: 20px;
    }

    .menu-item {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      transition: all 0.3s;
      white-space: nowrap;
    }

    .menu-item:hover {
      background-color: rgba(160, 100, 80, 0.8);
      color: white;
    }

    .menu-item.active {
      background-color: rgba(160, 100, 80, 0.8);
    }

    .menu-icon {
      font-size: 20px;
      margin-right: 15px;
      min-width: 20px;
    }

    .menu-text {
      font-size: 15px;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .sidebar:hover .menu-text, .sidebar.open .menu-text {
      opacity: 1;
    }

    .menu-container {
      max-width: 1200px;
      width: calc(100% - 60px);
      margin-left: 60px;
      background-color: rgba(60, 40, 35, 0.5);
      border-radius: 12px;
      padding: 40px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      transition: all 0.3s;
    }

    .sidebar:hover ~ .menu-container, .sidebar.open ~ .menu-container {
      margin-left: 250px;
      width: calc(100% - 250px);
    }

    .menu-header {
      text-align: center;
      margin-bottom: 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
      padding-bottom: 20px;
    }

    .logo {
      font-size: 28px;
      font-weight: 500;
      color: #fff;
      margin-bottom: 15px;
    }

    .page-title {
      font-size: 22px;
      font-weight: 400;
      color: #fff;
      margin-bottom: 10px;
      text-align: left;
    }

    .slogan {
      font-size: 16px;
      color: rgba(255, 255, 255, 0.8);
      font-weight: 300;
      letter-spacing: 0.8px;
      margin-bottom: 15px;
      text-align: left;
    }

    .user-info {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 20px;
    }

    .user-email {
      color: rgba(255, 255, 255, 0.9);
      margin-right: 15px;
      font-size: 13px;
    }

    .notification-btn {
      background: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      cursor: pointer;
      font-size: 13px;
      padding: 10px 12px;
      border-radius: 6px;
      transition: all 0.3s;
      margin-right: 10px;
      position: relative;
    }

    .notification-btn:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: #e74c3c;
      color: white;
      font-size: 10px;
      font-weight: bold;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid rgba(60, 40, 35, 0.8);
    }

    .logout-btn {
      background: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      cursor: pointer;
      font-size: 13px;
      padding: 10px 18px;
      border-radius: 6px;
      transition: all 0.3s;
    }

    .logout-btn:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    /* Estilos específicos para la gestión de turnos */
    .shifts-container {
      display: flex;
      gap: 25px;
      margin-top: 20px;
    }

    .shifts-calendar {
      flex: 1;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 8px;
      padding: 20px;
      color: #5a4a42;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .calendar-title {
      font-size: 18px;
      font-weight: 500;
      color: rgba(60, 40, 35, 0.8);
    }

    .calendar-nav {
      display: flex;
      gap: 10px;
    }

    .calendar-nav-btn {
      background: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
    }

    .calendar-day-header {
      text-align: center;
      font-weight: 500;
      font-size: 14px;
      padding: 5px;
      color: rgba(60, 40, 35, 0.8);
    }

    .calendar-day {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(160, 100, 80, 0.2);
      border-radius: 4px;
      padding: 8px;
      min-height: 80px;
      position: relative;
    }

    .day-number {
      font-weight: 500;
      margin-bottom: 5px;
    }

    .today {
      background: rgba(160, 100, 80, 0.1);
      border: 1px solid rgba(160, 100, 80, 0.5);
    }

    .shift-event {
      background: rgba(160, 100, 80, 0.8);
      color: white;
      font-size: 11px;
      padding: 2px 5px;
      border-radius: 3px;
      margin-bottom: 3px;
      cursor: pointer;
    }

    .shift-event:hover {
      background: rgba(180, 120, 100, 0.9);
    }

    .shifts-sidebar {
      width: 300px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 8px;
      padding: 20px;
      color: #5a4a42;
    }

    .sidebar-title {
      font-size: 18px;
      font-weight: 500;
      color: rgba(60, 40, 35, 0.8);
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(160, 100, 80, 0.2);
    }

    .shift-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .form-group label {
      font-size: 14px;
      color: rgba(60, 40, 35, 0.8);
    }

    .form-control {
      padding: 8px 12px;
      border: 1px solid rgba(160, 100, 80, 0.3);
      border-radius: 4px;
      font-size: 14px;
      background: rgba(255, 255, 255, 0.8);
    }

    .form-control:focus {
      outline: none;
      border-color: rgba(160, 100, 80, 0.8);
    }

    .btn {
      padding: 10px 15px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-primary {
      background: rgba(160, 100, 80, 0.8);
      color: white;
    }

    .btn-primary:hover {
      background: rgba(180, 120, 100, 0.9);
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.8);
      color: rgba(60, 40, 35, 0.8);
      border: 1px solid rgba(160, 100, 80, 0.3);
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.9);
    }

    .shifts-list {
      margin-top: 20px;
      max-height: 300px;
      overflow-y: auto;
    }

    .shift-item {
      padding: 10px;
      border-bottom: 1px solid rgba(160, 100, 80, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .shift-item:last-child {
      border-bottom: none;
    }

    .shift-info {
      font-size: 13px;
    }

    .shift-actions {
      display: flex;
      gap: 5px;
    }

    .shift-actions button {
      background: none;
      border: none;
      cursor: pointer;
      color: rgba(160, 100, 80, 0.8);
      font-size: 12px;
    }

    .shift-actions button:hover {
      color: rgba(180, 120, 100, 0.9);
    }

    .footer-links {
      display: flex;
      justify-content: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    .footer-links a {
      color: white;
      text-decoration: none;
      margin: 0 12px;
      font-size: 13px;
      font-weight: 400;
      transition: all 0.3s;
      padding: 5px 12px;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }

    .alert {
      padding: 10px 15px;
      margin-bottom: 15px;
      border-radius: 4px;
      font-size: 14px;
    }

    .alert-success {
      background-color: rgba(46, 204, 113, 0.2);
      border: 1px solid rgba(46, 204, 113, 0.5);
      color: #fff;
    }

    .alert-danger {
      background-color: rgba(231, 76, 60, 0.2);
      border: 1px solid rgba(231, 76, 60, 0.5);
      color: #fff;
    }

    @media (max-width: 900px) {
      .shifts-container {
        flex-direction: column;
      }
      
      .shifts-sidebar {
        width: 100%;
      }
      
      body {
        background-size: cover;
        background-position: center center;
      }

      .sidebar {
        width: 0;
        z-index: 1000;
      }

      .sidebar.open {
        width: 250px;
      }

      .sidebar-toggle {
        display: block;
      }

      .menu-container {
        width: 100%;
        margin-left: 0;
        border-radius: 0;
      }

      .sidebar:hover ~ .menu-container, .sidebar.open ~ .menu-container {
        margin-left: 0;
        width: 100%;
      }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-menu">
      <a href="empleado.php" class="menu-item">
        <i class="fas fa-home menu-icon"></i>
        <span class="menu-text">Inicio</span>
      </a>
      <a href="Gempleados.php" class="menu-item">
        <i class="fas fa-users menu-icon"></i>
        <span class="menu-text">Empleados</span>
      </a>
      <a href="agregarempleado.php" class="menu-item">
        <i class="fas fa-user-plus menu-icon"></i>
        <span class="menu-text">Nuevo Empleado</span>
      </a>
      <a href="turnos.php" class="menu-item active">
        <i class="fas fa-calendar-alt menu-icon"></i>
        <span class="menu-text">Turnos</span>
      </a>
      <a href="rolesypermisos.php" class="menu-item">
        <i class="fas fa-user-shield menu-icon"></i>
        <span class="menu-text">Roles y Permisos</span>
      </a>
      <a href="Actividades.php" class="menu-item">
        <i class="fas fa-tasks menu-icon"></i>
        <span class="menu-text">Actividades</span>
      </a>
      <a href="nomina.php" class="menu-item">
        <i class="fas fa-file-invoice-dollar menu-icon"></i>
        <span class="menu-text">Nómina</span>
      </a>
    </div>
  </div>

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
          <i class="fas fa-sign-out-alt"></i>
          Cerrar Sesión
        </button>
      </div>
    </header>

    <h2 class="page-title">Gestión de Turnos</h2>
    <p class="slogan">Organiza los horarios de tu equipo de pasteleros</p>

     <!-- Mensajes de éxito/error -->
     <?php if (isset($mensaje)): ?>
      <div class="alert alert-success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

        
      <div class="shifts-container">
  <div class="shifts-calendar">
    <div class="calendar-header">
      <h3 class="calendar-title">
        <?php 
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
        $nombre_mes = date('F', strtotime("$anio_actual-$mes_actual-01"));
        $meses_es = [
            'January' => 'Enero',
            'February' => 'Febrero',
            'March' => 'Marzo',
            'April' => 'Abril',
            'May' => 'Mayo',
            'June' => 'Junio',
            'July' => 'Julio',
            'August' => 'Agosto',
            'September' => 'Septiembre',
            'October' => 'Octubre',
            'November' => 'Noviembre',
            'December' => 'Diciembre'
        ];
        echo $meses_es[$nombre_mes] . ' ' . $anio_actual;
        ?>
      </h3>
      <div class="calendar-nav">
        <a href="<?php echo cambiarMes(-1); ?>" class="calendar-nav-btn">
          <i class="fas fa-chevron-left"></i>
        </a>
        <a href="turnos.php" class="calendar-nav-btn">Hoy</a>
        <a href="<?php echo cambiarMes(1); ?>" class="calendar-nav-btn">
          <i class="fas fa-chevron-right"></i>
        </a>
      </div>
    </div>
    
    <div class="calendar-grid">
      <!-- Encabezados de días -->
      <div class="calendar-day-header">Lun</div>
      <div class="calendar-day-header">Mar</div>
      <div class="calendar-day-header">Mié</div>
      <div class="calendar-day-header">Jue</div>
      <div class="calendar-day-header">Vie</div>
      <div class="calendar-day-header">Sáb</div>
      <div class="calendar-day-header">Dom</div>
      
      <!-- Días del mes -->
      <?php
      // Días vacíos al inicio (del mes anterior)
      for ($i = 1; $i < $primer_dia; $i++) {
          echo '<div class="calendar-day" style="opacity: 0.3;">';
          echo '<div class="day-number"></div>';
          echo '</div>';
      }
      
      // Días del mes actual
      $hoy = date('j');
      $mes_hoy = date('m');
      $anio_hoy = date('Y');
      
      for ($dia = 1; $dia <= $dias_en_mes; $dia++) {
          $es_hoy = ($dia == $hoy && $mes_actual == $mes_hoy && $anio_actual == $anio_hoy);
          $clase_today = $es_hoy ? 'today' : '';
          
          echo '<div class="calendar-day ' . $clase_today . '">';
          echo '<div class="day-number">' . $dia . '</div>';
          
          // Mostrar turnos para este día
          if (isset($turnos_calendario[$dia])) {
              foreach ($turnos_calendario[$dia] as $turno) {
                  echo '<div class="shift-event" onclick="verDetallesTurno(' . $turno['id'] . ')">';
                  echo htmlspecialchars($turno['empleado_nombre']) . ': ';
                  echo substr($turno['hora_inicio'], 0, 5) . '-';
                  echo substr($turno['hora_fin'], 0, 5);
                  echo '</div>';
              }
          }
          
          echo '</div>';
      }
      
      // Días vacíos al final (del siguiente mes)
      $total_celdas = $primer_dia + $dias_en_mes - 1;
      $filas_completas = ceil($total_celdas / 7);
      $celdas_vacias = $filas_completas * 7 - $total_celdas;
      
      for ($i = 0; $i < $celdas_vacias; $i++) {
          echo '<div class="calendar-day" style="opacity: 0.3;">';
          echo '<div class="day-number"></div>';
          echo '</div>';
      }
      ?>
    </div>
  </div>
      
      <div class="shifts-sidebar">
        <h3 class="sidebar-title">Agregar Turno</h3>
        <form class="shift-form" method="POST" action="Gturnos.php">
          <div class="form-group">
            <label for="employee">Empleado</label>
            <select id="employee" name="employee" class="form-control" required>
              <option value="">Seleccionar empleado</option>
              <?php foreach ($empleados as $empleado): ?>
                <option value="<?php echo $empleado['id']; ?>">
                  <?php echo htmlspecialchars($empleado['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="date">Fecha</label>
            <input type="date" id="date" name="date" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="start-time">Hora de inicio</label>
            <input type="time" id="start-time" name="start-time" class="form-control" value="08:00" required>
          </div>
          
          <div class="form-group">
            <label for="end-time">Hora de fin</label>
            <input type="time" id="end-time" name="end-time" class="form-control" value="14:00" required>
          </div>

          
          <button type="submit" name="agregar_turno" class="btn btn-primary">Guardar Turno</button>
        </form>
        
        <h3 class="sidebar-title" style="margin-top: 25px;">Turnos Programados</h3>
        <div class="shifts-list">
          <?php if (empty($turnos_lista)): ?>
            <p>No hay turnos programados.</p>
          <?php else: ?>
            <?php foreach ($turnos_lista as $turno): ?>
              <div class="shift-item">
                <div class="shift-info">
                  <strong><?php echo htmlspecialchars($turno['empleado_nombre']); ?></strong><br>
                  <?php echo date('d/m/Y', strtotime($turno['fecha'])); ?> - 
                  <?php echo substr($turno['hora_inicio'], 0, 5); ?> a <?php echo substr($turno['hora_fin'], 0, 5); ?>
                </div>
                <div class="shift-actions">
                  <button title="Editar" onclick="editarTurno(<?php echo $turno['id']; ?>)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button title="Eliminar" onclick="eliminarTurno(<?php echo $turno['id']; ?>)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-links">
      <a href="empleado.php"><i class="fas fa-arrow-left"></i> Volver al Menú</a>
      <a href="#" onclick="imprimirHorarios()"><i class="fas fa-print"></i> Imprimir Horarios</a>
      <a href="Ayuda.html"><i class="fas fa-question-circle"></i> Ayuda</a>
    </div>
  </div>

  <script>
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('open');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      
      if (window.innerWidth <= 900 && 
          !sidebar.contains(event.target) && 
          !sidebarToggle.contains(event.target) && 
          sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
      }
    });

    // Función para cerrar sesión
    function cerrarSesion() {
      if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
        window.location.href = 'login.php';
      }
    }

    function imprimirHorarios() {
      window.print();
    }

    // Función para editar turno
    function editarTurno(id) {
      window.location.href = 'editar_turno.php?id=' + id;
    }

    // Función para eliminar turno
    function eliminarTurno(id) {
      if (confirm('¿Estás seguro que deseas eliminar este turno?')) {
        window.location.href = 'eliminar_turno.php?id=' + id;
      }
    }

    // Función para ver detalles de un turno
    function verDetallesTurno(id) {
      window.location.href = 'editar_turno.php?id=' + id + '&modo=ver';
    }

    // Establecer la fecha actual en el formulario al cargar
    document.addEventListener('DOMContentLoaded', function() {
      const fechaHoy = new Date().toISOString().split('T')[0];
      document.getElementById('date').value = fechaHoy;
    });
  </script>
</body>
</html>
