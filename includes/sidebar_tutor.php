<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($active_page)) {
    $active_page = '';
}

$is_tutor_logged_in = (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'tutor');
$tutor_name = $is_tutor_logged_in && isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Tutor';

?>
<aside class="tutor-sidebar">
    <div class="sidebar-header">
        <h4>Panel del Tutor</h4>
        <?php if ($is_tutor_logged_in): ?>
            <p>Bienvenido/a, <?php echo htmlspecialchars($tutor_name); ?></p>
        <?php endif; ?>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($active_page === 'dashboard_tutor') ? 'active' : ''; ?>">
                <a href="dashboard_tutor.php">Mi Dashboard</a>
            </li>
            <li class="<?php echo ($active_page === 'crear_tutoria') ? 'active' : ''; ?>">
                <a href="crear_tutoria.php">Crear Tutoría</a>
            </li>
            <li class="<?php echo ($active_page === 'ver_tutorias_asignadas') ? 'active' : ''; ?>">
                <a href="ver_tutorias_tutor.php">Ver Mis Tutorías</a>
            </li>
            <li class="<?php echo ($active_page === 'generar_reporte_tutor') ? 'active' : ''; ?>">
                <a href="generar_reporte_tutor.php">Generar Reportes</a>
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