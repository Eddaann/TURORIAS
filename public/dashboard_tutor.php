<?php
// public/dashboard_tutor.php
session_start();
require_once '../includes/db.php'; // Conexión a la base de datos
require_once '../includes/functions.php'; // Funciones útiles

// Redirigir si el usuario no está logueado o no es un tutor
if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php'); // O a una página de error/acceso denegado
}

$tutor_id = $_SESSION['user_id'];
$tutor_nombre = $_SESSION['user_name'];

$page_title = "Dashboard del Tutor"; // Título específico para esta página
$active_page = 'dashboard_tutor'; // Para el sidebar

require_once '../includes/header.php'; // Incluye el encabezado
?>

<div class="page-container" style="display: flex;">
    <?php require_once '../includes/sidebar_tutor.php'; // Incluye el sidebar del tutor ?>

    <div class="main-content-area" style="flex-grow: 1; padding: 20px;">
        <div class="dashboard-container"> <h2>Bienvenido, Tutor <?php echo htmlspecialchars($tutor_nombre); ?>!</h2>
            <p>Este es tu panel de control donde puedes gestionar tus tutorías.</p>

            <h3>Tutorías Próximas Asignadas</h3>
            <?php
            // Obtener las próximas tutorías asignadas a este tutor
            // Usamos 'tutorías' con tilde según el DDL.
            // Incluimos 'enlace_o_lugar' y 'notas_adicionales' asumiendo que estas columnas
            // podrían ser añadidas a la tabla 'tutorías' como se consideró en 'crear_tutoria.php'.
            // Si no existen, la consulta podría fallar o devolver NULLs. Ajustar si es necesario.
            $stmt_proximas_tutorias_tutor = $conn->prepare("
                SELECT
                    t.ID_tutoria, t.fecha, t.hora, t.modalidad, t.estado_tutoria,
                    t.ID_materia, -- Para referencia
                    -- Las siguientes columnas no están en el DDL original de 'tutorías'
                    -- Si no las añades a la tabla, la consulta podría fallar o estas columnas serán NULL.
                    -- Comenta/elimina si no se usarán:
                    -- enlace_o_lugar, 
                    -- notas_adicionales,
                    e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante,
                    m.nombre_materia
                FROM
                    tutorías t
                JOIN
                    estudiantes e ON t.ID_estudiante = e.ID_estudiante
                JOIN
                    materias m ON t.ID_materia = m.ID_materia
                WHERE
                    t.ID_tutor = ? AND (t.fecha > CURDATE() OR (t.fecha = CURDATE() AND t.hora >= CURTIME()))
                ORDER BY
                    t.fecha ASC, t.hora ASC
                LIMIT 10 
            ");
            // Si enlace_o_lugar y notas_adicionales NO existen, la consulta debería ser:
            /*
            $stmt_proximas_tutorias_tutor = $conn->prepare("
                SELECT
                    t.ID_tutoria, t.fecha, t.hora, t.modalidad, t.estado_tutoria,
                    e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante,
                    m.nombre_materia
                FROM
                    tutorías t
                JOIN
                    estudiantes e ON t.ID_estudiante = e.ID_estudiante
                JOIN
                    materias m ON t.ID_materia = m.ID_materia
                WHERE
                    t.ID_tutor = ? AND (t.fecha > CURDATE() OR (t.fecha = CURDATE() AND t.hora >= CURTIME()))
                ORDER BY
                    t.fecha ASC, t.hora ASC
                LIMIT 10
            ");
            */


            if ($stmt_proximas_tutorias_tutor) {
                $stmt_proximas_tutorias_tutor->bind_param("i", $tutor_id);
                $stmt_proximas_tutorias_tutor->execute();
                $result_proximas_tutorias_tutor = $stmt_proximas_tutorias_tutor->get_result();

                if ($result_proximas_tutorias_tutor->num_rows > 0) {
                    echo '<table class="data-table">';
                    // Ajustar encabezado si 'enlace_o_lugar' no se muestra
                    echo '<thead><tr><th>Estudiante</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Modalidad</th><th>Lugar/Enlace</th><th>Estado</th><th>Acciones</th></tr></thead>';
                    echo '<tbody>';
                    while ($row = $result_proximas_tutorias_tutor->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['nombre_estudiante'] . ' ' . $row['apellido_estudiante']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['nombre_materia']) . '</td>';
                        echo '<td>' . htmlspecialchars(date('d/m/Y', strtotime($row['fecha']))) . '</td>';
                        echo '<td>' . htmlspecialchars(date('H:i A', strtotime($row['hora']))) . '</td>';
                        echo '<td>' . htmlspecialchars($row['modalidad']) . '</td>';
                        // Mostrar 'enlace_o_lugar' si la columna existe y tiene valor.
                        // Si la columna 'enlace_o_lugar' no está en tu SELECT (porque no existe en la tabla),
                        // esta línea dará un aviso de índice no definido.
                        // echo '<td>' . htmlspecialchars($row['enlace_o_lugar'] ?? 'No especificado') . '</td>';
                        // Alternativa si la columna no existe en el SELECT:
                         echo '<td>No especificado</td>'; // O ajusta según la lógica deseada si la columna no existe
                        echo '<td>' . htmlspecialchars($row['estado_tutoria']) . '</td>';
                        echo '<td>';
                        echo '<a href="detalle_tutoria.php?id_tutoria=' . $row['ID_tutoria'] . '" class="button small">Ver Detalles</a> ';
                        // Solo permitir registrar avance si la tutoría está Programada o quizás Completada (para añadir notas post-sesión)
                        if ($row['estado_tutoria'] == 'Programada' || $row['estado_tutoria'] == 'Completada') {
                            echo '<a href="registro_avance.php?id_tutoria=' . $row['ID_tutoria'] . '" class="button small success">Registrar Avance</a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>No tienes tutorías próximas asignadas.</p>';
                }
                $stmt_proximas_tutorias_tutor->close();
            } else {
                 echo '<p class="error-message">Error al preparar la consulta de próximas tutorías: ' . $conn->error . '</p>';
            }
            ?>

            <h3 style="margin-top: 30px;">Acciones Rápidas</h3>
            <div class="button-group-dashboard">
                <a href="crear_tutoria.php" class="button">Crear Nueva Tutoría</a>
                <a href="ver_tutorias_tutor.php" class="button">Ver Todas Mis Tutorías</a>
                <a href="generar_reporte_tutor.php" class="button">Generar Reportes</a>
                </div>
        </div>
    </div> </div> <?php
$conn->close(); // Cerrar la conexión a la base de datos
require_once '../includes/footer.php'; // Incluye el pie de página
?>