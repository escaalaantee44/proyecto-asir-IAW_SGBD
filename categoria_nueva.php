<?php
session_start();
// Inicia sesiones para leer $_SESSION y saber quién está logueado.


if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
    // Sin sesión activa → redirige al login y detiene el script.
}


if ($_SESSION["rol"] != "admin") {
    die("Acceso denegado");
    // Solo los admins pueden crear categorías.
    // Si un usuario normal accede a esta URL directamente, ve "Acceso denegado"
    // y el script se detiene. La BD tampoco lo dejaría actuar (admin_app vs usuario_app).
}

include "conexion.php";
// Conecta a la BD con "admin_app" (privilegios completos) porque el rol es admin.


// ── PROCESAMIENTO DEL FORMULARIO ──────────────────────────────────────────────

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Solo entra aquí cuando el formulario se ha enviado.
    // La primera carga de la página es GET → muestra el formulario vacío.

    $nombre      = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    // trim() elimina espacios al principio y al final.
    // Evita que alguien guarde una categoría con nombre "   " (solo espacios).

    // ── VALIDACIÓN EN SERVIDOR ────────────────────────────────────────────────

    if ($nombre == "") {
        $error = "El nombre es obligatorio";
        // Aunque el HTML tiene required (validación en navegador),
        // aquí se valida también en el servidor porque alguien podría
        // enviar el formulario sin pasar por el navegador (ej: con curl o Postman).
        // La validación del servidor es la que realmente importa.

    } else {

        // ── INSERCIÓN EN LA BASE DE DATOS ─────────────────────────────────────

        $sql = "INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)";
        // Consulta de inserción con dos marcadores ? para evitar SQL Injection.
        // No se especifica id_categoria porque es AUTO_INCREMENT: MySQL lo asigna solo.

        $stmt = $conn->prepare($sql);
        // Prepara la consulta: MySQL la analiza y la deja lista para recibir los valores.

        $stmt->bind_param("ss", $nombre, $descripcion);
        // Vincula las dos variables a los dos marcadores ?.
        // "ss" → ambos son strings (s = string).
        // Primer "s" → $nombre, Segundo "s" → $descripcion.

        if ($stmt->execute()) {
            // execute() devuelve true si la inserción fue correcta, false si hubo error.

            header("Location: categorias.php");
            exit();
            // Inserción correcta → redirige a la lista de categorías.
            // El usuario verá la nueva categoría en la tabla.
            // exit() detiene el script para que no se ejecute nada más.

        } else {
            $error = "Error al insertar la categoría";
            // Si MySQL falla (ej: nombre duplicado si hubiera UNIQUE, BD caída...)
            // guarda el error para mostrarlo en el formulario.
        }
    }
}

$titulo = "Nueva categoría";
include "includes/header.php";
// Título de pestaña y carga de navbar + Bootstrap CSS.
?>

<!-- ── BOTÓN VOLVER ── -->
<div class="mb-4">
    <a href="categorias.php" class="btn btn-outline-secondary btn-sm">
    <!--
        btn-outline-secondary → botón con borde gris (acción secundaria, menos prominente).
        btn-sm                → tamaño pequeño, apropiado para un botón de navegación.
        Lleva de vuelta al listado sin hacer ninguna acción.
    -->
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<!-- ══════════════════════════════════════════════
     FORMULARIO CENTRADO
══════════════════════════════════════════════ -->

<div class="row justify-content-center">
<!-- justify-content-center → centra la columna horizontalmente en la fila -->

    <div class="col-12 col-md-7 col-lg-5">
    <!--
        col-12  → móvil: ancho completo
        col-md-7 → tablet: 7/12 del ancho (formulario más estrecho que la pantalla)
        col-lg-5 → escritorio: 5/12 del ancho (formulario compacto y centrado)
        El formulario de creación no necesita ocupar toda la pantalla.
    -->
        <div class="card">
            <div class="card-header bg-primary text-white py-3">
            <!--
                bg-primary  → fondo azul en la cabecera de la tarjeta
                text-white  → texto blanco sobre fondo azul
                py-3        → padding vertical para que la cabecera respire
            -->
                <h5 class="mb-0"><i class="bi bi-tag me-2"></i>Nueva categoría</h5>
                <!-- mb-0 elimina el margen inferior del h5 dentro de la cabecera -->
            </div>

            <div class="card-body p-4">
            <!-- p-4 → padding generoso en todos los lados del formulario -->

                <!-- ── MENSAJE DE ERROR ── -->
                <?php if (isset($error)): ?>
                <!--
                    Solo aparece si el PHP de arriba definió $error.
                    Casos posibles: nombre vacío o error al insertar en BD.
                -->
                    <div class="alert alert-danger d-flex align-items-center">
                    <!--
                        alert-danger        → fondo rojo para errores
                        d-flex align-items-center → icono y texto alineados verticalmente
                    -->
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <!-- htmlspecialchars() por seguridad aunque el texto lo generamos nosotros -->
                    </div>
                <?php endif; ?>

                <!-- ── FORMULARIO ── -->
                <form method="POST">
                <!-- Sin action="..." → se envía a la misma página (categoria_nueva.php) por POST -->

                    <!-- Campo Nombre -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nombre <span class="text-danger">*</span>
                            <!--
                                El asterisco rojo indica visualmente que el campo es obligatorio.
                                text-danger → color rojo de Bootstrap.
                                Es solo un indicador visual; la obligatoriedad real
                                la imponen el required del input y la validación PHP.
                            -->
                        </label>
                        <input type="text" name="nombre" class="form-control"
                               placeholder="Ej: Salas de reuniones" required>
                        <!--
                            type="text"  → campo de texto de una sola línea
                            name="nombre" → PHP lo recoge como $_POST["nombre"]
                            required     → el navegador no deja enviar el form si está vacío
                                           (primera capa de validación, antes del PHP)
                        -->
                    </div>

                    <!-- Campo Descripción -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Descripción</label>
                        <!-- Sin asterisco rojo → campo opcional -->
                        <textarea name="descripcion" class="form-control" rows="3"
                                  placeholder="Descripción opcional..."></textarea>
                        <!--
                            textarea     → campo de texto multilínea (para descripciones largas)
                            name="descripcion" → PHP lo recoge como $_POST["descripcion"]
                            rows="3"     → altura inicial del textarea: 3 líneas visibles
                            Sin required → es opcional, puede enviarse vacío
                        -->
                    </div>

                    <!-- Botón Guardar -->
                    <div class="d-grid">
                    <!--
                        d-grid → convierte el contenedor en grid de una columna.
                                 Hace que el botón hijo ocupe el 100% del ancho
                                 sin necesidad de añadirle w-100 al botón.
                    -->
                        <button type="submit" class="btn btn-primary py-2">
                        <!--
                            type="submit" → envía el formulario al hacer clic
                            btn-primary   → botón azul relleno (acción principal)
                            py-2          → padding vertical extra, botón más alto y cómodo
                        -->
                            <i class="bi bi-check-lg me-1"></i>Guardar
                            <!-- bi-check-lg → icono de check grande (confirma que es guardar) -->
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
<!-- Cierra el container, muestra el footer y carga el JS de Bootstrap -->