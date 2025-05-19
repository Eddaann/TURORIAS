<?php
// public/crear_tutoria.php
session_start();
require_once '../includes/db.php'; // Conexión a la base de datos
require_once '../includes/functions.php'; // Funciones útiles

// Redirigir si el usuario no está logueado o no es un tutor
if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php'); // Redirige a la página de login si no es tutor
}

$tutor_id = $_SESSION['user_id']; // ID del tutor logueado
$page_title = "Crear Nueva Tutoría"; // Título para el header

$error_message = "";
$success_message = "";

// --- Lógica para manejar el envío del formulario ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_tutoria_submit'])) {
    // Sanitizar y obtener los datos del formulario
    $id_estudiante = filter_input(INPUT_POST, 'id_estudiante', FILTER_VALIDATE_INT);
    $id_materia = filter_input(INPUT_POST, 'id_materia', FILTER_VALIDATE_INT);
    $fecha_tutoria = sanitize_input($_POST['fecha_tutoria']);
    $hora_tutoria = sanitize_input($_POST['hora_tutoria']);
    $modalidad = sanitize_input($_POST['modalidad']);
    // Las siguientes columnas no están en tu DDL de 'tutorías'.
    // Si no las vas a añadir a la tabla, elimina estas líneas y sus referencias en el INSERT.
    $enlace_o_lugar = isset($_POST['enlace_o_lugar']) ? sanitize_input($_POST['enlace_o_lugar']) : null; 
    $notas_adicionales = isset($_POST['notas_adicionales']) ? sanitize_input($_POST['notas_adicionales']) : null;
    
    $estado_tutoria = "Programada"; // Estado inicial por defecto

    // Validaciones básicas
    if (empty($id_estudiante) || empty($id_materia) || empty($fecha_tutoria) || empty($hora_tutoria) || empty($modalidad)) {
        $error_message = "Por favor, completa todos los campos obligatorios (*).";
    } elseif (strtotime($fecha_tutoria) < strtotime(date('Y-m-d'))) {
        $error_message = "La fecha de la tutoría no puede ser en el pasado.";
    } elseif (!in_array($modalidad, ['Presencial', 'Online'])) { // Validar contra el ENUM
        $error_message = "Modalidad no válida.";
    }
     else {
        // Preparar la inserción en la base de datos
        // Ajusta el SQL si 'enlace_o_lugar' y 'notas_adicionales' no existen en tu tabla 'tutorías'.
        $sql_insert = "INSERT INTO tutorías (ID_tutor, ID_estudiante, ID_materia, fecha, hora, modalidad, estado_tutoria";
        $sql_values_q = "?, ?, ?, ?, ?, ?, ?";
        $bind_types = "iiissss";
        $bind_params_array = [$tutor_id, $id_estudiante, $id_materia, $fecha_tutoria, $hora_tutoria, $modalidad, $estado_tutoria];

        // Añadir campos opcionales si existen y se van a usar
        // Nota: Debes asegurarte que estas columnas existan en tu tabla 'tutorías'
        if ($enlace_o_lugar !== null) {
            $sql_insert .= ", enlace_o_lugar";
            $sql_values_q .= ", ?";
            $bind_types .= "s";
            $bind_params_array[] = $enlace_o_lugar;
        }
        // if ($notas_adicionales !== null) { // Asumiendo que tienes una columna notas_adicionales
        //     $sql_insert .= ", notas_adicionales";
        //     $sql_values_q .= ", ?";
        //     $bind_types .= "s";
        //     $bind_params_array[] = $notas_adicionales;
        // }

        $sql_insert .= ") VALUES (" . $sql_values_q . ")";
        
        $stmt_insert_tutoria = $conn->prepare($sql_insert);

        if ($stmt_insert_tutoria) {
            // El primer argumento de bind_param es la cadena de tipos, los siguientes son las variables.
            // Usamos el operador de propagación (...) para pasar el array de parámetros.
            $stmt_insert_tutoria->bind_param($bind_types, ...$bind_params_array);

            if ($stmt_insert_tutoria->execute()) {
                $success_message = "Tutoría creada exitosamente.";
                // Opcional: Limpiar los campos o redirigir
                // redirect('dashboard_tutor.php'); 
            } else {
                $error_message = "Error al crear la tutoría: " . $stmt_insert_tutoria->error;
            }
            $stmt_insert_tutoria->close();
        } else {
            $error_message = "Error al preparar la consulta: " . $conn->error;
        }
    }
}

// --- Obtener datos para los desplegables del formulario ---

// Obtener lista de estudiantes
$estudiantes = [];
$sql_estudiantes = "SELECT ID_estudiante, nombre, apellido FROM estudiantes ORDER BY apellido, nombre";
$result_estudiantes = $conn->query($sql_estudiantes);
if ($result_estudiantes && $result_estudiantes->num_rows > 0) {
    while ($row = $result_estudiantes->fetch_assoc()) {
        $estudiantes[] = $row;
    }
}

// Obtener lista de materias
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


require_once '../includes/header.php'; // Incluir el encabezado
?>

<div class="form-container">
    <h2>Crear Nueva Tutoría</h2>
    <p>Completa el formulario para programar una nueva sesión de tutoría.</p>

    <?php if ($error_message): ?>
        <p class="error-message"><?php echo $error_message; // No es necesario htmlspecialchars aquí si sanitize_input ya lo hace o si los mensajes son controlados ?></p>
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

        <div>
            <label for="enlace_o_lugar">Enlace (si es Online) o Lugar (si es Presencial):</label>
            <input type="text" id="enlace_o_lugar" name="enlace_o_lugar" value="<?php echo isset($_POST['enlace_o_lugar']) ? htmlspecialchars($_POST['enlace_o_lugar']) : ''; ?>" placeholder="Ej: Aula 101 o meet.google.com/xyz">
        </div>
        
        <div>
            <label for="notas_adicionales">Notas Adicionales (Opcional):</label>
            <textarea id="notas_adicionales" name="notas_adicionales" rows="3" placeholder="Temas a tratar, recordatorios, etc."><?php echo isset($_POST['notas_adicionales']) ? htmlspecialchars($_POST['notas_adicionales']) : ''; ?></textarea>
        </div>

        <button type="submit" name="crear_tutoria_submit">Crear Tutoría</button>
    </form>
    <p><a href="dashboard_tutor.php">Volver al Dashboard</a></p>
</div>

<?php
$conn->close(); // Cerrar la conexión a la base de datos
require_once '../includes/footer.php'; // Incluir el pie de página
?>