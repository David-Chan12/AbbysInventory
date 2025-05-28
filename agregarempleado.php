<?php
session_start();
require_once 'conexion.php';

// Verificar mensajes de redirección
if (isset($_GET['mensaje'])) {
  if ($_GET['mensaje'] == 'eliminado') {
      $mensaje = "<div class='mensaje-exito'><i class='fas fa-check-circle'></i> Empleado eliminado correctamente</div>";
  } elseif ($_GET['mensaje'] == 'error') {
      $error = isset($_GET['error']) ? $_GET['error'] : 'Error desconocido';
      $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Error al eliminar: " . htmlspecialchars($error) . "</div>";
  }
}
$mensaje = "";
$nombre = $telefono = $correo = $descripcion = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger y sanitizar datos
    $nombre = isset($_POST['nombre']) ? mysqli_real_escape_string($conn, $_POST['nombre']) : "";
    $telefono = isset($_POST['telefono']) ? $_POST['telefono'] : "";
    $correo = isset($_POST['correo']) ? mysqli_real_escape_string($conn, $_POST['correo']) : "";
    $descripcion = isset($_POST['descripcion']) ? mysqli_real_escape_string($conn, $_POST['descripcion']) : "";
    
    // Limpiar y validar teléfono
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Validar campos obligatorios
    if(empty($nombre) || empty($telefono) || empty($correo)) {
        $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Complete todos los campos obligatorios</div>";
    } elseif(strlen($telefono) < 10) {
        $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> El teléfono debe tener al menos 10 dígitos</div>";
    } else {
        // Usar sentencia preparada
        $stmt = $conn->prepare("INSERT INTO empleado (nombre, telefono, correo, descripcion) VALUES (?, ?, ?, ?)");
        
        if($stmt === false) {
            $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Error al preparar la consulta</div>";
        } else {
            $stmt->bind_param("ssss", $nombre, $telefono, $correo, $descripcion);
            
            if($stmt->execute()) {
                $mensaje = "<div class='mensaje-exito'><i class='fas fa-check-circle'></i> Empleado agregado correctamente</div>";
                // Limpiar campos
                $nombre = $telefono = $correo = $descripcion = "";
            } else {
                $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Error al guardar: ".htmlspecialchars($stmt->error)."</div>";
            }
            $stmt->close();
        }
    }
}

// Obtener lista de empleados para mostrar en una tabla
$sql_empleados = "SELECT * FROM empleado ORDER BY id DESC";
$result_empleados = $conn->query($sql_empleados);
$empleados = [];

