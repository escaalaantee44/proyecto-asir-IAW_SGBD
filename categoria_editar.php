<?php
session_start();
// Inicia sesiones para leer $_SESSION y verificar quién está logueado.


if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}


if ($_SESSION["rol"] != "admin") {
    die("Acceso denegado");
}

include "conexion.php";
// Conecta con "admin_app" porque el rol es admin.



if (!isset($_GET["id"])) {
    header("Location: categorias.php");
    exit();
    // Si acceden a categoria_editar.php sin ?id= en la URL → redirige al listado.
}

$id = (int) $_GET["id"];
// Cast a entero: convierte el valor de la URL a número, neutralizando
// cualquier texto malicioso que pudieran intentar inyectar en la URL.



$sql = "SELECT * FROM categorias WHERE id_categoria = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$categoria = $result->fetch_assoc();
// Busca en la BD la categoría cuyo ID llegó por la URL.
// fetch_assoc() devuelve la fila como array asociativo:
// $categoria["nombre"], $categoria["descripcion"], etc.
// Esta consulta sirve para DOS cosas:
//   1. Verificar que la categoría existe (si no, $categoria será null)
//   2. Obtener los datos actuales para pre-rellenar el formulario

if (!$categoria) {
    die("Categoría no encontrada");
    // Si el ID no existe en la BD (alguien puso ?id=9999 inventado),
    // detiene el script con un mensaje de error.
    // Evita mostrar un formulario vacío o generar errores PHP.
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Solo entra aquí cuando el formulario se envía.
    // La carga inicial de la página (GET) muestra el formulario pre-relleno.

    $nombre      = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    // trim() elimina espacios sobrantes al principio y al final.


    if ($nombre == "") {
        $error = "El nombre es obligatorio";
        // Segunda capa de validación (la primera es el required del HTML).
        // Protege ante envíos que salten la validación del navegador.

    } else {


        $sql = "UPDATE categorias SET nombre = ?, descripcion = ? WHERE id_categoria = ?";
        // Actualiza solo la fila cuyo id_categoria coincida con $id.
        // SET nombre = ?, descripcion = ? → los dos campos que se pueden editar.
        // WHERE id_categoria = ? → CRÍTICO: sin WHERE se actualizarían TODAS las categorías.
        // Tres marcadores ? para tres valores.

        $stmt = $conn->prepare($sql);
        // Prepara la consulta de forma segura.

        $stmt->bind_param("ssi", $nombre, $descripcion, $id);
        // Vincula los tres valores a los tres marcadores ?:
        //   "s" → $nombre      (string)
        //   "s" → $descripcion (string)
        //   "i" → $id          (integer)
        // El orden IMPORTA: debe coincidir exactamente con el orden de los ? en el SQL.
        // "ssi" = string, string, integer → los tipos de cada variable en ese orden.

        if ($stmt->execute()) {
            header("Location: categorias.php");
            exit();
            // Actualización correcta → vuelve al listado donde se verán los nuevos datos.

        } else {
            $error = "Error al actualizar la categoría";
            // Si MySQL falla por cualquier razón, muestra el error en el formulario.
        }
    }
}

$titulo = "Editar categoría";
include "includes/header.php";
// Título de pestaña y carga del navbar + Bootstrap CSS.
?>

<!-- ── BOTÓN VOLVER ── -->
<div class="mb-4">
    <a href="categorias.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>



<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
    <!--
        Mismo layout responsive que categoria_nueva.php:
        móvil → ancho completo / tablet → 7/12 / escritorio → 5/12 centrado
    -->
        <div class="card">
            <div class="card-header bg-warning text-dark py-3">
            <!--
                bg-warning  → fondo AMARILLO (diferencia visualmente editar de crear).
                              En categoria_nueva.php era bg-primary (azul).
                              El amarillo indica "precaución / modificación".
                text-dark   → texto oscuro (negro) porque el amarillo es claro,
                              al contrario que bg-primary donde se usaba text-white.
            -->
                <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Editar categoría</h5>
                <!-- bi-pencil → icono de lápiz, representa edición -->
            </div>

            <div class="card-body p-4">

                <!-- ── MENSAJE DE ERROR ── -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- ── FORMULARIO ── -->
                <form method="POST">
                <!-- Sin action → se envía a la misma URL, que incluye el ?id=
                     en la barra del navegador, así el PHP de arriba sabe qué categoría actualizar -->

                    <!-- Campo Nombre (pre-relleno con el valor actual de la BD) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nombre" class="form-control"
                               value="<?php echo htmlspecialchars($categoria["nombre"]); ?>"
                               required>
                        <!--
                            value="..." → PRE-RELLENA el input con el nombre actual de la BD.
                            Esta es la diferencia clave con el formulario de nueva categoría,
                            donde el input estaba vacío.
                            htmlspecialchars() protege contra XSS: si el nombre guardado en BD
                            contuviera comillas (") o <, no rompería el HTML del atributo value.
                            Ejemplo sin protección: value="Sala "A"" → rompe el HTML.
                            Con protección:         value="Sala &quot;A&quot;" → se muestra bien.
                        -->
                    </div>

                    <!-- Campo Descripción (pre-relleno con el valor actual de la BD) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($categoria["descripcion"]); ?></textarea>
                        <!--
                            En textarea el contenido pre-relleno va ENTRE las etiquetas
                            (no en un atributo value como en los input).
                            Por eso el PHP está entre <textarea> y </textarea>.
                            Sin espacios extra: cualquier espacio/salto de línea entre las
                            etiquetas aparecería en el textarea como contenido.
                        -->
                    </div>

                    <!-- Botón Guardar -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning py-2 fw-semibold">
                        <!--
                            btn-warning → botón AMARILLO, coherente con la cabecera de la tarjeta.
                                          Refuerza visualmente que esta acción es de modificación.
                            fw-semibold → texto en seminegrita para que destaque
                        -->
                            <i class="bi bi-check-lg me-1"></i>Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>