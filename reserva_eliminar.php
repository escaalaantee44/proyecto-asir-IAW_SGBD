<?php
session_start();
// Inicia sesiones para leer $_SESSION y saber quién está logueado.

// ── CONTROL DE ACCESO: NIVEL 1 → ¿Está logueado? ────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
    // Sin sesión activa → redirige al login.
    // NOTA: A diferencia de los archivos de categorías y recursos,
    // aquí NO se comprueba si es admin. Las reservas las pueden
    // eliminar tanto admins como usuarios normales (con restricciones).
}

include "conexion.php";
// Conecta a la BD. El usuario MySQL dependerá del rol:
// admin → admin_app / usuario normal → usuario_app.

// ── VARIABLES DE SESIÓN ───────────────────────────────────────────────────────

$id_usuario = $_SESSION["id_usuario"];
$rol        = $_SESSION["rol"];
// Se guardan en variables locales para no repetir $_SESSION["..."] a lo largo
// del código. Hace el código más limpio y fácil de leer.


// ── VALIDACIÓN DEL PARÁMETRO ID ───────────────────────────────────────────────

if (!isset($_GET["id"])) {
    header("Location: reserva.php");
    exit();
    // Si acceden a reserva_eliminar.php sin ?id= en la URL → redirige al listado.
}

$id = (int) $_GET["id"];
// Cast a entero: convierte el valor de la URL a número entero.
// Neutraliza cualquier texto malicioso que pudieran poner en la URL.


// ── VERIFICACIÓN DE QUE LA RESERVA EXISTE ────────────────────────────────────

$sql = "SELECT * FROM reservas WHERE id_reserva = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$reserva = $result->fetch_assoc();
// Busca la reserva en la BD ANTES de intentar borrarla.
// Esto es necesario por dos razones:
//   1. Verificar que la reserva existe (evitar borrar un ID inventado)
//   2. Obtener el id_usuario de la reserva para comprobar a quién pertenece

if (!$reserva) {
    die("Reserva no encontrada");
    // Si el ID no existe en la BD → detiene el script.
    // Evita continuar con una reserva que no existe.
}


// ── CONTROL DE ACCESO: NIVEL 2 → ¿Puede este usuario eliminar ESTA reserva? ──

if ($rol != "admin" && $reserva["id_usuario"] != $id_usuario) {
    die("No puedes eliminar esta reserva");
}
/*
    Esta es la lógica de permisos más importante de este archivo.
    Combina DOS condiciones con && (Y lógico):

    Condición 1: $rol != "admin"
        → El usuario logueado NO es administrador.

    Condición 2: $reserva["id_usuario"] != $id_usuario
        → La reserva NO pertenece al usuario logueado.
        → $reserva["id_usuario"] es el ID del usuario que CREÓ la reserva (viene de la BD).
        → $id_usuario es el ID del usuario que está AHORA logueado (viene de la sesión).

    Si AMBAS condiciones son true (no es admin Y la reserva no es suya) → die().
    Esto significa:
         Admin puede borrar CUALQUIER reserva (la suya o la de cualquier usuario)
         Usuario normal puede borrar SUS PROPIAS reservas
         Usuario normal NO puede borrar reservas de OTROS usuarios

    Sin esta comprobación, cualquier usuario podría borrar reservas ajenas
    simplemente cambiando el ?id= en la URL.
*/


// ── ELIMINACIÓN EN LA BASE DE DATOS ──────────────────────────────────────────

$sql = "DELETE FROM reservas WHERE id_reserva = ?";
// Borra solo la fila con el id_reserva indicado.
// WHERE es imprescindible: sin él se borrarían TODAS las reservas.

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// Consulta preparada con el ID como entero. Protección completa contra SQL Injection.

if ($stmt->execute()) {
    header("Location: reserva.php");
    exit();
    // Eliminación correcta → vuelve al listado de reservas.

} else {
    die("Error al eliminar la reserva");
    // Si MySQL falla por cualquier motivo → muestra el error y detiene el script.
}
