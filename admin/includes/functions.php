<?php
/**
 * Función para sanitizar datos de entrada
 * Protege contra XSS y prepara datos para consultas SQL
 * * @param string $data El dato a sanitizar
 * @param bool $allow_html Permitir HTML (false por defecto para mayor seguridad)
 * @return string Datos sanitizados
 */
function sanitizar($data, $allow_html = false) {
    // Verificar si el dato está vacío
    if (empty($data)) {
        return '';
    }
    
    // Eliminar espacios en blanco al inicio y final
    $data = trim($data);
    
    // Si no se permite HTML, eliminar todas las etiquetas
    if (!$allow_html) {
        $data = strip_tags($data);
    }
    
    // Convertir caracteres especiales a entidades HTML (protección XSS)
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Eliminar barras invertidas añadidas por magic_quotes (si están habilitadas)
    /* * ESTE BLOQUE SE COMENTA PORQUE get_magic_quotes_gpc() FUE ELIMINADO EN PHP 8.0+
    if (get_magic_quotes_gpc()) {
        $data = stripslashes($data);
    }
    */
    
    return $data;
}

/**
 * Sanitizar específicamente para consultas SQL (prevenir inyección SQL)
 * NOTA: Siempre es mejor usar prepared statements, esta función es una capa adicional
 * * @param string $data Dato a sanitizar para SQL
 * @return string Dato sanitizado
 */
function sanitizar_sql($data) {
    global $db; // Asumiendo que tienes una conexión a la base de datos
    
    if (empty($data)) {
        return '';
    }
    
    // Si hay una conexión a la base de datos, usar real_escape_string
    if (isset($db) && is_object($db)) {
        return $db->real_escape_string($data);
    }
    
    // Fallback: eliminar caracteres potencialmente peligrosos
    $data = preg_replace('/[\/\'"\;\-\-\#\*]/', '', $data);
    return $data;
}

/**
 * Sanitizar números enteros
 * * @param mixed $data Dato a convertir a entero
 * @return int Número entero sanitizado
 */
function sanitizar_int($data) {
    return intval($data);
}

/**
 * Sanitizar números decimales
 * * @param mixed $data Dato a convertir a decimal
 * @return float Número decimal sanitizado
 */
function sanitizar_float($data) {
    return floatval($data);
}

/**
 * Sanitizar email
 * * @param string $email Email a sanitizar
 * @return string|bool Email sanitizado o false si no es válido
 */
function sanitizar_email($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    return false;
}

/**
 * Sanitizar URL
 * * @param string $url URL a sanitizar
 * @return string|bool URL sanitizada o false si no es válida
 */
function sanitizar_url($url) {
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    return false;
}
?>