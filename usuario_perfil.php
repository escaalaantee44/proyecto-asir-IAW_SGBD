<?php
session_start();
// Inicia sesiones para leer $_SESSION y saber quién está logueado.

// ── CONTROL DE ACCESO ─────────────────────────────────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
    // Sin sesión activa → redirige al login.
    // No se comprueba rol: cualquier usuario logueado puede editar su propio perfil.
    // exit() detiene el script tras el redirect.
}

include "conexion.php";


// ── OBTENCIÓN DEL USUARIO LOGUEADO ───────────────────────────────────────────

$id = $_SESSION["id_usuario"];
/*
    A diferencia de usuario_editar.php donde el ID venía de $_GET["id"]
    (parámetro de URL manipulable), aquí viene DIRECTAMENTE de la sesión.
    [SEGURIDAD] Un usuario solo puede editar SU PROPIO perfil: no hay
    ?id= en la URL que pueda cambiar para editar el perfil de otro.
*/

$sql  = "SELECT * FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
// Carga los datos actuales para pre-rellenar el formulario.
// No hace falta comprobar if(!$usuario): si hay sesión, el usuario existe en la BD.


// ── PROCESAMIENTO DEL FORMULARIO ──────────────────────────────────────────────

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre   = trim($_POST["nombre"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    // trim() elimina espacios sobrantes al principio y al final.
    // Sin campo "rol": un usuario no puede ascenderse a admin editando su perfil.
    // El rol solo lo cambia un admin desde usuario_editar.php.

    // ── VALIDACIÓN DE CAMPOS VACÍOS ───────────────────────────────────────────
    if ($nombre == "" || $email == "") {
        $error = "El nombre y el email son obligatorios.";
    } else {

        // ── COMPROBACIÓN DE EMAIL DUPLICADO ───────────────────────────────────
        // Comprueba si el email ya existe EN OTRO usuario distinto al logueado.
        // AND id_usuario != ? es clave: sin él, el usuario siempre detectaría
        // su propio email como duplicado y no podría guardar sin cambiarlo.
        $check = $conn->prepare(
            "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?"
        );
        $check->bind_param("si", $email, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // El email pertenece a otro usuario → avisamos sin hacer el UPDATE.
            $error_email = "El correo <strong>" . htmlspecialchars($email) . "</strong> ya está registrado por otra cuenta.";
        } else {
            // ── CONTRASEÑA: HASH O MANTENER LA ACTUAL ─────────────────────────
            if ($password !== "") {
                // Si el usuario escribió una nueva contraseña → hashearla y actualizarla.
                // [SEGURIDAD] password_hash() genera un hash bcrypt con salt aleatorio.
                // Nunca se guarda la contraseña en texto plano.
                $password_final = password_hash($password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE usuarios SET nombre=?, email=?, password=? WHERE id_usuario=?";
                $stmt2 = $conn->prepare($sql_update);
                $stmt2->bind_param("sssi", $nombre, $email, $password_final, $id);
            } else {
                // Si el campo contraseña se dejó vacío → no tocar la contraseña actual.
                // El usuario puede actualizar nombre/email sin obligatoriamente
                // cambiar su contraseña.
                $sql_update = "UPDATE usuarios SET nombre=?, email=? WHERE id_usuario=?";
                $stmt2 = $conn->prepare($sql_update);
                $stmt2->bind_param("ssi", $nombre, $email, $id);
            }

            // ── EJECUCIÓN DEL UPDATE ──────────────────────────────────────────
            if ($stmt2->execute()) {
                // [CORRECCIÓN] El original no comprobaba si execute() tenía éxito.
                // Ahora solo muestra el mensaje de éxito si el UPDATE fue bien.

                $_SESSION["nombre"] = $nombre;
                // Actualiza el nombre en la sesión activa para que el navbar
                // lo refleje inmediatamente sin necesidad de hacer logout.

                // Recargamos los datos del usuario para que el formulario
                // muestre los valores recién guardados (especialmente el email).
                $stmt3 = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
                $stmt3->bind_param("i", $id);
                $stmt3->execute();
                $usuario = $stmt3->get_result()->fetch_assoc();

                $mensaje = "Perfil actualizado correctamente.";
                // Mensaje de éxito que se muestra en la misma página.
                // A diferencia de otros archivos que redirigen, aquí nos quedamos
                // para que el usuario vea la confirmación junto al formulario actualizado.

            } else {
                $error = "Error al guardar los cambios. Inténtalo de nuevo.";
            }
        }
        $check->close();
    }
}

// ── CABECERA ──────────────────────────────────────────────────────────────────
// [CORRECCIÓN] El original incluía header.php al principio del script.
// Se mueve aquí para que toda la lógica PHP esté resuelta antes de imprimir HTML,
// manteniendo consistencia con el resto de archivos del proyecto.
$titulo = "Mi perfil";
include "includes/header.php";
?>

<!-- ══════════════════════════════════════════════
     CABECERA: botón volver + título
══════════════════════════════════════════════ -->
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h2 class="mb-0 fw-bold">
        <i class="bi bi-person-circle me-2 text-primary"></i>Mi perfil
    </h2>
</div>

<!-- ── ALERT: ÉXITO ── -->
<?php if (isset($mensaje)): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <!--
            alert-success → fondo verde (operación completada correctamente).
            d-flex align-items-center → alinea el icono y el texto en línea.
        -->
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<!-- ── ALERT: ERROR GENÉRICO ── -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════
     FORMULARIO DE EDICIÓN DE PERFIL
══════════════════════════════════════════════ -->
<form method="POST" class="col-md-6">
    <!--
        Sin action → se envía a la misma página (usuario_perfil.php).
        col-md-6 → en tablet/escritorio el formulario ocupa la mitad del ancho.
    -->

    <!-- Nombre -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Nombre</label>
        <input type="text" name="nombre" class="form-control"
            value="<?= htmlspecialchars($_POST["nombre"] ?? $usuario["nombre"]) ?>" required>
        <!--
            [CORRECCIÓN] El original no tenía htmlspecialchars() en el value.
            $_POST["nombre"] ?? $usuario["nombre"]:
            - Si hay POST con error → mantiene lo que escribió el usuario.
            - Si es carga inicial o éxito → usa el valor actual de la BD.
        -->
    </div>

    <!-- Email -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email"
            class="form-control <?= isset($error_email) ? 'is-invalid' : '' ?>"
            value="<?= htmlspecialchars($_POST["email"] ?? $usuario["email"]) ?>" required>
        <!--
            is-invalid → borde rojo de Bootstrap, solo si hay email duplicado.
            [CORRECCIÓN] El original no tenía htmlspecialchars() en el value.
        -->
        <?php if (isset($error_email)): ?>
            <div class="invalid-feedback d-block">
                <!--
                    invalid-feedback → texto rojo bajo el input.
                    d-block → lo fuerza a visible (Bootstrap lo oculta por defecto).
                -->
                <i class="bi bi-exclamation-circle me-1"></i>
                <?= $error_email ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contraseña -->
    <div class="mb-4">
        <label class="form-label fw-semibold">
            Contraseña
            <span class="text-muted fw-normal ms-1" style="font-size:.85rem">
                (dejar vacío para no cambiarla)
            </span>
        </label>
        <input type="password" name="password" class="form-control"
            autocomplete="new-password">
        <!--
            [CORRECCIÓN] El original tenía type="text" (mostraba la contraseña visible)
            y pre-rellenaba el campo con el hash bcrypt de la BD (filtración de info).
            Ahora: type="password" oculta lo que se escribe, y el campo aparece
            siempre vacío. Sin required: si se deja vacío, la contraseña no cambia.
            autocomplete="new-password" → el gestor de contraseñas sugiere
            una nueva contraseña en lugar de rellenar la actual.
        -->
    </div>

    <button type="submit" class="btn btn-primary">
        <!--
            [CORRECCIÓN] El original no tenía type="submit" explícito.
        -->
        <i class="bi bi-floppy me-1"></i>Guardar cambios
    </button>

</form>

<?php include "includes/footer.php"; ?>