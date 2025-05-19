<?php
session_start();
require_once '../includes/db.php'; // Conexión a la base de datos
require_once '../includes/functions.php'; // Funciones útiles

// Redirigir si el usuario no está logueado o no es un tutor
if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php');
}

$tutor_id = $_SESSION['user_id'];
$tutor_nombre = $_SESSION['user_name'];
$page_title = "Generar Reporte de Tutorías";

// Inicializar variables para el formulario y el reporte
$fecha_inicio = '';
$fecha_fin = '';
$report_data = [];
$report_summary = [
    'total_tutorias' => 0,
    'programadas' => 0,
    'completadas' => 0,
    'canceladas' => 0,
    'reprogramadas' => 0,
];
$error_message = '';
$show_report = false;

// Procesar el formulario de generación de reporte
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generar_reporte'])) {
    $fecha_inicio = sanitize_input($_POST['fecha_inicio']);
    $fecha_fin = sanitize_input($_POST['fecha_fin']);

    if (empty($fecha_inicio) || empty($fecha_fin)) {
        $error_message = "Por favor, selecciona ambas fechas para generar el reporte.";
    } elseif (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        $error_message = "La fecha de fin no puede ser anterior a la fecha de inicio.";
    } else {
        $show_report = true;
        // Consultar la base de datos para obtener las tutorías del tutor en el rango de fechas
        // Usamos 'tutorías' con tilde según tu esquema de BD
        $stmt = $conn->prepare("
            SELECT 
                t.ID_tutoria, t.fecha, t.hora, t.modalidad, t.estado_tutoria,
                e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante,
                m.nombre_materia
            FROM tutorías t
            JOIN estudiantes e ON t.ID_estudiante = e.ID_estudiante
            JOIN materias m ON t.ID_materia = m.ID_materia
            WHERE t.ID_tutor = ? AND t.fecha BETWEEN ? AND ?
            ORDER BY t.fecha ASC, t.hora ASC
        ");

        if ($stmt) {
            $stmt->bind_param("iss", $tutor_id, $fecha_inicio, $fecha_fin);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                    // Actualizar el resumen
                    $report_summary['total_tutorias']++;
                    switch ($row['estado_tutoria']) {
                        case 'Programada':
                            $report_summary['programadas']++;
                            break;
                        case 'Completada':
                            $report_summary['completadas']++;
                            break;
                        case 'Cancelada':
                            $report_summary['canceladas']++;
                            break;
                        case 'Reprogramada':
                            $report_summary['reprogramadas']++;
                            break;
                    }
                }
            } else {
                $error_message = "No se encontraron tutorías para el rango de fechas seleccionado.";
                $show_report = false; // No mostrar la sección de reporte si no hay datos
            }
            $stmt->close();
        } else {
            $error_message = "Error al preparar la consulta: " . $conn->error;
            $show_report = false;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <h2>Generar Reporte de Tutorías</h2>
    <p>Selecciona un rango de fechas para generar un reporte de las tutorías que has impartido.</p>

    <?php if ($error_message && !$show_report): // Mostrar error solo si no se va a mostrar el reporte ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form action="generar_reporte_tutor.php" method="POST" class="form-container" style="max-width: 700px; margin-bottom: 30px;">
        <div style="display: flex; gap: 20px; align-items: flex-end;">
            <div style="flex-grow: 1;">
                <label for="fecha_inicio">Fecha de Inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
            </div>
            <div style="flex-grow: 1;">
                <label for="fecha_fin">Fecha de Fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
            </div>
            <button type="submit" name="generar_reporte">Generar Reporte</button>
        </div>
    </form>

    <?php if ($show_report && !empty($report_data)): ?>
    <div class="report-section">
        <h3>Reporte de Tutorías para <?php echo htmlspecialchars($tutor_nombre); ?></h3>
        <p><strong>Periodo:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($fecha_inicio))); ?> - <?php echo htmlspecialchars(date('d/m/Y', strtotime($fecha_fin))); ?></p>
        
        <h4>Resumen de Tutorías</h4>
        <ul>
            <li><strong>Total de Tutorías en el periodo:</strong> <?php echo $report_summary['total_tutorias']; ?></li>
            <li>Tutorías Completadas: <?php echo $report_summary['completadas']; ?></li>
            <li>Tutorías Programadas (pendientes o futuras en el rango): <?php echo $report_summary['programadas']; ?></li>
            <li>Tutorías Canceladas: <?php echo $report_summary['canceladas']; ?></li>
            <li>Tutorías Reprogramadas: <?php echo $report_summary['reprogramadas']; ?></li>
        </ul>

        <h4>Detalle de Tutorías</h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estudiante</th>
                    <th>Materia</th>
                    <th>Modalidad</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $tutoria): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($tutoria['fecha']))); ?></td>
                    <td><?php echo htmlspecialchars(date('H:i A', strtotime($tutoria['hora']))); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['nombre_estudiante'] . ' ' . $tutoria['apellido_estudiante']); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['nombre_materia']); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['modalidad']); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['estado_tutoria']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top: 20px; text-align: center;">
            <button onclick="window.print();" class="button">Imprimir Reporte</button>
            </div>
    </div>
    <?php elseif ($show_report && empty($report_data)): ?>
        <p class="info-message">No se encontraron tutorías para el rango de fechas seleccionado.</p>
    <?php endif; ?>
    <?php if ($error_message && $show_report): // Mostrar error si ocurrió durante la generación del reporte ?>
        <p class="error-message" style="margin-top: 15px;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>


    <p style="margin-top: 30px;"><a href="dashboard_tutor.php">Volver al Dashboard</a></p>
</div>

<?php
// Lógica para guardar el reporte en la tabla 'reportes' (opcional)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_reporte_db'])) {
    $fecha_inicio_g = sanitize_input($_POST['fecha_inicio_guardar']);
    $fecha_fin_g = sanitize_input($_POST['fecha_fin_guardar']);
    $contenido_g = "Reporte de tutorías del " . $fecha_inicio_g . " al " . $fecha_fin_g . ". Resumen: " . sanitize_input($_POST['contenido_reporte_guardar']); // Simplificado
    $fecha_realizacion_g = date('Y-m-d');

    $stmt_save = $conn->prepare("INSERT INTO reportes (ID_tutor, fecha_realizacion, contenido) VALUES (?, ?, ?)");
    if ($stmt_save) {
        $stmt_save->bind_param("iss", $tutor_id, $fecha_realizacion_g, $contenido_g);
        if ($stmt_save->execute()) {
            echo "<p class='success-message' style='text-align:center; margin-top:10px;'>Resumen del reporte guardado en el sistema.</p>";
        } else {
            echo "<p class='error-message' style='text-align:center; margin-top:10px;'>Error al guardar el resumen: " . $stmt_save->error . "</p>";
        }
        $stmt_save->close();
    } else {
         echo "<p class='error-message' style='text-align:center; margin-top:10px;'>Error al preparar guardado: " . $conn->error . "</p>";
    }
}

$conn->close();
require_once '../includes/footer.php';
?>