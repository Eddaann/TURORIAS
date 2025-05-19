<?php
// public/solicitar_tutoria.php
session_start();
require_once '../includes/db.php'; // Conexión a la base de datos
require_once '../includes/functions.php'; // Funciones útiles

// Redirigir si el usuario no está logueado o no es un estudiante
if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'estudiante') {
    redirect('login.php');
}

$estudiante_id = $_SESSION['user_id'];
$page_title = "Solicitar Nueva Tutoría";
$active_page = 'solicitar_tutoria'; // Para el sidebar

$error_message = "";
$success_message = "";

// --- Lógica para manejar el envío del formulario ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['solicitar_tutoria_submit'])) {
    // Sanitizar y obtener los datos del formulario
    $id_materia = filter_input(INPUT_POST, 'id_materia', FILTER_VALIDATE_INT);
    $id_tutor = filter_input(INPUT_POST, 'id_tutor', FILTER_VALIDATE_INT);
    $fecha_tutoria = sanitize_input($_POST['fecha_tutoria']);
    $hora_tutoria = sanitize_input($_POST['hora_tutoria']);
    $modalidad = sanitize_input($_POST['modalidad']);
    // (Opcional) Campo para notas del estudiante
    $notas_estudiante = isset($_POST['notas_estudiante']) ? sanitize_input($_POST['notas_estudiante']) : null;

    // Nuevo estado inicial para una solicitud
    $estado_tutoria = "Solicitada"; // Cambiado de "Programada"

    // Validaciones básicas
    if (empty($id_materia) || empty($id_tutor) || empty($fecha_tutoria) || empty($hora_tutoria) || empty($modalidad)) {
        $error_message = "Por favor, completa todos los campos obligatorios (*).";
    } elseif (strtotime($fecha_tutoria) < strtotime(date('Y-m-d'))) {
        $error_message = "La fecha de la tutoría no puede ser en el pasado.";
    } elseif (!in_array($modalidad, ['Presencial', 'Online'])) { // Validar contra el ENUM de la tabla tutorías
        $error_message = "Modalidad no válida.";
    } else {
        // Preparar la inserción en la base de datos
        // Ajusta el SQL si añadiste 'notas_estudiante'
        if ($notas_estudiante !== null) { // Asumiendo que añadiste la columna notas_estudiante
            $sql_insert = "INSERT INTO tutorías (ID_estudiante, ID_tutor, ID_materia, fecha, hora, modalidad, estado_tutoria, notas_estudiante) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $bind_types = "iiisssss";
            $bind_params_array = [$estudiante_id, $id_tutor, $id_materia, $fecha_tutoria, $hora_tutoria, $modalidad, $estado_tutoria, $notas_estudiante];
        } else {
            $sql_insert = "INSERT INTO tutorías (ID_estudiante, ID_tutor, ID_materia, fecha, hora, modalidad, estado_tutoria) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $bind_types = "iiissss";
            $bind_params_array = [$estudiante_id, $id_tutor, $id_materia, $fecha_tutoria, $hora_tutoria, $modalidad, $estado_tutoria];
        }
        
        $stmt_insert_tutoria = $conn->prepare($sql_insert);

        if ($stmt_insert_tutoria) {
            // El primer argumento de bind_param es la cadena de tipos, los siguientes son las variables.
            // Usamos el operador de propagación (...) para pasar el array de parámetros.
            $stmt_insert_tutoria->bind_param($bind_types, ...$bind_params_array);

            if ($stmt_insert_tutoria->execute()) {
                $success_message = "Tutoría solicitada exitosamente. Está pendiente de aprobación por el tutor.";
                // Opcional: Limpiar los campos del formulario después de un envío exitoso
                $_POST = array(); 
            } else {
                $error_message = "Error al solicitar la tutoría: " . $stmt_insert_tutoria->error;
            }
            $stmt_insert_tutoria->close();
        } else {
            $error_message = "Error al preparar la consulta: " . $conn->error;
        }
    }
}

// --- Obtener datos para los desplegables del formulario ---

// Obtener lista de materias
$materias = [];
$sql_materias = "SELECT ID_materia, nombre_materia FROM materias ORDER BY nombre_materia";
$result_materias = $conn->query($sql_materias);
if ($result_materias && $result_materias->num_rows > 0) {
    while ($row = $result_materias->fetch_assoc()) {
        $materias[] = $row;
    }
}

// Obtener lista de tutores
$tutores = [];
$sql_tutores = "SELECT ID_tutor, nombre, apellido, area_especializacion FROM tutores ORDER BY apellido, nombre";
$result_tutores = $conn->query($sql_tutores);
if ($result_tutores && $result_tutores->num_rows > 0) {
    while ($row = $result_tutores->fetch_assoc()) {
        $tutores[] = $row;
    }
}

