<?php
// includes/sidebar_tutor.php

// Asegurarse de que la sesión esté iniciada.
// Si este sidebar se incluye después del header.php, la sesión ya debería estar iniciada.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Variable para saber qué página está activa y aplicar un estilo.
// El script que incluya este sidebar debería definir $active_page.
// Ejemplo: $active_page = 'dashboard_tutor';
if (!isset($active_page)) {
    $active_page = ''; // Valor por defecto si no se establece
}

// Verificar si el usuario está logueado y es un tutor
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

<style>
/* Estilos básicos para el sidebar del tutor. Es recomendable mover esto a tu style.css principal */
.tutor-sidebar {
    width: 250px; /* Ancho del sidebar */
    background-color: #f8f9fa; /* Color de fondo claro */
    padding: 15px;
    border-right: 1px solid #dee2e6; /* Borde derecho para separarlo del contenido */
    min-height: calc(100vh - 70px); /* Asumiendo que el header tiene unos 70px de alto */
    /* Si tu header tiene altura diferente, ajusta el 70px */
    /* Esta altura asegura que el sidebar se extienda al menos hasta el final de la ventana visible,
       considerando la altura del header. Si el contenido principal es más largo, el body/html
       debería manejar el scroll general de la página. */
}

.tutor-sidebar .sidebar-header {
    padding-bottom: 10px;
    margin-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.tutor-sidebar .sidebar-header h4 {
    margin-top: 0;
    color: #17a2b8; /* Un color diferente para el panel de tutor, ej: info */
}

.tutor-sidebar .sidebar-header p {
    font-size: 0.9em;
    color: #6c757d; /* Color de texto secundario */
}

.tutor-sidebar .sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tutor-sidebar .sidebar-nav li a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #343a40; /* Color de enlace oscuro */
    border-radius: 4px;
    margin-bottom: 5px;
    transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
}

.tutor-sidebar .sidebar-nav li a:hover {
    background-color: #e9ecef; /* Fondo al pasar el mouse */
    color: #0056b3; /* Color de enlace más oscuro al pasar el mouse */
}

.tutor-sidebar .sidebar-nav li.active a {
    background-color: #17a2b8; /* Color de fondo para el enlace activo (info) */
    color: white; /* Texto blanco para el enlace activo */
    font-weight: bold;
}
</style>