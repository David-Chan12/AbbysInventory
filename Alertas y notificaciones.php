<?php
session_start();
require_once 'conexion.php';

// Ejecutar verificación automática de notificaciones cada vez que se carga la página
// Solo ejecutar cada 5 minutos para evitar sobrecarga
if (!isset($_SESSION['ultima_verificacion']) || (time() - $_SESSION['ultima_verificacion']) > 300) {
    include_once 'scripts_notificaciones/verificar_automatico.php';
    $_SESSION['ultima_verificacion'] = time();
}

// Al inicio del archivo, después de session_start():
// Verificar si hay notificaciones no leídas para mostrar un badge
$sql_unread = "SELECT COUNT(*) AS unread FROM notificacion WHERE estado = 'no leida'";
$result_unread = $conn->query($sql_unread);
$unread_count = $result_unread->fetch_assoc()['unread'];

// Puedes usar $unread_count para mostrar un badge en tu menú principal

// Consulta para obtener las notificaciones de la base de datos
$sql = "SELECT * FROM notificacion ORDER BY fecha DESC";
$result = $conn->query($sql);

// Contadores para cada tipo de notificación
$totalCount = 0;
$alertCount = 0;
$warningCount = 0;
$infoCount = 0;
$successCount = 0;
$reminderCount = 0;

// Array para almacenar las notificaciones
$notifications = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totalCount++;
        
        // Determinar el tipo de notificación basado en el campo 'tipo'
        $notificationType = strtolower($row['tipo']);
        
        // Incrementar el contador correspondiente
        if ($notificationType == 'alerta') {
            $alertCount++;
            $cssClass = 'alert';
            $icon = 'fas fa-exclamation-triangle';
        } elseif ($notificationType == 'advertencia') {
            $warningCount++;
            $cssClass = 'warning';
            $icon = 'fas fa-exclamation-circle';
        } elseif ($notificationType == 'informacion' || $notificationType == 'información') {
            $infoCount++;
            $cssClass = 'info';
            $icon = 'fas fa-info-circle';
        } elseif ($notificationType == 'exito' || $notificationType == 'éxito') {
            $successCount++;
            $cssClass = 'success';
            $icon = 'fas fa-check-circle';
        } elseif ($notificationType == 'recordatorio') {
            $reminderCount++;
            $cssClass = 'reminder';
            $icon = 'fas fa-clock';
        } else {
            // Por defecto, si no coincide con ninguno de los tipos anteriores
            $infoCount++;
            $cssClass = 'info';
            $icon = 'fas fa-info-circle';
        }
        
        // Formatear la fecha para mostrarla de manera amigable
        $fechaNotificacion = new DateTime($row['fecha']);
        $fechaActual = new DateTime();
        $intervalo = $fechaActual->diff($fechaNotificacion);
        
        if ($intervalo->days == 0) {
            if ($intervalo->h == 0) {
                $tiempoTranscurrido = "Hace " . $intervalo->i . " minutos";
            } else {
                $tiempoTranscurrido = "Hace " . $intervalo->h . " horas";
            }
        } else if ($intervalo->days == 1) {
            $tiempoTranscurrido = "Hace 1 día";
        } else {
            $tiempoTranscurrido = "Hace " . $intervalo->days . " días";
        }
        
        // Guardar la notificación en el array
        $notifications[] = [
            'id' => $row['id'],
            'tipo' => $notificationType,
            'cssClass' => $cssClass,
            'icon' => $icon,
            'mensaje' => $row['mensaje'],
            'descripcion' => $row['descripcion'],
            'estado' => $row['estado'],
            'fecha' => $row['fecha'],
            'tiempoTranscurrido' => $tiempoTranscurrido
        ];
    }
}

// Función para marcar una notificación como leída
if (isset($_POST['marcar_leida'])) {
    $id = $_POST['id'];
    $sql = "UPDATE notificacion SET estado = 'leida' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    
    // Verificar si la actualización fue exitosa
    if ($result) {
        // Redirigir a la misma página para ver los cambios
        header("Location: Alertas y notificaciones.php");
        exit();
    } else {
        // Mostrar error si la actualización falló
        echo "Error al marcar como leída: " . $conn->error;
    }
}

// Función para eliminar una notificación
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    $sql = "DELETE FROM notificacion WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: Alertas y notificaciones.php");
    exit();
}

// Función para limpiar todas las notificaciones
if (isset($_POST['limpiar_todas'])) {
    $sql = "DELETE FROM notificacion";
    $conn->query($sql);
    header("Location: Alertas y notificaciones.php");
    exit();
}

