<?php
session_start();
// Inicia sesiones para leer $_SESSION y verificar quién está logueado.

// ── CONTROL DE ACCESO: NIVEL 1 → ¿Está logueado? ────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
    // Sin sesión activa → redirige al login y detiene el script.
}

// ── CONTROL DE ACCESO: NIVEL 2 → ¿Es administrador? ─────────────────────────

if ($_SESSION["rol"] != "admin") {
    die("Acceso denegado");
    // Solo los admins pueden eliminar categorías.
    // Un usuario normal que acceda a esta URL directamente verá "Acceso denegado".
}

include "conexion.php";
// Conecta a la BD con "admin_app" (privilegios completos) porque el rol es admin.
// admin_app tiene permiso de DELETE, que usuario_app no tiene.


// ── VALIDACIÓN DEL PARÁMETRO ID ───────────────────────────────────────────────

if (!isset($_GET["id"])) {
    header("Location: categorias.php");
    exit();
    // Si alguien accede a categoria_eliminar.php sin el ?id= en la URL
    // (directamente, sin venir del botón eliminar), lo manda al listado.
    // Evita un error PHP al intentar usar una variable que no existe.
}

$id = (int) $_GET["id"];
// (int) es un "cast" o conversión forzada a número entero.
// SEGURIDAD: aunque $_GET["id"] fuera "3; DROP TABLE categorias" (ataque),
// al convertirlo a entero quedaría simplemente 3.
// Es una capa extra de protección además de la consulta preparada de abajo.
// Los IDs siempre son números enteros positivos, nunca texto.


// ── ELIMINACIÓN EN LA BASE DE DATOS ──────────────────────────────────────────

$sql = "DELETE FROM categorias WHERE id_categoria = ?";
// Consulta que elimina la fila de la tabla categorias cuyo id_categoria coincida.
// El ? es el marcador de posición seguro (consulta preparada).
// WHERE es CRÍTICO: sin él se borrarían TODAS las categorías de la tabla.

$stmt = $conn->prepare($sql);
// Prepara la consulta: MySQL la analiza antes de recibir el valor real del ID.

$stmt->bind_param("i", $id);
// Vincula la variable $id al marcador ?.
// "i" → el tipo de dato es integer (número entero).
// Diferencia con los anteriores que usaban "s" (string):
// aquí el ID es un número, no texto.

if ($stmt->execute()) {
    // execute() devuelve true si el DELETE se ejecutó correctamente.

    header("Location: categorias.php");
    exit();
    // Eliminación correcta → vuelve al listado de categorías.
    // El usuario verá la tabla sin la categoría eliminada.

} else {
    die("No se pudo eliminar la categoría. Es posible que tenga recursos asociados.");
    // El DELETE puede fallar si la categoría tiene recursos vinculados en la tabla RECURSOS.
    // Esto ocurre porque en la BD hay una FOREIGN KEY (clave foránea):
    // RECURSOS.id_categoria referencia a CATEGORIAS.id_categoria.
    // MySQL impide borrar una categoría que todavía tiene recursos asociados
    // para mantener la integridad referencial de los datos.
    // En una app más pulida se redirigría con un mensaje de error más amigable.
}