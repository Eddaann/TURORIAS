<?php
// public/registro_avance.php
session_start();
require_once '../includes/db.php'; // Conexión a la base de datos
require_once '../includes/functions.php'; // Funciones útiles

// Redirigir si el usuario no está logueado o no es un tutor
if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php');
}

$tutor_id = $_SESSION['user_id'];
$page_title = "Registrar Avance de Tutoría";

$error_message = "";
$success_message = "";
$tutoria_info = null;
$id_tutoria_param = null;

// 1. Obtener el ID de la tutoría desde el parámetro GET
if (isset($_GET['id_tutoria'])) {
    $id_tutoria_param = filter_input(INPUT_GET, 'id_tutoria', FILTER_VALIDATE_INT);
    if (!$id_tutoria_param) {
        $error_message = "ID de tutoría no válido.";
        // Considerar redirigir o mostrar un error más prominente si el ID es crucial
    }
} else {
    $error_message = "No se especificó el ID de la tutoría.";
    // redirect('dashboard_tutor.php'); // Podrías redirigir si el ID es obligatorio para estar en la página
}

// 2. Si tenemos un ID de tutoría válido, obtener información de la tutoría
if ($id_tutoria_param && empty($error_message)) {
    $stmt_tutoria = $conn->prepare("
        SELECT 
            t.ID_tutoria, t.fecha, t.hora,
            e.nombre AS nombre_estudiante, e.apellido AS apellido_estudiante,
            m.nombre_materia
        FROM tutorías t
        JOIN estudiantes e ON t.ID_estudiante = e.ID_estudiante
        JOIN materias m ON t.ID_materia = m.ID_materia
        WHERE t.ID_tutoria = ? AND t.ID_tutor = ? 
    ");
    // Se añade t.ID_tutor = ? para asegurar que el tutor solo pueda registrar avances de sus propias tutorías.
    if ($stmt_tutoria) {
        $stmt_tutoria->bind_param("ii", $id_tutoria_param, $tutor_id);
        $stmt_tutoria->execute();
        $result_tutoria = $stmt_tutoria->get_result();
        if ($result_tutoria->num_rows === 1) {
            $tutoria_info = $result_tutoria->fetch_assoc();
        } else {
            $error_message = "Tutoría no encontrada o no tienes permiso para registrar avances en ella.";
            $tutoria_info = null; // Asegurarse que no se muestre el formulario si no hay tutoría válida
        }
        $stmt_tutoria->close();
    } else {
        $error_message = "Error al preparar la consulta de la tutoría: " . $conn->error;
    }
}


// 3. Procesar el formulario de registro de avance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_avance_submit']) && $tutoria_info) {
    $descripcion_avance = sanitize_input($_POST['descripcion_avance']);
    $fecha_registro = date('Y-m-d'); // Fecha actual

    if (empty($descripcion_avance)) {
        $error_message = "La descripción del avance no puede estar vacía.";
    } else {
        // Insertar en la tabla 'avances'
        $stmt_insert = $conn->prepare("INSERT INTO avances (ID_tutoria, ID_tutor, descripcion_avance, fecha_registro) VALUES (?, ?, ?, ?)");
        if ($stmt_insert) {
            $stmt_insert->bind_param("iiss", $tutoria_info['ID_tutoria'], $tutor_id, $descripcion_avance, $fecha_registro);
            if ($stmt_insert->execute()) {
                $success_message = "Avance registrado exitosamente.";
                // Opcional: Redirigir o limpiar formulario
                // redirect("detalle_tutoria.php?id=" . $tutoria_info['ID_tutoria']);
            } else {
                $error_message = "Error al registrar el avance: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $error_message = "Error al preparar la inserción del avance: " . $conn->error;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <h2>Registrar Avance de Tutoría</h2>

    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <?php if ($tutoria_info): ?>
        <div class="tutoria-info-box form-container" style="max-width: 700px; margin-bottom: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 5px;">
            <h4>Detalles de la Tutoría Seleccionada:</h4>
            <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($tutoria_info['nombre_estudiante'] . ' ' . $tutoria_info['apellido_estudiante']); ?></p>
            <p><strong>Materia:</strong> <?php echo htmlspecialchars($tutoria_info['nombre_materia']); ?></p>
            <p><strong>Fecha:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($tutoria_info['fecha']))); ?></p>
            <p><strong>Hora:</strong> <?php echo htmlspecialchars(date('H:i A', strtotime($tutoria_info['hora']))); ?></p>
        </div>

        <form action="registro_avance.php?id_tutoria=<?php echo htmlspecialchars($tutoria_info['ID_tutoria']); ?>" method="POST" class="form-container" style="max-width: 700px;">
            <input type="hidden" name="id_tutoria" value="<?php echo htmlspecialchars($tutoria_info['ID_tutoria']); ?>">
            
            <div>
                <label for="descripcion_avance">Descripción del Avance: <span class="required">*</span></label>
                <textarea id="descripcion_avance" name="descripcion_avance" rows="6" required placeholder="Describe los temas tratados, el progreso del estudiante, tareas asignadas, etc."><?php echo isset($_POST['descripcion_avance']) ? htmlspecialchars($_POST['descripcion_avance']) : ''; ?></textarea>
            </div>

            <div>
                <label for="fecha_registro_display">Fecha de Registro:</label>
                <input type="text" id="fecha_registro_display" name="fecha_registro_display" value="<?php echo date('d/m/Y'); ?>" readonly disabled>
            </div>

            <button type="submit" name="registrar_avance_submit">Guardar Avance</button>
        </form>
    <?php elseif (!$id_tutoria_param && !isset($_GET['id_tutoria'])): ?>
        <p>Para registrar un avance, primero debes seleccionar una tutoría desde tu dashboard o la lista de tutorías.</p>
        <p><a href="dashboard_tutor.php" class="button">Ir al Dashboard</a></p>
    <?php endif; ?>

    <p style="margin-top: 30px;">
        <?php if ($id_tutoria_param): ?>
            <a href="detalle_tutoria.php?id=<?php echo htmlspecialchars($id_tutoria_param); ?>" class="button">Volver a Detalles de Tutoría</a>
        <?php else: ?>
            <a href="dashboard_tutor.php" class="button">Volver al Dashboard</a>
        <?php endif; ?>
    </p>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>