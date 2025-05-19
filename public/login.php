<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Si el usuario ya está logueado, redirigirlo a su dashboard
if (is_logged_in()) {
    if ($_SESSION['user_role'] == 'estudiante') {
        redirect('dashboard_estudiante.php');
    } elseif ($_SESSION['user_role'] == 'tutor') {
        redirect('dashboard_tutor.php');
    }
}

$error_message = "";

// Lógica para INICIO DE SESIÓN
if (isset($_POST['login_submit'])) {
    $correo_login = sanitize_input($_POST['correo_login']);
    $password_login = sanitize_input($_POST['password_login']);

    if (empty($correo_login) || empty($password_login)) {
        $error_message = "Ambos campos de inicio de sesión son obligatorios.";
    } else {
        // Intentar buscar en la tabla de estudiantes
        $stmt_estudiante = $conn->prepare("SELECT ID_estudiante, nombre, contraseña FROM estudiantes WHERE correo = ?");
        $stmt_estudiante->bind_param("s", $correo_login);
        $stmt_estudiante->execute();
        $stmt_estudiante->store_result();
        $stmt_estudiante->bind_result($user_id, $user_nombre, $hashed_password);

        if ($stmt_estudiante->num_rows == 1) {
            $stmt_estudiante->fetch();
            if (password_verify($password_login, $hashed_password)) {
                // Inicio de sesión exitoso como estudiante
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $user_nombre;
                $_SESSION['user_role'] = 'estudiante';
                redirect('dashboard_estudiante.php');
            } else {
                $error_message = "Credenciales incorrectas.";
            }
        } else {
            // Si no es estudiante, intentar buscar en la tabla de tutores
            $stmt_tutor = $conn->prepare("SELECT ID_tutor, nombre, contraseña FROM tutores WHERE correo = ?");
            $stmt_tutor->bind_param("s", $correo_login);
            $stmt_tutor->execute();
            $stmt_tutor->store_result();
            $stmt_tutor->bind_result($user_id, $user_nombre, $hashed_password);

            if ($stmt_tutor->num_rows == 1) {
                $stmt_tutor->fetch();
                if (password_verify($password_login, $hashed_password)) {
                    // Inicio de sesión exitoso como tutor
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $user_nombre;
                    $_SESSION['user_role'] = 'tutor';
                    redirect('dashboard_tutor.php');
                } else {
                    $error_message = "Credenciales incorrectas.";
                }
            } else {
                $error_message = "Credenciales incorrectas.";
            }
        }
        $stmt_estudiante->close();
        if (isset($stmt_tutor)) $stmt_tutor->close(); // Asegúrate de cerrar si se abrió
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container form-container">
        <h1>Iniciar Sesión</h1>

        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="correo_login">Correo Electrónico:</label>
            <input type="email" id="correo_login" name="correo_login" required>

            <label for="password_login">Contraseña:</label>
            <input type="password" id="password_login" name="password_login" required>

            <button type="submit" name="login_submit">Iniciar Sesión</button>
        </form>
        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>