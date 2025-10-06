<?php
session_start();

$empleados = isset($_SESSION['empleados']) ? $_SESSION['empleados'] : [];

if (count($empleados) === 0) {
    die("No hay empleados registrados para generar el reporte.");
}

// Función para cálculo completo
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

// Calcular totales
$totalNomina = 0;
$totalDevengados = 0;
$totalDeducciones = 0;
foreach ($empleados as $emp) {
    $calculo = calcularNominaCompleta($emp);
    $totalNomina += $calculo['salario_neto'];
    $totalDevengados += $calculo['devengados'];
    $totalDeducciones += $calculo['total_deducciones'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Desprendible de Nómina - Todos los Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { font-size: 11px; }
            .card { border: none !important; box-shadow: none !important; }
            .table-sm th, .table-sm td { padding: 4px; }
        }
        .empresa-header { background-color: #2c3e50; color: white; padding: 15px; }
        .table-deductions { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .table-deductions th, .table-deductions td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .table-deductions th { background-color: #f8f9fa; font-weight: bold; }
        .employee-section { margin-bottom: 30px; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; }
        .summary-card { background-color: #f8f9fa; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container mt-3">
        <!-- Encabezado del reporte general -->
        <div class="card mb-4">
            <div class="card-header empresa-header">
                <h3 class="text-center mb-0">HERMES INFINITY PROJECTS SAS</h3>
                <p class="text-center mb-0">NIT. 950.468.970-5</p>
                <h4 class="text-center mt-2">REPORTE GENERAL DE NÓMINA</h4>
                <p class="text-center mb-0">Período: Abril 2024 - Total Empleados: <?php echo count($empleados); ?></p>
            </div>
        </div>

        <!-- Resumen general -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Devengados</h5>
                        <h4 class="text-primary">$<?php echo number_format($totalDevengados, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Deducciones</h5>
                        <h4 class="text-danger">$<?php echo number_format($totalDeducciones, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Neto a Pagar</h5>
                        <h4 class="text-success">$<?php echo number_format($totalNomina, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desprendibles individuales -->
        <?php foreach ($empleados as $index => $emp): ?>
        <div class="employee-section <?php echo ($index > 0) ? 'page-break' : ''; ?>">
            <?php $calculo = calcularNominaCompleta($emp); ?>
            
            <!-- Información del empleado -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Nombre del trabajador:</strong> <?php echo $emp['nombre']; ?></p>
                    <p><strong>Identificación:</strong> <?php echo $emp['identificacion']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Centro de Costo:</strong> <?php echo $emp['sede']; ?></p>
                    <p><strong>Días laborados:</strong> <?php echo $emp['dias_laborados']; ?></p>
                    <p><strong>Cargo:</strong> <?php echo $emp['cargo']; ?></p>
                </div>
            </div>

            <!-- Tabla de devengados y deducciones -->
            <div class="row">
                <div class="col-md-6">
                    <h6>DEVENGADOS</h6>
                    <table class="table-deductions">
                        <tr>
                            <th>Concepto</th>
                            <th>Días</th>
                            <th>Valor</th>
                        </tr>
                        <tr>
                            <td>Salario</td>
                            <td><?php echo $emp['dias_laborados']; ?></td>
                            <td>$<?php echo number_format($calculo['salario_proporcional'], 2); ?></td>
                        </tr>
                        <?php if ($calculo['auxilio_transporte'] > 0): ?>
                        <tr>
                            <td>Auxilio de transporte</td>
                            <td><?php echo $emp['dias_laborados']; ?></td>
                            <td>$<?php echo number_format($calculo['auxilio_transporte'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="background-color: #e9ecef;">
                            <td colspan="2"><strong>TOTAL DEVENGADOS</strong></td>
                            <td><strong>$<?php echo number_format($calculo['devengados'], 2); ?></strong></td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h6>DEDUCIDOS</h6>
                    <table class="table-deductions">
                        <tr>
                            <th>Concepto</th>
                            <th>Días</th>
                            <th>Valor</th>
                        </tr>
                        <tr>
                            <td>Salud</td>
                            <td>30</td>
                            <td>$<?php echo number_format($calculo['salud'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Pensión</td>
                            <td>30</td>
                            <td>$<?php echo number_format($calculo['pension'], 2); ?></td>
                        </tr>
                        <?php if ($calculo['fondo_solidaridad'] > 0): ?>
                        <tr>
                            <td>Fondo de solidaridad</td>
                            <td>30</td>
                            <td>$<?php echo number_format($calculo['fondo_solidaridad'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($calculo['deduccion_prestamo'] > 0): ?>
                        <tr>
                            <td>Préstamo</td>
                            <td>-</td>
                            <td>$<?php echo number_format($calculo['deduccion_prestamo'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="background-color: #e9ecef;">
                            <td colspan="2"><strong>TOTAL DEDUCIDOS</strong></td>
                            <td><strong>$<?php echo number_format($calculo['total_deducciones'], 2); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Neto a pagar -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-primary text-center py-2">
                        <h5 class="mb-0"><strong>NETO A PAGAR: $<?php echo number_format($calculo['salario_neto'], 2); ?></strong></h5>
                    </div>
                </div>
            </div>

            <!-- Información de préstamo -->
            <?php if ($emp['monto_prestamo'] > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Información Préstamo</h5>
                        <table class="table-deductions">
                            <tr>
                                <th>Valor inicial</th>
                                <th>Cuota descontada</th> 
                                <th>Valor cuota</th>
                                <th>Cuotas por descontar</th>
                            </tr>
                            <tr>
                                <td>$<?php echo number_format($emp['monto_prestamo'], 2); ?></td>
                                <td>1</td> <td>$<?php echo number_format($calculo['deduccion_prestamo'], 2); ?></td>
                                <td><?php echo ($emp['cuotas'] - 1); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            <!-- Firma -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <p>_________________________</p>
                    <p><strong><?php echo $emp['nombre']; ?></strong></p>
                    <p>C.C. <?php echo $emp['identificacion']; ?></p>
                    <p><em>Recibí de conformidad y acepto en todas partes este pago</em></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Botones de acción -->
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-success">Imprimir Reporte Completo</button>
            <a href="ver_empleados.php" class="btn btn-primary">Volver a Empleados</a>
        </div>
    </div>

    <script>
        // Auto-print al cargar la página
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>