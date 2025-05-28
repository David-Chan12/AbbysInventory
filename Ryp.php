<?php
session_start();
require_once 'conexion.php';
require_once 'permisos.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Verificar si tiene permiso para acceder a esta página
verificarAcceso('permisos');

// Obtener lista de empleados
$empleados = [];
$query = "SELECT * FROM empleado";
$result = $conn->query($query);
if ($result) {
    $empleados = $result->fetch_all(MYSQLI_ASSOC);
}

// Obtener empleado seleccionado (si existe)
$empleado_seleccionado = null;
if (isset($_GET['empleado_id'])) {
    $empleado_id = $_GET['empleado_id'];
    foreach ($empleados as $emp) {
        if ($emp['id'] == $empleado_id) {
            $empleado_seleccionado = $emp;
            break;
        }
    }
}

// Verificar si el empleado ya tiene cuenta
$tiene_cuenta = false;
if ($empleado_seleccionado) {
    $query = "SELECT * FROM usuarios WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $username = strtolower(str_replace(' ', '.', $empleado_seleccionado['nombre']));
    $email = $empleado_seleccionado['correo'];
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $tiene_cuenta = $result->num_rows > 0;
}

// Procesar formulario de permisos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_permisos'])) {
    $empleado_id = $_POST['empleado_id'];
    $permisos = isset($_POST['permisos']) ? $_POST['permisos'] : [];
    
    if (guardarPermisos($empleado_id, $permisos)) {
        $_SESSION['mensaje'] = "Permisos actualizados correctamente";
    } else {
        $_SESSION['error'] = "Error al guardar los permisos";
    }
    
    header("Location: Ryp.php?empleado_id=".$empleado_id);
    exit();
}

// Procesar creación de cuenta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cuenta'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $tipo_cuenta = $_POST['account-type'];
    
    // Determinar si es administrador según el tipo de cuenta
    $es_admin = ($tipo_cuenta === 'admin') ? 1 : 0;
    
    // Insertar en la tabla usuarios
    $query = "INSERT INTO usuarios (username, password, email, es_admin) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $username, $password, $email, $es_admin);
    
    if ($stmt->execute()) {
        $usuario_id = $conn->insert_id; // Obtener el ID del usuario recién creado
        
        // Si es administrador, asignar todos los permisos disponibles
        if ($es_admin) {
            $permisos = ['inventario', 'empleados', 'ventas', 'recetas', 'reportes', 'eventos', 'permisos'];
            guardarPermisos($_POST['empleado_id'], $permisos);
        }
        
        $_SESSION['mensaje'] = "Cuenta creada correctamente" . ($es_admin ? " con privilegios de administrador" : "");
        header("Location: Ryp.php?empleado_id=".$_POST['empleado_id']);
        exit();
    } else {
        $_SESSION['error'] = "Error al crear la cuenta: " . $conn->error;
    }
}

