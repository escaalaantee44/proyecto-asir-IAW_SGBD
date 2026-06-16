<?php
session_start();
// Inicia sesiones para leer $_SESSION y saber quién está logueado.

// ── CONTROL DE ACCESO ─────────────────────────────────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
    // Sin sesión activa → redirige al login.
    // No se comprueba rol aquí: tanto admins como usuarios normales
    // pueden ver esta página (cada uno verá cosas distintas).
}

include "conexion.php";

$id_usuario = $_SESSION["id_usuario"];
$rol        = $_SESSION["rol"];
// Variables locales para no repetir $_SESSION["..."] a lo largo del código.


// ── CONSULTA DIFERENTE SEGÚN EL ROL ───────────────────────────────────────────

if ($rol == "admin") {

    $sql = "SELECT r.*, u.nombre AS usuario, rc.nombre AS recurso
            FROM reservas r
            JOIN usuarios u  ON r.id_usuario  = u.id_usuario
            JOIN recursos rc ON r.id_recurso  = rc.id_recurso
            ORDER BY r.fecha DESC, r.hora_inicio DESC";
    $stmt = $conn->prepare($sql);
    /*
        El admin ve TODAS las reservas de todos los usuarios.

        SELECT r.*              → todas las columnas de la tabla reservas
        u.nombre AS usuario     → nombre del usuario que hizo la reserva
                                  (alias "usuario" para distinguirlo de otros "nombre")
        rc.nombre AS recurso    → nombre del recurso reservado
                                  (alias "recurso" para distinguirlo de otros "nombre")

        JOIN usuarios u         → une la tabla usuarios para obtener el nombre del usuario
        JOIN recursos rc        → une la tabla recursos para obtener el nombre del recurso

        Sin JOINs solo tendríamos id_usuario e id_recurso (números), no nombres legibles.
        Con dos JOINs obtenemos todo en una sola consulta.

        ORDER BY r.fecha DESC, r.hora_inicio DESC
                                → las reservas más recientes aparecen primero.
                                  DESC = descendente (de más nuevo a más antiguo).
                                  Primero ordena por fecha, y si hay varias reservas
                                  el mismo día, las ordena por hora de inicio.

        Sin WHERE porque el admin ve todas las reservas.
        No necesita bind_param porque no hay parámetros variables.
    */
} else {

    $sql = "SELECT r.*, u.nombre AS usuario, rc.nombre AS recurso
            FROM reservas r
            JOIN usuarios u  ON r.id_usuario  = u.id_usuario
            JOIN recursos rc ON r.id_recurso  = rc.id_recurso
            WHERE r.id_usuario = ?
            ORDER BY r.fecha DESC, r.hora_inicio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    /*
        El usuario normal solo ve SUS PROPIAS reservas.

        La consulta es idéntica a la del admin EXCEPTO por el WHERE:
        WHERE r.id_usuario = ? → filtra solo las reservas del usuario logueado.

        El ? se vincula con bind_param("i", $id_usuario):
        "i" → integer, el ID del usuario de la sesión.

        Aunque un usuario manipulara la URL o la sesión, la BD solo
        devolvería sus propias reservas gracias a este filtro.
    */
}

$stmt->execute();
$result = $stmt->get_result();
// Se ejecuta fuera del if/else porque en ambos casos el proceso es el mismo.
// $result contiene el conjunto de reservas a mostrar (todas o solo las del usuario).

$titulo = "Reservas";
include "includes/header.php";
?>

<!-- ══════════════════════════════════════════════
     CABECERA: título + botón nueva reserva
══════════════════════════════════════════════ -->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <!--
    d-flex justify-content-between → título a la izquierda, botón a la derecha.
    flex-wrap gap-2 → en móvil se apilan verticalmente con espacio entre ellos.
-->
    <h2 class="mb-0 fw-bold">
        <i class="bi bi-calendar3 me-2 text-primary"></i>Reservas
    </h2>
    <a href="reserva_nueva.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nueva reserva
    </a>
</div>

<!-- ══════════════════════════════════════════════
     TABLA DE RESERVAS
══════════════════════════════════════════════ -->

