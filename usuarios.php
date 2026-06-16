<?php
session_start();
// Inicia sesiones para leer $_SESSION y verificar quién está logueado.

// ── CONTROL DE ACCESO: NIVEL 1 → ¿Está logueado? ────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// ── CONTROL DE ACCESO: NIVEL 2 → ¿Es administrador? ─────────────────────────

if ($_SESSION["rol"] != "admin") {
    die("Acceso denegado");
    // Solo los admins pueden ver y gestionar la lista de usuarios.
}

include "conexion.php";
// Conecta con "admin_app" porque el rol es admin.

include "includes/header.php";
// Se incluye antes de la consulta. Sin $titulo definido, la pestaña
// mostrará el título por defecto "Sistema de Reservas" del header.php.


// ── CONSULTA A LA BASE DE DATOS ───────────────────────────────────────────────

$sql = "SELECT * FROM usuarios ORDER BY rol, nombre";
$result = $conn->query($sql);
/*
    Obtiene todos los usuarios de la BD.
    Sin WHERE porque el admin necesita ver todos.
    No usa consulta preparada porque no hay datos del usuario en la consulta.

    ORDER BY rol, nombre → ordena en dos niveles:
        1º por rol (alfabéticamente: "admin" antes que "usuario")
        2º dentro de cada rol, por nombre (A→Z)
    Resultado: primero aparecen todos los admins ordenados, luego todos
    los usuarios normales ordenados. Facilita la gestión visual.
*/
?>

<!-- ══════════════════════════════════════════════
     CABECERA: título + botón nuevo usuario
══════════════════════════════════════════════ -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <!--
    d-flex justify-content-between → título a la izquierda, botón a la derecha.
    mb-3 → margen inferior para separar de los botones de abajo.
-->
    <h2>Gestión de usuarios</h2>
    <a href="usuario_nuevo.php" class="btn btn-success">Nuevo usuario</a>
    <!-- btn-success → botón verde, igual que en recurso.php para "Nuevo recurso" -->
</div>

<a href="index.php" class="btn btn-secondary mb-3">Volver</a>
<!-- btn-secondary → botón gris para volver al panel principal. mb-3 separa de la tabla. -->


<!-- ══════════════════════════════════════════════
     TABLA DE USUARIOS
     NOTA: Mismo estilo básico que recurso.php
     (table-striped, table-dark en cabecera),
     sin card ni table-responsive como en reserva.php y categorias.php.
══════════════════════════════════════════════ -->

<table class="table table-striped table-hover">
    <!--
    table-striped → filas alternas gris/blanco para facilitar la lectura.
    table-hover   → resalta la fila al pasar el ratón por encima.
-->
    <thead class="table-dark">
        <!-- table-dark → cabecera con fondo negro y texto blanco -->
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th style="width:150px;">Acciones</th>
            <!--
                width:150px → fija el ancho de la columna de acciones para que
                los botones Editar/Eliminar no cambien de tamaño según el contenido.
            -->
        </tr>
    </thead>

    <tbody>
        <?php while ($u = $result->fetch_assoc()) { ?>
            <!--
            Bucle que recorre todos los usuarios devueltos por la consulta.
            Usa la variable $u (en vez de $row como en otros archivos), más corto.
            Cada iteración pinta una fila <tr> con los datos de un usuario.
            Se usa sintaxis { } en vez de (): y endwhile; (ambas son equivalentes en PHP).
        -->
            <tr>
                <td><?= $u["id_usuario"] ?></td>
                <!--
               
                ID numérico del usuario. Sin htmlspecialchars porque es un número.
            -->

                <td><?= htmlspecialchars($u["nombre"]) ?></td>
                <!-- Nombre del usuario. htmlspecialchars() protege contra XSS. -->

                <td><?= htmlspecialchars($u["email"]) ?></td>
                <!-- Email del usuario. htmlspecialchars() por si contuviera caracteres especiales. -->

                <td><?= $u["rol"] ?></td>
                <!--
                Rol del usuario ("admin" o "usuario").
                Sin htmlspecialchars porque los valores están controlados por la BD.
                NOTA: En reserva.php el estado usaba match() y ucfirst() para
                mostrarlo con color y mayúscula. Aquí se muestra el rol en texto plano.
                Una mejora sería añadir un badge de color como en reserva.php.
            -->

                <td>
                    <!-- Botón EDITAR -->
                    <a href="usuario_editar.php?id=<?= $u["id_usuario"] ?>"
                        class="btn btn-warning btn-sm">Editar</a>
                    <!--
                    btn-warning → botón amarillo (acción de modificación).
                    btn-sm      → tamaño pequeño para caber en la celda.
                    ?id=...     → pasa el ID del usuario por URL a usuario_editar.php.
                    Sin icono Bootstrap, solo texto (a diferencia de categorias.php
                    y reserva.php que sí tenían iconos bi-pencil).
                -->

                    <!-- Botón ELIMINAR -->
                    <a href="usuario_eliminar.php?id=<?= $u["id_usuario"] ?>"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('¿Eliminar este usuario?')">Eliminar</a>
                    <!--
                    btn-danger → botón rojo (acción destructiva).
                    onclick confirm() → diálogo de confirmación antes de eliminar.
                    Si cancela → return false → no navega a usuario_eliminar.php.
                    El mensaje es más corto que en otros archivos ("¿Eliminar este usuario?"
                    vs "¿Seguro que deseas eliminar esta categoría?"), pero cumple la función.
                -->
                </td>
            </tr>
        <?php } ?>
        <!-- Fin del bucle: se ha pintado una fila por cada usuario de la BD -->
    </tbody>
</table>

<?php include "includes/footer.php"; ?>
<!-- Cierra el container, muestra el footer y carga el JS de Bootstrap -->