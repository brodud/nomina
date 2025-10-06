<?php
session_start();

if (!isset($_SESSION['empleados'])) {
    $_SESSION['empleados'] = [];
}

// Función para calcular nómina con todas las deducciones y prestaciones
function calcularNominaCompleta($empleado) {
    // Calcular salario proporcional
    $salarioProporcional = ($empleado['sueldo'] * $empleado['dias_laborados']) / 30;
    
    // Calcular auxilio de transporte (para 2024 en Colombia: $162.000)
    $auxilioTransporte = 162000;
    $devengados = $salarioProporcional;
    
    // Solo se da auxilio de transporte si el salario es menor a 2 SMMLV (2024: $2.600.000)
    if ($empleado['sueldo'] < 2600000) {
        $auxilioTransporteProporcional = ($auxilioTransporte * $empleado['dias_laborados']) / 30;
        $devengados += $auxilioTransporteProporcional;
    } else {
        $auxilioTransporteProporcional = 0;
    }
    
    // DEDUCCIONES LEGALES
    $deducciones = 0;
    
    // Salud (4% sobre el salario base)
    $salud = $salarioProporcional * 0.04;
    $deducciones += $salud;
    
    // Pensión (4% sobre el salario base)
    $pension = $salarioProporcional * 0.04;
    $deducciones += $pension;
    
    // Fondo de solidaridad pensional (1% para salarios superiores a 4 SMMLV)
    $fondoSolidaridad = 0;
    if ($empleado['sueldo'] > 5200000) { // 4 SMMLV 2024: $5.200.000
        $fondoSolidaridad = $salarioProporcional * 0.01;
        $deducciones += $fondoSolidaridad;
    }
    
    // Deducción por préstamo
    $deduccionPrestamo = 0;
    if ($empleado['monto_prestamo'] > 0 && $empleado['cuotas'] > 0) {
        $deduccionPrestamo = $empleado['monto_prestamo'] / $empleado['cuotas'];
        $deducciones += $deduccionPrestamo;
    }
    
    // PRESTACIONES SOCIALES
    $prestaciones = calcularPrestacionesSociales($empleado);
    
    // Salario neto
    $salarioNeto = $devengados - $deducciones;
    
    return [
        'salario_proporcional' => $salarioProporcional,
        'auxilio_transporte' => $auxilioTransporteProporcional,
        'devengados' => $devengados,
        'salud' => $salud,
        'pension' => $pension,
        'fondo_solidaridad' => $fondoSolidaridad,
        'deduccion_prestamo' => $deduccionPrestamo,
        'total_deducciones' => $deducciones,
        'salario_neto' => max($salarioNeto, 0),
        'prestaciones' => $prestaciones
    ];
}

// Función para calcular prestaciones sociales
function calcularPrestacionesSociales($empleado) {
    $sueldoBase = $empleado['sueldo'];
    $diasLaborados = $empleado['dias_laborados'];
    
    // Cesantías (8.33% por año - 1 mes de salario por año)
    $cesantias = ($sueldoBase * $diasLaborados) / 360;
    
    // Intereses sobre cesantías (12% anual sobre las cesantías)
    $interesesCesantias = $cesantias * 0.12 * ($diasLaborados / 360);
    
    // Vacaciones (4.17% por año - 15 días por año)
    $vacaciones = ($sueldoBase * $diasLaborados) / 720;
    
    // Prima de servicios (8.33% por año - 1 mes de salario por semestre)
    $primaServicios = ($sueldoBase * $diasLaborados) / 360;
    
    // Dotación (para salarios hasta 2 SMMLV)
    $dotacion = 0;
    if ($sueldoBase <= 2600000) {
        // Se calcula 1 uniforme por cada 6 meses (valor aproximado $150.000 por uniforme)
        $dotacion = 150000 * ($diasLaborados / 180);
    }
    
    return [
        'cesantias' => $cesantias,
        'intereses_cesantias' => $interesesCesantias,
        'vacaciones' => $vacaciones,
        'prima_servicios' => $primaServicios,
        'dotacion' => $dotacion,
        'total_prestaciones' => $cesantias + $interesesCesantias + $vacaciones + $primaServicios + $dotacion
    ];
}

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    
    $calculo = calcularNominaCompleta($nuevoEmpleado);
    
    mostrarResultado($nuevoEmpleado, $calculo, count($_SESSION['empleados']));
}

