<?php
session_start();
require_once 'conexion.php';

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel de Empleado - Abby's Cookies & Cakes</title>
  <link rel="stylesheet" href="css/empleado.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
  <div class="panel-background">
    <div class="main-panel">

      <div class="top-bar">
        <a href="Menu.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
        <div class="user-info">
          <i class="fas fa-user-circle"></i>
          usuario@abbys.com
        </div>
      </div>

      <!-- Fila 1 -->
      <div class="cards-container">
        <div class="card">
          <i class="fas fa-users"></i>
          <h3>Empleados</h3>
          <p>Gestión de tu equipo de pasteleros</p>
          <a href="Gempleados.php" class="access-btn">Acceder</a>
        </div>

        <div class="card">
          <i class="fas fa-user-plus"></i>
          <h3>Nuevo empleado</h3>
          <p>Agrega a un nuevo miembro al equipo</p>
          <a href="agregarempleado.php" class="access-btn">Acceder</a>
        </div>

        <div class="card">
          <i class="fas fa-user-shield"></i>
          <h3>Roles y Permisos</h3>
          <p>Control de accesos y privilegios</p>
          <a href="Ryp.php" class="access-btn">Acceder</a>
        </div>

        <!-- Fila 2 -->
         <div class="card">
          <i class="fas fa-calendar-check"></i>
          <h3>Turnos</h3>
          <p>Organiza horarios de trabajo</p>
          <a href="Gturnos.php" class="access-btn">Acceder</a>
        </div>

        <div class="card">
          <i class="fas fa-tasks"></i>
          <h3>Actividades</h3>
          <p>Registro y seguimiento de tareas</p>
          <a href="actividades.php" class="access-btn">Acceder</a>
        </div>

        <div class="card">
          <i class="fas fa-file-invoice-dollar"></i>
          <h3>Nómina</h3>
          <p>Administración de pagos</p>
          <a href="nomina.php" class="access-btn">Acceder</a>
        </div>
      </div>

    </div> <!-- Cierre main-panel -->
  </div>
</body>
</html>
