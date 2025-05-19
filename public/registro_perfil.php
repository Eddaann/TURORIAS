<?php
session_start();
require_once '../includes/db.php'; // Asegúrate que la conexión $conn esté disponible aquí
require_once '../includes/functions.php'; // Para sanitize_input, redirect, is_logged_in

// Inicializar mensajes
$error_message = "";
$success_message = ""; // Generalmente no se muestra si hay redirección inmediata

// 1. Verificar si hay información temporal del usuario en la sesión
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_user_email'])) {
    // Si no hay datos temporales, redirigir al inicio del registro o login
    redirect('register.php');
    exit;
}

// 2. Si el usuario ya completó el perfil y está logueado, redirigirlo a su dashboard
if (is_logged_in()) {
    if ($_SESSION['user_role'] == 'estudiante') {
        redirect('dashboard_estudiante.php');
    } elseif ($_SESSION['user_role'] == 'tutor') {
        redirect('dashboard_tutor.php');
    }
    exit;
}

// 3. Recuperar datos del usuario temporal desde la tabla 'estudiantes'
$temp_user_id = $_SESSION['temp_user_id'];
$nombre_usuario = "Usuario"; // Valor por defecto
$apellido_usuario = "";    // Valor por defecto
$correo_usuario = "";
$hashed_password_usuario = "";

if ($conn) { // Asegurarse que $conn es válida
    $stmt_fetch_temp_user = $conn->prepare("SELECT nombre, apellido, correo, contraseña FROM estudiantes WHERE ID_estudiante = ?");
    if ($stmt_fetch_temp_user) {
        $stmt_fetch_temp_user->bind_param("i", $temp_user_id);
        $stmt_fetch_temp_user->execute();
        $result_temp_user = $stmt_fetch_temp_user->get_result();

        if ($temp_user_data = $result_temp_user->fetch_assoc()) {
            $nombre_usuario = $temp_user_data['nombre'];
            $apellido_usuario = $temp_user_data['apellido'];
            $correo_usuario = $temp_user_data['correo'];
            $hashed_password_usuario = $temp_user_data['contraseña'];
        } else {
            $error_message = "Error al recuperar tus datos. Por favor, intenta registrarte de nuevo.";
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_email']);
            // Considera redirigir a register.php aquí si este error es crítico
        }
        $stmt_fetch_temp_user->close();
    } else {
        $error_message = "Error de preparación de consulta al buscar usuario temporal: " . $conn->error;
    }
} else {
    $error_message = "Error de conexión a la base de datos.";
}


// 4. Procesar el formulario cuando se envía
if (isset($_POST['submit_profile']) && empty($error_message)) { // Solo procesar si no hubo errores previos
    $rol = sanitize_input($_POST['rol']);
    $area_especializacion = isset($_POST['area_especializacion']) ? sanitize_input($_POST['area_especializacion']) : null;

    // Validaciones
    if (empty($rol)) {
        $error_message = "Debes seleccionar un rol (Estudiante o Tutor).";
    } elseif ($rol == 'tutor' && empty($area_especializacion)) {
        $error_message = "Si seleccionas 'Tutor', el área de especialización es obligatoria.";
    } else {
        // Proceder según el rol
        if ($rol == 'estudiante') {
            // El usuario ya está en la tabla 'estudiantes', solo actualizamos la sesión
            $_SESSION['user_id'] = $temp_user_id;
            $_SESSION['user_role'] = 'estudiante';
            $_SESSION['user_name'] = $nombre_usuario . ' ' . $apellido_usuario;
            $_SESSION['user_email'] = $correo_usuario;

            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_email']);

            // $success_message = "Perfil de estudiante completado. Redirigiendo..."; // Opcional si rediriges
            redirect('dashboard_estudiante.php');
            exit;

        } elseif ($rol == 'tutor') {
            // Iniciar transacción para asegurar atomicidad (opcional pero recomendado)
            $conn->begin_transaction();

            // 1. Insertar en la tabla 'tutores'
            $stmt_insert_tutor = $conn->prepare("INSERT INTO tutores (nombre, apellido, correo, contraseña, area_especializacion) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_insert_tutor) {
                $stmt_insert_tutor->bind_param("sssss", $nombre_usuario, $apellido_usuario, $correo_usuario, $hashed_password_usuario, $area_especializacion);

                if ($stmt_insert_tutor->execute()) {
                    $new_tutor_id = $stmt_insert_tutor->insert_id;
                    $stmt_insert_tutor->close();

                    // 2. Eliminar de la tabla 'estudiantes'
                    $stmt_delete_estudiante = $conn->prepare("DELETE FROM estudiantes WHERE ID_estudiante = ?");
                    if ($stmt_delete_estudiante) {
                        $stmt_delete_estudiante->bind_param("i", $temp_user_id);

                        if ($stmt_delete_estudiante->execute()) {
                            $stmt_delete_estudiante->close();
                            $conn->commit(); // Confirmar transacción

                            // 3. Actualizar sesión para el tutor
                            $_SESSION['user_id'] = $new_tutor_id;
                            $_SESSION['user_role'] = 'tutor';
                            $_SESSION['user_name'] = $nombre_usuario . ' ' . $apellido_usuario;
                            $_SESSION['user_email'] = $correo_usuario;

                            unset($_SESSION['temp_user_id']);
                            unset($_SESSION['temp_user_email']);

                            redirect('dashboard_tutor.php');
                            exit;
                        } else {
                            $conn->rollback(); // Revertir transacción
                            $error_message = "Error al finalizar registro de tutor (eliminando temporal): " . $stmt_delete_estudiante->error;
                            $stmt_delete_estudiante->close();
                        }
                    } else {
                        $conn->rollback();
                        $error_message = "Error de preparación de consulta (eliminar estudiante): " . $conn->error;
                    }
                } else {
                    $conn->rollback();
                    if ($stmt_insert_tutor->errno == 1062) { // Error de entrada duplicada (ej. correo ya existe en tutores)
                        $error_message = "Error: Ya existe un tutor con este correo electrónico.";
                    } else {
                        $error_message = "Error al crear el perfil de tutor: " . $stmt_insert_tutor->error;
                    }
                    $stmt_insert_tutor->close();
                }
            } else {
                 $conn->rollback(); // Aunque no se haya iniciado formalmente la transacción si falló prepare
                 $error_message = "Error de preparación de consulta (insertar tutor): " . $conn->error;
            }
        }
    }
}

