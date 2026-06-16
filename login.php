<?php
session_start();
// Inicia el sistema de sesiones. Debe ir antes de cualquier HTML o echo.
// Permite leer y escribir en $_SESSION para recordar quién está logueado.


// ── REDIRECCIÓN SI YA ESTÁ LOGUEADO ──────────────────────────────────────────

if (isset($_SESSION["id_usuario"])) {
    // Si ya existe una sesión activa (el usuario ya se logueó antes),
    // no tiene sentido mostrarle el login de nuevo.
    header("Location: index.php");
    exit();
    // exit() detiene el script inmediatamente tras enviar el redirect.
    // Sin él, el código de abajo seguiría ejecutándose aunque el navegador
    // ya estuviera navegando a index.php.
}

// ── CONEXIÓN A LA BD ──────────────────────────────────────────────────────────

include "conexion.php";
// Se incluye DESPUÉS de comprobar si ya hay sesión.
// Si el usuario ya estaba logueado, ya hemos redirigido y salido (exit()),
// así que aquí NUNCA se abre una conexión innecesaria a la BD.
// [SEGURIDAD] Abrir la BD solo cuando realmente se va a usar evita
// consumir conexiones del pool de MySQL sin motivo.


// ── PROCESAMIENTO DEL FORMULARIO ──────────────────────────────────────────────

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Solo entra aquí cuando el formulario se ha enviado (método POST).
    // La primera vez que se carga la página es GET, así que este bloque se salta.

    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    // trim() elimina espacios en blanco al principio y al final.
    // Evita que "usuario@email.com " (con espacio) falle al comparar con la BD.
    // $_POST recoge los datos que el usuario escribió en el formulario.


    // ── CONSULTA PREPARADA (seguridad contra SQL Injection) ───────────────────

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    // La ? es un marcador de posición. El valor real se añade después de forma segura.
    // [SEGURIDAD] Si escribiéramos directamente WHERE email = '$email', un atacante
    // podría meter código SQL en el campo y manipular la consulta (SQL Injection).
    // Con prepare() + bind_param(), MySQL trata el valor como dato puro, nunca como código.

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    // "s" → string. Vincula $email al marcador ? de forma segura.

    $stmt->execute();
    $resultado = $stmt->get_result();


    // ── VERIFICACIÓN DEL USUARIO ──────────────────────────────────────────────

    if ($resultado->num_rows == 1) {
        // num_rows == 1 → el email existe en la BD (es UNIQUE, nunca puede ser > 1).

        $usuario = $resultado->fetch_assoc();
        // Convierte la fila de la BD en un array asociativo PHP.

        // ── COMPROBACIÓN DE CONTRASEÑA ────────────────────────────────────────

        // [SEGURIDAD - CRÍTICO] Nunca comparar contraseñas en texto plano.
        // La contraseña debe guardarse en BD con password_hash() y verificarse
        // con password_verify(). Estas funciones usan bcrypt internamente:
        //
        //   Al registrar un usuario:
        //     $hash = password_hash($password_en_texto, PASSWORD_DEFAULT);
        //     // Guarda $hash en la BD, nunca el texto plano.
        //
        //   Al hacer login (aquí):
        //     password_verify($password_escrita, $hash_de_la_BD)
        //     // Devuelve true si coinciden, false si no.
        //
        // password_verify() es resistente a timing attacks: siempre tarda
        // el mismo tiempo aunque la contraseña sea incorrecta, lo que impide
        // que un atacante deduzca información midiendo el tiempo de respuesta.
        //
        // NOTA PARA EL PROYECTO: si la BD ya tiene contraseñas en texto plano,
        // hay que migrarlas. Al editar/crear un usuario, hashear con password_hash()
        // antes de hacer el INSERT/UPDATE. Una vez migradas, cambiar la comparación
        // de abajo por password_verify().

        if (password_verify($password, $usuario["password"])) {
            // password_verify(contraseña_introducida, hash_guardado_en_BD)
            // → true si la contraseña es correcta, false si no.
            // [SEGURIDAD] Sustituye la comparación == que antes usaba texto plano.

            // ── INICIO DE SESIÓN EXITOSO ──────────────────────────────────────

            // [SEGURIDAD] Regenerar el ID de sesión tras el login evita
            // un atacante que haya obtenido el ID de sesión
            // anónima no podrá usarlo después del login porque el ID cambia.
            session_regenerate_id(true);
            // true → destruye la sesión antigua además de crear una nueva.

            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            // Guarda el ID del usuario en sesión. Se usa en conexion.php y en
            // otros archivos para saber quién está logueado.

            $_SESSION["nombre"] = $usuario["nombre"];
            // Guarda el nombre para mostrarlo en el navbar y en el panel.

            $_SESSION["rol"] = $usuario["rol"];
            // Guarda el rol ("admin" o "usuario"). A partir de aquí, conexion.php
            // usará el usuario MySQL correspondiente en cada página.

            header("Location: index.php");
            exit();
            // Login correcto → redirige al panel principal y detiene el script.
            // [SEGURIDAD] exit() es obligatorio tras header("Location:..."):
            // sin él, PHP seguiría ejecutando el código de abajo aunque el
            // navegador ya estuviera navegando a index.php.

        } else {
            $error = "Credenciales incorrectas. Comprueba tu email y contraseña.";
            // [SEGURIDAD] Mensaje genérico intencionado: no se especifica si el
            // email o la contraseña son lo que falla. Dar pistas concretas
            // ayudaría a un atacante a confirmar qué emails existen en el sistema.
        }
    } else {
        $error = "Credenciales incorrectas. Comprueba tu email y contraseña.";
        // [SEGURIDAD] Mismo mensaje que arriba, aunque aquí el email no existe.
        // Mensajes idénticos en ambas ramas → no se puede distinguir cuál es el caso.
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistema de Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            /* Fondo degradado azul en diagonal (135 grados).
               #0d6efd es el azul primario de Bootstrap,
               #0a58ca es un azul más oscuro. Va de claro (arriba-izquierda) a oscuro (abajo-derecha). */
            min-height: 100vh;
            /* La página ocupa al menos el 100% de la altura visible del navegador.
               Evita que el degradado se corte si el contenido es pequeño. */
            display: flex;
            align-items: center;
            /* flex + align-items: center → centra verticalmente la tarjeta de login
               en la pantalla, independientemente del tamaño de la ventana. */
        }

        .card {
            border: none;
            border-radius: 16px;
            /* Esquinas más redondeadas que en el resto de la app (16px vs 12px del header global),
               da un aspecto más moderno a la pantalla de login. */
            box-shadow: 0 8px 32px rgba(0, 0, 0, .18);
            /* Sombra más pronunciada que en las tarjetas del panel (8px de desplazamiento,
               18% de opacidad) para que la tarjeta destaque sobre el fondo azul. */
        }

        .card-header {
            border-radius: 16px 16px 0 0 !important;
            background: #0d6efd;
            /* Cabecera azul sólido que contrasta con el fondo degradado.
               !important necesario para sobreescribir el border-radius de Bootstrap. */
        }

        .btn {
            border-radius: 8px;
        }

        .form-control {
            border-radius: 8px;
        }

        /* Botones e inputs con esquinas redondeadas, consistente con el diseño global. */
    </style>
