<?php
// includes/functions.php

/**
 * Función para sanear (limpiar) los datos de entrada
 * @param string $data El dato a sanear
 * @return string El dato saneado
 */
function sanitize_input($data) {
    $data = trim($data); // Elimina espacios en blanco del principio y final
    $data = stripslashes($data); // Elimina barras invertidas
    $data = htmlspecialchars($data); // Convierte caracteres especiales en entidades HTML
    return $data;
}

/**
 * Función para verificar si un usuario está logueado
 * @return bool True si está logueado, False en caso contrario
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}



/**
 * Función para redirigir a una página
 * @param string $location La URL a la que redirigir
 */
function redirect($location) {
    header("Location: " . $location);
    exit(); // Importante para detener la ejecución del script después de la redirección
}


// Puedes añadir más funciones aquí a medida que las necesites
?>