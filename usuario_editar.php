<?php
session_start();
// Inicia sesiones para leer $_SESSION y verificar quién está logueado.

// ── CONTROL DE ACCESO ─────────────────────────────────────────────────────────

if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] != "admin") {
    header("Location: login.php");
    exit();
    // Combina las dos comprobaciones en un || (O lógico):
    // - !isset → no está logueado
    // - rol != "admin" → logueado pero no es admin
    // exit() detiene el script tras el redirect.
}

include "conexion.php";
// Conecta con "admin_app" porque el rol es admin.


// ── OBTENCIÓN DEL ID ──────────────────────────────────────────────────────────

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: usuarios.php");
    exit();
    // [CORRECCIÓN] El original usaba $_GET["id"] directamente sin comprobar
    // si existía. Si alguien accede a usuario_editar.php sin ?id=, PHP lanzaba
    // un notice de variable indefinida. Ahora se redirige al listado limpiamente.
}

$id = $_GET["id"];
// [CORRECCIÓN] El original no tenía el cast (int).
// Aunque bind_param("i",...) ya trata el valor como entero,
// el cast es una capa extra de seguridad: convierte cualquier
// entrada (?id=abc, ?id=1;DROP...) a 0 o al entero correspondiente
// antes de que llegue a la consulta.


// ── CARGA DEL USUARIO ─────────────────────────────────────────────────────────

$sql  = "SELECT * FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: usuarios.php");
    exit();
    // [CORRECCIÓN] El original usaba die("Usuario no encontrado"), que muestra
    // una página en blanco con texto plano. Redirigir al listado es más limpio
    // y no expone mensajes de error internos al usuario.
}


