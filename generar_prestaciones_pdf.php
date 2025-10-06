<?php
session_start();

// Buscar empleado por identificación (si es individual)
$empleados = [];
if (isset($_GET['identificacion']) && isset($_SESSION['empleados'])) {
    foreach ($_SESSION['empleados'] as $emp) {
        if ($emp['identificacion'] == $_GET['identificacion']) {
            $empleados[] = $emp;
            break;
        }
    }
    $titulo = "Prestaciones Sociales - Individual";
} else {
    // Todos los empleados
    $empleados = isset($_SESSION['empleados']) ? $_SESSION['empleados'] : [];
    $titulo = "Prestaciones Sociales - General";
}

if (count($empleados) === 0) {
    die("No hay empleados registrados para generar el reporte.");
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

// Calcular totales
$totales = [
    'cesantias' => 0,
    'intereses_cesantias' => 0,
    'vacaciones' => 0,
    'prima_servicios' => 0,
    'dotacion' => 0,
    'total_prestaciones' => 0
];

foreach ($empleados as $emp) {
    $prestaciones = calcularPrestacionesSociales($emp);
    foreach ($totales as $key => $value) {
        $totales[$key] += $prestaciones[$key];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Prestaciones Sociales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { font-size: 11px; }
            .card { border: none !important; box-shadow: none !important; }
        }
        .empresa-header { background-color: #2c3e50; color: white; padding: 15px; }
        .prestacion-card { border-left: 4px solid #007bff; }
        .table-prestaciones { font-size: 0.85em; }
    </style>
</head>
<body>
    <div class="container mt-3">
        <!-- Encabezado -->
        <div class="card mb-4">
            <div class="card-header empresa-header">
                <h3 class="text-center mb-0">HERMES INFINITY PROJECTS SAS</h3>
                <p class="text-center mb-0">NIT. 950.468.970-5</p>
                <h4 class="text-center mt-2">REPORTE DE PRESTACIONES SOCIALES</h4>
                <p class="text-center mb-0"><?php echo $titulo; ?> - Período: Abril 2024</p>
            </div>
        </div>

        <!-- Resumen general -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card prestacion-card">
                    <div class="card-body text-center">
                        <h6>Total Cesantías</h6>
                        <h5 class="text-primary">$<?php echo number_format($totales['cesantias'], 2); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card prestacion-card">
                    <div class="card-body text-center">
                        <h6>Total Vacaciones</h6>
                        <h5 class="text-success">$<?php echo number_format($totales['vacaciones'], 2); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card prestacion-card">
                    <div class="card-body text-center">
                        <h6>Total Prestaciones</h6>
                        <h5 class="text-warning">$<?php echo number_format($totales['total_prestaciones'], 2); ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de prestaciones -->
        <div class="table-responsive">
            <table class="table table-bordered table-prestaciones">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Empleado</th>
                        <th>Identificación</th>
                        <th>Cargo</th>
                        <th>Días</th>
                        <th>Cesantías</th>
                        <th>Int. Cesantías</th>
                        <th>Vacaciones</th>
                        <th>Prima Serv.</th>
                        <th>Dotación</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empleados as $index => $emp): 
                        $prestaciones = calcularPrestacionesSociales($emp);
                    ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $emp['nombre']; ?></td>
                        <td><?php echo $emp['identificacion']; ?></td>
                        <td><?php echo $emp['cargo']; ?></td>
                        <td><?php echo $emp['dias_laborados']; ?></td>
                        <td>$<?php echo number_format($prestaciones['cesantias'], 2); ?></td>
                        <td>$<?php echo number_format($prestaciones['intereses_cesantias'], 2); ?></td>
                        <td>$<?php echo number_format($prestaciones['vacaciones'], 2); ?></td>
                        <td>$<?php echo number_format($prestaciones['prima_servicios'], 2); ?></td>
                        <td>$<?php echo number_format($prestaciones['dotacion'], 2); ?></td>
                        <td><strong>$<?php echo number_format($prestaciones['total_prestaciones'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Fila de totales -->
                    <tr class="table-info">
                        <td colspan="5" class="text-end"><strong>TOTALES:</strong></td>
                        <td><strong>$<?php echo number_format($totales['cesantias'], 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($totales['intereses_cesantias'], 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($totales['vacaciones'], 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($totales['prima_servicios'], 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($totales['dotacion'], 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($totales['total_prestaciones'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Notas explicativas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Notas sobre Prestaciones Sociales</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li><strong>Cesantías:</strong> 8.33% anual (1 mes de salario por año laborado)</li>
                            <li><strong>Intereses sobre Cesantías:</strong> 12% anual sobre el valor de las cesantías</li>
                            <li><strong>Vacaciones:</strong> 4.17% anual (15 días hábiles por año laborado)</li>
                            <li><strong>Prima de Servicios:</strong> 8.33% anual (1 mes de salario por semestre laborado)</li>
                            <li><strong>Dotación:</