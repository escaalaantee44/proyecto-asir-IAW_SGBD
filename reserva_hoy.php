<?php
session_start();
// Inicia el sistema de sesiones de PHP.
// DEBE ser la primera línea antes de cualquier HTML o echo.

// ── CONTROL DE ACCESO ─────────────────────────────────────────────────────────
// Se comprueba ANTES de cualquier include para que, si no hay sesión,
// el redirect funcione limpio sin haber imprimido HTML.

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
    // exit() detiene el script inmediatamente tras enviar el redirect.
    // Sin él, el código de abajo seguiría ejecutándose.
}

// ── VARIABLES DE SESIÓN ───────────────────────────────────────────────────────

$id_usuario = $_SESSION["id_usuario"];
$rol        = $_SESSION["rol"];
// Se guardan en variables cortas para no repetir $_SESSION["..."] en toda la página.

// ── CONEXIÓN Y QUERY ──────────────────────────────────────────────────────────

include "conexion.php";
// Se incluye DESPUÉS del control de acceso: si no hay sesión ya redirigimos
// y evitamos abrir conexiones innecesarias a la BD.

if ($rol == "admin") {
    // Admin → ve TODAS las reservas de hoy, de todos los usuarios.
    $sql  = "SELECT *
             FROM reservas_hoy
             ORDER BY fecha DESC, hora_inicio DESC";
    $stmt = $conn->prepare($sql);
} else {
    // Usuario normal → solo ve SUS PROPIAS reservas de hoy.
    // El WHERE filtra por id_usuario para que, aunque manipule la sesión,
    // la BD nunca devuelva reservas ajenas.
    $sql  = "SELECT *
             FROM reservas_hoy
             WHERE id_usuario = ?
             ORDER BY fecha DESC, hora_inicio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    // "i" → integer, vincula el ID del usuario logueado al placeholder ?
}

$stmt->execute();
$result = $stmt->get_result();

// ── TÍTULO Y CABECERA ─────────────────────────────────────────────────────────

$titulo = "Reservas de hoy";
include "includes/header.php";
// Se incluye aquí, con sesión ya verificada y datos ya listos.
?>

<!-- ══════════════════════════════════════════════
     CABECERA: botón volver + título + nueva reserva
══════════════════════════════════════════════ -->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <!--
        d-flex justify-content-between → elementos repartidos: izquierda y derecha.
        align-items-center → alineados verticalmente al centro.
        flex-wrap gap-2 → en móvil se apilan con espacio entre ellos.
    -->

    <!-- Grupo izquierdo: botón Volver + título -->
    <div class="d-flex align-items-center gap-3">
        <a href="index.php" class="btn btn-outline-secondary">
            <!--
                btn-outline-secondary → botón discreto con borde gris.
                Menos prominente que btn-primary; es una acción de navegación,
                no la acción principal de la página.
            -->
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>

        <h2 class="mb-0 fw-bold">
            <!-- mb-0 → elimina el margen inferior del h2 para que quede alineado con el botón -->
            <i class="bi bi-calendar-check me-2 text-primary"></i>Reservas de hoy
            <!--
                bi-calendar-check → icono de calendario con marca (distingue esta página
                de la de todas las reservas, que usa bi-calendar3)
                me-2              → margen derecho entre icono y texto
                text-primary      → color azul de Bootstrap
            -->
        </h2>
    </div>




    <!-- ══════════════════════════════════════════════
     TABLA DE RESERVAS DE HOY
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
                            Oculta en móvil, visible desde tablet (md = ≥768 px).
                            Los admins la usan para saber quién hizo cada reserva.
                            Los usuarios normales siempre verán su propio nombre.
                        -->

                            <th>Recurso</th>
                            <th>Fecha</th>

                            <th class="d-none d-sm-table-cell">Horario</th>
                            <!--
                            Oculta en móvil pequeño, visible desde sm (≥576 px).
                            "HH:MM – HH:MM" ocupa bastante espacio horizontal.
                        -->

                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <!-- Si no hay reservas hoy, se muestra un mensaje en lugar de la tabla vacía -->
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                    No hay reservas para hoy.
                                </td>
                            </tr>

                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <!-- Bucle: una iteración = una fila de la tabla = una reserva -->

                                <tr>
                                    <td class="text-muted">
                                        <?php echo $row["id_reserva"]; ?>
                                        <!-- ID numérico en gris, menos prominente -->
                                    </td>

                                    <td class="d-none d-md-table-cell">
                                        <?php echo htmlspecialchars($row["usuario"]); ?>
                                        <!--
                                        Nombre del usuario (alias del JOIN en la vista).
                                        htmlspecialchars() protege contra XSS.
                                        Oculto en móvil (misma clase que el <th>).
                                    -->
                                    </td>

                                    <td class="fw-semibold">
                                        <?php echo htmlspecialchars($row["recurso"]); ?>
                                        <!--
                                        Nombre del recurso (alias del JOIN en la vista).
                                        fw-semibold → seminegrita, dato más relevante de la fila.
                                    -->
                                    </td>

                                    <td><?php echo date("d/m/Y", strtotime($row["fecha"])); ?></td>
                                    <!--
                                    MySQL devuelve la fecha como "YYYY-MM-DD".
                                    strtotime() la convierte a timestamp Unix.
                                    date("d/m/Y") la reformatea a "DD/MM/YYYY" (más legible para el usuario).
                                -->

                                    <td class="d-none d-sm-table-cell text-muted" style="font-size:.85rem">
                                        <!--
                                        Oculto en móvil pequeño. text-muted → gris.
                                        font-size:.85rem → ligeramente más pequeño para que quepa.
                                    -->
                                        <?php echo substr($row["hora_inicio"], 0, 5); ?>
                                        –
                                        <?php echo substr($row["hora_fin"], 0, 5); ?>
                                        <!--
                                        MySQL guarda las horas como "HH:MM:SS".
                                        substr(..., 0, 5) elimina los ":SS" finales → muestra "HH:MM".
                                        Resultado: "09:00 – 10:30"
                                    -->
                                    </td>

                                    <td class="text-end">
                                        <?php if ($rol == "admin" || $row["id_usuario"] == $id_usuario): ?>
                                            <!--
                                        LÓGICA DE VISIBILIDAD DE ACCIONES:
                                        - Admin → puede editar/eliminar cualquier reserva.
                                        - Usuario normal → solo las suyas (compara id_usuario
                                          de la fila con el ID de sesión, no el nombre, para
                                          evitar colisiones entre usuarios con el mismo nombre).
                                    -->
                                            <a href="reserva_editar.php?id=<?php echo $row["id_reserva"]; ?>"
                                                class="btn btn-sm btn-outline-primary me-1">
                                                <i class="bi bi-pencil"></i>
                                                <span class="d-none d-sm-inline ms-1">Editar</span>
                                                <!--
                                                En móvil solo el icono (ahorra espacio).
                                                En sm+ también el texto "Editar".
                                            -->
                                            </a>
                                            <a href="reserva_eliminar.php?id=<?php echo $row["id_reserva"]; ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('¿Seguro que deseas cancelar esta reserva?');">
                                                <i class="bi bi-trash"></i>
                                                <span class="d-none d-sm-inline ms-1">Eliminar</span>
                                            </a>
                                            <!--
                                            onclick confirm() → diálogo de confirmación nativo.
                                            Si el usuario cancela → return false → no navega
                                            a reserva_eliminar.php y la reserva se conserva.
                                        -->
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>
    <!--
    Inserta el footer.php: cierra el </div> del container, muestra el pie de página
    con el copyright, carga el JS de Bootstrap y cierra </body> y </html>
-->