if ($result_empleados && $result_empleados->num_rows > 0) {
    while($row = $result_empleados->fetch_assoc()) {
        $empleados[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nuevo Empleado - Abby's</title>
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

    /* Estilos para mensajes */
    .mensaje-exito {
      background-color: #d4edda;
      color: #155724;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      border: 1px solid #c3e6cb;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .mensaje-error {
      background-color: #f8d7da;
      color: #721c24;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      border: 1px solid #f5c6cb;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Estilos específicos para el formulario de empleado */
    .form-container {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 8px;
      padding: 25px;
      color: #5a4a42;
      margin-top: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .form-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(160, 100, 80, 0.2);
    }

    .form-title {
      font-size: 18px;
      font-weight: 500;
      color: rgba(60, 40, 35, 0.8);
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group.full-width {
      grid-column: span 2;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-size: 14px;
      color: rgba(60, 40, 35, 0.8);
      font-weight: 500;
    }

    .form-input {
      width: 100%;
      padding: 12px 15px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(160, 100, 80, 0.3);
      border-radius: 6px;
      font-size: 14px;
      color: #333;
      transition: all 0.3s;
    }

    .form-input:focus {
      outline: none;
      border-color: rgba(160, 100, 80, 0.8);
      box-shadow: 0 0 0 2px rgba(160, 100, 80, 0.2);
    }

    .form-input:disabled {
      background-color: rgba(0, 0, 0, 0.05);
      cursor: not-allowed;
    }

    .form-textarea {
      width: 100%;
      padding: 12px 15px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(160, 100, 80, 0.3);
      border-radius: 6px;
      font-size: 14px;
      color: #333;
      transition: all 0.3s;
      min-height: 120px;
      resize: vertical;
    }

    .form-textarea:focus {
      outline: none;
      border-color: rgba(160, 100, 80, 0.8);
      box-shadow: 0 0 0 2px rgba(160, 100, 80, 0.2);
    }

    .form-select {
      width: 100%;
      padding: 12px 15px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(160, 100, 80, 0.3);
      border-radius: 6px;
      font-size: 14px;
      color: #333;
      transition: all 0.3s;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 15px center;
    }

    .form-select:focus {
      outline: none;
      border-color: rgba(160, 100, 80, 0.8);
      box-shadow: 0 0 0 2px rgba(160, 100, 80, 0.2);
    }

    .form-note {
      font-size: 12px;
      color: rgba(60, 40, 35, 0.6);
      margin-top: 5px;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid rgba(160, 100, 80, 0.2);
    }

    .btn {
      padding: 12px 25px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
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

    /* Tabla de empleados - ESTILOS MEJORADOS */
    .empleados-table-container {
      margin-top: 30px;
      overflow: hidden;
      border-radius: 8px;
    }

    .empleados-table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255, 255, 255, 0.9);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .empleados-table th,
    .empleados-table td {
      padding: 14px 18px;
      text-align: left;
      border-bottom: 1px solid rgba(160, 100, 80, 0.1);
    }

    .empleados-table th {
      background-color: rgba(160, 100, 80, 0.2);
      color: rgba(60, 40, 35, 0.9);
      font-weight: 500;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .empleados-table tr:last-child td {
      border-bottom: none;
    }

    .empleados-table tr:hover {
      background-color: rgba(160, 100, 80, 0.05);
    }

    .empleados-table .actions {
      display: flex;
      gap: 12px;
      justify-content: center;
    }

    .empleados-table .action-btn {
      background: none;
      border: none;
      cursor: pointer;
      color: rgba(160, 100, 80, 0.8);
      font-size: 16px;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }

    .empleados-table .action-btn:hover {
      background-color: rgba(160, 100, 80, 0.1);
      color: rgba(160, 100, 80, 1);
    }

    .empleados-table .action-btn.edit:hover {
      color: #2980b9;
      background-color: rgba(41, 128, 185, 0.1);
    }

    .empleados-table .action-btn.delete:hover {
      color: #e74c3c;
      background-color: rgba(231, 76, 60, 0.1);
    }

    .table-empty-message {
      padding: 20px;
      text-align: center;
      color: rgba(60, 40, 35, 0.6);
      font-style: italic;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 8px;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .table-title {
      font-size: 18px;
      font-weight: 500;
      color: #fff;
    }

    .table-actions {
      display: flex;
      gap: 10px;
    }

    .table-search {
      position: relative;
      width: 250px;
    }

    .table-search input {
      width: 100%;
      padding: 10px 15px 10px 35px;
      border-radius: 6px;
      border: none;
      background-color: rgba(255, 255, 255, 0.2);
      color: white;
      font-size: 14px;
    }

    .table-search input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    .table-search i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255, 255, 255, 0.7);
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
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      .form-group.full-width {
        grid-column: span 1;
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
        padding: 20px;
      }

      .sidebar:hover ~ .menu-container, .sidebar.open ~ .menu-container {
        margin-left: 0;
        width: 100%;
      }

      .table-search {
        width: 100%;
        margin-top: 10px;
      }

      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .empleados-table th,
      .empleados-table td {
        padding: 10px;
      }

      .empleados-table {
        font-size: 14px;
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
      <a href="empleado.php" class="menu-item">
        <i class="fas fa-users menu-icon"></i>
        <span class="menu-text">Empleados</span>
      </a>
      <a href="agregarempleado.php" class="menu-item active">
        <i class="fas fa-user-plus menu-icon"></i>
        <span class="menu-text">Nuevo Empleado</span>
      </a>
      <a href="Gturnos.php" class="menu-item">
        <i class="fas fa-calendar-alt menu-icon"></i>
        <span class="menu-text">Turnos</span>
      </a>
      <a href="Ryp.php" class="menu-item">
        <i class="fas fa-user-shield menu-icon"></i>
        <span class="menu-text">Roles y Permisos</span>
      </a>
      <a href="actividades.php" class="menu-item">
        <i class="fas fa-tasks menu-icon"></i>
        <span class="menu-text">Actividades</span>
      </a>
      <a href="nomina.html" class="menu-item">
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

    <h2 class="page-title">Nuevo Empleado</h2>
    <p class="slogan">Agrega un nuevo miembro a tu equipo de trabajo</p>

    <div class="form-container">
      <?php if(!empty($mensaje)) { echo $mensaje; } ?>
      
      <div class="form-header">
        <h3 class="form-title">Información del Empleado</h3>
      </div>

      <form id="empleadoForm" method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label for="id" class="form-label">ID</label>
            <input type="text" id="id" name="id" class="form-input" disabled placeholder="Asignado automáticamente">
            <p class="form-note">Este campo se generará automáticamente</p>
          </div>

          <div class="form-group">
            <label for="nombre" class="form-label">Nombre Completo *</label>
            <input type="text" id="nombre" name="nombre" class="form-input" required 
                   placeholder="Ej. María López Rodríguez"
                   value="<?php echo htmlspecialchars($nombre); ?>">
          </div>

          <div class="form-group">
            <label for="telefono" class="form-label">Teléfono *</label>
            <input type="tel" id="telefono" name="telefono" class="form-input" required 
                   placeholder="Ej. 555-123-4567"
                   value="<?php echo htmlspecialchars($telefono); ?>">
          </div>

          <div class="form-group">
            <label for="correo" class="form-label">Correo Electrónico *</label>
            <input type="email" id="correo" name="correo" class="form-input" required 
                   placeholder="Ej. empleado@ejemplo.com"
                   value="<?php echo htmlspecialchars($correo); ?>">
          </div>

          <div class="form-group full-width">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-textarea" 
                      placeholder="Información adicional sobre el empleado, habilidades, experiencia, etc."><?php echo htmlspecialchars($descripcion); ?></textarea>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="window.location.href='empleados.php'">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Empleado
          </button>
        </div>
      </form>
    </div>

    <!-- Tabla de empleados - MEJORADA -->
    <div class="form-container empleados-table-container">
      <div class="form-header">
        <h3 class="form-title">Empleados Registrados</h3>
        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="searchEmpleados" placeholder="Buscar empleado..." onkeyup="buscarEmpleados()">
        </div>
      </div>

      <?php if (count($empleados) > 0): ?>
        <table class="empleados-table" id="tablaEmpleados">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Teléfono</th>
              <th>Correo</th>
              <th>Descripción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($empleados as $emp): ?>
              <tr>
                <td><?php echo $emp['id']; ?></td>
                <td><?php echo htmlspecialchars($emp['nombre']); ?></td>
                <td><?php echo htmlspecialchars($emp['telefono']); ?></td>
                <td><?php echo htmlspecialchars($emp['correo']); ?></td>
                <td><?php echo htmlspecialchars(substr($emp['descripcion'], 0, 50)) . (strlen($emp['descripcion']) > 50 ? '...' : ''); ?></td>
                <td class="actions">
                  <button class="action-btn edit" title="Editar" onclick="editarEmpleado(<?php echo $emp['id']; ?>)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" title="Eliminar" onclick="eliminarEmpleado(<?php echo $emp['id']; ?>)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="table-empty-message">
          <i class="fas fa-info-circle"></i> No hay empleados registrados.
        </div>
      <?php endif; ?>
    </div>

    <div class="footer-links">
      <a href="empleado.php"><i class="fas fa-arrow-left"></i> Volver a Empleados</a>
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
        window.location.href = 'login.html';
      }
    }

    // Función para editar empleado
    function editarEmpleado(id) {
      window.location.href = 'editar_empleado.php?id=' + id;
    }

    // Función para eliminar empleado
    function eliminarEmpleado(id) {
      if (confirm('¿Estás seguro que deseas eliminar este empleado?')) {
        window.location.href = 'eliminar_empleado.php?id=' + id;
      }
    }

    // Función para buscar empleados
    function buscarEmpleados() {
      const input = document.getElementById('searchEmpleados');
      const filter = input.value.toUpperCase();
      const table = document.getElementById('tablaEmpleados');
      const tr = table.getElementsByTagName('tr');

      for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length - 1; j++) { // Excluir la columna de acciones
          const txtValue = td[j].textContent || td[j].innerText;
          if (txtValue.toUpperCase().indexOf(filter) > -1) {
            found = true;
            break;
          }
        }
        
        tr[i].style.display = found ? '' : 'none';
      }
    }

    // Validación básica del formulario
    document.getElementById('empleadoForm').addEventListener('submit', function(event) {
      const nombre = document.getElementById('nombre').value.trim();
      const telefono = document.getElementById('telefono').value.trim();
      const correo = document.getElementById('correo').value.trim();
      
      // Validar que el nombre tenga al menos 3 caracteres
      if (nombre.length < 3) {
        alert('El nombre debe tener al menos 3 caracteres');
        event.preventDefault();
        return;
      }
      
      // Validar formato de teléfono (simple)
      const telefonoRegex = /^[\d\s\-()]+$/;
      if (!telefonoRegex.test(telefono) || telefono.replace(/\D/g, '').length < 10) {
        alert('Por favor, introduce un número de teléfono válido (mínimo 10 dígitos)');
        event.preventDefault();
        return;
      }
      
      // Validar formato de correo electrónico
      const correoRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!correoRegex.test(correo)) {
        alert('Por favor, introduce un correo electrónico válido');
        event.preventDefault();
        return;
      }
    });
  </script>
</body>
</html>