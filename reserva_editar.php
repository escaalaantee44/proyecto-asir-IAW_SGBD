<?php
session_start();
// Inicia sesiones para leer $_SESSION y saber quién está logueado.

// ── CONTROL DE ACCESO: NIVEL 1 → ¿Está logueado? ────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

include "conexion.php";
// Conecta a la BD. admin → admin_app / usuario normal → usuario_app.

// ── VARIABLES DE SESIÓN ───────────────────────────────────────────────────────

$id_usuario = $_SESSION["id_usuario"];
$rol        = $_SESSION["rol"];
// Se guardan en variables locales para usarlas más cómodamente abajo.


// ── VALIDACIÓN DEL PARÁMETRO ID ───────────────────────────────────────────────

if (!isset($_GET["id"])) {
    header("Location: reserva.php");
    exit();
    // Sin ?id= en la URL → redirige al listado de reservas.
}

$id = (int) $_GET["id"];
// Cast a entero: convierte el valor de la URL a número, neutralizando
// cualquier texto malicioso que pudieran inyectar en la URL.


// ── VERIFICACIÓN DE QUE LA RESERVA EXISTE ────────────────────────────────────

$sql = "SELECT * FROM reservas WHERE id_reserva = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$reserva = $result->fetch_assoc();
// Carga la reserva de la BD antes de hacer nada más.
// Necesario para: verificar que existe Y obtener sus datos actuales
// (id_usuario propietario, recurso, fecha, horas) para pre-rellenar el formulario.

if (!$reserva) {
    die("Reserva no encontrada");
    // El ID no existe en la BD → detiene el script.
}


// ── CONTROL DE ACCESO: NIVEL 2 → ¿Puede este usuario editar ESTA reserva? ────

if ($rol != "admin" && $reserva["id_usuario"] != $id_usuario) {
    die("No puedes editar esta reserva");
}
/*
    Misma lógica de permisos que en reserva_eliminar.php:
        ✅ Admin puede editar CUALQUIER reserva
        ✅ Usuario normal puede editar SUS PROPIAS reservas
        ❌ Usuario normal NO puede editar reservas de OTROS usuarios

    Sin esto, cualquier usuario podría editar reservas ajenas cambiando el ?id= en la URL.
*/


// ── CARGA DE RECURSOS DISPONIBLES PARA EL SELECTOR ───────────────────────────

$recursos = $conn->query("SELECT * FROM recursos WHERE estado = 'activo' ORDER BY nombre");
// Obtiene todos los recursos con estado 'activo' ordenados por nombre.
// Solo se muestran los activos porque no tiene sentido reservar un recurso
// que está en mantenimiento o fuera de servicio.
// Esta consulta alimenta el <select> del formulario para elegir recurso.
// Se ejecuta SIEMPRE (tanto en GET como en POST) porque el select
// siempre necesita estar relleno de opciones.


// ── PROCESAMIENTO DEL FORMULARIO ──────────────────────────────────────────────

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Solo entra aquí cuando el formulario se envía.
    // La carga inicial (GET) muestra el formulario pre-relleno con los datos actuales.

    $id_recurso  = (int) $_POST["id_recurso"];
    // Cast a entero: el ID del recurso seleccionado en el desplegable.
    // Aunque venga de un <select> controlado, el cast es una capa extra de seguridad.

    $fecha       = $_POST["fecha"];
    $hora_inicio = $_POST["hora_inicio"];
    $hora_fin    = $_POST["hora_fin"];
    // Recoge los valores de los campos de fecha y hora del formulario.
    // Formato fecha: "YYYY-MM-DD" (lo que devuelve input type="date")
    // Formato hora:  "HH:MM"     (lo que devuelve input type="time")


    // ── VALIDACIÓN: HORA FIN POSTERIOR A HORA INICIO ──────────────────────────

    if ($hora_fin <= $hora_inicio) {
        $error = "La hora de fin debe ser posterior a la de inicio.";
        // Compara las horas como strings. Funciona correctamente porque
        // el formato "HH:MM" permite comparación lexicográfica directa:
        // "09:00" < "10:00" < "18:30" → la comparación de texto da el mismo
        // resultado que la comparación numérica para este formato.
        // Si hora_fin es igual o anterior a hora_inicio → error.

    } else {

        // ── ACTUALIZACIÓN EN LA BASE DE DATOS CON TRY/CATCH ──────────────────

        try {
            // try/catch captura errores de MySQL que normalmente romperían el script.
            // A diferencia de los otros archivos que usaban if($stmt->execute()),
            // aquí se usa try/catch para capturar excepciones mysqli_sql_exception.
            // Esto permite mostrar el mensaje de error real de MySQL en pantalla,
            // útil por ejemplo si el TRIGGER de solapamiento de la BD lanza un error.

            $sql = "UPDATE reservas
                    SET id_recurso = ?, fecha = ?, hora_inicio = ?, hora_fin = ?
                    WHERE id_reserva = ?";
            // Actualiza los 4 campos editables de la reserva.
            // WHERE id_reserva = ? → CRÍTICO: sin WHERE se actualizarían TODAS las reservas.
            // Cinco marcadores ? para cinco valores.

            $stmt = $conn->prepare($sql);

            $stmt->bind_param("isssi", $id_recurso, $fecha, $hora_inicio, $hora_fin, $id);
            /*
                Vincula los 5 valores a los 5 marcadores ? en orden:
                "i" → $id_recurso  (integer: ID del recurso seleccionado)
                "s" → $fecha       (string:  "YYYY-MM-DD")
                "s" → $hora_inicio (string:  "HH:MM")
                "s" → $hora_fin    (string:  "HH:MM")
                "i" → $id          (integer: ID de la reserva que estamos editando)

                "isssi" = integer, string, string, string, integer
                El orden debe coincidir EXACTAMENTE con el orden de los ? en el SQL.
            */

            $stmt->execute();

            header("Location: reserva.php");
            exit();
            // Actualización correcta → vuelve al listado de reservas.

        } catch (mysqli_sql_exception $e) {
            $error = "Error al actualizar la reserva: " . $e->getMessage();
            // Si MySQL lanza una excepción (ej: el TRIGGER detecta solapamiento
            // de horarios y hace un SIGNAL SQLSTATE), se captura aquí y se
            // muestra el mensaje de error en el formulario sin romper la página.
            // $e->getMessage() contiene el mensaje de error que devuelve MySQL.
        }
    }
}