// Función para verificar manualmente las notificaciones
if (isset($_POST['verificar_ahora'])) {
    include_once 'scripts_notificaciones/verificar_automatico.php';
    $_SESSION['ultima_verificacion'] = time();
    header("Location: Alertas y notificaciones.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alertas y Notificaciones</title>
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
      background-image: url('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/top-view-chocolate-desserts-ready-be-served.jpg-eNj5AzUFwZEqQaAX3IIV8GpupxAHrT.jpeg');
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
      background-color: rgba(0, 0, 0, 0.5); /* Overlay más oscuro como en la imagen */
      z-index: -1;
      pointer-events: none;
    }

    .container {
      display: flex;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      background-color: rgba(60, 40, 35, 0.4); /* Fondo marrón oscuro semi-transparente */
      border-radius: 12px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      overflow: hidden;
      color: #fff; /* Texto blanco como en la imagen */
    }

    /* Barra lateral */
    .sidebar {
      width: 250px;
      background-color: rgba(80, 50, 45, 0.4); /* Fondo marrón oscuro similar al contenedor */
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
      flex: 1;
    }

    .menu-item {
      background: rgba(76, 51, 47, 0.914);
      border: none;
      color: rgba(255, 255, 255, 0.786);
      cursor: pointer;
      font-size: 14px;
      padding: 12px 15px;
      border-radius: 6px; /* Bordes menos redondeados como en la imagen */
      transition: all 0.3s;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .menu-item:hover {
      background-color: rgba(160, 100, 80, 0.8);
    }

    .menu-item.selected {
      background-color: rgba(160, 100, 80, 0.8); /* Color similar al botón de login */
    }

    .back-button {
      margin-top: auto;
      background: rgba(160, 100, 80, 0.8); /* Color similar al botón de login */
      border: none;
      color: white;
      cursor: pointer;
      font-size: 14px;
      padding: 12px 15px;
      border-radius: 6px; /* Bordes menos redondeados como en la imagen */
      transition: all 0.3s;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .back-button:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    /* Contenido principal */
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

    .user-email {
      color: rgba(255, 255, 255, 0.9);
      font-size: 14px;
    }

    .settings-button {
      background: rgba(160, 100, 80, 0.8); /* Color similar al botón de login */
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

    /* Área de notificaciones */
    .notifications-area {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .notification {
      background: rgba(255, 255, 255, 0.9); /* Fondo blanco como los inputs */
      border-radius: 6px; /* Bordes menos redondeados como en la imagen */
      padding: 25px;
      transition: all 0.3s;
      border: none;
      position: relative;
      overflow: hidden;
      display: none; /* Ocultar por defecto */
      color: rgba(60, 40, 35, 0.8); /* Texto oscuro para contraste */
    }

    .notification::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 6px;
    }

    .notification-title {
      color:  rgba(60, 40, 35, 0.8);
      margin-bottom: 12px;
      font-size: 18px;
      font-weight: 500;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notification-text {
      color: #666;
      line-height: 1.5;
      font-size: 14px;
      font-weight: 300;
      padding-left: 26px;
    }

    /* Tipos de notificaciones */
    .notification.alert::before {
      background-color: #e74c3c;
    }

    .notification.warning::before {
      background-color: #f39c12;
    }

    .notification.info::before {
      background-color: #3498db;
    }

    .notification.success::before {
      background-color: #2ecc71;
    }

    .notification.reminder::before {
      background-color: #9b59b6;
    }

    .notification.all {
      display: block; /* Mostrar todas cuando se seleccione "Todas" */
    }

    /* Estilos para el contador de notificaciones */
    .notification-count {
      margin-left: auto;
      background-color: #e74c3c;
      color: white;
      font-size: 12px;
      font-weight: bold;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .warning .notification-count {
      background-color: #f39c12;
    }

    .info .notification-count {
      background-color: #3498db;
    }

    .success .notification-count {
      background-color: #2ecc71;
    }

    .reminder .notification-count {
      background-color: #9b59b6;
    }

    /* Enlaces de pie de página como el "¿Olvidaste tu contraseña?" */
    .footer-links {
      display: flex;
      justify-content: center;
      margin-top: 30px;
      padding-top: 25px;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    .footer-links form {
      display: inline;
    }

    .footer-links button {
      color: white;
      text-decoration: none;
      margin: 0 12px;
      font-size: 13px;
      font-weight: 400;
      transition: all 0.3s;
      padding: 5px 12px;
      background: transparent;
      border: none;
      cursor: pointer;
    }

    .footer-links button:hover {
      text-decoration: underline;
    }

    /* Botones de acción para cada notificación */
    .notification-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 15px;
      gap: 10px;
    }

    .notification-actions form {
      display: inline;
    }

    .notification-actions button {
      background: rgba(60, 40, 35, 0.1);
      border: none;
      color: rgba(60, 40, 35, 0.7);
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .notification-actions button:hover {
      background: rgba(60, 40, 35, 0.2);
    }

    /* Estado de la notificación */
    .notification-status {
      font-size: 12px;
      color: #888;
      margin-top: 5px;
      padding-left: 26px;
    }

    .no-notifications {
      text-align: center;
      padding: 30px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      color: white;
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
      
      .back-button {
        margin-top: 20px;
      }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
  <div class="container">
    <!-- Barra lateral -->
    <aside class="sidebar">
      <h2 class="sidebar-title"><i class="fas fa-bell"></i> Alertas</h2>
      <nav class="sidebar-menu">
        <button class="menu-item selected" onclick="filterNotifications('all')">
          <i class="fas fa-inbox"></i> Todas
          <span class="notification-count"><?php echo $totalCount; ?></span>
        </button>
        <button class="menu-item alert" onclick="filterNotifications('alert')">
          <i class="fas fa-exclamation-triangle"></i> Críticas
          <span class="notification-count"><?php echo $alertCount; ?></span>
        </button>
        <button class="menu-item warning" onclick="filterNotifications('warning')">
          <i class="fas fa-exclamation-circle"></i> Advertencias
          <span class="notification-count"><?php echo $warningCount; ?></span>
        </button>
        <button class="menu-item info" onclick="filterNotifications('info')">
          <i class="fas fa-info-circle"></i> Notificaciones
          <span class="notification-count"><?php echo $infoCount; ?></span>
        </button>
        <button class="menu-item success" onclick="filterNotifications('success')">
          <i class="fas fa-check-circle"></i> Éxitos
          <span class="notification-count"><?php echo $successCount; ?></span>
        </button>
        <button class="menu-item reminder" onclick="filterNotifications('reminder')">
          <i class="fas fa-clock"></i> Recordatorios
          <span class="notification-count"><?php echo $reminderCount; ?></span>
        </button>
      </nav>
      <button class="back-button" onclick="goBack()">
        <i class="fas fa-arrow-left"></i> Volver al Menú
      </button>
    </aside>
    <!-- Contenido principal -->
    <main class="main-content">
      <div class="header">
        <p class="user-email">
          <i class="fas fa-user-circle"></i> 
          <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'usuario@abby.com'; ?>
        </p>
        <form method="post" style="display: inline;">
          <button type="submit" name="verificar_ahora" class="settings-button" title="Verificar ahora">
            <i class="fas fa-sync"></i>
          </button>
        </form>
      </div>
      <div class="notifications-area">
        <?php if (count($notifications) > 0): ?>
          <?php foreach ($notifications as $notification): ?>
            <div class="notification <?php echo $notification['cssClass']; ?> all">
              <h3 class="notification-title">
                <i class="<?php echo $notification['icon']; ?>"></i> 
                <?php echo ucfirst($notification['tipo']); ?>
              </h3>
              <p class="notification-text"><?php echo $notification['mensaje']; ?></p>
              <p class="notification-text"><small><?php echo $notification['tiempoTranscurrido']; ?></small></p>
              <p class="notification-status">Estado: <?php echo ucfirst($notification['estado']); ?></p>
              <div class="notification-actions">
                <form method="post">
                  <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                  <button type="submit" name="marcar_leida">
                    <i class="fas fa-check"></i> Marcar como leída
                  </button>
                </form>
                <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar esta notificación?');">
                  <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                  <button type="submit" name="eliminar">
                    <i class="fas fa-trash"></i> Eliminar
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-notifications">
            <i class="fas fa-bell-slash fa-3x"></i>
            <p>No hay notificaciones disponibles</p>
          </div>
        <?php endif; ?>
      </div>
      <div class="footer-links">
        <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar todas las notificaciones?');">
          <button type="submit" name="limpiar_todas">
            <i class="fas fa-trash-alt"></i> Limpiar todas
          </button>
        </form>
        <form method="post">
          <button type="submit" name="verificar_ahora">
            <i class="fas fa-sync"></i> Verificar sistema
          </button>
        </form>
      </div>
    </main>
  </div>

  <script>
    // Función para filtrar notificaciones
    function filterNotifications(type) {
      // Remover la clase selected de todos los botones
      document.querySelectorAll('.menu-item').forEach(btn => {
        btn.classList.remove('selected');
      });
      
      // Añadir la clase selected al botón clickeado
      event.currentTarget.classList.add('selected');
      
      // Mostrar/ocultar notificaciones según el tipo
      document.querySelectorAll('.notification').forEach(notification => {
        if (type === 'all') {
          notification.style.display = 'block';
        } else {
          notification.style.display = notification.classList.contains(type) ? 'block' : 'none';
        }
      });
    }
    
    // Función para volver al menú principal
    function goBack() {
      window.location.href = "Menu.php"; // Cambiado para que vaya al menú principal
    }
    
    // Mostrar todas las notificaciones al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
      filterNotifications('all');
    });
  </script>
</body>
</html>