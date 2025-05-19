<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    if (isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] == 'estudiante') {
            redirect('dashboard_estudiante.php');
        } elseif ($_SESSION['user_role'] == 'tutor') {
            redirect('dashboard_tutor.php');
        }
    }
}

$error_message = "";

if (isset($_POST['login_submit'])) {
    $correo_login = sanitize_input($_POST['correo_login']);
    $password_login = $_POST['password_login'];

    if (empty($correo_login) || empty($password_login)) {
        $error_message = "Ambos campos de inicio de sesión son obligatorios.";
    } else {
        $user_found = false;
        $stmt_estudiante = $conn->prepare("SELECT ID_estudiante, nombre, apellido, contraseña FROM estudiantes WHERE correo = ?");
        if ($stmt_estudiante) {
            $stmt_estudiante->bind_param("s", $correo_login);
            $stmt_estudiante->execute();
            $stmt_estudiante->store_result();
            
            if ($stmt_estudiante->num_rows == 1) {
                $stmt_estudiante->bind_result($user_id, $user_nombre, $user_apellido, $hashed_password);
                $stmt_estudiante->fetch();
                if (password_verify($password_login, $hashed_password)) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = trim($user_nombre . ' ' . $user_apellido);
                    $_SESSION['user_role'] = 'estudiante';
                    $_SESSION['user_email'] = $correo_login;
                    $user_found = true;
                    redirect('dashboard_estudiante.php');
                }
            }
            $stmt_estudiante->close();
        } else {
            $error_message = "Error al preparar consulta de estudiantes: " . $conn->error;
        }
        if (!$user_found && empty($error_message)) {
            $stmt_tutor = $conn->prepare("SELECT ID_tutor, nombre, apellido, contraseña FROM tutores WHERE correo = ?");
            if ($stmt_tutor) {
                $stmt_tutor->bind_param("s", $correo_login);
                $stmt_tutor->execute();
                $stmt_tutor->store_result();

                if ($stmt_tutor->num_rows == 1) {
                    $stmt_tutor->bind_result($user_id, $user_nombre, $user_apellido, $hashed_password);
                    $stmt_tutor->fetch();
                    if (password_verify($password_login, $hashed_password)) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_name'] = trim($user_nombre . ' ' . $user_apellido);
                        $_SESSION['user_role'] = 'tutor';
                        $_SESSION['user_email'] = $correo_login;
                        $user_found = true;
                        redirect('dashboard_tutor.php');
                    }
                }
                $stmt_tutor->close();
            } else {
                 $error_message = "Error al preparar consulta de tutores: " . $conn->error;
            }
        }

        if (!$user_found && empty($error_message)) {
            $error_message = "Correo electrónico o contraseña incorrectos.";
        } elseif (!$user_found && !empty($error_message) && strpos($error_message, "incorrectos") === false) {
        } elseif ($user_found == false && empty($error_message)) {
             $error_message = "Correo electrónico o contraseña incorrectos.";
        }


    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Tutorías</title>
    <link rel="stylesheet" href="css/style.css"> </head>
<body>
    <div class="form-container"> <h1>Iniciar Sesión</h1>

        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div>
                <label for="correo_login">Correo Electrónico:</label>
                <input type="email" id="correo_login" name="correo_login" value="<?php echo isset($_POST['correo_login']) ? htmlspecialchars($_POST['correo_login']) : ''; ?>" required>
            </div>
            <div>
                <label for="password_login">Contraseña:</label>
                <input type="password" id="password_login" name="password_login" required>
            </div>
            <button type="submit" name="login_submit">Iniciar Sesión</button>
        </form>
        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
        <p><a href="index.php">Volver a la página principal</a></p>
    </div>
    <?php
    if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
        $conn->close();
    }
    ?>
</body>
</html>