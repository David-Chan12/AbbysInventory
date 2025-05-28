<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Acceso Denegado - Abby's</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
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

    .error-container {
      max-width: 600px;
      width: 100%;
      background-color: rgba(60, 40, 35, 0.5);
      border-radius: 12px;
      padding: 40px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      text-align: center;
    }

    .error-icon {
      font-size: 60px;
      color: #e74c3c;
      margin-bottom: 20px;
    }

    .error-title {
      font-size: 28px;
      font-weight: 500;
      margin-bottom: 15px;
    }

    .error-message {
      font-size: 16px;
      margin-bottom: 30px;
      line-height: 1.6;
    }

    .btn {
      display: inline-block;
      color: white;
      text-decoration: none;
      font-weight: 400;
      font-size: 14px;
      padding: 12px 25px;
      background-color: rgba(160, 100, 80, 0.8);
      border-radius: 6px;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
      margin: 0 10px;
    }

    .btn:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    .btn-secondary {
      background-color: rgba(108, 117, 125, 0.8);
    }

    .btn-secondary:hover {
      background-color: rgba(108, 117, 125, 0.9);
    }
  </style>
</head>
<body>
  <div class="error-container">
    <i class="fas fa-lock error-icon"></i>
    <h1 class="error-title">Acceso Denegado</h1>
    <p class="error-message">
      Lo sentimos, no tienes permisos para acceder a esta sección. 
      Por favor, contacta con el administrador si crees que deberías tener acceso.
    </p>
    <div>
      <a href="empleado.php" class="btn">
        <i class="fas fa-home"></i> Volver al Inicio
      </a>
      <a href="logout.php" class="btn btn-secondary">
        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
      </a>
    </div>
  </div>
</body>
</html>
