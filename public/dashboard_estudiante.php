<?php
// public/dashboard_estudiante.php
session_start();
require_once '../includes/db.php'; // Conexión a la base de datos
require_once '../includes/functions.php'; // Funciones útiles

// Redirigir si el usuario no está logueado o no es un estudiante
if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'estudiante') {
    redirect('login.php'); // Redirige a la página de login si no es estudiante
}

$estudiante_id = $_SESSION['user_id']; // ID del estudiante logueado
$estudiante_nombre = $_SESSION['user_name']; // Nombre del estudiante logueado
$page_title = "Dashboard del Estudiante"; // Título para el header

require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <h2>¡Bienvenido, <?php echo htmlspecialchars($estudiante_nombre); ?>!</h2>
    <p>Este es tu panel de control donde puedes gestionar tus tutorías.</p>

    <h3>Tus Próximas Tutorías</h3>
    <?php
    $stmt_proximas = $conn->prepare("
        SELECT 
            t.ID_tutoria, t.fecha, t.hora, t.modalidad, t.estado_tutoria, 
            t.ID_materia, -- Asegúrate que esta columna exista si la usas directamente, o es solo para el JOIN
            -- Si 'enlace_o_lugar' no está en tu tabla 'tutorías', esta línea causará un error.
            -- Comenta o elimina la siguiente línea si es el caso:
            -- t.enlace_o_lugar, 
            tut.nombre AS nombre_tutor, tut.apellido AS apellido_tutor,
            m.nombre_materia
        FROM tutorías t
        JOIN tutores tut ON t.ID_tutor = tut.ID_tutor
        JOIN materias m ON t.ID_materia = m.ID_materia
        WHERE t.ID_estudiante = ? AND (t.fecha > CURDATE() OR (t.fecha = CURDATE() AND t.hora >= CURTIME()))
        ORDER BY t.fecha ASC, t.hora ASC
        LIMIT 5
    ");

    // Para manejar el caso de que 'enlace_o_lugar' no exista, podrías hacer la consulta sin ella
    // y luego ajustar la tabla HTML. Por simplicidad, la consulta actual asume que sí existe o que
    // la tabla 'tutorías' se actualizará para incluirla.
    // Si 'enlace_o_lugar' NO está en tu DDL y no la vas a añadir, la consulta debería ser:
    /*
    $stmt_proximas = $conn->prepare("
        SELECT
            t.ID_tutoria, t.fecha, t.hora, t.modalidad, t.estado_tutoria,
            tut.nombre AS nombre_tutor, tut.apellido AS apellido_tutor,
            m.nombre_materia
        FROM tutorías t
        JOIN tutores tut ON t.ID_tutor = tut.ID_tutor
        JOIN materias m ON t.ID_materia = m.ID_materia
        WHERE t.ID_estudiante = ? AND (t.fecha > CURDATE() OR (t.fecha = CURDATE() AND t.hora >= CURTIME()))
        ORDER BY t.fecha ASC, t.hora ASC
        LIMIT 5
    ");
    */


    if ($stmt_proximas) {
        $stmt_proximas->bind_param("i", $estudiante_id);
        $stmt_proximas->execute();
        $result_proximas = $stmt_proximas->get_result();

        if ($result_proximas->num_rows > 0) {
            echo '<table class="data-table">';
            // Ajusta el encabezado de la tabla si 'enlace_o_lugar' no se muestra
            echo '<thead><tr><th>Materia</th><th>Tutor</th><th>Fecha</th><th>Hora</th><th>Modalidad</th><th>Lugar/Enlace</th><th>Estado</th><th>Acciones</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result_proximas->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['nombre_materia']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nombre_tutor'] . ' ' . $row['apellido_tutor']) . '</td>';
                echo '<td>' . htmlspecialchars(date('d/m/Y', strtotime($row['fecha']))) . '</td>';
                echo '<td>' . htmlspecialchars(date('H:i A', strtotime($row['hora']))) . '</td>';
                echo '<td>' . htmlspecialchars($row['modalidad']) . '</td>';
                // Si 'enlace_o_lugar' no está en tu SELECT, esta línea dará error.
                // Deberías mostrar 'No especificado' o quitar la columna de la tabla.
                // echo '<td>' . htmlspecialchars($row['enlace_o_lugar'] ?? 'No especificado') . '</td>';
                // Alternativa si la columna no existe en el SELECT:
                 echo '<td>No especificado</td>'; // O ajusta según la lógica deseada si la columna no existe
                echo '<td>' . htmlspecialchars($row['estado_tutoria']) . '</td>';
                echo '<td>';
                echo '<a href="detalle_tutoria.php?id=' . $row['ID_tutoria'] . '" class="button small">Ver Detalles</a>';
                if ($row['estado_tutoria'] == 'Programada') {
                    // echo ' <a href="cancelar_tutoria.php?id=' . $row['ID_tutoria'] . '" class="button small alert" onclick="return confirm(\'¿Estás seguro de que deseas cancelar esta tutoría?\');">Cancelar</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No tienes tutorías próximas programadas.</p>';
        }
        $stmt_proximas->close();
    } else {
        echo '<p class="error-message">Error al preparar la consulta de próximas tutorías: ' . $conn->error . '</p>';
    }
    ?>

    <h3>Tutorías Pasadas (Recientes)</h3>
     <?php
    // Consulta para obtener las tutorías pasadas recientes del estudiante
    // NOTA: Se usa 'tutorías' con tilde.
    $stmt_pasadas = $conn->prepare("
        SELECT 
            t.ID_tutoria, t.fecha, t.hora, t.modalidad, t.estado_tutoria,
            tut.nombre AS nombre_tutor, tut.apellido AS apellido_tutor,
            m.nombre_materia
        FROM tutorías t
        JOIN tutores tut ON t.ID_tutor = tut.ID_tutor
        JOIN materias m ON t.ID_materia = m.ID_materia
        WHERE t.ID_estudiante = ? AND (t.fecha < CURDATE() OR (t.fecha = CURDATE() AND t.hora < CURTIME()))
        ORDER BY t.fecha DESC, t.hora DESC
        LIMIT 5
    ");
    if ($stmt_pasadas) {
        $stmt_pasadas->bind_param("i", $estudiante_id);
        $stmt_pasadas->execute();
        $result_pasadas = $stmt_pasadas->get_result();

        if ($result_pasadas->num_rows > 0) {
            echo '<table class="data-table">';
            echo '<thead><tr><th>Materia</th><th>Tutor</th><th>Fecha</th><th>Hora</th><th>Modalidad</th><th>Estado</th><th>Acciones</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result_pasadas->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['nombre_materia']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nombre_tutor'] . ' ' . $row['apellido_tutor']) . '</td>';
                echo '<td>' . htmlspecialchars(date('d/m/Y', strtotime($row['fecha']))) . '</td>';
                echo '<td>' . htmlspecialchars(date('H:i A', strtotime($row['hora']))) . '</td>';
                echo '<td>' . htmlspecialchars($row['modalidad']) . '</td>';
                echo '<td>' . htmlspecialchars($row['estado_tutoria']) . '</td>';
                echo '<td>';
                echo '<a href="detalle_tutoria.php?id=' . $row['ID_tutoria'] . '" class="button small">Ver Detalles</a>';
                // if ($row['estado_tutoria'] == 'Completada') {
                //     echo ' <a href="resena_tutoria.php?id=' . $row['ID_tutoria'] . '" class="button small">Dejar Reseña</a>';
                // }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No tienes tutorías pasadas recientes.</p>';
        }
        $stmt_pasadas->close();
    } else {
        echo '<p class="error-message">Error al preparar la consulta de tutorías pasadas: ' . $conn->error . '</p>';
    }
    ?>

    <h3>Acciones Rápidas</h3>
    <div class="button-group-dashboard">
        <a href="solicitar_tutoria.php" class="button">Solicitar Nueva Tutoría</a>
        <a href="historial_tutorias_estudiante.php" class="button">Ver Historial Completo</a>
        <a href="buscar_tutores.php" class="button">Buscar Tutores</a>
    </div>

</div>

<?php
$conn->close(); // Cerrar la conexión a la base de datos
require_once '../includes/footer.php'; // Incluir el pie de página
?>