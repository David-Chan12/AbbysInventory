<?php
    session_start();
    require_once 'conexion.php'; // Conexión a la base de datos

    // Obtener ventas
    $query = "SELECT * FROM ventas ORDER BY fecha DESC";
    $resultado = mysqli_query($conn, $query);

    $sql = "SELECT * FROM reporteventas";
    $result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de ventas</title>
    <link rel="stylesheet" href="css/ventas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script type="text/javascript">
        function confirmar(){
            return confirm('¿Estas seguro?, se eliminaran los datos de manera permanente.');
        }
        
        function verDetalles(ventaId) {
            // Abrir modal o redireccionar a página de detalles
            window.location.href = 'detalle_venta.php?id=' + ventaId;
        }
    </script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        
        .btn-ver-detalles {
            background: rgba(160, 100, 80, 0.8);
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    
    
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
                    <a href="Menu.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
                </div>
            </header>

            <h1 class="page-title">Reporte de ventas</h1>
            <div class="report-container">
                <h2 class="section-title">TABLA DE VENTAS</h2>

                <?php if(isset($_GET['success'])): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                        <?php 
                        $success = $_GET['success'];
                        if($success == 1) echo "Venta registrada correctamente.";
                        elseif($success == 2) echo "Venta actualizada correctamente.";
                        elseif($success == 3) echo "Venta eliminada correctamente.";
                        ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                        <?php 
                        $error = $_GET['error'];
                        if($error == 1) echo "Error al eliminar la venta.";
                        elseif($error == 2) echo "Error al actualizar la venta.";
                        ?>
                    </div>
                <?php endif; ?>

                <div class="report-header">
                    <div class="report-info">
                        <div class="report-info-item">
                            <strong>Ventas: </strong>
                            <select id="ventasSelect" style="padding: 10px; width: auto; border-radius: 8px; border: 1px solid #ccc; font-family: 'Poppins';">
                                <option value="">Seleccione una fecha</option>
                                <?php 
                                // Reiniciar el puntero del resultado
                                mysqli_data_seek($resultado, 0);
                                while ($venta = mysqli_fetch_assoc($resultado)): 
                                ?>
                                <option value="<?= $venta['id'] ?>"><?= htmlspecialchars($venta['fecha']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <form action="<?=$_SERVER['PHP_SELF']?>">
                                <br>
                                <button class="mostrardatos" onclick="fetchTodasVentas()">Mostrar todos los datos</button>
                                <a href="agregarventa.php" class="mostrardatos"><i class="fa-solid fa-plus"></i></a>
                            </form>
                        </div>
                    </div>
                </div>
                
                <h3 class="section-title">Detalles de ventas</h3>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Método de pago</th>
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

    <!-- Modal para detalles de venta -->
    <div id="detalleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Detalle de Venta</h2>
            <div id="detalle-contenido"></div>
        </div>
    </div>
    
    <script>
        // Mostrar todas las ventas al cargar la página
        window.addEventListener('DOMContentLoaded', fetchTodasVentas);

        function fetchTodasVentas() {
            fetch('obtener_todos_ventas.php')
                .then(response => response.json())
                .then(data => {
                    const tabla = document.getElementById('tabla-detalle');
                    tabla.innerHTML = '';

                    if (data.length > 0) {
                        data.forEach(venta => {
                            tabla.innerHTML += `
                                <tr>
                                    <td>${venta.id}</td>
                                    <td>${venta.fecha}</td>
                                    <td>$${parseFloat(venta.total).toFixed(2)}</td>
                                    <td>${venta.metodo_pago}</td>
                                    <td>${venta.descripcion}</td>
                                    <td>
                                        <a href="javascript:void(0)" class="btn-ver-detalles" onclick="cargarDetalleVenta(${venta.id})">
                                            <i class="fas fa-eye"></i> Ver Detalles
                                        </a><br>
                                        <a href='editarVenta.php?id=${venta.id}' class="mostrardatos">EDITAR</a><br>
                                        <a href='eliminarVenta.php?id=${venta.id}' class="mostrardatos" style="background-color: #dc3545;" onclick='return confirmar()'>ELIMINAR</a>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        tabla.innerHTML = `<tr><td colspan="6" style="text-align:center;">No hay ventas registradas.</td></tr>`;
                    }
                })
                .catch(error => console.error('Error al obtener ventas:', error));
        }

        // Filtro por venta específica usando un <select>
        document.getElementById('ventasSelect').addEventListener('change', function () {
            const ventaID = this.value;

            if (ventaID) {
                fetch('obtener_venta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ venta_id: ventaID })
                })
                .then(response => response.json())
                .then(venta => {
                    const tabla = document.getElementById('tabla-detalle');
                    tabla.innerHTML = '';

                    if (venta) {
                        tabla.innerHTML = `
                            <tr>
                                <td>${venta.id}</td>
                                <td>${venta.fecha}</td>
                                <td>$${parseFloat(venta.total).toFixed(2)}</td>
                                <td>${venta.metodo_pago}</td>
                                <td>${venta.descripcion}</td>
                                <td>
                                    <a href="javascript:void(0)" class="btn-ver-detalles" onclick="cargarDetalleVenta(${venta.id})">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a><br>
                                    <a href='editarVenta.php?id=${venta.id}' class="mostrardatos">EDITAR</a><br>
                                    <a href='eliminarVenta.php?id=${venta.id}' class="mostrardatos" style="background-color: #dc3545;" onclick='return confirmar()'>ELIMINAR</a>
                                </td>
                            </tr>`;
                    } else {
                        tabla.innerHTML = `<tr><td colspan="6">No se encontró la venta.</td></tr>`;
                    }
                })
                .catch(error => console.error('Error al filtrar venta:', error));
            } else {
                fetchTodasVentas(); // si se deselecciona, vuelve a cargar todas
            }
        });

        // Función para cargar los detalles de una venta
        function cargarDetalleVenta(ventaId) {
            fetch('obtener_detalle_venta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ venta_id: ventaId })
            })
            .then(response => response.json())
            .then(data => {
                const detalleContenido = document.getElementById('detalle-contenido');
                
                if (data.venta && data.detalles) {
                    let html = `
                        <div style="margin-bottom: 20px;">
                            <p><strong>Venta ID:</strong> ${data.venta.id}</p>
                            <p><strong>Fecha:</strong> ${data.venta.fecha}</p>
                            <p><strong>Método de Pago:</strong> ${data.venta.metodo_pago}</p>
                            <p><strong>Descripción:</strong> ${data.venta.descripcion}</p>
                        </div>
                        
                        <h3>Productos</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Producto</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Cantidad</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Precio Unitario</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.detalles.forEach(detalle => {
                        html += `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">${detalle.nombre_producto}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${detalle.cantidad}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">$${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">$${parseFloat(detalle.subtotal).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px; text-align: right;">
                            <h3>Total: $${parseFloat(data.venta.total).toFixed(2)}</h3>
                        </div>
                    `;
                    
                    detalleContenido.innerHTML = html;
                } else {
                    detalleContenido.innerHTML = '<p>No se encontraron detalles para esta venta.</p>';
                }
                
                // Mostrar el modal
                document.getElementById('detalleModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error al cargar detalles:', error);
                alert('Error al cargar los detalles de la venta.');
            });
        }

        // Función para cerrar el modal
        function cerrarModal() {
            document.getElementById('detalleModal').style.display = 'none';
        }

        // Cerrar el modal si se hace clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('detalleModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>