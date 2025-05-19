<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($active_page)) {
    $active_page = '';
}

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