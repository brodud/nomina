<?php
session_start();

$empleados = isset($_SESSION['empleados']) ? $_SESSION['empleados'] : [];

// Función para calcular nómina (actualizada)
function calcularNomina($empleado) {
    $salarioProporcional = ($empleado['sueldo'] * $empleado['dias_laborados']) / 30;
    
    // Auxilio de transporte
    $auxilioTransporte = 162000;
    if ($empleado['sueldo'] < 2600000) {
        $auxilioTransporteProporcional = ($auxilioTransporte * $empleado['dias_laborados']) / 30;
        $devengados = $salarioProporcional + $auxilioTransporteProporcional;
    } else {
        $devengados = $salarioProporcional;
    }
    
    // Deducciones
    $deducciones = 0;
    $deducciones += $salarioProporcional * 0.04; // Salud
    $deducciones += $salarioProporcional * 0.04; // Pensión
    
    // Fondo solidaridad
    if ($empleado['sueldo'] > 5200000) {
        $deducciones += $salarioProporcional * 0.01;
    }
    
    // Préstamo
    if ($empleado['monto_prestamo'] > 0 && $empleado['cuotas'] > 0) {
        $deducciones += $empleado['monto_prestamo'] / $empleado['cuotas'];
    }
    
    return max($devengados - $deducciones, 0);
}

// Función para cálculo completo (para el PDF de todos)
function calcularNominaCompleta($empleado) {
    $salarioProporcional = ($empleado['sueldo'] * $empleado['dias_laborados']) / 30;
    
    $auxilioTransporte = 162000;
    $devengados = $salarioProporcional;
    
    if ($empleado['sueldo'] < 2600000) {
        $auxilioTransporteProporcional = ($auxilioTransporte * $empleado['dias_laborados']) / 30;
        $devengados += $auxilioTransporteProporcional;
    } else {
        $auxilioTransporteProporcional = 0;
    }
    
    $deducciones = 0;
    $salud = $salarioProporcional * 0.04;
    $pension = $salarioProporcional * 0.04;
    $deducciones += $salud + $pension;
    
    $fondoSolidaridad = 0;
    if ($empleado['sueldo'] > 5200000) {
        $fondoSolidaridad = $salarioProporcional * 0.01;
        $deducciones += $fondoSolidaridad;
    }
    
    $deduccionPrestamo = 0;
    if ($empleado['monto_prestamo'] > 0 && $empleado['cuotas'] > 0) {
        $deduccionPrestamo = $empleado['monto_prestamo'] / $empleado['cuotas'];
        $deducciones += $deduccionPrestamo;
    }
    
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
        'salario_neto' => max($salarioNeto, 0)
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

// Calcular totales para el resumen
$totalNomina = 0;
$totalPrestaciones = 0;
$totalCesantias = 0;
$totalVacaciones = 0;

foreach ($empleados as $emp) {
    $totalNomina += calcularNomina($emp);
    $prestaciones = calcularPrestacionesSociales($emp);
    $totalPrestaciones += $prestaciones['total_prestaciones'];
    $totalCesantias += $prestaciones['cesantias'];
    $totalVacaciones += $prestaciones['vacaciones'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Empleados Registrados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        .actions-column {
            white-space: nowrap;
        }
        .prestaciones-badge {
            font-size: 0.8em;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h1 class="text-center">Empleados registrados</h1>
                <p class="text-center mb-0">Total: <?php echo count($empleados); ?> empleados</p>
            </div>
            <div class="card-body">
                <?php if (count($empleados) > 0): ?>
                
                <!-- Resumen general -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h6>Total Nómina</h6>
                                <h5>$<?php echo number_format($totalNomina, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h6>Prestaciones</h6>
                                <h5>$<?php echo number_format($totalPrestaciones, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h6>Cesantías</h6>
                                <h5>$<?php echo number_format($totalCesantias, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h6>Vacaciones</h6>
                                <h5>$<?php echo number_format($totalVacaciones, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Lista de Empleados</h4>
                    <div>
                        <a href="generar_pdf_todos.php" class="btn btn-success" target="_blank">
                            PDF General
                        </a>
                        <a href="generar_prestaciones_pdf.php" class="btn btn-info" target="_blank">
                            PDF Prestaciones
                        </a>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Sede</th>
                                <th>Cargo</th>
                                <th>Identificación</th>
                                <th>Sueldo Base</th>
                                <th>Días</th>
                                <th>Salario Neto</th>
                                <th>Prestaciones</th>
                                <th class="actions-column">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $index => $emp): 
                                $salario = calcularNomina($emp);
                                $prestaciones = calcularPrestacionesSociales($emp);
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($emp['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($emp['sede']); ?></td>
                                <td><?php echo htmlspecialchars($emp['cargo']); ?></td>
                                <td><?php echo htmlspecialchars($emp['identificacion']); ?></td>
                                <td>$<?php echo number_format($emp['sueldo'], 2); ?></td>
                                <td><?php echo $emp['dias_laborados']; ?></td>
                                <td><strong>$<?php echo number_format($salario, 2); ?></strong></td>
                                <td>
                                    <span class="badge bg-success prestaciones-badge" 
                                          title="Cesantías: $<?php echo number_format($prestaciones['cesantias'], 2); ?>&#10;Vacaciones: $<?php echo number_format($prestaciones['vacaciones'], 2); ?>&#10;Total: $<?php echo number_format($prestaciones['total_prestaciones'], 2); ?>">
                                        $<?php echo number_format($prestaciones['total_prestaciones'], 2); ?>
                                    </span>
                                </td>
                                <td class="actions-column">
                                    <a href='generar_pdf.php?identificacion=<?php echo $emp['identificacion']; ?>' 
                                       class='btn btn-sm btn-warning' target="_blank">PDF Nómina</a>
                                    <a href='generar_prestaciones_pdf.php?identificacion=<?php echo $emp['identificacion']; ?>' 
                                       class='btn btn-sm btn-info' target="_blank">Información prestaciones</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.html" class="btn btn-primary">Registrar nuevo empleado</a>
                    <a href="limpiar_sesion.php" class="btn btn-danger">Limpiar Registros</a>
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