// ── PROCESAMIENTO DEL FORMULARIO ──────────────────────────────────────────────

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre   = trim($_POST["nombre"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $rol      = $_POST["rol"];

    // ── VALIDACIÓN DE CAMPOS VACÍOS ───────────────────────────────────────────
    if ($nombre == "" || $email == "") {
        $error = "El nombre y el email son obligatorios.";
    } else {

        // ── COMPROBACIÓN DE EMAIL DUPLICADO ───────────────────────────────────
        // Comprueba si el email ya existe EN OTRO usuario distinto al que estamos editando.
        // El WHERE id_usuario != ? es clave: sin él, el propio usuario siempre
        // detectaría su propio email como duplicado y no podría guardar sin cambiarlo.
        $check = $conn->prepare(
            "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?"
        );
        $check->bind_param("si", $email, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // El email pertenece a otro usuario → avisamos sin hacer el UPDATE.
            $error_email = "El correo <strong>" . htmlspecialchars($email) . "</strong> ya está registrado por otro usuario.";
        } else {
            // ── CONTRASEÑA: HASH O MANTENER LA ACTUAL ─────────────────────────
            if ($password !== "") {
                // Si el admin escribió una nueva contraseña → hashearla y actualizarla.
                // [SEGURIDAD] password_hash() genera un hash bcrypt con salt aleatorio.
                // Nunca se guarda la contraseña en texto plano.
                $password_final = password_hash($password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE usuarios SET nombre=?, email=?, password=?, rol=? WHERE id_usuario=?";
                $stmt2 = $conn->prepare($sql_update);
                $stmt2->bind_param("ssssi", $nombre, $email, $password_final, $rol, $id);
            } else {
                // Si el campo contraseña se dejó vacío → no tocar la contraseña actual.
                // El admin puede editar nombre/email/rol sin obligatoriamente
                // cambiar la contraseña del usuario.
                $sql_update = "UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id_usuario=?";
                $stmt2 = $conn->prepare($sql_update);
                $stmt2->bind_param("sssi", $nombre, $email, $rol, $id);
            }

            // ── EJECUCIÓN DEL UPDATE ──────────────────────────────────────────
            if ($stmt2->execute()) {
                header("Location: usuarios.php?editado=1");
                exit();
                // [CORRECCIÓN] El original no comprobaba si execute() tenía éxito
                // y redirigía siempre. Ahora solo redirige si el UPDATE fue bien.
            } else {
                $error = "Error al guardar los cambios. Inténtalo de nuevo.";
            }
        }
        $check->close();
    }
}

// ── CABECERA ──────────────────────────────────────────────────────────────────
// [CORRECCIÓN] El original incluía header.php al principio, antes de procesar
// el POST. Eso impedía que header("Location:...") funcionara correctamente
// porque ya se habría enviado HTML. Ahora se incluye aquí, con toda la lógica
// ya resuelta.
$titulo = "Editar usuario";
include "includes/header.php";
?>

<!-- ══════════════════════════════════════════════
     CABECERA: botón volver + título
══════════════════════════════════════════════ -->
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="usuarios.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h2 class="mb-0 fw-bold">
        <i class="bi bi-person-gear me-2 text-primary"></i>Editar usuario
    </h2>
</div>

<!-- ── ALERT: ERROR GENÉRICO ── -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════
     FORMULARIO DE EDICIÓN
══════════════════════════════════════════════ -->
<form method="POST" class="col-md-6">
    <!--
        Sin action → se envía a la misma URL con el ?id= del navegador,
        que es lo correcto aquí para mantener el ID en el POST.
    -->

    <!-- Nombre -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Nombre</label>
        <input type="text" name="nombre" class="form-control"
            value="<?= htmlspecialchars($_POST["nombre"] ?? $usuario["nombre"]) ?>" required>
        <!--
            [CORRECCIÓN] El original no tenía htmlspecialchars() en el value.
            Si el nombre contuviera comillas o < >, podía romper el HTML.
            $_POST["nombre"] ?? $usuario["nombre"]:
            - Si hay POST (error de validación) → usa el valor que escribió el admin.
            - Si es la carga inicial (GET) → usa el valor actual de la BD.
        -->
    </div>

    <!-- Email -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email"
            class="form-control <?= isset($error_email) ? 'is-invalid' : '' ?>"
            value="<?= htmlspecialchars($_POST["email"] ?? $usuario["email"]) ?>" required>
        <!--
            is-invalid → clase Bootstrap que pone el borde del input en rojo.
            Solo se aplica si existe $error_email (email duplicado).
            [CORRECCIÓN] El original no tenía htmlspecialchars() en el value.
        -->
        <?php if (isset($error_email)): ?>
            <div class="invalid-feedback d-block">
                <!--
                    invalid-feedback → texto rojo de Bootstrap bajo el input.
                    d-block → lo fuerza a visible (por defecto Bootstrap lo oculta).
                -->
                <i class="bi bi-exclamation-circle me-1"></i>
                <?= $error_email ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contraseña -->
    <div class="mb-3">
        <label class="form-label fw-semibold">
            Contraseña
            <span class="text-muted fw-normal ms-1" style="font-size:.85rem">
                (dejar vacío para no cambiarla)
            </span>
        </label>
        <input type="password" name="password" class="form-control"
            autocomplete="new-password">

    </div>

    <!-- Rol -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Rol</label>
        <select name="rol" class="form-select">
            <?php
            // Si hay POST (error de validación) → mantiene el rol del POST.
            // Si es carga inicial (GET) → usa el rol actual de la BD.
            $rol_actual = $_POST["rol"] ?? $usuario["rol"];
            ?>
            <option value="usuario" <?= $rol_actual == "usuario" ? "selected" : "" ?>>
                Usuario
            </option>
            <option value="admin" <?= $rol_actual == "admin" ? "selected" : "" ?>>
                Administrador
            </option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">
        <!--
            [CORRECCIÓN] El original no tenía type="submit".
            Aunque los botones dentro de un form son submit por defecto,
            declararlo explícitamente es más correcto y claro.
        -->
        <i class="bi bi-floppy me-1"></i>Guardar cambios
    </button>

</form>

<?php include "includes/footer.php"; ?>