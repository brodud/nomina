<?php
// Iniciar sesión para mantener los datos temporales
session_start();

// Inicializar el vector de empleados en la sesión si no existe
if (!isset($_SESSION['empleados'])) {
    $_SESSION['empleados'] = [];
}

// Función para calcular nómina
function calcularNomina($empleado) {
    $salarioMensual = ($empleado['sueldo'] * $empleado['dias_laborados']) / 30;

    // Préstamo
    if ($empleado['monto_prestamo'] > 0 && $empleado['cuotas'] > 0) {
        $deduccionPrestamo = $empleado['monto_prestamo'] / $empleado['cuotas'];
        $salarioMensual -= $deduccionPrestamo;
    }
    
    return max($salarioMensual, 0);
}

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Crear nuevo empleado - CORREGIDO los nombres de campos
    $nuevoEmpleado = [
        'nombre' => $_POST['nombre'],
        'sede' => $_POST['sede'],
        'cargo' => $_POST['cargo'],
        'identificacion' => $_POST['identificacion'],
        'sueldo' => floatval($_POST['sueldo']),
        'dias_laborados' => intval($_POST['diasLaborados']),
        'monto_prestamo' => floatval($_POST['montoPrestamo']), 
        'cuotas' => intval($_POST['cuotas']),
        'fecha_prestamo' => $_POST['fechaPrestamo'],
        'fecha_registro' => date('Y-m-d H:i:s')
    ];
    
    
    $_SESSION['empleados'][] = $nuevoEmpleado;
    
   
    $salario = calcularNomina($nuevoEmpleado);
    
   
    mostrarResultado($nuevoEmpleado, $salario, count($_SESSION['empleados']));
}

function mostrarResultado($empleado, $salario, $totalEmpleados = 0) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Resultado Nómina</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container mt-5'>
            <div class='card shadow'>
                <div class='card-header bg-success text-white'>
                    <h2 class='text-center'>Nómina Calculada</h2>
                    <p class='text-center mb-0'>Empleados registrados: <?php echo $totalEmpleados; ?></p>
                </div>
                <div class='card-body'>
                    <h4>Datos del Empleado:</h4>
                    <p><strong>Nombre:</strong> <?php echo $empleado['nombre']; ?></p>
                    <p><strong>Sede:</strong> <?php echo $empleado['sede']; ?></p>
                    <p><strong>Cargo:</strong> <?php echo $empleado['cargo']; ?></p>
                    <p><strong>Identificación:</strong> <?php echo $empleado['identificacion']; ?></p>
                    <p><strong>Días laborados:</strong> <?php echo $empleado['dias_laborados']; ?></p>
                    <p><strong>Sueldo base:</strong> $<?php echo number_format($empleado['sueldo'], 2); ?></p>
                    
                    <h4 class='mt-4'>Cálculos:</h4>
                    <p><strong>Salario proporcional:</strong> $<?php echo number_format(($empleado['sueldo'] * $empleado['dias_laborados']) / 30, 2); ?></p>
                    
                    <?php if ($empleado['monto_prestamo'] > 0): ?>
                    <p><strong>Descuento préstamo:</strong> -$<?php echo number_format($empleado['monto_prestamo'] / $empleado['cuotas'], 2); ?></p>
                    <?php endif; ?>
                    
                    <div class='alert alert-primary mt-3'>
                        <h5><strong>SALARIO NETO: $<?php echo number_format($salario, 2); ?></strong></h5>
                    </div>
                    
                    <div class='d-flex gap-2 flex-wrap'>
                        <a href='index.html' class='btn btn-primary'>Registrar otro empleado</a>
                        <a href='ver_empleados.php' class='btn btn-info'>Ver todos los empleados</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>