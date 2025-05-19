<?php
session_start();
require_once '../includes/db.php'; 
require_once '../includes/functions.php';

if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'estudiante') {
    redirect('login.php');
}

$estudiante_id = $_SESSION['user_id'];
$estudiante_nombre = $_SESSION['user_name'];
$page_title = "Historial Completo de Tutorías";

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

$stmt_count = $conn->prepare("SELECT COUNT(*) FROM tutorías WHERE ID_estudiante = ?");
if ($stmt_count) {
    $stmt_count->bind_param("i", $estudiante_id);
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
            tut.nombre AS nombre_tutor, tut.apellido AS apellido_tutor,
            m.nombre_materia
        FROM tutorías t
        JOIN tutores tut ON t.ID_tutor = tut.ID_tutor
        JOIN materias m ON t.ID_materia = m.ID_materia
        WHERE t.ID_estudiante = ?
        ORDER BY t.fecha DESC, t.hora DESC
        LIMIT ? OFFSET ?
    ");

    if ($stmt_tutorias) {
        $stmt_tutorias->bind_param("iii", $estudiante_id, $results_per_page, $offset);
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

<div class="dashboard-container">
    <h2>Historial Completo de Tutorías de <?php echo htmlspecialchars($estudiante_nombre); ?></h2>

    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <?php if (!empty($tutorias)): ?>
        <p>Mostrando <?php echo count($tutorias); ?> de <?php echo $total_tutorias; ?> tutorías.</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Materia</th>
                    <th>Tutor</th>
                    <th>Modalidad</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tutorias as $tutoria): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($tutoria['fecha']))); ?></td>
                    <td><?php echo htmlspecialchars(date('H:i A', strtotime($tutoria['hora']))); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['nombre_materia']); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['nombre_tutor'] . ' ' . $tutoria['apellido_tutor']); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['modalidad']); ?></td>
                    <td><?php echo htmlspecialchars($tutoria['estado_tutoria']); ?></td>
                    <td>
                        <a href="detalle_tutoria.php?id=<?php echo $tutoria['ID_tutoria']; ?>" class="button small">Ver Detalles</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top: 20px; text-align: center;">
            <?php if ($current_page > 1): ?>
                <a href="historial_tutorias_estudiante.php?page=<?php echo $current_page - 1; ?>" class="button">&laquo; Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $current_page): ?>
                    <strong class="button" style="background-color: #0056b3; color: white; cursor: default;"><?php echo $i; ?></strong>
                <?php else: ?>
                    <a href="historial_tutorias_estudiante.php?page=<?php echo $i; ?>" class="button"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="historial_tutorias_estudiante.php?page=<?php echo $current_page + 1; ?>" class="button">Siguiente &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php elseif (empty($error_message)): ?>
        <p>No tienes ninguna tutoría registrada en tu historial.</p>
    <?php endif; ?>

    <p style="margin-top: 30px;">
        <a href="dashboard_estudiante.php" class="button">Volver al Dashboard</a>
    </p>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>