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
    // Solo los admins pueden gestionar recursos.
}

include "conexion.php";
// Conecta con "admin_app" porque el rol es admin.

include "includes/header.php";
// NOTA: A diferencia de otros archivos, aquí se incluye el header ANTES de la consulta.
// No hay $titulo definido, así que la pestaña mostrará el título por defecto
// ('Sistema de Reservas') definido en header.php con el operador ternario.


// ── CONSULTA CON JOIN ─────────────────────────────────────────────────────────

$sql = "SELECT r.*, c.nombre AS categoria
        FROM recursos r
        JOIN categorias c ON r.id_categoria = c.id_categoria
        ORDER BY c.nombre, r.nombre";
/*
    Esta consulta une dos tablas para obtener los datos completos de cada recurso:

    SELECT r.*              → trae TODAS las columnas de la tabla recursos
                              (id_recurso, nombre, capacidad, estado, id_categoria...)
    c.nombre AS categoria   → trae el nombre de la categoría y lo renombra como "categoria"
                              para distinguirlo del nombre del recurso (ambos se llaman "nombre")
                              En PHP se accede como $row["categoria"]

    FROM recursos r         → tabla principal, con alias "r" para escribir menos
    JOIN categorias c       → une la tabla categorias (alias "c")
    ON r.id_categoria = c.id_categoria
                            → condición de unión: une cada recurso con su categoría
                              usando la clave foránea id_categoria que comparten ambas tablas

    ORDER BY c.nombre, r.nombre
                            → ordena primero por nombre de categoría (A→Z)
                              y dentro de cada categoría, por nombre de recurso (A→Z)
                              Resultado: los recursos aparecen agrupados visualmente por categoría

    Sin el JOIN tendríamos solo el id_categoria (un número), no el nombre legible.
    El JOIN evita hacer una segunda consulta por cada recurso para buscar su categoría.
*/

$result = $conn->query($sql);
// Ejecuta la consulta directamente (sin prepare) porque no hay datos del usuario,
// así que no hay riesgo de SQL Injection.
?>

<!-- ══════════════════════════════════════════════
     CABECERA DE LA PÁGINA
══════════════════════════════════════════════ -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <!--
    d-flex                  → flexbox: título y botón en línea horizontal
    justify-content-between → título a la izquierda, botón a la derecha
    align-items-center      → alineados verticalmente al centro
    mb-3                    → margen inferior para separar de la tabla
-->
    <h2>Recursos</h2>
    <a href="recurso_nuevo.php" class="btn btn-success">Nuevo recurso</a>
    <!--
        btn-success → botón VERDE (diferente al azul de categorías).
                      El verde indica "crear / añadir algo nuevo".
    -->
</div>

<a href="index.php" class="btn btn-secondary mb-3">Volver al panel</a>
<!--
    btn-secondary → botón gris (acción secundaria, menos importante).
    mb-3          → margen inferior para separar del encabezado de la tabla.
    NOTA: En categorias.php este botón no existía. Aquí se navega
    directamente al panel en vez de a una página padre de recursos.
-->

<!-- ══════════════════════════════════════════════
     TABLA DE RECURSOS
     NOTA: Esta tabla usa estilos Bootstrap más básicos que categorias.php.
     No tiene card, ni table-responsive, ni columnas ocultas en móvil.
══════════════════════════════════════════════ -->

<table class="table table-striped table-hover">
    <!--
    table          → estilos base Bootstrap para tablas
    table-striped  → filas alternas con fondo gris claro / blanco (efecto zebra).
                     Facilita la lectura cuando hay muchas filas.
                     categorias.php usaba table-hover sin striped.
    table-hover    → la fila sobre la que pasa el ratón se resalta
-->
    <thead class="table-dark">
        <!--
        table-dark → cabecera con fondo NEGRO y texto blanco.
                     categorias.php usaba table-light (fondo gris claro).
                     Son dos estilos válidos de Bootstrap para cabeceras de tabla.
    -->
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <!-- El nombre de la categoría viene del JOIN: c.nombre AS categoria -->
            <th>Capacidad</th>
            
            <th style="width: 150px;">Acciones</th>
            <!--
                style="width: 150px;" → fija el ancho de la columna de acciones
                para que los botones Editar/Eliminar no cambien de tamaño
                según el contenido de las otras columnas.
                Se usa style inline porque es un ajuste puntual de maquetación.
            -->
        </tr>
    </thead>

    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <!--
            Bucle que recorre todas las filas del resultado de la consulta JOIN.
            Aquí se usa la sintaxis { } en vez de (): y endwhile; que se usaba en categorias.php.
            Ambas son equivalentes en PHP, solo es una diferencia de estilo.
            fetch_assoc() devuelve cada fila como array y avanza al siguiente.
            Cuando no quedan filas devuelve null y el while termina.
        -->
            <tr>
                <td><?php echo $row["id_recurso"]; ?></td>
                <!-- ID numérico del recurso. Sin htmlspecialchars porque es un número. -->

                <td><?php echo htmlspecialchars($row["nombre"]); ?></td>
                <!-- Nombre del recurso (de la tabla recursos). htmlspecialchars evita XSS. -->

                <td><?php echo htmlspecialchars($row["categoria"]); ?></td>
                <!--
                Nombre de la categoría. Viene del JOIN: c.nombre AS categoria.
                Sin el AS, $row["nombre"] sería ambiguo (recursos.nombre o categorias.nombre).
                El alias "categoria" hace que sea $row["categoria"], sin confusión.
            -->

                <td><?php echo $row["capacidad"]; ?></td>
                <!-- Número de personas/unidades que admite el recurso. Es un número, sin XSS. -->

             

                <td>
                    <!-- Botón EDITAR -->
                    <a href="recurso_editar.php?id=<?php echo $row["id_recurso"]; ?>"
                        class="btn btn-warning btn-sm">Editar</a>
                    <!--
                    btn-warning → botón amarillo (acción de modificación).
                    btn-sm      → tamaño pequeño para caber dentro de la celda.
                    ?id=...     → pasa el ID del recurso por URL a la página de edición.
                    NOTA: A diferencia de categorias.php, aquí no hay icono Bootstrap,
                    solo texto. Es una diferencia de estilo entre los dos archivos.
                -->

                    <!-- Botón ELIMINAR -->
                    <a href="recurso_eliminar.php?id=<?php echo $row["id_recurso"]; ?>"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('¿Seguro que deseas eliminar este recurso?');">
                        Eliminar
                    </a>
                    <!--
                    btn-danger  → botón rojo (acción destructiva / irreversible).
                    onclick confirm() → JavaScript pide confirmación antes de navegar.
                    Si el usuario cancela, return false detiene la navegación.
                    Si acepta, return true y se va a recurso_eliminar.php con el ID.
                -->
                </td>
            </tr>
        <?php } ?>
        <!-- Cierra el while: se ha pintado una fila por cada recurso de la BD -->
    </tbody>
</table>

<?php include "includes/footer.php"; ?>
<!-- Cierra el container, muestra el footer y carga el JS de Bootstrap -->