<?php
session_start();

// Buscar empleado por identificación
$empleado = null;
if (isset($_GET['identificacion']) && isset($_SESSION['empleados'])) {
    foreach ($_SESSION['empleados'] as $emp) {
        if ($emp['identificacion'] == $_GET['identificacion']) {
            $empleado = $emp;
            break;
        }
    }
}

if (!$empleado) {
    die("Empleado no encontrado");
}

// Incluir la función de cálculo
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

$calculo = calcularNominaCompleta($empleado);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Desprendible de Nómina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .card { border: none !important; box-shadow: none !important; }
        }
        .empresa-header { background-color: #2c3e50; color: white; padding: 15px; }
        .table-deductions { width: 100%; border-collapse: collapse; }
        .table-deductions th, .table-deductions td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table-deductions th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container mt-3">
        <div class="card">
            <div class="card-header empresa-header">
                <h3 class="text-center mb-0">HERMES INFINITY PROJECTS SAS</h3>
                <p class="text-center mb-0">NIT. 950.468.970-5</p>
            </div>
            <div class="card-body">
                <!-- Información del empleado -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Nombre del trabajador:</strong> <?php echo $empleado['nombre']; ?></p>
                        <p><strong>Identificación:</strong> <?php echo $empleado['identificacion']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Centro de Costo:</strong> <?php echo $empleado['sede']; ?></p>
                        <p><strong>Días laborados:</strong> <?php echo $empleado['dias_laborados']; ?></p>
                        <p><strong>Período:</strong> Abril 2024</p>
                    </div>
                </div>

                <!-- Tabla de devengados y deducciones -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>DEVENGADOS</h5>
                        <table class="table-deductions">
                            <tr>
                                <th>Concepto</th>
                                <th>Días</th>
                                <th>Valor</th>
                            </tr>
                            <tr>
                                <td>Salario</td>
                                <td><?php echo $empleado['dias_laborados']; ?></td>
                                <td>$<?php echo number_format($calculo['salario_proporcional'], 2); ?></td>
                            </tr>
                            <?php if ($calculo['auxilio_transporte'] > 0): ?>
                            <tr>
                                <td>Auxilio de transporte</td>
                                <td><?php echo $empleado['dias_laborados']; ?></td>
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
                        <h5>DEDUCIDOS</h5>
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
                                <td>Fondo de solidaridad Pensional</td>
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
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-primary text-center">
                            <h4 class="mb-0"><strong>NETO A PAGAR: $<?php echo number_format($calculo['salario_neto'], 2); ?></strong></h4>
                        </div>
                    </div>
                </div>

                <!-- Información de préstamo -->
                <?php if ($empleado['monto_prestamo'] > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Información Préstamo</h5>
                        <table class="table-deductions">
                            <tr>
                                <th>Monto Total Préstamo</th>
                                <th>Cuotas Totales</th> <th>Valor cuota</th>
                                <th>Cuotas por descontar</th>
                            </tr>
                            <tr>
                                <td>$<?php echo number_format($empleado['monto_prestamo'], 2); ?></td>
                                <td><?php echo $empleado['cuotas']; ?></td> <td>$<?php echo number_format($calculo['deduccion_prestamo'], 2); ?></td>
                                <td><?php echo ($empleado['cuotas'] - 1); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
               

                <!-- Firma -->
                <div class="row mt-5">
                    <div class="col-12 text-center">
                        <p>_________________________</p>
                        <p><strong><?php echo $empleado['nombre']; ?></strong></p>
                        <p>C.C. <?php echo $empleado['identificacion']; ?></p>
                        <p><em>Recibí de conformidad y acepto en todas partes este pago</em></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="text-center mt-3 no-print">
            <button onclick="window.print()" class="btn btn-success">Imprimir PDF</button>
            <a href="ver_empleados.php" class="btn btn-primary">Volver a empleados</a>
        </div>
    </div>
</body>
</html>