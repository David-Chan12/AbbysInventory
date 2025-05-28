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
          <i class="fa-brands fa-product-hunt"></i>
          <h3>Productos</h3>
          <p>Gestión de tus productos</p>
          <a href="productos.php" class="access-btn">Acceder</a>
        </div>

        <div class="card">
          <i class="fa-solid fa-square-plus"></i>
          <h3>Crear productos</h3>
          <p>preparar productos</p>
          <a href="Preparar_producto.php" class="access-btn">Acceder</a>
        </div>

    </div> <!-- Cierre main-panel -->
  </div>
</body>
</html>
