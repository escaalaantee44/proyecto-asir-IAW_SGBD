<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="utf-8">
    <!-- Establece la codificación de caracteres a UTF-8, necesario para mostrar tildes, ñ, etc. -->

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Le dice al navegador móvil que adapte el ancho de la página al ancho de la pantalla.
         Sin esto, Bootstrap responsive no funciona correctamente -->

    <title><?php echo isset($titulo) ? htmlspecialchars($titulo) : 'Sistema de Reservas'; ?></title>
    <!--
        PHP: Comprueba si existe la variable $titulo (que cada página puede definir antes de incluir este header).
        - isset($titulo)         → devuelve true si la variable existe y no es null
        - htmlspecialchars($titulo) → convierte caracteres peligrosos como < > & en su equivalente HTML
                                      seguro, evitando ataques XSS (que alguien inyecte código HTML en el título)
        - Si $titulo no existe, muestra 'Sistema de Reservas' como título por defecto
        Resultado: cada página puede tener su propio título en la pestaña del navegador
    -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Carga la hoja de estilos de Bootstrap 5.3.3 desde un CDN (servidor externo).
         Bootstrap es el framework CSS que da estilos a botones, tablas, tarjetas, etc. sin escribirlos a mano -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Carga la librería de iconos de Bootstrap (flechas, calendarios, personas...).
         Se usan con clases como: <i class="bi bi-calendar2-check"></i> -->

    <style>
        /* ── Estilos personalizados que sobreescriben o complementan Bootstrap ── */

        body {
            background-color: #f8f9fa;
            /* Color de fondo gris muy claro para toda la página.
               #f8f9fa es un color de Bootstrap (equivale a bg-light) */
        }

        .navbar-brand {
            font-weight: 700;
            /* El nombre "Sistema de Reservas" en la barra aparece en negrita (700 = negrita máxima) */
            letter-spacing: 0.5px;
            /* Añade un pequeño espacio entre letras para que se lea mejor */
        }

        .card {
            border: none;
            /* Elimina el borde por defecto que tienen las tarjetas de Bootstrap */
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            /* Añade una sombra suave debajo de la tarjeta para efecto de profundidad.
               rgba(0,0,0,.08) = negro al 8% de opacidad (muy sutil) */
            border-radius: 12px;
            /* Esquinas redondeadas. Bootstrap por defecto usa menos redondeo */
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
            /* Redondea solo las esquinas de arriba del encabezado de la tarjeta.
               !important fuerza este estilo por encima del CSS de Bootstrap */
            font-weight: 600;
            /* Texto en seminegrita */
        }

        .table th {
            font-size: .85rem;
            /* Las cabeceras de las tablas son un poco más pequeñas que el texto normal */
            text-transform: uppercase;
            /* Convierte el texto de las cabeceras a MAYÚSCULAS automáticamente */
            letter-spacing: .5px;
            /* Pequeño espaciado entre letras para estilo más "profesional" */
            color: #6c757d;
            /* Color gris medio (Bootstrap: text-secondary) para que destaquen menos que los datos */
        }

        .btn {
            border-radius: 8px;
            /* Todos los botones tienen esquinas más redondeadas que el valor por defecto de Bootstrap */
        }

        .badge {
            font-size: .78rem;
            /* Las etiquetas/badges (como "admin", "activo") son ligeramente más pequeñas */
        }

        footer {
            font-size: .8rem;
            /* El pie de página tiene texto pequeño */
            color: #adb5bd;
            /* Color gris claro (Bootstrap: text-muted) */
        }
    </style>
</head>

