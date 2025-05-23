<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    if ($_SESSION['user_role'] == 'estudiante') {
        redirect('dashboard_estudiante.php');
    } elseif ($_SESSION['user_role'] == 'tutor') {
        redirect('dashboard_tutor.php');
    }
}

$error_message = "";
$success_message = "";

if (isset($_POST['register_submit'])) {
    $nombre_registro = sanitize_input($_POST['nombre_registro']);
    $apellidos_registro = sanitize_input($_POST['apellidos_registro']);
    $correo_registro = sanitize_input($_POST['correo_registro']);
    $password_registro = sanitize_input($_POST['password_registro']);
    $confirm_password_registro = sanitize_input($_POST['confirm_password_registro']);

    if (empty($nombre_registro) || empty($apellidos_registro) || empty($correo_registro) || empty($password_registro) || empty($confirm_password_registro)) { // Añadida validación para apellidos
        $error_message = "Todos los campos de registro son obligatorios.";
    } elseif (!filter_var($correo_registro, FILTER_VALIDATE_EMAIL)) {
        $error_message = "El formato del correo electrónico es inválido.";
    } elseif ($password_registro !== $confirm_password_registro) {
        $error_message = "Las contraseñas no coinciden.";
    } elseif (strlen($password_registro) < 6) {
        $error_message = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $stmt_check = $conn->prepare("SELECT correo FROM estudiantes WHERE correo = ? UNION SELECT correo FROM tutores WHERE correo = ?");
        $stmt_check->bind_param("ss", $correo_registro, $correo_registro);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_message = "Este correo electrónico ya está registrado.";
        } else {
            $hashed_password = password_hash($password_registro, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO estudiantes (nombre, apellido, correo, contraseña) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre_registro, $apellidos_registro, $correo_registro, $hashed_password);

            if ($stmt->execute()) {
                $success_message = "¡Registro exitoso! Por favor, completa tu perfil.";
                $_SESSION['temp_user_id'] = $stmt->insert_id;
                $_SESSION['temp_user_email'] = $correo_registro;
                redirect('registro_perfil.php');
            } else {
                $error_message = "Error al registrar: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container form-container">
        <h1>Registrarse</h1>

        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <label for="nombre_registro">Nombre(s):</label>
            <input type="text" id="nombre_registro" name="nombre_registro" value="<?php echo isset($_POST['nombre_registro']) ? htmlspecialchars($_POST['nombre_registro']) : ''; ?>" required>

            <label for="apellidos_registro">Apellidos:</label> <input type="text" id="apellidos_registro" name="apellidos_registro" value="<?php echo isset($_POST['apellidos_registro']) ? htmlspecialchars($_POST['apellidos_registro']) : ''; ?>" required>

            <label for="correo_registro">Correo Electrónico:</label>
            <input type="email" id="correo_registro" name="correo_registro" value="<?php echo isset($_POST['correo_registro']) ? htmlspecialchars($_POST['correo_registro']) : ''; ?>" required>

            <label for="password_registro">Contraseña:</label>
            <input type="password" id="password_registro" name="password_registro" required>

            <label for="confirm_password_registro">Confirmar Contraseña:</label>
            <input type="password" id="confirm_password_registro" name="confirm_password_registro" required>

            <button type="submit" name="register_submit">Registrarse</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
</body>
</html>