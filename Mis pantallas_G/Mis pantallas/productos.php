<?php
    session_start();
    require_once 'conexion.php'; // Conexión a la base de datos
    
    // Obtener productos
    $query = "SELECT p.*, e.descripcion as estado_desc FROM producto p 
              LEFT JOIN estadopro e ON p.estado = e.id"; 
    $resultado = $conn->query($query); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Ginsumos.css">
    <title>Tabla de Productos - Abby Cookies & Cakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script type="text/javascript">
        function confirmar(){
            return confirm('¿Estas seguro?, se eliminaran los datos de manera permanente.');
        }
    </script>
</head>

    
    <div class="panel-background">
        <div class="main-panel">
            <header class="menu-header">
                <h1 class="logo">Abby's Cookies & Cakes</h1>
                <div class="user-info">
                    <p class="user-email">usuario@abby.com</p>
                    <button class="notification-btn" onclick="window.location.href='Alertas_y_notificaciones.php'">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <button class="logout-btn" onclick="cerrarSesion()">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </button>
                </div>

                <div class="top-bar">
                    <a href="ProductoM.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
                </div>
            </header>

            <h1 class="page-title">Reporte de Productos</h1>
            <div class="report-container">
                <h2 class="section-title">TABLA DE PRODUCTOS</h2>

                <div class="report-header">
                    <div class="report-info">
                        <div class="report-info-item">
                            <strong>Productos: </strong>
                            <select id="productoSelect" style="padding: 10px; width: auto; border-radius: 8px; border: 1px solid #ccc; font-family: 'Poppins';">
                                <option value="">Seleccione un producto</option>
                                <?php 
                                $productos_query = "SELECT * FROM producto";
                                $productos_result = $conn->query($productos_query);
                                while ($producto = mysqli_fetch_assoc($productos_result)): 
                                ?>
                                <option value="<?= $producto['id'] ?>"><?= htmlspecialchars($producto['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <form action="<?=$_SERVER['PHP_SELF']?>">
                                <br>
                                <button class="mostrardatos" onclick="fetchTodosProductos()">Mostrar todos los datos</button>
                                <a href="agregarproducto.php" class="mostrardatos"><i class="fa-solid fa-plus"></i></a>
                            </form>
                        </div>
                    </div>
                </div>
                
                <h3 class="section-title">Detalles de los productos</h3>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Cantidad disponible</th>
                            <th>Fecha creación</th>
                            <th>Fecha actualización</th>
                            <th>Estado</th>
                            <th>Descripción</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-detalle">
                        <!-- Aquí se cargará dinámicamente el reporte -->
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- Cierre del main panel -->

    <script>
        // Mostrar todos los productos al cargar la página
        window.addEventListener('DOMContentLoaded', fetchTodosProductos);
        
        function fetchTodosProductos() {
            fetch('obtener_todos_productos.php')
                .then(response => response.json())
                .then(data => {
                    const tabla = document.getElementById('tabla-detalle');
                    tabla.innerHTML = '';

                    if (data.length > 0) {
                        data.forEach(producto => {
                            tabla.innerHTML += `
                                <tr>
                                    <td>${producto.id}</td>
                                    <td>${producto.nombre}</td>
                                    <td>${producto.precio}</td>
                                    <td>${producto.cantidad_disponible}</td>
                                    <td>${producto.fecha_creacion}</td>
                                    <td>${producto.fecha_actualizacion}</td>
                                    <td>${producto.estado_desc}</td>
                                    <td>${producto.descripcion}</td>
                                    <td>
                                        <a href='editar_producto.php?id=${producto.id}'>EDITAR</a><br>
                                        <a href='eliminar_producto.php?id=${producto.id}' onclick='return confirmar()'>ELIMINAR</a>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        tabla.innerHTML = `<tr><td colspan="9" style="text-align:center;">No hay productos registrados.</td></tr>`;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        document.getElementById('productoSelect').addEventListener('change', function(){
            const productoId = this.value;

            if (productoId) {
                fetch('obtener_producto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ producto_id: productoId })
                })
                .then(response => response.json())
                .then(data => {
                    const tabla = document.getElementById('tabla-detalle');
                    tabla.innerHTML = '';

                    if (data) {
                        tabla.innerHTML += `
                            <tr>
                                <td>${data.id}</td>
                                <td>${data.nombre}</td>
                                <td>${data.precio}</td>
                                <td>${data.cantidad_disponible}</td>
                                <td>${data.fecha_creacion}</td>
                                <td>${data.fecha_actualizacion}</td>
                                <td>${data.estado_desc}</td>
                                <td>${data.descripcion}</td>
                                <td>
                                    <a href='editar_producto.php?id=${data.id}'>EDITAR</a><br>
                                    <a href='eliminar_producto.php?id=${data.id}' onclick='return confirmar()'>ELIMINAR</a>
                                </td>
                            </tr>`;
                    } else {
                        tabla.innerHTML = `<tr><td colspan="9">No se encontró el producto.</td></tr>`;
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                document.getElementById('tabla-detalle').innerHTML = '';
            }
        });
    </script>
</body>
</html>