<body>
    <!-- ══════════════════════════════════════════════
         BARRA DE NAVEGACIÓN SUPERIOR
         Componente: Bootstrap Navbar
    ══════════════════════════════════════════════ -->

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <!--
        navbar              → convierte el <nav> en una barra de navegación Bootstrap
        navbar-expand-lg    → en pantallas grandes (lg = ≥992px) el menú se despliega horizontal.
                              En móvil se colapsa y aparece el botón de hamburguesa
        navbar-dark         → los textos e iconos del navbar serán blancos (para fondos oscuros)
        bg-primary          → fondo azul (el color primario de Bootstrap, por defecto #0d6efd)
        shadow-sm           → sombra pequeña debajo de la barra para efecto de elevación
    -->

        <div class="container-fluid">
        <!-- container-fluid → el contenido ocupa el 100% del ancho de la pantalla (sin márgenes laterales) -->

            <a class="navbar-brand" href="index.php">
            <!--
                navbar-brand → estilo especial para el nombre/logo de la aplicación (más grande y destacado)
                href="index.php" → al hacer clic vuelve al panel principal
            -->
                <i class="bi bi-calendar2-check me-2"></i>Sistema de Reservas
                <!--
                    bi bi-calendar2-check → icono de calendario con check de Bootstrap Icons
                    me-2 → margin-end (margen derecho) de 2 unidades, para separar el icono del texto
                -->
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <!--
                navbar-toggler      → botón de hamburguesa que Bootstrap muestra en móvil
                data-bs-toggle="collapse" → le dice a Bootstrap que este botón abre/cierra un menú colapsable
                data-bs-target="#navbarNav" → indica QUÉ elemento va a abrir/cerrar (el div con id="navbarNav")
            -->
                <span class="navbar-toggler-icon"></span>
                <!-- navbar-toggler-icon → Bootstrap dibuja aquí el icono de las 3 líneas (hamburguesa) -->
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
            <!--
                collapse        → este div está oculto por defecto en móvil
                navbar-collapse → clase necesaria para que Bootstrap gestione la animación de apertura
                id="navbarNav"  → identificador que usa el botón de arriba para saber qué abrir/cerrar
            -->

                <?php if (isset($_SESSION["id_usuario"])): ?>
                <!--
                    LÓGICA PHP: Solo muestra el menú de navegación si el usuario ha iniciado sesión.
                    $_SESSION["id_usuario"] se guarda en login.php cuando el usuario se autentica.
                    Si no existe esta variable, el usuario no está logueado y no ve el menú.
                -->

                    <!-- Menú izquierdo: enlaces principales -->
                    <ul class="navbar-nav me-auto">
                    <!--
                        navbar-nav  → lista de navegación con estilos Bootstrap (sin viñetas, en horizontal)
                        me-auto     → margin-end automático, empuja este menú a la izquierda
                                      y el siguiente (usuario/salir) a la derecha
                    -->
                        <li class="nav-item">
                        <!-- nav-item → elemento de la lista de navegación -->
                            <a class="nav-link" href="index.php">
                            <!-- nav-link → estilo de enlace dentro de la barra (color blanco, hover, etc.) -->
                                <i class="bi bi-house me-1"></i>Panel
                                <!-- bi-house → icono de casita | me-1 → pequeño margen derecho -->
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="reserva.php">
                                <i class="bi bi-calendar3 me-1"></i>Reservas
                            </a>
                        </li>

                        <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] == "admin"): ?>
                        <!--
                            LÓGICA PHP: Solo muestra el enlace a "Categorías" si el usuario es administrador.
                            $_SESSION["rol"] se guarda en login.php con el rol que tiene el usuario en la BD.
                            Doble condición:
                              - isset() → comprueba que la variable existe (evita error PHP si no está definida)
                              - == "admin" → comprueba que el valor sea exactamente "admin"
                            Si el rol es "usuario" normal, este enlace no aparece en el menú.
                        -->
                            <li class="nav-item">
                                <a class="nav-link" href="categorias.php">
                                    <i class="bi bi-tags me-1"></i>Categorías
                                    <!-- bi-tags → icono de etiquetas -->
                                </a>
                            </li>
                        <?php endif; ?>

                    </ul>

                    <!-- Menú derecho: nombre del usuario y botón de salir -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <span class="nav-link text-white-50">
                            <!--
                                nav-link    → mismo estilo que los otros enlaces
                                text-white-50 → texto blanco al 50% de opacidad (grisáceo),
                                               para diferenciarlo visualmente de los enlaces clicables
                            -->
                                <a href="usuario_perfil.php" class="bi bi-person-circle me-1">
                                    <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
                                    <!--
                                        Muestra el nombre del usuario logueado (guardado en sesión en login.php).
                                        htmlspecialchars() protege contra XSS: si el nombre contuviera
                                        caracteres como < o >, los convierte a &lt; &gt; y no se ejecutan como HTML.
                                    -->
                                </a>
                            </span>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Salir
                                <!-- bi-box-arrow-right → icono de salida (flecha saliendo de un cuadro) -->
                            </a>
                        </li>
                    </ul>

                <?php endif; ?>
                <!-- Fin del bloque: solo visible si el usuario está logueado -->

            </div>
        </div>
    </nav>
    <!-- ══ Fin de la barra de navegación ══ -->

    <div class="container py-4">
    <!--
        container → centra el contenido con márgenes automáticos a los lados (ancho máximo según pantalla)
        py-4      → padding vertical (arriba y abajo) de 4 unidades Bootstrap = 1.5rem ≈ 24px
                    Separa el contenido de la página del navbar y del footer
    -->
    <!-- Aquí empieza el contenido específico de cada página que incluya este header -->