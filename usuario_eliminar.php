<?php
session_start();
// Inicia sesiones para leer $_SESSION y verificar quién está logueado.

// ── CONTROL DE ACCESO (doble en una línea) ────────────────────────────────────

if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] != "admin") {
    header("Location: login.php");
    exit();
}
/*
    Igual que en usuario_editar.php, combina dos comprobaciones con || (O lógico):
    - !isset($_SESSION["id_usuario"]) → no está logueado
    - $_SESSION["rol"] != "admin"     → no es admin
    Si cualquiera es true → redirige al login.
    Solo llega al código siguiente quien esté logueado Y sea admin.
*/

include "conexion.php";
// Conecta con "admin_app" porque el rol es admin.
// admin_app tiene permisos de DELETE que usuario_app no tiene.


// ── OBTENCIÓN DEL ID ──────────────────────────────────────────────────────────

$id = $_GET["id"];



// ── ELIMINACIÓN EN LA BASE DE DATOS ──────────────────────────────────────────

$sql = "DELETE FROM usuarios WHERE id_usuario = ?";
// Borra la fila del usuario cuyo id_usuario coincida con $id.
// WHERE es CRÍTICO: sin él se borrarían TODOS los usuarios de la tabla.

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// "i" → integer. El ID del usuario a eliminar.

$stmt->execute();


header("Location: usuarios.php");
exit();
// Redirige al listado de usuarios independientemente de si el DELETE
