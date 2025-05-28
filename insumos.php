<?php
session_start();
require_once 'conexion.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/insumos.css">
    <title>Panel de insumos</title>
</head>
<body>
    <div class="panel-background">
    <div class="main-panel">

    <div class="top-bar">
      <a href="Menu.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
      <div class="user-info">
        <i class="fas fa-user-circle"></i>usuario@abbys.com
      </div>
      <button class="notification-btn" onclick="window.location.href='Alertas_y_notificaciones.php'">
        <i class="fas fa-bell"></i>
        <span class="notification-badge">4</span>
      </button>

    </div>

      <!-- Fila 1 -->
      <div class="cards-container">
        <div class="card">
          <i class="fa-solid fa-egg"></i>
          <h3>Insumos</h3>
          <p>Consulta los ingredientes en existencia</p>
          <a href="Ginsumos.php" class="access-btn">Acceder</a>
        </div>

        <div class="card">
          <i class="fas fa-bread-slice"></i>
          <h3>Nuevo insumo</h3>
          <p>Agrega ingredientes recien obtenidos</p>
          <a href="agregarinsumo.php" class="access-btn">Acceder</a>
        </div>

        <div class="card">
          <i class="fa-solid fa-address-book"></i>
          <h3>Proveedores</h3>
          <p>Consulta los proveedores de los insumos</p>
          <a href="proveedores.php" class="access-btn">Acceder</a>
        </div>

    </div> <!-- Cierre main-panel -->
  </div>
</body>
</html>