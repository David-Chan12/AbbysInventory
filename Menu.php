<?php
session_start();
require_once 'conexion.php';
require_once 'permisos.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Obtener el ID del usuario actual
$usuario_id = $_SESSION['usuario']['id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Menú Pastelería</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Montserrat', 'Segoe UI', sans-serif;
    }

    /* Cambiar la imagen de fondo a la nueva imagen */
    body {
      background-color: #fff9f7;
      color: #5a4a42;
      min-height: 100vh;
      background-image: url('css/top-view-chocolate-desserts-ready-be-served.jpg');
      background-size: cover;
      background-position: center center;
      background-attachment: fixed;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
    }

    /* Overlay más oscuro similar al de la imagen de login */
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

    /* Contenedor principal con estilo similar al de login */
    .menu-container {
      max-width: 1000px;
      width: 100%;
      background-color: rgba(60, 40, 35, 0.5); /* Fondo marrón oscuro semi-transparente */
      border-radius: 12px;
      padding: 40px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff; /* Texto blanco como en la imagen */
    }

    .menu-header {
      text-align: center;
      margin-bottom: 40px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
      padding-bottom: 25px;
    }

    /* Estilo del título exactamente como en la imagen */
    .logo {
      font-size: 28px; /* Tamaño más pequeño como en la imagen */
      font-weight: 500; /* Peso medio como en la imagen */
      color: #fff; /* Color blanco como en la imagen */
      margin-bottom: 20px;
      letter-spacing: normal;
    }

    /* Eliminar el strong para que se vea como en la imagen */
    .logo span {
      font-weight: 500; /* Mismo peso que el resto del título */
    }

    .slogan {
      font-size: 16px;
      color: rgba(255, 255, 255, 0.8); /* Color blanco con transparencia */
      font-weight: 300;
      letter-spacing: 0.8px;
      margin-bottom: 25px;
    }

    .user-info {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 25px;
    }

    .user-email {
      color: rgba(255, 255, 255, 0.9);
      margin-right: 15px;
      font-size: 13px;
    }

    /* Botones con estilo exacto al botón de login */
    .notification-btn {
      background: rgba(160, 100, 80, 0.8); /* Color similar al botón de login */
      border: none;
      color: white;
      cursor: pointer;
      font-size: 13px;
      padding: 10px 12px; /* Altura similar al botón de login */
      border-radius: 6px; /* Bordes menos redondeados como en la imagen */
      transition: all 0.3s;
      margin-right: 10px;
      position: relative;
    }

    .notification-btn:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    /* Indicador de notificaciones */
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
      background: rgba(160, 100, 80, 0.8); /* Color similar al botón de login */
      border: none;
      color: white;
      cursor: pointer;
      font-size: 13px;
      padding: 10px 18px; /* Altura similar al botón de login */
      border-radius: 6px; /* Bordes menos redondeados como en la imagen */
      transition: all 0.3s;
    }

    .logout-btn:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    .menu-grid {
      display: flex;
      flex-direction: column;
      gap: 25px;
      margin-top: 30px;
    }

    .menu-row {
      display: flex;
      justify-content: space-between;
      gap: 25px;
    }

    /* Estilo de las tarjetas similar a los inputs del login */
    .menu-card {
      background: rgba(255, 255, 255, 0.9); /* Fondo blanco como los inputs */
      border-radius: 6px; /* Bordes menos redondeados como en la imagen */
      padding: 25px;
      transition: all 0.3s;
      text-align: center;
      border: none;
      width: calc(33.333% - 17px);
    }

    .menu-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }
    
    /* Estilo para tarjetas deshabilitadas */
    .menu-card.disabled {
      opacity: 0.6;
      cursor: not-allowed;
      pointer-events: none;
    }
    
    .menu-card.disabled a {
      background-color: #6c757d;
      cursor: not-allowed;
    }
    
    .menu-card.disabled .card-icon {
      color: #6c757d;
    }

    .card-icon {
      font-size: 24px;
      color: rgba(60, 40, 35, 0.8); /* Color oscuro para los iconos */
      margin-bottom: 15px;
    }

    /* Textos en color oscuro como en los inputs */
    .menu-card h2 {
      color: rgba(60, 40, 35, 0.8);
      margin-bottom: 12px;
      font-size: 20px;
      font-weight: 500;
      letter-spacing: 0.5px;
    }

    .menu-card p {
      color: #666;
      line-height: 1.5;
      margin-bottom: 20px;
      font-size: 14px;
      font-weight: 300;
    }

    /* Botón exactamente como el de "Entrar" */
    .menu-card a {
      display: inline-block;
      color: white;
      text-decoration: none;
      font-weight: 400; /* Peso normal como en la imagen */
      font-size: 14px;
      padding: 10px 20px; /* Altura similar al botón de login */
      width: 100%; /* Ancho completo como el botón de login */
      background-color: rgba(160, 100, 80, 0.8); /* Color similar al botón de login */
      border-radius: 6px; /* Bordes menos redondeados como en la imagen */
      transition: all 0.3s;
      text-align: center;
    }

    .menu-card a:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    /* Enlaces de pie de página como el "¿Olvidaste tu contraseña?" */
    .footer-links {
      display: flex;
      justify-content: center;
      margin-top: 50px;
      padding-top: 25px;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    .footer-links a {
      color:white; /* Color azul como el enlace de olvidar contraseña */
      text-decoration: none;
      margin: 0 12px;
      font-size: 13px;
      font-weight: 400;
      transition: all 0.3s;
      padding: 5px 12px;
      background: transparent; /* Sin fondo */
      border-radius: 0;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }
    
    /* Tooltip para tarjetas deshabilitadas */
    .tooltip {
      position: relative;
    }
    
    .tooltip .tooltip-text {
      visibility: hidden;
      width: 200px;
      background-color: rgba(0, 0, 0, 0.8);
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 8px;
      position: absolute;
      z-index: 1;
      bottom: 125%;
      left: 50%;
      transform: translateX(-50%);
      opacity: 0;
      transition: opacity 0.3s;
      font-size: 12px;
    }
    
    .tooltip .tooltip-text::after {
      content: "";
      position: absolute;
      top: 100%;
      left: 50%;
      margin-left: -5px;
      border-width: 5px;
      border-style: solid;
      border-color: rgba(0, 0, 0, 0.8) transparent transparent transparent;
    }
    
    .tooltip:hover .tooltip-text {
      visibility: visible;
      opacity: 1;
    }

    @media (max-width: 900px) {
      .menu-row {
        flex-direction: column;
      }
      .menu-card {
        width: 100%;
      }
      body {
        background-size: cover;
        background-position: center center;
      }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
  <div class="menu-container">
    <header class="menu-header">
      <h1 class="logo">Abby's Cookies & Cakes</h1>
      <p class="slogan">Dulces creaciones con amor y tradición</p>
      <div class="user-info">
        <p class="user-email"><?php echo $_SESSION['usuario']['email'] ?? 'usuario@abby.com'; ?></p>
        <!-- Botón de notificaciones modificado para redirigir a filter-notifications -->
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


    <div class="menu-grid">
      <div class="menu-row">
        <!-- Tarjeta de Inventario -->
        <div class="menu-card <?php echo !tienePermiso($usuario_id, 'inventario') ? 'disabled tooltip' : ''; ?>">
          <i class="fas fa-boxes card-icon"></i>
          <h2>Inventario</h2>
          <p>Control de ingredientes y productos terminados</p>
          <a href="insumos.php">Acceder</a>
          <?php if (!tienePermiso($usuario_id, 'inventario')): ?>
            <span class="tooltip-text">No tienes permiso para acceder a esta sección</span>
          <?php endif; ?>
        </div>

        <!-- Tarjeta de Empleados -->
        <div class="menu-card <?php echo !tienePermiso($usuario_id, 'empleados') ? 'disabled tooltip' : ''; ?>">
         <i class="fas fa-user-tie card-icon"></i>
         <h2>Empleados</h2>
         <p>Gestión de tu equipo de pasteleros</p>
         <a href="empleado.php">Acceder</a>
         <?php if (!tienePermiso($usuario_id, 'empleados')): ?>
            <span class="tooltip-text">No tienes permiso para acceder a esta sección</span>
          <?php endif; ?>
        </div>

        <!-- Tarjeta de Ventas -->
        <div class="menu-card <?php echo !tienePermiso($usuario_id, 'ventas') ? 'disabled tooltip' : ''; ?>">
          <i class="fas fa-cash-register card-icon"></i>
          <h2>Ventas</h2>
          <p>Registro de pedidos y transacciones</p>
          <a href="ventas.php">Acceder</a>
          <?php if (!tienePermiso($usuario_id, 'ventas')): ?>
            <span class="tooltip-text">No tienes permiso para acceder a esta sección</span>
          <?php endif; ?>
        </div>

      </div>
      <div class="menu-row">
        <!-- Tarjeta de Recetas -->
        <div class="menu-card <?php echo !tienePermiso($usuario_id, 'recetas') ? 'disabled tooltip' : ''; ?>">
          <i class="fa fa-birthday-cake"></i>
          <h2>Productos</h2>
          <p>Tu colección de productos</p>
          <a href="ProductoM.php">Acceder</a>
          <?php if (!tienePermiso($usuario_id, 'recetas')): ?>
            <span class="tooltip-text">No tienes permiso para acceder a esta sección</span>
          <?php endif; ?>
        </div>

        <!-- Tarjeta de Reportes -->
        <div class="menu-card <?php echo !tienePermiso($usuario_id, 'reportes') ? 'disabled tooltip' : ''; ?>">
          <i class="fas fa-chart-line card-icon"></i>
          <h2>Reportes</h2>
          <p>Estadísticas de tu negocio</p>
          <a href="menuR.php">Acceder</a>
          <?php if (!tienePermiso($usuario_id, 'reportes')): ?>
            <span class="tooltip-text">No tienes permiso para acceder a esta sección</span>
          <?php endif; ?>
        </div>

        <!-- Tarjeta de Eventos -->
        <div class="menu-card <?php echo !tienePermiso($usuario_id, 'eventos') ? 'disabled tooltip' : ''; ?>">
          <i class="fas fa-calendar-alt card-icon"></i>
          <h2>Eventos</h2>
          <p>Gestión de encargos especiales</p>
          <a href="Pedidos.php">Acceder</a>
          <?php if (!tienePermiso($usuario_id, 'eventos')): ?>
            <span class="tooltip-text">No tienes permiso para acceder a esta sección</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-links">
      <a href="#"><i class="fas fa-phone"></i> Contacto</a>
      <a href="#"><i class="fas fa-info-circle"></i> Nosotros</a>
      <a href="#" style="color: white;">Ayuda</a>
    </div>
  </div>
  
  <script>
    // Función para cerrar sesión
    function cerrarSesion() {
      if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
        window.location.href = 'logout.php';
      }
    }
  </script>
</body>
</html>