function mostrarResultado($empleado, $calculo, $totalEmpleados = 0) {
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
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo $empleado['nombre']; ?></p>
                            <p><strong>Sede:</strong> <?php echo $empleado['sede']; ?></p>
                            <p><strong>Cargo:</strong> <?php echo $empleado['cargo']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Identificación:</strong> <?php echo $empleado['identificacion']; ?></p>
                            <p><strong>Días laborados:</strong> <?php echo $empleado['dias_laborados']; ?></p>
                            <p><strong>Sueldo base:</strong> $<?php echo number_format($empleado['sueldo'], 2); ?></p>
                        </div>
                    </div>
                    
                    <h4 class='mt-4'>Cálculos de Nómina:</h4>
                    
                    <div class="row">
                        <!-- DEVENGADOS -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">DEVENGADOS</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Salario proporcional:</strong> $<?php echo number_format($calculo['salario_proporcional'], 2); ?></p>
                                    <?php if ($calculo['auxilio_transporte'] > 0): ?>
                                    <p><strong>Auxilio de transporte:</strong> $<?php echo number_format($calculo['auxilio_transporte'], 2); ?></p>
                                    <?php endif; ?>
                                    <hr>
                                    <p><strong>TOTAL DEVENGADOS:</strong> $<?php echo number_format($calculo['devengados'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- DEDUCIDOS -->
                        <div class="col-md-6">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">DEDUCIDOS</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Salud (4%):</strong> $<?php echo number_format($calculo['salud'], 2); ?></p>
                                    <p><strong>Pensión (4%):</strong> $<?php echo number_format($calculo['pension'], 2); ?></p>
                                    <?php if ($calculo['fondo_solidaridad'] > 0): ?>
                                    <p><strong>Fondo solidaridad (1%):</strong> $<?php echo number_format($calculo['fondo_solidaridad'], 2); ?></p>
                                    <?php endif; ?>
                                    <?php if ($calculo['deduccion_prestamo'] > 0): ?>
                                    <p><strong>Deducción préstamo:</strong> $<?php echo number_format($calculo['deduccion_prestamo'], 2); ?></p>
                                    <?php endif; ?>
                                    <hr>
                                    <p><strong>TOTAL DEDUCCIONES:</strong> $<?php echo number_format($calculo['total_deducciones'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PRESTACIONES SOCIALES -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">PRESTACIONES SOCIALES ACUMULADAS</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>Cesantías:</strong> $<?php echo number_format($calculo['prestaciones']['cesantias'], 2); ?></p>
                                            <p><strong>Intereses cesantías:</strong> $<?php echo number_format($calculo['prestaciones']['intereses_cesantias'], 2); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><strong>Vacaciones:</strong> $<?php echo number_format($calculo['prestaciones']['vacaciones'], 2); ?></p>
                                            <p><strong>Prima de servicios:</strong> $<?php echo number_format($calculo['prestaciones']['prima_servicios'], 2); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <?php if ($calculo['prestaciones']['dotacion'] > 0): ?>
                                            <p><strong>Dotación:</strong> $<?php echo number_format($calculo['prestaciones']['dotacion'], 2); ?></p>
                                            <?php endif; ?>
                                            <hr>
                                            <p><strong>TOTAL PRESTACIONES:</strong> $<?php echo number_format($calculo['prestaciones']['total_prestaciones'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class='alert alert-primary mt-4 text-center'>
                        <h4><strong>SALARIO NETO A PAGAR: $<?php echo number_format($calculo['salario_neto'], 2); ?></strong></h4>
                    </div>
                    
                    <div class='d-flex gap-2 flex-wrap justify-content-center'>
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