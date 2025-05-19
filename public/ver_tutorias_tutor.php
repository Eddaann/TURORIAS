<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php');
}

$tutor_id = $_SESSION['user_id'];
$tutor_nombre = $_SESSION['user_name'];
$page_title = "Mis Tutorías Asignadas";
$active_page = 'ver_tutorias_asignadas';

$results_per_page = 10;
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $current_page = (int)$_GET['page'];
} else {
    $current_page = 1;
}
$offset = ($current_page - 1) * $results_per_page;

$tutorias = [];
$total_tutorias = 0;
$error_message = '';

$stmt_count = $conn->prepare("SELECT COUNT(*) FROM tutorías WHERE ID_tutor = ?");
if ($stmt_count) {
    $stmt_count->bind_param("i", $tutor_id);
    $stmt_count->execute();
    $stmt_count->bind_result($total_tutorias);
    $stmt_count->fetch();
    $stmt_count->close();
} else {
    $error_message = "Error al contar las tutorías: " . $conn->error;
}

$total_pages = ceil($total_tutorias / $results_per_page);

if ($total_tutorias > 0 && empty($error_message)) {
    $stmt_tutorias = $conn->prepare("
        SELECT 
            t.ID_tutoria, t.fecha, t.hora, t.modalidad, t.estado_tutoria,
            e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante,
            m.nombre_materia
        FROM tutorías t
        JOIN estudiantes e ON t.ID_estudiante = e.ID_estudiante
        JOIN materias m ON t.ID_materia = m.ID_materia
        WHERE t.ID_tutor = ?
        ORDER BY t.fecha DESC, t.hora DESC
        LIMIT ? OFFSET ?
    ");

    if ($stmt_tutorias) {
        $stmt_tutorias->bind_param("iii", $tutor_id, $results_per_page, $offset);
        $stmt_tutorias->execute();
        $result_tutorias = $stmt_tutorias->get_result();
        while ($row = $result_tutorias->fetch_assoc()) {
            $tutorias[] = $row;
        }
        $stmt_tutorias->close();
    } else {
        $error_message = "Error al obtener las tutorías: " . $conn->error;
    }
}

require_once '../includes/header.php';
?>

<div class="page-container" style="display: flex;">
    <?php require_once '../includes/sidebar_tutor.php'; ?>
    <div class="main-content-area" style="flex-grow: 1; padding: 20px;">
        <div class="dashboard-container">
            <h2>Historial Completo de Mis Tutorías</h2>

            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <?php if (!empty($tutorias)): ?>
                <p>Mostrando <?php echo count($tutorias); ?> de <?php echo $total_tutorias; ?> tutorías.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Materia</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Modalidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tutorias as $tutoria): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tutoria['nombre_estudiante'] . ' ' . $tutoria['apellido_estudiante']); ?></td>
                            <td><?php echo htmlspecialchars($tutoria['nombre_materia']); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($tutoria['fecha']))); ?></td>
                            <td><?php echo htmlspecialchars(date('H:i A', strtotime($tutoria['hora']))); ?></td>
                            <td><?php echo htmlspecialchars($tutoria['modalidad']); ?></td>
                            <td><?php echo htmlspecialchars($tutoria['estado_tutoria']); ?></td>
                            <td>
                                <a href="detalle_tutoria.php?id_tutoria=<?php echo $tutoria['ID_tutoria']; ?>" class="button small">Ver Detalles</a>
                                <?php if ($tutoria['estado_tutoria'] === 'Solicitada'): ?>
                                    <a href="gestionar_solicitud.php?id_tutoria=<?php echo $tutoria['ID_tutoria']; ?>" class="button small warning">Gestionar</a>
                                <?php elseif (in_array($tutoria['estado_tutoria'], ['Programada', 'Completada'])): ?>
                                    <a href="registro_avance.php?id_tutoria=<?php echo $tutoria['ID_tutoria']; ?>" class="button small success">Registrar Avance</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination" style="margin-top: 20px; text-align: center;">
                    <?php if ($current_page > 1): ?>
                        <a href="ver_tutorias_tutor.php?page=<?php echo $current_page - 1; ?>" class="button">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <strong class="button" style="background-color: #0056b3; color: white; cursor: default;"><?php echo $i; ?></strong>
                        <?php else: ?>
                            <a href="ver_tutorias_tutor.php?page=<?php echo $i; ?>" class="button"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="ver_tutorias_tutor.php?page=<?php echo $current_page + 1; ?>" class="button">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php elseif (empty($error_message)): ?>
                <p>No tienes ninguna tutoría registrada en tu historial.</p>
            <?php endif; ?>

            <p style="margin-top: 30px;">
                <a href="dashboard_tutor.php" class="button">Volver al Dashboard</a>
            </p>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>