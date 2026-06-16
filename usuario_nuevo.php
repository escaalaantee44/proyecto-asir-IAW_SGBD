<?php
session_start();

// ── CONTROL DE ACCESO ─────────────────────────────────────────────────────────
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] != "admin") {
    header("Location: login.php");
    exit();
    // Solo admins pueden crear usuarios.
    // exit() detiene el script tras el redirect (sin él PHP seguiría ejecutando).
}

include "conexion.php";

// ── PROCESAMIENTO DEL FORMULARIO ──────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre   = trim($_POST["nombre"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $rol      = $_POST["rol"];

    // ── VALIDACIÓN DE CAMPOS VACÍOS ───────────────────────────────────────────
    if ($nombre == "" || $email == "" || $password == "") {
        $error = "Todos los campos son obligatorios.";
    } else {

        // ── COMPROBACIÓN DE EMAIL DUPLICADO ───────────────────────────────────
        // Antes de intentar el INSERT, consultamos si el email ya existe.
        // Aunque la BD lo rechazaría de todas formas (campo UNIQUE), capturar
        // el error de MySQL directamente da un mensaje genérico poco útil.
        // Con esta consulta previa podemos mostrar un aviso claro al usuario.
        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        // store_result() carga el resultado en memoria para poder usar num_rows.

        if ($check->num_rows > 0) {
            // El email ya existe en la BD → avisamos sin intentar el INSERT.
            $error_email = "El correo <strong>" . htmlspecialchars($email) . "</strong> ya está registrado.";
            // Variable separada ($error_email) para poder resaltar el campo
            // de email en rojo de forma independiente al error genérico.

        } else {
            // ── HASH DE CONTRASEÑA ────────────────────────────────────────────
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            // password_hash() genera un hash bcrypt con salt aleatorio.
            // PASSWORD_DEFAULT usa bcrypt con cost 12 (ajustable según el servidor).
            // [SEGURIDAD] Nunca guardar contraseñas en texto plano: si alguien
            // accede a la BD, obtendría todas las contraseñas directamente.
            // Con bcrypt, solo obtiene hashes irreversibles.

            // ── INSERT ────────────────────────────────────────────────────────
            $sql  = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nombre, $email, $password_hash, $rol);
            // Se inserta $password_hash, nunca $password (texto plano).

            if ($stmt->execute()) {
                header("Location: usuarios.php?creado=1");
                exit();
                // Redirige al listado con un parámetro para mostrar un mensaje
                // de éxito allí si lo implementas. exit() detiene el script.
            } else {
                $error = "Error al crear el usuario. Inténtalo de nuevo.";
            }
        }
        $check->close();
    }
}

// ── CABECERA ──────────────────────────────────────────────────────────────────
$titulo = "Nuevo usuario";
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
        <i class="bi bi-person-plus me-2 text-primary"></i>Nuevo usuario
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
     FORMULARIO
══════════════════════════════════════════════ -->
<form method="POST" class="col-md-6">

    <!-- Nombre -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Nombre</label>
        <input type="text" name="nombre" class="form-control"
            value="<?= htmlspecialchars($_POST["nombre"] ?? "") ?>" required>
        <!--
            value con $_POST preserva lo que el usuario escribió si hay error,
            así no tiene que volver a rellenar todos los campos.
            htmlspecialchars() evita XSS al reflejar datos del usuario en el HTML.
            ?? "" evita el notice de PHP si $_POST["nombre"] no existe (primera carga).
        -->
    </div>

    <!-- Email -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email"
            class="form-control <?= isset($error_email) ? 'is-invalid' : '' ?>"
            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required>
        <!--
            is-invalid → clase Bootstrap que pone el borde del input en rojo
            y muestra el div.invalid-feedback de abajo.
            Solo se aplica si existe $error_email (email duplicado).
        -->
        <?php if (isset($error_email)): ?>
            <div class="invalid-feedback d-block">
                <!--
                    invalid-feedback → texto rojo de Bootstrap bajo el input.
                    d-block → Bootstrap lo oculta por defecto con display:none;
                    d-block lo fuerza a visible sin depender del :invalid de CSS.
                -->
                <i class="bi bi-exclamation-circle me-1"></i>
                <?= $error_email ?>

            </div>
        <?php endif; ?>
    </div>

    <!-- Contraseña -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Contraseña</label>
        <input type="password" name="password" class="form-control"
            autocomplete="new-password" required>
        <!--
            autocomplete="new-password" → indica al navegador/gestor de
            contraseñas que genere o sugiera una contraseña nueva (no rellene
            la contraseña actual del usuario logueado).
            No se rellena el value en error: es buena práctica no devolver
            contraseñas al HTML aunque sea el propio usuario quien la escribió.
        -->
    </div>

    <!-- Rol -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Rol</label>
        <select name="rol" class="form-select">
            <option value="usuario" <?= (($_POST["rol"] ?? "") == "usuario") ? "selected" : "" ?>>
                Usuario
            </option>
            <option value="admin" <?= (($_POST["rol"] ?? "") == "admin")   ? "selected" : "" ?>>
                Administrador
            </option>
        </select>
        <!-- El rol seleccionado se mantiene si hay error en el formulario. -->
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-person-check me-1"></i>Crear usuario
    </button>

</form>

<?php include "includes/footer.php"; ?>