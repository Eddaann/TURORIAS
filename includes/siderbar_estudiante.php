<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($active_page)) {
    $active_page = ''; // Valor por defecto si no se establece
}

// Verificar si el usuario está logueado y es un estudiante
$is_student_logged_in = (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'estudiante');
$student_name = $is_student_logged_in && isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Estudiante';

?>
<aside class="student-sidebar">
    <div class="sidebar-header">
        <h4>Panel del Estudiante</h4>
        <?php if ($is_student_logged_in): ?>
            <p>Bienvenido/a, <?php echo htmlspecialchars($student_name); ?></p>
        <?php endif; ?>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($active_page === 'dashboard_estudiante') ? 'active' : ''; ?>">
                <a href="dashboard_estudiante.php">Mi Dashboard</a>
            </li>
            <li class="<?php echo ($active_page === 'solicitar_tutoria') ? 'active' : ''; ?>">
                <a href="solicitar_tutoria.php">Solicitar Tutoría</a>
            </li>
            <li class="<?php echo ($active_page === 'historial_tutorias') ? 'active' : ''; ?>">
                <a href="historial_tutorias_estudiante.php">Historial de Tutorías</a>
            </li>
            <li class="<?php echo ($active_page === 'buscar_tutores') ? 'active' : ''; ?>">
                <a href="buscar_tutores.php">Buscar Tutores</a>
            </li>
            <li class="<?php echo ($active_page === 'mi_perfil') ? 'active' : ''; ?>">
                <a href="perfil.php">Mi Perfil</a>
            </li>
            <li>
                <a href="logout.php">Cerrar Sesión</a>
            </li>
        </ul>
    </nav>
</aside>

<style>
/* Estilos básicos para el sidebar del estudiante. Puedes mover esto a tu style.css principal */
.student-sidebar {
    width: 250px; /* Ancho del sidebar */
    background-color: #f8f9fa; /* Color de fondo claro */
    padding: 15px;
    border-right: 1px solid #dee2e6; /* Borde derecho para separarlo del contenido */
    min-height: calc(100vh - 70px); /* Asumiendo que el header tiene unos 70px de alto */
    /* Si tu header tiene altura diferente, ajusta el 70px */
}

.student-sidebar .sidebar-header {
    padding-bottom: 10px;
    margin-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.student-sidebar .sidebar-header h4 {
    margin-top: 0;
    color: #007bff; /* Color primario para el título */
}

.student-sidebar .sidebar-header p {
    font-size: 0.9em;
    color: #6c757d; /* Color de texto secundario */
}

.student-sidebar .sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.student-sidebar .sidebar-nav li a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #343a40; /* Color de enlace oscuro */
    border-radius: 4px;
    margin-bottom: 5px;
    transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
}

.student-sidebar .sidebar-nav li a:hover {
    background-color: #e9ecef; /* Fondo al pasar el mouse */
    color: #0056b3; /* Color de enlace más oscuro al pasar el mouse */
}

.student-sidebar .sidebar-nav li.active a {
    background-color: #007bff; /* Color de fondo para el enlace activo */
    color: white; /* Texto blanco para el enlace activo */
    font-weight: bold;
}
</style>