<div class="card">
    <div class="card-body p-0">
        <!-- p-0 → la tabla llega al borde de la tarjeta sin padding extra -->
        <div class="table-responsive">
            <!-- table-responsive → scroll horizontal en móvil si la tabla no cabe -->

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>

                        <th class="d-none d-md-table-cell">Usuario</th>
                        <!--
                            Oculta en móvil, visible desde tablet (md = ≥768px).
                            En móvil el espacio es limitado y el usuario puede
                            inferir de quién es la reserva por el contexto.
                            Los admins ven esta columna para saber quién hizo cada reserva.
                            Los usuarios normales también la ven (siempre será su propio nombre).
                        -->

                        <th>Recurso</th>
                        <th>Fecha</th>

                        <th class="d-none d-sm-table-cell">Horario</th>
                        <!--
                            Oculta en móvil pequeño, visible desde sm (≥576px).
                            Contiene "HH:MM – HH:MM", que ocupa bastante espacio horizontal.
                        -->


                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <!-- Bucle: una iteración = una fila de la tabla = una reserva -->

                        <tr>
                            <td class="text-muted">
                                <?php echo $row["id_reserva"]; ?>
                                <!-- ID numérico de la reserva en gris (menos prominente) -->
                            </td>

                            <td class="d-none d-md-table-cell">
                                <?php echo htmlspecialchars($row["usuario"]); ?>
                                <!--
                                    Nombre del usuario (viene del JOIN con la tabla usuarios).
                                    alias "usuario" definido en el SELECT como u.nombre AS usuario.
                                    htmlspecialchars() protege contra XSS.
                                    Oculto en móvil (misma clase que el <th>).
                                -->
                            </td>

                            <td class="fw-semibold">
                                <?php echo htmlspecialchars($row["recurso"]); ?>
                                <!--
                                    Nombre del recurso (viene del JOIN con la tabla recursos).
                                    alias "recurso" definido como rc.nombre AS recurso.
                                    En seminegrita porque es la información más importante de la fila.
                                -->
                            </td>

                            <td><?php echo $row["fecha"]; ?></td>
                            <!--
                                Fecha de la reserva en formato YYYY-MM-DD (como viene de MySQL).
                                En una mejora futura se podría formatear con date() de PHP
                                para mostrarlo como DD/MM/YYYY.
                            -->

                            <td class="d-none d-sm-table-cell text-muted"
                                style="font-size:.85rem">
                                <!--
                                Oculto en móvil pequeño. text-muted → color gris.
                                font-size:.85rem → texto ligeramente más pequeño para que quepa.
                                style inline porque es un ajuste puntual de este elemento.
                            -->
                                <?php echo substr($row["hora_inicio"], 0, 5); ?>
                                –
                                <?php echo substr($row["hora_fin"], 0, 5); ?>
                                <!--
                                    substr($hora, 0, 5) → recorta los primeros 5 caracteres.
                                    MySQL guarda las horas como "HH:MM:SS" (con segundos).
                                    substr(..., 0, 5) elimina los ":SS" del final y muestra solo "HH:MM".
                                    Resultado en pantalla: "09:00 – 10:30"
                                -->
                            </td>


                            <td class="text-end">
                                <?php if ($rol == "admin" || $row["id_usuario"] == $id_usuario): ?>
                                    <!--
                                    LÓGICA DE VISIBILIDAD: Los botones solo aparecen si:
                                        El usuario es admin (puede editar/eliminar cualquiera)
                                        La reserva pertenece al usuario logueado (es suya)
                                    No aparecen si la reserva es de otro usuario normal.

                                   
                                -->
                                    <a href="reserva_editar.php?id=<?php echo $row["id_reserva"]; ?>"
                                        class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-sm-inline ms-1">Editar</span>
                                        <!--
                                            En móvil solo el icono (ahorra espacio).
                                            En sm+ aparece también el texto "Editar".
                                        -->
                                    </a>
                                    <a href="reserva_eliminar.php?id=<?php echo $row["id_reserva"]; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Seguro que deseas cancelar esta reserva?');">
                                        <i class="bi bi-trash"></i>
                                        <span class="d-none d-sm-inline ms-1">Eliminar</span>
                                    </a>
                                    <!--
                                        onclick confirm() → diálogo de confirmación antes de eliminar.
                                        Si cancela → return false → no navega a reserva_eliminar.php.
                                    -->
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>