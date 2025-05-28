  <?php
session_start();
require_once 'conexion.php'; // Asegúrate de que este archivo conecta a tu base de datos correctamente

?>
  
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-menu">
      <a href="Menu.php" class="menu-item">
        <i class="fas fa-home menu-icon"></i>
        <span class="menu-text"> Inicio</span>
      </a>
      <a href="agregarinsumo.php" class="menu-item">
        <i class="fas fa-bread-slice"></i>
        <span class="menu-text"> Nuevo insumo</span>
      </a>
      <a href=".." class="menu-item">
        <i class="fa-solid fa-clipboard"></i>
        <span class="menu-text"> Reportes de consumos</span>
      </a>
      <a href="proveedores.php" class="menu-item">
        <i class="fa-solid fa-address-book"></i>
        <span class="menu-text"> Proveedores</span>
      </a>
    </div>
  </div>

  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
  </button>