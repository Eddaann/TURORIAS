<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$page_title = "Mi Perfil";
$active_page = 'mi_perfil';

$error_message = "";
$success_message = "";

$user_data = [];
$table_name = ($user_role === 'estudiante') ? 'estudiantes' : 'tutores';
$id_column = ($user_role === 'estudiante') ? 'ID_estudiante' : 'ID_tutor';

$stmt_user = $conn->prepare("SELECT nombre, apellido, correo" . ($user_role === 'tutor' ? ", area_especializacion" : "") . " FROM {$table_name} WHERE {$id_column} = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows === 1) {
        $user_data = $result_user->fetch_assoc();
    } else {
        $error_message = "Error al cargar los datos del usuario.";
    }
    $stmt_user->close();
} else {
    $error_message = "Error de preparación de consulta: " . $conn->error;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $nombre = sanitize_input($_POST['nombre']);
        $apellido = sanitize_input($_POST['apellido']);
        $correo = sanitize_input($_POST['correo']);
        $area_especializacion = ($user_role === 'tutor') ? sanitize_input($_POST['area_especializacion']) : null;

        if (empty($nombre) || empty($apellido) || empty($correo)) {
            $error_message = "Nombre, apellido y correo son obligatorios.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error_message = "El formato del correo electrónico es inválido.";
        } else {
            $email_changed = ($correo !== $user_data['correo']);
            $email_conflict = false;

            if ($email_changed) {
                $stmt_check_email = null;
                if ($user_role == 'estudiante') {
                    $stmt_check_email = $conn->prepare("SELECT ID_estudiante FROM estudiantes WHERE correo = ? AND ID_estudiante != ? UNION SELECT ID_tutor FROM tutores WHERE correo = ?");
                    $stmt_check_email->bind_param("sis", $correo, $user_id, $correo);
                } else {
                    $stmt_check_email = $conn->prepare("SELECT ID_tutor FROM tutores WHERE correo = ? AND ID_tutor != ? UNION SELECT ID_estudiante FROM estudiantes WHERE correo = ?");
                    $stmt_check_email->bind_param("sis", $correo, $user_id, $correo);
                }
                $stmt_check_email->execute();
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows > 0) {
                    $email_conflict = true;
                    $error_message = "El correo electrónico ya está en uso por otro usuario.";
                }
                $stmt_check_email->close();
            }

            if (!$email_conflict) {
                $sql_update_profile = "";
                $types = "";
                $params = [];

                if ($user_role === 'estudiante') {
                    $sql_update_profile = "UPDATE estudiantes SET nombre = ?, apellido = ?, correo = ? WHERE ID_estudiante = ?";
                    $types = "sssi";
                    $params = [$nombre, $apellido, $correo, $user_id];
                } else {
                    $sql_update_profile = "UPDATE tutores SET nombre = ?, apellido = ?, correo = ?, area_especializacion = ? WHERE ID_tutor = ?";
                    $types = "ssssi";
                    $params = [$nombre, $apellido, $correo, $area_especializacion, $user_id];
                }

                $stmt_update = $conn->prepare($sql_update_profile);
                $stmt_update->bind_param($types, ...$params);

                if ($stmt_update->execute()) {
                    $success_message = "Perfil actualizado exitosamente.";
                    $_SESSION['user_name'] = $nombre . ' ' . $apellido;
                    if ($email_changed) $_SESSION['user_email'] = $correo;
                    
                    $stmt_user_refresh = $conn->prepare("SELECT nombre, apellido, correo" . ($user_role === 'tutor' ? ", area_especializacion" : "") . " FROM {$table_name} WHERE {$id_column} = ?");
                    $stmt_user_refresh->bind_param("i", $user_id);
                    $stmt_user_refresh->execute();
                    $result_user_refresh = $stmt_user_refresh->get_result();
                    $user_data = $result_user_refresh->fetch_assoc();
                    $stmt_user_refresh->close();

                } else {
                    $error_message = "Error al actualizar el perfil: " . $stmt_update->error;
                }
                $stmt_update->close();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = sanitize_input($_POST['current_password']);
        $new_password = sanitize_input($_POST['new_password']);
        $confirm_password = sanitize_input($_POST['confirm_password']);

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "Todos los campos de contraseña son obligatorios.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "La nueva contraseña y la confirmación no coinciden.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "La nueva contraseña debe tener al menos 6 caracteres.";
        } else {
            $stmt_fetch_pass = $conn->prepare("SELECT contraseña FROM {$table_name} WHERE {$id_column} = ?");
            $stmt_fetch_pass->bind_param("i", $user_id);
            $stmt_fetch_pass->execute();
            $result_pass = $stmt_fetch_pass->get_result();
            if ($db_user_data = $result_pass->fetch_assoc()) {
                if (password_verify($current_password, $db_user_data['contraseña'])) {
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_update_pass = $conn->prepare("UPDATE {$table_name} SET contraseña = ? WHERE {$id_column} = ?");
                    $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
                    if ($stmt_update_pass->execute()) {
                        $success_message = "Contraseña actualizada exitosamente.";
                    } else {
                        $error_message = "Error al actualizar la contraseña: " . $stmt_update_pass->error;
                    }
                    $stmt_update_pass->close();
                } else {
                    $error_message = "La contraseña actual es incorrecta.";
                }
            }
            $stmt_fetch_pass->close();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="page-container" style="display: flex;">
    <?php
    if ($user_role === 'estudiante') {
        if (file_exists('../includes/siderbar_estudiante.php')) {
            require_once '../includes/siderbar_estudiante.php';
        } elseif (file_exists('../includes/sidebar_estudiante.php')) {
            require_once '../includes/sidebar_estudiante.php';
        }
    } else {
        require_once '../includes/sidebar_tutor.php';
    }
    ?>
    <div class="main-content-area" style="flex-grow: 1; padding: 20px;">
        <div class="dashboard-container">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>

            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <?php if (!empty($user_data)): ?>
            <form action="perfil.php" method="POST" class="form-container" style="max-width: 700px; margin-bottom: 40px;">
                <h3>Actualizar Información Personal</h3>
                <div>
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user_data['nombre']); ?>" required>
                </div>
                <div>
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($user_data['apellido']); ?>" required>
                </div>
                <div>
                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($user_data['correo']); ?>" required>
                </div>
                <?php if ($user_role === 'tutor'): ?>
                <div>
                    <label for="area_especializacion">Área de Especialización:</label>
                    <input type="text" id="area_especializacion" name="area_especializacion" value="<?php echo htmlspecialchars($user_data['area_especializacion'] ?? ''); ?>" required>
                </div>
                <?php endif; ?>
                <button type="submit" name="update_profile">Guardar Cambios</button>
            </form>

            <form action="perfil.php" method="POST" class="form-container" style="max-width: 700px;">
                <h3>Cambiar Contraseña</h3>
                <div>
                    <label for="current_password">Contraseña Actual:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div>
                    <label for="new_password">Nueva Contraseña:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div>
                    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password">Cambiar Contraseña</button>
            </form>
            <?php else: ?>
                 <?php if (empty($error_message)) : ?>
                    <p class="error-message">No se pudieron cargar los datos del perfil.</p>
                <?php endif; ?>
            <?php endif; ?>
             <p style="margin-top: 30px;">
                <?php
                $dashboard_link = ($user_role === 'estudiante') ? 'dashboard_estudiante.php' : 'dashboard_tutor.php';
                ?>
                <a href="<?php echo $dashboard_link; ?>" class="button">Volver al Dashboard</a>
            </p>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>