<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php');
}

$tutor_id = $_SESSION['user_id'];
$page_title = "Crear Nueva Tutoría";

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_tutoria_submit'])) {
    $id_estudiante = filter_input(INPUT_POST, 'id_estudiante', FILTER_VALIDATE_INT);
    $id_materia = filter_input(INPUT_POST, 'id_materia', FILTER_VALIDATE_INT);
    $fecha_tutoria = sanitize_input($_POST['fecha_tutoria']);
    $hora_tutoria = sanitize_input($_POST['hora_tutoria']);
    $modalidad = sanitize_input($_POST['modalidad']);
    
    $estado_tutoria = "Programada";

    if (empty($id_estudiante) || empty($id_materia) || empty($fecha_tutoria) || empty($hora_tutoria) || empty($modalidad)) {
        $error_message = "Por favor, completa todos los campos obligatorios (*).";
    } elseif (strtotime($fecha_tutoria) < strtotime(date('Y-m-d'))) {
        $error_message = "La fecha de la tutoría no puede ser en el pasado.";
    } elseif (!in_array($modalidad, ['Presencial', 'Online'])) { // Validar contra el ENUM
        $error_message = "Modalidad no válida.";
    } else {

        $sql_insert = "INSERT INTO tutorías (ID_tutor, ID_estudiante, ID_materia, fecha, hora, modalidad, estado_tutoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_insert_tutoria = $conn->prepare($sql_insert);

        if ($stmt_insert_tutoria) {

            $stmt_insert_tutoria->bind_param("iiissss", $tutor_id, $id_estudiante, $id_materia, $fecha_tutoria, $hora_tutoria, $modalidad, $estado_tutoria);

            if ($stmt_insert_tutoria->execute()) {
                $success_message = "Tutoría creada exitosamente.";
            } else {
                $error_message = "Error al crear la tutoría: " . $stmt_insert_tutoria->error;
            }
            $stmt_insert_tutoria->close();
        } else {
            $error_message = "Error al preparar la consulta: " . $conn->error;
        }
    }
}

$estudiantes = [];
$sql_estudiantes = "SELECT ID_estudiante, nombre, apellido FROM estudiantes ORDER BY apellido, nombre";
$result_estudiantes = $conn->query($sql_estudiantes);
if ($result_estudiantes && $result_estudiantes->num_rows > 0) {
    while ($row = $result_estudiantes->fetch_assoc()) {
        $estudiantes[] = $row;
    }
}

$materias = [];
$sql_materias = "SELECT ID_materia, nombre_materia FROM materias ORDER BY nombre_materia";
$result_materias = $conn->query($sql_materias);
if ($result_materias && $result_materias->num_rows > 0) {
    while ($row = $result_materias->fetch_assoc()) {
        $materias[] = $row;
    }
} else {
     $error_message .= (empty($error_message) ? "" : "<br>") . "Advertencia: No hay materias disponibles en el sistema. Por favor, añade materias para poder asignarlas.";
}


require_once '../includes/header.php';
?>

<div class="form-container">
    <h2>Crear Nueva Tutoría</h2>
    <p>Completa el formulario para programar una nueva sesión de tutoría.</p>

    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <form action="crear_tutoria.php" method="POST">
        <div>
            <label for="id_estudiante">Estudiante: <span class="required">*</span></label>
            <select id="id_estudiante" name="id_estudiante" required>
                <option value="">Selecciona un estudiante</option>
                <?php foreach ($estudiantes as $estudiante): ?>
                    <option value="<?php echo htmlspecialchars($estudiante['ID_estudiante']); ?>" <?php echo (isset($_POST['id_estudiante']) && $_POST['id_estudiante'] == $estudiante['ID_estudiante']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?>
                    </option>
                <?php endforeach; ?>
                <?php if (empty($estudiantes)): ?>
                    <option value="" disabled>No hay estudiantes registrados</option>
                <?php endif; ?>
            </select>
        </div>

        <div>
            <label for="id_materia">Materia: <span class="required">*</span></label>
            <select id="id_materia" name="id_materia" required>
                <option value="">Selecciona una materia</option>
                <?php foreach ($materias as $materia): ?>
                    <option value="<?php echo htmlspecialchars($materia['ID_materia']); ?>" <?php echo (isset($_POST['id_materia']) && $_POST['id_materia'] == $materia['ID_materia']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($materia['nombre_materia']); ?>
                    </option>
                <?php endforeach; ?>
                 <?php if (empty($materias)): ?>
                    <option value="" disabled>No hay materias disponibles</option>
                <?php endif; ?>
            </select>
        </div>

        <div>
            <label for="fecha_tutoria">Fecha: <span class="required">*</span></label>
            <input type="date" id="fecha_tutoria" name="fecha_tutoria" value="<?php echo isset($_POST['fecha_tutoria']) ? htmlspecialchars($_POST['fecha_tutoria']) : ''; ?>" required min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div>
            <label for="hora_tutoria">Hora: <span class="required">*</span></label>
            <input type="time" id="hora_tutoria" name="hora_tutoria" value="<?php echo isset($_POST['hora_tutoria']) ? htmlspecialchars($_POST['hora_tutoria']) : ''; ?>" required>
        </div>

        <div>
            <label for="modalidad">Modalidad: <span class="required">*</span></label>
            <select id="modalidad" name="modalidad" required>
                <option value="">Selecciona la modalidad</option>
                <option value="Presencial" <?php echo (isset($_POST['modalidad']) && $_POST['modalidad'] == 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                <option value="Online" <?php echo (isset($_POST['modalidad']) && $_POST['modalidad'] == 'Online') ? 'selected' : ''; ?>>Online</option>
            </select>
        </div>

        <?php /* */ ?>

        <button type="submit" name="crear_tutoria_submit">Crear Tutoría</button>
    </form>
    <p><a href="dashboard_tutor.php">Volver al Dashboard</a></p>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>