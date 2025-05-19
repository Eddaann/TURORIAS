<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php');
}

$tutor_id = $_SESSION['user_id'];
$tutor_nombre = $_SESSION['user_name'] ?? 'Tutor';

$page_title = "Dashboard del Tutor";
$active_page = 'dashboard_tutor';

require_once '../includes/header.php';
?>

<div class="page-container">
    <?php require_once '../includes/sidebar_tutor.php';?>

    <div class="main-content-area">
        <div class="dashboard-container"> <h2>Bienvenido, Tutor <?php echo htmlspecialchars($tutor_nombre); ?>!</h2>
            <p>Este es tu panel de control donde puedes gestionar tus tutorías.</p>

            <h3>Tutorías Próximas Asignadas</h3>
            <?php

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

            if ($stmt_proximas_tutorias_tutor) {
                $stmt_proximas_tutorias_tutor->bind_param("i", $tutor_id);
                $stmt_proximas_tutorias_tutor->execute();
                $result_proximas_tutorias_tutor = $stmt_proximas_tutorias_tutor->get_result();

                if ($result_proximas_tutorias_tutor->num_rows > 0) {
                    echo '<table class="data-table">';
                    echo '<thead><tr><th>Estudiante</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Modalidad</th><th>Estado</th><th>Acciones</th></tr></thead>';
                    echo '<tbody>';
                    while ($row = $result_proximas_tutorias_tutor->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['nombre_estudiante'] . ' ' . $row['apellido_estudiante']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['nombre_materia']) . '</td>';
                        echo '<td>' . htmlspecialchars(date('d/m/Y', strtotime($row['fecha']))) . '</td>';
                        echo '<td>' . htmlspecialchars(date('H:i A', strtotime($row['hora']))) . '</td>';
                        echo '<td>' . htmlspecialchars($row['modalidad']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['estado_tutoria']) . '</td>';
                        echo '<td>';
                        echo '<a href="detalle_tutoria.php?id=' . $row['ID_tutoria'] . '" class="button small">Ver Detalles</a> ';
                        if ($row['estado_tutoria'] == 'Programada' || $row['estado_tutoria'] == 'Completada' || $row['estado_tutoria'] == 'Reprogramada') {
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
                 echo '<p class="error-message">Error al preparar la consulta de próximas tutorías: ' . htmlspecialchars($conn->error) . '</p>';
            }
            ?>

            <h3 style="margin-top: 30px;">Acciones Rápidas</h3>
            <div class="button-group-dashboard">
                <a href="crear_tutoria.php" class="button">Crear Nueva Tutoría</a>
                <a href="ver_tutorias_tutor.php" class="button">Ver Todas Mis Tutorías</a>
                <a href="generar_reporte_tutor.php" class="button">Generar Reportes</a>
                <a href="gestionar_materias.php" class="button">Gestionar Materias</a>
                <a href="perfil.php" class="button">Mi Perfil</a>
            </div>
        </div>
    </div> 
</div> 
<?php
$conn->close();
require_once '../includes/footer.php';
?>