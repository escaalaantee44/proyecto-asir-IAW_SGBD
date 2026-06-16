<?php
session_start();
// Inicia el sistema de sesiones para poder acceder a la sesión activa del usuario.
// Aunque parezca raro iniciar una sesión para destruirla, es NECESARIO:
// PHP necesita cargar primero los datos de la sesión antes de poder eliminarlos.
// Sin session_start(), session_destroy() no tendría ninguna sesión que destruir.

session_destroy();
// Elimina completamente la sesión del servidor.
// Borra todos los datos guardados en $_SESSION (id_usuario, nombre, rol...).
// Después de esto, esas variables dejan de existir y el usuario queda desconectado.
// NOTA: No borra la cookie del navegador automáticamente, pero como la sesión
// del servidor ya no existe, la cookie queda inútil.

header("Location: login.php");
// Redirige al navegador a la página de login.
// El usuario ya no tiene sesión activa, así que si intenta ir a cualquier
// otra página protegida, el if(!isset($_SESSION["id_usuario"])) de cada
// página lo volverá a mandar al login.

exit();
// Detiene la ejecución del script inmediatamente tras la redirección.
// Aunque aquí no hay más código, es buena práctica incluirlo siempre
// después de header("Location:...") para evitar que algo se ejecute por error.
