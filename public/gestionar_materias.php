<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tutor') {
    redirect('login.php');
}

$page_title = "Gestionar Materias";
$active_page = 'gestionar_materias';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_materia_submit'])) {
    $nombre_materia_nueva = sanitize_input($_POST['nombre_materia_nueva']);

    if (empty($nombre_materia_nueva)) {
        $error_message = "El nombre de la materia no puede estar vacío.";
    } else {
        $stmt_check = $conn->prepare("SELECT ID_materia FROM materias WHERE nombre_materia = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $nombre_materia_nueva);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $error_message = "La materia '" . htmlspecialchars($nombre_materia_nueva) . "' ya existe.";
            } else {
                $stmt_insert = $conn->prepare("INSERT INTO materias (nombre_materia) VALUES (?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("s", $nombre_materia_nueva);
                    if ($stmt_insert->execute()) {
                        $success_message = "Materia '" . htmlspecialchars($nombre_materia_nueva) . "' añadida exitosamente.";
                    } else {
                        $error_message = "Error al añadir la materia: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                    $error_message = "Error al preparar la inserción: " . $conn->error;
                }
            }
            $stmt_check->close();
        } else {
            $error_message = "Error al preparar la verificación: " . $conn->error;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id_materia'])) {
    $id_materia_del = filter_input(INPUT_GET, 'id_materia', FILTER_VALIDATE_INT);
    if ($id_materia_del) {
        $stmt_check_uso = $conn->prepare("SELECT COUNT(*) as count FROM tutorías WHERE ID_materia = ?");
        $stmt_check_uso->bind_param("i", $id_materia_del);
        $stmt_check_uso->execute();
        $result_check_uso = $stmt_check_uso->get_result();
        $row_check_uso = $result_check_uso->fetch_assoc();
        $stmt_check_uso->close();

        if ($row_check_uso['count'] > 0) {
            $error_message = "No se puede eliminar la materia porque está asignada a " . $row_check_uso['count'] . " tutoría(s).";
        } else {
            $stmt_delete = $conn->prepare("DELETE FROM materias WHERE ID_materia = ?");
            if ($stmt_delete) {
                $stmt_delete->bind_param("i", $id_materia_del);
                if ($stmt_delete->execute()) {
                    $success_message = "Materia eliminada exitosamente.";
                } else {
                    $error_message = "Error al eliminar la materia: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                $error_message = "Error al preparar la eliminación: " . $conn->error;
            }
        }
    } else {
        $error_message = "ID de materia no válido para eliminar.";
    }
}

$materias_existentes = [];
$sql_materias = "SELECT ID_materia, nombre_materia FROM materias ORDER BY nombre_materia ASC";
$result_materias = $conn->query($sql_materias);
if ($result_materias && $result_materias->num_rows > 0) {
    while ($row = $result_materias->fetch_assoc()) {
        $materias_existentes[] = $row;
    }
}

require_once '../includes/header.php';
?>

<div class="page-container">
    <?php 
    if ($_SESSION['user_role'] === 'tutor') {
        require_once '../includes/sidebar_tutor.php'; 
    }
    ?>

    <div class="main-content-area">
        <div class="dashboard-container">
            <h2>Gestionar Materias del Sistema</h2>
            <p>Aquí puedes ver, añadir, y administrar las materias disponibles para las tutorías.</p>

            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <div class="form-container" style="max-width: 500px; margin-bottom: 30px;">
                <h3>Añadir Nueva Materia</h3>
                <form action="gestionar_materias.php" method="POST">
                    <div>
                        <label for="nombre_materia_nueva">Nombre de la Materia: <span class="required">*</span></label>
                        <input type="text" id="nombre_materia_nueva" name="nombre_materia_nueva" required>
                    </div>
                    <button type="submit" name="add_materia_submit">Añadir Materia</button>
                </form>
            </div>

            <h3>Materias Existentes</h3>
            <?php if (!empty($materias_existentes)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de la Materia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materias_existentes as $materia): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($materia['ID_materia']); ?></td>
                            <td><?php echo htmlspecialchars($materia['nombre_materia']); ?></td>
                            <td>
                                <a href="editar_materia.php?id_materia=<?php echo $materia['ID_materia']; ?>" class="button small">Editar</a>
                                
                                <a href="gestionar_materias.php?action=delete&id_materia=<?php echo $materia['ID_materia']; ?>" 
                                   class="button small alert" 
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar la materia \'<?php echo htmlspecialchars(addslashes($materia['nombre_materia'])); ?>\'? Esta acción no se puede deshacer.');">
                                   Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay materias registradas en el sistema todavía.</p>
            <?php endif; ?>
            
            <p style="margin-top: 30px;">
                <a href="dashboard_tutor.php" class="button">Volver al Dashboard del Tutor</a>
            </p>

        </div>
    </div>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>