// Obtener permisos del empleado seleccionado
$permisos_empleado = [];
if ($empleado_seleccionado) {
    $permisos_empleado = obtenerPermisos($empleado_seleccionado['id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Permisos - Abby's</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/Ryp.css">
  <style>
    .selected-employee {
      background-color: #f0f7ff;
    }
    
    .employee-info {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e0e0e0;
    }
    
    .employee-avatar {
      font-size: 40px;
      color: #6c757d;
    }
    
    .employee-details h4 {
      margin: 0;
      color: #343a40;
      font-weight: 500;
    }
    
    .employee-details p {
      margin: 5px 0;
      color: #6c757d;
      font-size: 14px;
    }
    
    .search-container {
      position: relative;
      width: 250px;
    }
    
    .search-input {
      width: 100%;
      padding: 8px 15px 8px 35px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: 'Montserrat', sans-serif;
    }
    
    .search-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
    }
    
    .status-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .status-badge.active {
      background-color: #e6f7ee;
      color: #28a745;
    }
    
    .status-badge.inactive {
      background-color: #fef0f0;
      color: #dc3545;
    }
    
    /* Estilo para las categorías principales */
    .main-permission-item {
      margin-bottom: 15px;
      display: flex;
      align-items: center;
    }
    
    .main-permission-item i {
      margin-right: 10px;
      width: 20px;
      color: #6c757d;
    }
    
    /* Nuevos estilos para la sección de creación de cuenta */
    .account-section {
      margin-top: 30px;
      padding: 20px;
      background-color: #f8f9fa;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .account-section h3 {
      margin-top: 0;
      color: #343a40;
      border-bottom: 1px solid #dee2e6;
      padding-bottom: 10px;
    }
    
    .account-form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    
    .form-group input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-family: 'Montserrat', sans-serif;
    }
    
    .form-actions {
      grid-column: span 2;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    .password-strength {
      font-size: 12px;
      margin-top: 5px;
      color: #6c757d;
    }
    
    .password-strength.weak {
      color: #dc3545;
    }
    
    .password-strength.medium {
      color: #fd7e14;
    }
    
    .password-strength.strong {
      color: #28a745;
    }
    
    .alert {
      padding: 10px 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }
    
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    /* Estilo para resaltar la opción de administrador */
    .admin-option {
      background-color: #fff3cd;
      font-weight: bold;
    }
  </style>
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
      <a href="Nuevo Empleado.html" class="menu-item">
        <i class="fas fa-user-plus menu-icon"></i>
        <span class="menu-text">Nuevo Empleado</span>
      </a>
      <a href="Gturnos.php" class="menu-item">
        <i class="fas fa-calendar-alt menu-icon"></i>
        <span class="menu-text">Turnos</span>
      </a>
      <a href="Ryp.php" class="menu-item">
        <i class="fas fa-user-shield menu-icon"></i>
        <span class="menu-text">Permisos</span>
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
        <p class="user-email"><?php echo $_SESSION['usuario']['email'] ?? 'usuario@abby.com'; ?></p>
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

    <h2 class="page-title">Gestión de Permisos</h2>
    <p class="slogan">Asigna permisos de acceso específicos a cada miembro de tu equipo</p>

    <?php if (isset($_SESSION['mensaje'])): ?>
      <div class="alert alert-success">
        <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-error">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <div class="roles-container">
      <div class="roles-list">
        <div class="roles-header">
          <h3 class="roles-title">Lista de Empleados</h3>
          <div class="search-container">
            <input type="text" id="employeeSearch" class="search-input" placeholder="Buscar empleado...">
            <i class="fas fa-search search-icon"></i>
          </div>
        </div>
        
        <table class="roles-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($empleados as $empleado): ?>
              <tr class="<?php echo ($empleado_seleccionado && $empleado_seleccionado['id'] == $empleado['id']) ? 'selected-employee' : ''; ?>">
                <td><?php echo htmlspecialchars($empleado['nombre']); ?></td>
                <td>
                  <span class="status-badge active">Activo</span>
                </td>
                <td>
                  <div class="role-actions">
                    <a href="Ryp.php?empleado_id=<?php echo $empleado['id']; ?>" title="Editar permisos">
                      <i class="fas fa-user-cog"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <div class="permissions-sidebar">
        <?php if ($empleado_seleccionado): ?>
          <h3 class="sidebar-title">Permisos del Empleado</h3>
          <form class="role-form" method="POST">
            <input type="hidden" name="empleado_id" value="<?php echo $empleado_seleccionado['id']; ?>">
            <input type="hidden" name="guardar_permisos" value="1">
            
            <div class="employee-info">
              <div class="employee-avatar">
                <i class="fas fa-user-circle"></i>
              </div>
              <div class="employee-details">
                <h4><?php echo htmlspecialchars($empleado_seleccionado['nombre']); ?></h4>
                <p class="employee-email"><?php echo htmlspecialchars($empleado_seleccionado['correo']); ?></p>
              </div>
            </div>
            
            <h4 class="sidebar-title" style="margin-top: 20px; font-size: 16px;">Asignar Permisos</h4>
            <div class="permissions-list">
              <div class="main-permission-item">
                <i class="fas fa-boxes"></i>
                <input type="checkbox" id="perm-inventario" name="permisos[]" value="inventario" class="permission-checkbox" <?php echo in_array('inventario', $permisos_empleado) ? 'checked' : ''; ?>>
                <label for="perm-inventario" class="permission-label">Inventario</label>
              </div>
              
              <div class="main-permission-item">
                <i class="fas fa-users"></i>
                <input type="checkbox" id="perm-empleados" name="permisos[]" value="empleados" class="permission-checkbox" <?php echo in_array('empleados', $permisos_empleado) ? 'checked' : ''; ?>>
                <label for="perm-empleados" class="permission-label">Empleados</label>
              </div>
              
              <div class="main-permission-item">
                <i class="fas fa-cash-register"></i>
                <input type="checkbox" id="perm-ventas" name="permisos[]" value="ventas" class="permission-checkbox" <?php echo in_array('ventas', $permisos_empleado) ? 'checked' : ''; ?>>
                <label for="perm-ventas" class="permission-label">Ventas</label>
              </div>
              
              <div class="main-permission-item">
                <i class="fas fa-utensils"></i>
                <input type="checkbox" id="perm-recetas" name="permisos[]" value="recetas" class="permission-checkbox" <?php echo in_array('recetas', $permisos_empleado) ? 'checked' : ''; ?>>
                <label for="perm-recetas" class="permission-label">Recetas</label>
              </div>
              
              <div class="main-permission-item">
                <i class="fas fa-chart-bar"></i>
                <input type="checkbox" id="perm-reportes" name="permisos[]" value="reportes" class="permission-checkbox" <?php echo in_array('reportes', $permisos_empleado) ? 'checked' : ''; ?>>
                <label for="perm-reportes" class="permission-label">Reportes</label>
              </div>
              
              <div class="main-permission-item">
                <i class="fas fa-calendar-check"></i>
                <input type="checkbox" id="perm-eventos" name="permisos[]" value="eventos" class="permission-checkbox" <?php echo in_array('eventos', $permisos_empleado) ? 'checked' : ''; ?>>
                <label for="perm-eventos" class="permission-label">Eventos</label>
              </div>
              
              <div class="main-permission-item">
                <i class="fas fa-user-shield"></i>
                <input type="checkbox" id="perm-permisos" name="permisos[]" value="permisos" class="permission-checkbox" <?php echo in_array('permisos', $permisos_empleado) ? 'checked' : ''; ?>>
                <label for="perm-permisos" class="permission-label">Gestión de Permisos</label>
              </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
              <button type="submit" class="btn btn-primary" style="flex: 1;">
                <i class="fas fa-save"></i> Guardar Cambios
              </button>
              <a href="Ryp.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                <i class="fas fa-times"></i> Cancelar
              </a>
            </div>
          </form>
          
          <!-- Sección para creación de cuenta -->
          <div class="account-section">
            <h3><i class="fas fa-user-plus"></i> Crear Cuenta de Acceso</h3>
            
            <?php if ($tiene_cuenta): ?>
              <div class="alert alert-success">
                Este empleado ya tiene una cuenta de acceso.
              </div>
            <?php else: ?>
              <form class="account-form" method="POST">
                <input type="hidden" name="empleado_id" value="<?php echo $empleado_seleccionado['id']; ?>">
                <input type="hidden" name="crear_cuenta" value="1">
                
                <div class="form-group">
                  <label for="username">Nombre de Usuario</label>
                  <input type="text" id="username" name="username" 
                         value="<?php echo strtolower(str_replace(' ', '.', $empleado_seleccionado['nombre'])); ?>" 
                         placeholder="Ej: maria.gonzalez" required>
                </div>
                
                <div class="form-group">
                  <label for="email">Correo Electrónico</label>
                  <input type="email" id="email" name="email" 
                         value="<?php echo htmlspecialchars($empleado_seleccionado['correo']); ?>" 
                         placeholder="Ej: maria.gonzalez@abby.com" required>
                </div>
                
                <div class="form-group">
                  <label for="password">Contraseña</label>
                  <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
                  <div id="password-strength" class="password-strength">Seguridad: <span id="strength-level">Débil</span></div>
                </div>
                
                <div class="form-group">
                  <label for="confirm-password">Confirmar Contraseña</label>
                  <input type="password" id="confirm-password" name="confirm_password" placeholder="Repite la contraseña" required>
                </div>
                
                <div class="form-group">
                  <label for="account-type">Tipo de Cuenta</label>
                  <select id="account-type" name="account-type" class="search-input">
                    <option value="basic">Básica</option>
                    <option value="manager">Gerente</option>
                    <option value="admin" class="admin-option">Administrador</option>
                  </select>
                  <p style="font-size: 12px; margin-top: 5px; color: #856404;">
                    <i class="fas fa-info-circle"></i> 
                    El tipo "Administrador" otorga todos los permisos y acceso completo al sistema.
                  </p>
                </div>
                
                <div class="form-actions">
                  <button type="button" class="btn btn-secondary" onclick="resetAccountForm()">
                    <i class="fas fa-times"></i> Cancelar
                  </button>
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Cuenta
                  </button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="employee-info">
            <div class="employee-avatar">
              <i class="fas fa-user-circle"></i>
            </div>
            <div class="employee-details">
              <h4>Seleccione un empleado</h4>
              <p>Para ver y editar sus permisos</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="footer-links">
      <a href="empleado.php"><i class="fas fa-arrow-left"></i> Volver al Menú</a>
      <a href="#" onclick="exportarPermisos()"><i class="fas fa-file-export"></i> Exportar Permisos</a>
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

    // Seleccionar empleado de la tabla
    const employeeRows = document.querySelectorAll('.roles-table tbody tr');
    employeeRows.forEach(row => {
      row.addEventListener('click', function() {
        employeeRows.forEach(r => r.classList.remove('selected-employee'));
        this.classList.add('selected-employee');
      });
    });

    // Función para cerrar sesión
    function cerrarSesion() {
      if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
        window.location.href = 'logout.php';
      }
    }

    // Función para exportar permisos
    function exportarPermisos() {
      alert('Exportando permisos... Esta funcionalidad generaría un archivo con los permisos de todos los empleados.');
    }

    // Búsqueda de empleados
    document.getElementById('employeeSearch').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = document.querySelectorAll('.roles-table tbody tr');

      rows.forEach(row => {
        const employeeName = row.cells[0].textContent.toLowerCase();
        if (employeeName.includes(searchTerm)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
    
    // Validación de contraseña (solo visual)
    document.getElementById('password')?.addEventListener('input', function(e) {
      const password = e.target.value;
      const strengthText = document.getElementById('strength-level');
      const strengthMeter = document.getElementById('password-strength');
      
      if (password.length === 0) {
        strengthText.textContent = 'Débil';
        strengthMeter.className = 'password-strength';
      } else if (password.length < 6) {
        strengthText.textContent = 'Débil';
        strengthMeter.className = 'password-strength weak';
      } else if (password.length < 10) {
        strengthText.textContent = 'Media';
        strengthMeter.className = 'password-strength medium';
      } else {
        strengthText.textContent = 'Fuerte';
        strengthMeter.className = 'password-strength strong';
      }
    });
    
    // Resetear formulario de cuenta
    function resetAccountForm() {
      document.querySelector('.account-form').reset();
      const strengthLevel = document.getElementById('strength-level');
      if (strengthLevel) {
        strengthLevel.textContent = 'Débil';
      }
      const strengthMeter = document.getElementById('password-strength');
      if (strengthMeter) {
        strengthMeter.className = 'password-strength';
      }
    }
  </script>
</body>
</html>
