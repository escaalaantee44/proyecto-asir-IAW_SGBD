<?php

// ── DATOS DEL SERVIDOR DE BASE DE DATOS ──────────────────────────────────────

$host = "localhost";
// El servidor de base de datos está en la misma máquina que el servidor web (XAMPP).
// "localhost" equivale a 127.0.0.1. Si la BD estuviera en otro servidor, iría su IP aquí.

$db = "sistema_reservas";
// Nombre de la base de datos a la que nos conectamos (la que creaste en phpMyAdmin/MySQL)


// ── SELECCIÓN DE USUARIO SEGÚN ROL (lógica de seguridad propia) ───────────────

if (isset($_SESSION["rol"]) && $_SESSION["rol"] == "admin") {
    // Comprueba si existe la variable de sesión "rol" Y si su valor es "admin".
    // isset() va primero para evitar un error PHP si la variable no existiera aún.
    // Esta variable se guardó en login.php cuando el usuario inició sesión.

    $user = "admin_app";
    $pass = "admin123";
    // Si es administrador → usa el usuario MySQL "admin_app".
    // Este usuario tiene privilegios completos en la BD:
    // SELECT, INSERT, UPDATE, DELETE y también gestión de categorías y recursos.

} else {
    $user = "usuario_app";
    $pass = "user123";
    // Cualquier otro caso (rol "usuario" o sesión no iniciada) → usuario MySQL restringido.
    // "usuario_app" solo tiene permisos de SELECT, INSERT y UPDATE en las tablas permitidas.
    // Aunque alguien manipulara la sesión, la BD misma bloquearía operaciones no permitidas.
}

// ── POR QUÉ DOS USUARIOS MYSQL EN VEZ DE UNO SOLO ────────────────────────────
// Esto es una capa de seguridad extra llamada "principio de mínimo privilegio":
// si un usuario normal intenta hacer algo que no debería (ej: borrar categorías),
// MySQL lo rechaza directamente, independientemente de lo que diga el PHP.
// No basta con ocultar botones en la interfaz → la BD también debe protegerse.


// ── CONEXIÓN A LA BASE DE DATOS ───────────────────────────────────────────────

$conn = new mysqli($host, $user, $pass, $db);
// Crea una nueva conexión MySQL con los datos definidos arriba.
// mysqli = "MySQL Improved", la extensión moderna de PHP para conectar con MySQL/MariaDB.
// Parámetros: (servidor, usuario_mysql, contraseña, nombre_base_de_datos)
// El resultado ($conn) es el objeto que usaremos en todas las consultas: $conn->query(...)


// ── COMPROBACIÓN DE ERRORES DE CONEXIÓN ──────────────────────────────────────

if ($conn->connect_error) {
    // connect_error contiene el mensaje de error si la conexión falló.
    // Puede fallar si: MySQL no está arrancado, las credenciales son incorrectas,
    // la BD no existe, o el usuario no tiene permisos.

    die("Error de conexión: " . $conn->connect_error);
    // die() detiene TODA la ejecución del script inmediatamente y muestra el mensaje.
    // Así evitamos que la página continúe ejecutándose sin conexión y genere
    // errores en cascada o información sensible visible al usuario.
}


// ── CODIFICACIÓN DE CARACTERES ────────────────────────────────────────────────

$conn->set_charset("utf8");
// Le dice a MySQL que envíe y reciba datos en UTF-8.
// Sin esto, los caracteres especiales (tildes, ñ, €...) pueden aparecer
// corruptos o como signos de interrogación en la pantalla.
// Debe coincidir con el charset de la BD (utf8 o utf8mb4) y el del HTML (meta charset="utf-8").