// Corregir el nombre del archivo del sidebar si es necesario
// Si el archivo se llama 'siderbar_estudiante.php', usa ese nombre.
// Si lo has corregido a 'sidebar_estudiante.php', usa el nombre corregido.
$sidebar_file = '../includes/siderbar_estudiante.php'; // o sidebar_estudiante.php
if (!file_exists($sidebar_file)) {
    // Intenta con el nombre corregido como fallback o maneja el error
    $sidebar_file = '../includes/sidebar_estudiante.php'; 
}


require_once '../includes/header.php'; // Incluir el encabezado
?>

<div class="page-container" style="display: flex;">
    <?php 
    // Asegúrate de que el archivo de la barra lateral exista y sea el correcto.
    // El nombre original en el análisis previo era 'siderbar_estudiante.php'
    if (file_exists('../includes/siderbar_estudiante.php')) {
        require_once '../includes/siderbar_estudiante.php'; 
    } elseif (file_exists('../includes/sidebar_estudiante.php')) { // Intenta con el nombre corregido
        require_once '../includes/sidebar_estudiante.php';
    } else {
        echo '<aside style="width: 250px; padding: 15px; background-color: #f8f9fa; border-right: 1px solid #dee2e6;"><p>Error: Sidebar no encontrado.</p></aside>';
    }
    ?>

    <div class="main-content-area" style="flex-grow: 1; padding: 20px;">
        <div class="dashboard-container"> <h2>Solicitar Nueva Tutoría</h2>
            <p>Completa el formulario para encontrar y solicitar una sesión de tutoría.</p>

            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <form action="solicitar_tutoria.php" method="POST" class="form-container" style="max-width: 700px;">
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
                    <label for="id_tutor">Tutor: <span class="required">*</span></label>
                    <select id="id_tutor" name="id_tutor" required>
                        <option value="">Selecciona un tutor</option>
                        <?php foreach ($tutores as $tutor): ?>
                            <option value="<?php echo htmlspecialchars($tutor['ID_tutor']); ?>" <?php echo (isset($_POST['id_tutor']) && $_POST['id_tutor'] == $tutor['ID_tutor']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tutor['nombre'] . ' ' . $tutor['apellido'] . (!empty($tutor['area_especializacion']) ? ' - ' . $tutor['area_especializacion'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($tutores)): ?>
                            <option value="" disabled>No hay tutores disponibles</option>
                        <?php endif; ?>
                    </select>
                    <small><a href="buscar_tutores.php" target="_blank">Ver perfiles de tutores</a></small>
                </div>
                
                <div>
                    <label for="fecha_tutoria">Fecha Preferida: <span class="required">*</span></label>
                    <input type="date" id="fecha_tutoria" name="fecha_tutoria" value="<?php echo isset($_POST['fecha_tutoria']) ? htmlspecialchars($_POST['fecha_tutoria']) : ''; ?>" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div>
                    <label for="hora_tutoria">Hora Preferida: <span class="required">*</span></label>
                    <input type="time" id="hora_tutoria" name="hora_tutoria" value="<?php echo isset($_POST['hora_tutoria']) ? htmlspecialchars($_POST['hora_tutoria']) : ''; ?>" required>
                </div>

                <div>
                    <label for="modalidad">Modalidad Preferida: <span class="required">*</span></label>
                    <select id="modalidad" name="modalidad" required>
                        <option value="">Selecciona la modalidad</option>
                        <option value="Presencial" <?php echo (isset($_POST['modalidad']) && $_POST['modalidad'] == 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                        <option value="Online" <?php echo (isset($_POST['modalidad']) && $_POST['modalidad'] == 'Online') ? 'selected' : ''; ?>>Online</option>
                    </select>
                </div>

                <?php // (Opcional) Campo para notas del estudiante ?>
                <?php if (true): /* Cambia a true si añadiste la columna notas_estudiante y quieres usar el campo */ ?>
                <div>
                    <label for="notas_estudiante">Notas para el Tutor (opcional):</label>
                    <textarea id="notas_estudiante" name="notas_estudiante" rows="3" placeholder="Ej: Temas específicos a tratar, dudas principales..."><?php echo isset($_POST['notas_estudiante']) ? htmlspecialchars($_POST['notas_estudiante']) : ''; ?></textarea>
                </div>
                <?php endif; ?>


                <button type="submit" name="solicitar_tutoria_submit">Enviar Solicitud de Tutoría</button>
            </form>
            
            <p style="margin-top: 30px;">
                <a href="dashboard_estudiante.php" class="button">Volver al Dashboard</a>
            </p>
        </div> 
    </div> 
</div> 
<?php
$conn->close(); // Cerrar la conexión a la base de datos
require_once '../includes/footer.php'; // Incluir el pie de página
?>