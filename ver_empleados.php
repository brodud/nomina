<?php
session_start();

// Si no hay empleados, inicializar array vacío
$empleados = isset($_SESSION['empleados']) ? $_SESSION['empleados'] : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Empleados Registrados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h1 class="text-center">Empleados registrados</h1>
            </div>
            <div class="card-body">
                <?php if (count($empleados) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Sede</th>
                                <th>Cargo</th>
                                <th>Identificación</th>
                                <th>Sueldo Base</th>
                                <th>Días</th>
                                <th>Préstamo</th>
                                <th>Salario Neto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $index => $emp): 
                                $salario = calcularNomina($emp);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($emp['sede']); ?></td>
                                <td><?php echo htmlspecialchars($emp['cargo']); ?></td>
                                <td><?php echo htmlspecialchars($emp['identificacion']); ?></td>
                                <td>$<?php echo number_format($emp['sueldo'], 2); ?></td>
                                <td><?php echo $emp['dias_laborados']; ?></td>
                                <td>$<?php echo number_format($emp['monto_prestamo'], 2); ?></td>
                                <td><strong>$<?php echo number_format($salario, 2); ?></strong></td>
                                <td>
                                    <a href='generar_pdf.php?identificacion=<?php echo $emp['identificacion']; ?>' 
                                       class='btn btn-sm btn-warning'>PDF</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.html" class="btn btn-primary">Registrar nuevo empleado</a>
                    <a href="limpiar_sesion.php" class="btn btn-warning">Descargar PDF empleados</a>
                </div>
                
                <?php else: ?>
                <div class="alert alert-warning text-center">
                    <h4>No hay empleados registrados</h4>
                    <p>Los datos se mantienen temporalmente durante esta sesión de navegación.</p>
                    <a href="index.html" class="btn btn-primary">Ir al formulario de registro</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Función para calcular nómina
function calcularNomina($empleado) {
    $salarioMensual = ($empleado['sueldo'] * $empleado['dias_laborados']) / 30;

    if ($empleado['monto_prestamo'] > 0 && $empleado['cuotas'] > 0) {
        $deduccionPrestamo = $empleado['monto_prestamo'] / $empleado['cuotas'];
        $salarioMensual -= $deduccionPrestamo;
    }
    
    return max($salarioMensual, 0);
}
?>