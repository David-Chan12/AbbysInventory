<?php
// editar_turno.php
require_once 'conexion.php';
session_start();

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Gturnos.php');
    exit;
}

$id_turno = $_GET['id'];
$modo_ver = isset($_GET['modo']) && $_GET['modo'] === 'ver';

// Obtener empleados para el select
$empleados = [];
$sql_empleado = "SELECT id, nombre FROM empleado";
$result_empleado = $conn->query($sql_empleado);

if ($result_empleado->num_rows > 0) {
    while($row = $result_empleado->fetch_assoc()) {
        $empleados[] = $row;
    }
}

// Obtener datos del turno
$sql_turno = "SELECT t.*, e.nombre as empleado_nombre 
              FROM turnos t 
              JOIN empleado e ON t.empleado_id = e.id 
              WHERE t.id = ?";
$stmt = $conn->prepare($sql_turno);
$stmt->bind_param("i", $id_turno);
$stmt->execute();
$result_turno = $stmt->get_result();

if ($result_turno->num_rows === 0) {
    header('Location: Gturnos.php');
    exit;
}

$turno = $result_turno->fetch_assoc();

// Procesar formulario para actualizar turno
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_turno'])) {
    $empleado_id = $_POST['employee'];
    $fecha = $_POST['date'];
    $hora_inicio = $_POST['start-time'];
    $hora_fin = $_POST['end-time'];
    
    $sql = "UPDATE turnos SET empleado_id = ?, fecha = ?, hora_inicio = ?, hora_fin = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $empleado_id, $fecha, $hora_inicio, $hora_fin, $id_turno);
    
    if ($stmt->execute()) {
        $mensaje = "Turno actualizado correctamente";
        // Actualizar los datos del turno para mostrar los cambios
        $sql_turno = "SELECT t.*, e.nombre as empleado_nombre 
                      FROM turnos t 
                      JOIN empleado e ON t.empleado_id = e.id 
                      WHERE t.id = ?";
        $stmt = $conn->prepare($sql_turno);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $result_turno = $stmt->get_result();
        $turno = $result_turno->fetch_assoc();
    } else {
        $error = "Error al actualizar turno: " . $conn->error;
    }
}

// Cerrar conexión
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $modo_ver ? 'Ver Turno' : 'Editar Turno'; ?> - Abby's</title>
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

    .edit-form-container {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 8px;
      padding: 25px;
      color: #5a4a42;
      max-width: 500px;
      margin: 0 auto;
    }

    .form-title {
      font-size: 20px;
      font-weight: 500;
      color: rgba(60, 40, 35, 0.8);
      margin-bottom: 20px;
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

    .form-control:disabled {
      background: rgba(240, 240, 240, 0.8);
      cursor: not-allowed;
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

    .form-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
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

    @media (max-width: 900px) {
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

      .edit-form-container {
        max-width: 100%;
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
      <a href="empleados.php" class="menu-item">
        <i class="fas fa-users menu-icon"></i>
        <span class="menu-text">Empleados</span>
      </a>
      <a href="Nuevo empleado.php" class="menu-item">
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

    <h2 class="page-title"><?php echo $modo_ver ? 'Ver Turno' : 'Editar Turno'; ?></h2>
    <p class="slogan">Gestiona los horarios de tu equipo de pasteleros</p>

    <!-- Mensajes de éxito/error -->
    <?php if (isset($mensaje)): ?>
      <div class="alert alert-success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="edit-form-container">
      <h3 class="form-title"><?php echo $modo_ver ? 'Detalles del Turno' : 'Modificar Turno'; ?></h3>
      <form class="shift-form" method="POST" action="editar_turno.php?id=<?php echo $id_turno; ?>">
        <div class="form-group">
          <label for="employee">Empleado</label>
          <select id="employee" name="employee" class="form-control" <?php echo $modo_ver ? 'disabled' : ''; ?> required>
            <?php foreach ($empleados as $empleado): ?>
              <option value="<?php echo $empleado['id']; ?>" <?php echo ($empleado['id'] == $turno['empleado_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($empleado['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="date">Fecha</label>
          <input type="date" id="date" name="date" class="form-control" value="<?php echo $turno['fecha']; ?>" <?php echo $modo_ver ? 'disabled' : ''; ?> required>
        </div>
        
        <div class="form-group">
          <label for="start-time">Hora de inicio</label>
          <input type="time" id="start-time" name="start-time" class="form-control" value="<?php echo $turno['hora_inicio']; ?>" <?php echo $modo_ver ? 'disabled' : ''; ?> required>
        </div>
        
        <div class="form-group">
          <label for="end-time">Hora de fin</label>
          <input type="time" id="end-time" name="end-time" class="form-control" value="<?php echo $turno['hora_fin']; ?>" <?php echo $modo_ver ? 'disabled' : ''; ?> required>
        </div>

        <div class="form-actions">
          <a href="Gturnos.php" class="btn btn-secondary">Volver</a>
          <?php if (!$modo_ver): ?>
            <button type="submit" name="actualizar_turno" class="btn btn-primary">Guardar Cambios</button>
          <?php else: ?>
            <a href="editar_turno.php?id=<?php echo $id_turno; ?>" class="btn btn-primary">Editar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="footer-links">
      <a href="Gturnos.php"><i class="fas fa-arrow-left"></i> Volver a Turnos</a>
      <?php if (!$modo_ver): ?>
        <a href="eliminar_turno.php?id=<?php echo $id_turno; ?>" onclick="return confirm('¿Estás seguro que deseas eliminar este turno?')">
          <i class="fas fa-trash"></i> Eliminar Turno
        </a>
      <?php endif; ?>
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
        window.location.href = 'Login.html';
      }
    }
  </script>
</body>
</html>