$titulo = "Editar reserva";
include "includes/header.php";
?>

<!-- ── BOTÓN VOLVER ── -->
<div class="mb-4">
    <a href="reserva.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<!-- ══════════════════════════════════════════════
     FORMULARIO DE EDICIÓN DE RESERVA
══════════════════════════════════════════════ -->

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <!--
        col-12   → móvil: ancho completo
        col-md-8 → tablet: 8/12 del ancho
        col-lg-6 → escritorio: mitad del ancho centrado
        
    -->
        <div class="card">
            <div class="card-header bg-warning text-dark py-3">
                <!--
                bg-warning → cabecera amarilla, igual que categoria_editar.php.
                             El amarillo indica de forma consistente "estás editando algo".
                text-dark  → texto oscuro sobre fondo amarillo (el amarillo es claro).
            -->
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i>Editar reserva
                    <!-- bi-calendar-check → icono de calendario con check (representa una reserva) -->
                </h5>
            </div>

            <div class="card-body p-4">

                <!-- ── MENSAJE DE ERROR ── -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <!--
                            Muestra el error de validación (hora_fin <= hora_inicio)
                            o el error capturado por el catch (ej: solapamiento de la BD).
                        -->
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Sin action → se envía a la misma URL con el ?id= del navegador,
                     así el PHP sabe qué reserva actualizar -->

                    <!-- ── SELECTOR DE RECURSO ── -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Recurso <span class="text-danger">*</span>
                        </label>
                        <select name="id_recurso" class="form-select" required>
                            <!--
                            form-select → estilo Bootstrap para listas desplegables (select).
                            name="id_recurso" → PHP lo recoge como $_POST["id_recurso"].
                            required → no se puede enviar el formulario sin seleccionar un recurso.
                        -->
                            <?php while ($r = $recursos->fetch_assoc()): ?>
                                <!-- Bucle que genera una <option> por cada recurso activo de la BD -->

                                <option value="<?php echo $r["id_recurso"]; ?>"
                                    <?php if ($r["id_recurso"] == $reserva["id_recurso"]) echo "selected"; ?>>
                                    <!--
                                    value="..." → el valor que se enviará por POST al seleccionar esta opción.
                                    selected    → marca como seleccionada la opción que coincide con el recurso
                                                  actual de la reserva ($reserva["id_recurso"]).
                                    Así el desplegable aparece pre-seleccionado con el recurso actual,
                                    no en blanco como si fuera una reserva nueva.
                                -->
                                    <?php echo htmlspecialchars($r["nombre"]); ?>
                                    <!-- Texto visible en el desplegable: nombre del recurso -->
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- ── CAMPO FECHA ── -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Fecha <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="fecha" class="form-control"
                            value="<?php echo $reserva["fecha"]; ?>" required>
                        <!--
                            type="date" → el navegador muestra un selector de calendario.
                            value="..."  → pre-rellena con la fecha actual de la reserva (formato YYYY-MM-DD).
                            El navegador muestra la fecha en formato local (DD/MM/YYYY en España)
                            pero internamente la maneja en YYYY-MM-DD, que es el formato de MySQL.
                        -->
                    </div>

                    <!-- ── CAMPOS HORA INICIO / HORA FIN (en dos columnas) ── -->
                    <div class="row g-3 mb-4">
                        <!--
                        row g-3 → fila Bootstrap con espacio entre columnas.
                        Los dos campos de hora se colocan uno al lado del otro.
                    -->
                        <div class="col-6">
                            <!-- col-6 → ocupa la mitad izquierda de la fila (6 de 12 columnas) -->
                            <label class="form-label fw-semibold">Hora inicio</label>
                            <input type="time" name="hora_inicio" class="form-control"
                                value="<?php echo $reserva["hora_inicio"]; ?>" required>
                            <!--
                                type="time" → el navegador muestra un selector de hora (HH:MM).
                                value="..."  → pre-rellena con la hora de inicio actual de la reserva.
                            -->
                        </div>
                        <div class="col-6">
                            <!-- col-6 → ocupa la mitad derecha de la fila -->
                            <label class="form-label fw-semibold">Hora fin</label>
                            <input type="time" name="hora_fin" class="form-control"
                                value="<?php echo $reserva["hora_fin"]; ?>" required>
                            <!--
                                value="..." → pre-rellena con la hora de fin actual de la reserva.
                                La validación $hora_fin <= $hora_inicio del PHP de arriba
                                comprueba que este valor sea posterior al de hora_inicio.
                            -->
                        </div>
                    </div>

                    <!-- ── BOTÓN GUARDAR ── -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning py-2 fw-semibold">
                            <!--
                            btn-warning → botón amarillo, coherente con la cabecera.
                            Indica visualmente que se está modificando algo existente.
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