// Incluir el header de la página
$page_title = "Completar Perfil";
require_once '../includes/header.php';
?>

<div class="container form-container"> <h2>Completa tu Perfil</h2>
    
    <?php if (!empty($nombre_usuario) && $nombre_usuario !== "Usuario"): ?>
    <p>¡Bienvenido(a), <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>!</p>
    <p>Tu apellido (<strong><?php echo htmlspecialchars($apellido_usuario); ?></strong>) ya ha sido registrado.</p>
    <p>Por favor, selecciona tu rol para finalizar tu registro.</p>
    <?php else: ?>
    <p>Por favor, selecciona tu rol para finalizar tu registro.</p>
    <?php endif; ?>


    <?php if (!empty($error_message)): ?>
        <p class="error-message" style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <?php if (!empty($success_message)): // Raramente se verá si hay redirección ?>
        <p class="success-message" style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <form action="registro_perfil.php" method="POST">
        <label for="rol">Soy:</label>
        <select id="rol" name="rol" required>
            <option value="">Selecciona tu rol</option>
            <option value="estudiante" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'estudiante') ? 'selected' : ''; ?>>Estudiante</option>
            <option value="tutor" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'tutor') ? 'selected' : ''; ?>>Tutor</option>
        </select>

        <div id="tutor_fields" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'tutor') ? '' : 'style="display:none;"'; ?>>
            <label for="area_especializacion">Área de Especialización (ej. Matemáticas, Programación):</label>
            <input type="text" id="area_especializacion" name="area_especializacion" value="<?php echo isset($_POST['area_especializacion']) ? htmlspecialchars($_POST['area_especializacion']) : ''; ?>">
        </div>

        <button type="submit" name="submit_profile">Completar Perfil</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var rolSelect = document.getElementById('rol');
    var tutorFields = document.getElementById('tutor_fields');
    var areaEspecializacionInput = document.getElementById('area_especializacion');

    function toggleTutorFields() {
        if (rolSelect.value === 'tutor') {
            tutorFields.style.display = 'block';
            areaEspecializacionInput.required = true;
        } else {
            tutorFields.style.display = 'none';
            areaEspecializacionInput.required = false;
        }
    }

    // Ejecutar al cargar la página por si hay valores preseleccionados (ej. tras error)
    toggleTutorFields();

    // Ejecutar cuando cambie la selección del rol
    rolSelect.addEventListener('change', toggleTutorFields);
});
</script>

<?php
require_once '../includes/footer.php';
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) { // Cierra la conexión si sigue abierta
    $conn->close();
}
?>