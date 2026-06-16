<?php
session_start();
// Inicia las sesiones para poder leer $_SESSION y saber quién está logueado.

// ── CONTROL DE ACCESO: NIVEL 1 → ¿Está logueado? ────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
    // Si no hay sesión activa, redirige al login y detiene el script.
    // Igual que en el resto de páginas protegidas.
}





// ── CONEXIÓN Y CONSULTA A LA BASE DE DATOS ───────────────────────────────────

include "conexion.php";
// Incluye la conexión. Como $_SESSION["rol"] == "admin" en este punto,
// conexion.php usará el usuario MySQL "admin_app" con privilegios completos.

$sql = "SELECT * FROM categorias ORDER BY nombre";
// Consulta que obtiene TODAS las filas de la tabla categorias.
// ORDER BY nombre → las ordena alfabéticamente por nombre (A → Z).
// No necesita WHERE porque queremos mostrar todas las categorías.
// No usa consulta preparada porque no hay datos del usuario en esta consulta,
// así que no hay riesgo de SQL Injection.

$result = $conn->query($sql);
// Ejecuta la consulta directamente (sin prepare, porque no hay parámetros).
// $result contiene el conjunto de filas devueltas por MySQL.

$titulo = "Categorías";
include "includes/header.php";
// Define el título de la pestaña y carga navbar + Bootstrap CSS.
?>


<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
<!--
    d-flex                  → activa flexbox: los hijos se colocan en línea horizontal
    justify-content-between → separa los elementos: título a la izquierda, botón a la derecha
    align-items-center      → alinea verticalmente título y botón en el centro
    mb-4                    → margen inferior para separar del contenido de abajo
    flex-wrap               → si no caben en una línea (móvil), se ponen en filas separadas
    gap-2                   → espacio entre elementos cuando se apilan en móvil
-->
    <h2 class="mb-0 fw-bold">
        <i class="bi bi-tags me-2 text-primary"></i>Categorías
    </h2>

    <a href="categoria_nueva.php" class="btn btn-primary">
    <!--
        Enlace que lleva a la página de crear nueva categoría.
        btn btn-primary → estilo de botón azul relleno.
        Solo llega aquí quien sea admin, así que no hace falta ocultarlo.
    -->
        <i class="bi bi-plus-lg me-1"></i>Nueva categoría
        <!-- bi-plus-lg → icono de + grande, me-1 lo separa del texto -->
    </a>
</div>

<!-- ══════════════════════════════════════════════
     TABLA DE CATEGORÍAS
══════════════════════════════════════════════ -->

<div class="card">
<!-- card → tarjeta Bootstrap: fondo blanco, esquinas redondeadas, sombra suave -->

    <div class="card-body p-0">
    <!-- p-0 → elimina el padding del card-body para que la tabla llegue a los bordes
               de la tarjeta sin espacio en blanco alrededor -->

        <div class="table-responsive">
        <!-- table-responsive → si la tabla es más ancha que la pantalla (móvil),
                                 aparece scroll horizontal en vez de romper el layout -->

            <table class="table table-hover align-middle mb-0">
            <!--
                table         → estilos base Bootstrap para tablas (bordes, padding de celdas)
                table-hover   → la fila sobre la que pasa el ratón se resalta en gris claro
                align-middle  → alinea verticalmente el contenido de todas las celdas al centro
                mb-0          → elimina el margen inferior de la tabla (ya lo gestiona la card)
            -->
                <thead class="table-light">
                <!-- table-light → cabecera de la tabla con fondo gris muy claro -->
                    <tr>
                        <th>#</th>
                        <!-- Columna del ID numérico de la categoría -->

                        <th>Nombre</th>

                        <th class="d-none d-md-table-cell">Descripción</th>
                        <!--
                            d-none          → oculta esta columna en móvil (display: none)
                            d-md-table-cell → la muestra a partir de pantallas medianas (≥768px)
                            En móvil solo se ven # , Nombre y Acciones para no saturar la pantalla
                        -->

                        <th class="text-end">Acciones</th>
                        <!-- text-end → alinea el texto a la derecha, los botones irán a la derecha -->
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <!--
                        LÓGICA PHP: Bucle que recorre todas las filas devueltas por la consulta.
                        fetch_assoc() devuelve cada fila como array asociativo y avanza al siguiente.
                        Cuando no quedan filas devuelve null y el while termina.
                        Cada iteración pinta una fila <tr> de la tabla con los datos de una categoría.
                    -->
                        <tr>
                            <td class="text-muted">
                                <?php echo $row["id_categoria"]; ?>
                                <!-- ID numérico de la categoría. text-muted lo pone en gris
                                     para que sea menos prominente que el nombre. -->
                            </td>

                            <td class="fw-semibold">
                                <?php echo htmlspecialchars($row["nombre"]); ?>
                                <!-- Nombre de la categoría en seminegrita.
                                     htmlspecialchars() protege contra XSS por si el nombre
                                     contuviera caracteres como < > & -->
                            </td>

                            <td class="d-none d-md-table-cell text-muted">
                                <?php echo htmlspecialchars($row["descripcion"]); ?>
                                <!-- Descripción: oculta en móvil (d-none d-md-table-cell),
                                     visible en tablet/escritorio, en color gris (text-muted) -->
                            </td>

                            <td class="text-end">
                            <!-- text-end → los botones quedan alineados a la derecha -->

                                <!-- Botón EDITAR -->
                                <a href="categoria_editar.php?id=<?php echo $row["id_categoria"]; ?>"
                                   class="btn btn-sm btn-outline-primary me-1">
                                <!--
                                    href con ?id=... → pasa el ID de la categoría por la URL (GET).
                                    categoria_editar.php recoge ese ID con $_GET["id"] para saber
                                    qué categoría cargar en el formulario de edición.
                                    btn-sm           → botón pequeño (tamaño reducido para caber en la tabla)
                                    btn-outline-primary → borde y texto azul, sin fondo relleno
                                    me-1             → margen derecho para separar del botón eliminar
                                -->
                                    <i class="bi bi-pencil"></i>
                                    <span class="d-none d-sm-inline ms-1">Editar</span>
                                    <!--
                                        d-none d-sm-inline → en móvil solo muestra el icono (ahorra espacio).
                                        En pantallas pequeñas (sm = ≥576px) aparece también el texto "Editar".
                                        ms-1 → pequeño margen izquierdo entre icono y texto.
                                    -->
                                </a>

                                <!-- Botón ELIMINAR -->
                                <a href="categoria_eliminar.php?id=<?php echo $row["id_categoria"]; ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                                <!--
                                    href con ?id=... → igual que editar, pasa el ID por URL.
                                    btn-outline-danger → borde y texto rojo (indica acción destructiva).
                                    onclick="return confirm(...)" → antes de seguir el enlace,
                                        JavaScript muestra un diálogo de confirmación al usuario.
                                        Si el usuario pulsa "Cancelar", confirm() devuelve false,
                                        el onclick devuelve false y el navegador NO sigue el enlace.
                                        Si pulsa "Aceptar", devuelve true y sí navega a eliminar.
                                        Es una protección básica contra eliminaciones accidentales.
                                -->
                                    <i class="bi bi-trash"></i>
                                    <span class="d-none d-sm-inline ms-1">Eliminar</span>
                                    <!-- Mismo comportamiento responsive que el botón editar -->
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <!-- Fin del bucle: se ha pintado una fila por cada categoría de la BD -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
<!-- Cierra el container, muestra el footer y carga el JS de Bootstrap -->