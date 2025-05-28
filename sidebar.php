<?php
session_start();
require_once 'conexion.php'; // Asegúrate de que este archivo conecta a tu base de datos correctamente

?>
  
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