</head>

<body>
    <div class="container">
        <!-- container → centra el contenido con márgenes automáticos a los lados -->

        <div class="row justify-content-center">
            <!--
            row                    → fila del grid de Bootstrap
            justify-content-center → centra las columnas horizontalmente dentro de la fila
        -->

            <div class="col-12 col-sm-9 col-md-6 col-lg-4">
                <!--
                Sistema responsive de la tarjeta de login:
                col-12   → móvil pequeño: ocupa todo el ancho
                col-sm-9 → móvil grande: 9/12 del ancho (algo de margen lateral)
                col-md-6 → tablet: mitad del ancho
                col-lg-4 → escritorio: un tercio del ancho (tarjeta estrecha y centrada)
            -->

                <div class="card">
                    <div class="card-header text-white text-center py-4">
                        <!--
                        text-white  → texto blanco sobre fondo azul
                        text-center → centra el icono y el título
                        py-4        → padding vertical generoso para que la cabecera respire
                    -->
                        <i class="bi bi-calendar2-check fs-1"></i>
                        <!-- Icono grande (fs-1) del calendario, mismo que en el navbar para coherencia -->
                        <h4 class="mb-0 mt-2 fw-bold">Sistema de Reservas</h4>
                        <!-- mb-0 elimina el margen inferior, mt-2 añade pequeño margen superior tras el icono -->
                    </div>

                    <div class="card-body p-4">
                        <!-- p-4 → padding de 4 unidades en todos los lados del cuerpo de la tarjeta -->

                        <h5 class="mb-4 text-center text-muted">Iniciar sesión</h5>
                        <!-- Subtítulo en gris (text-muted), mb-4 separa del formulario -->

                        <!-- ── MENSAJE DE ERROR ── -->
                        <?php if (isset($error)): ?>
                            <!--
                            Solo muestra el alert si la variable $error fue definida arriba.
                            $error se define cuando el email no existe o la contraseña es incorrecta.
                            La primera vez que se carga la página (GET), $error no existe y no aparece.
                        -->
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <!--
                                alert           → componente Bootstrap para mensajes de aviso
                                alert-danger    → fondo rojo (para errores)
                                d-flex          → display flex para alinear icono y texto en línea
                                align-items-center → centra verticalmente el icono con el texto
                                role="alert"    → accesibilidad: indica a lectores de pantalla que es un aviso
                            -->
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <!--
                                [SEGURIDAD] htmlspecialchars() evita XSS aunque el mensaje
                                lo generemos nosotros mismos. Es buena práctica aplicarlo siempre
                                a cualquier variable que se imprima en HTML.
                            -->
                            </div>
                        <?php endif; ?>

                        <!-- ── FORMULARIO DE LOGIN ── -->
                        <form method="POST">
                            <!--
                            method="POST" → envía los datos en el cuerpo de la petición HTTP, no en la URL.
                            Sin action="..." el formulario se envía a la misma página (login.php).
                            [SEGURIDAD] POST es más seguro que GET para credenciales: los datos
                            no aparecen en la URL ni en el historial del navegador.
                        -->

                            <!-- Campo Email -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="tu@email.com" required
                                        autocomplete="email">
                                    <!--
                                    type="email"        → el navegador valida el formato antes de enviar
                                    name="email"        → PHP lo recoge como $_POST["email"]
                                    required            → el navegador no deja enviar el formulario si está vacío
                                    autocomplete="email" → permite al navegador/gestor de contraseñas
                                                          autocompletar el email de forma segura
                                -->
                                </div>
                            </div>

                            <!-- Campo Contraseña -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control"
                                        placeholder="••••••••" required
                                        autocomplete="current-password">
                                    <!--
                                    type="password"           → oculta los caracteres escritos
                                    autocomplete="current-password" → permite al gestor de contraseñas
                                                                      rellenar la contraseña actual.
                                    [SEGURIDAD] autocomplete="off" estaba de moda antes pero los
                                    gestores de contraseñas modernos lo ignoran, y bloquear el
                                    autocompletado en realidad perjudica la seguridad porque lleva
                                    a los usuarios a usar contraseñas más sencillas.
                                -->
                                </div>
                            </div>

                            <!-- Botón de envío -->
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                <!--
                                type="submit"  → al hacer clic envía el formulario por POST
                                btn-primary    → botón azul relleno
                                w-100          → ocupa todo el ancho de la tarjeta
                                py-2           → padding vertical extra, botón más cómodo de pulsar
                                fw-semibold    → texto en seminegrita
                            -->
                                <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS de Bootstrap al final del body. Necesario para componentes interactivos. -->
</body>

</html>