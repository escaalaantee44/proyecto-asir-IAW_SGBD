<?php
session_start();
// Inicia el sistema de sesiones de PHP.
// DEBE ser la primera línea antes de cualquier HTML o echo.
// Sin esto, $_SESSION no existe y no podemos leer quién está logueado.

// ── CONTROL DE ACCESO ─────────────────────────────────────────────────────────

if (!isset($_SESSION["id_usuario"])) {
    // Comprueba si NO existe la variable de sesión "id_usuario".
    // Si no existe significa que el usuario no ha iniciado sesión.

    header("Location: login.php");
    exit();
    // [SEGURIDAD] exit() es obligatorio tras header("Location:...").
    // Sin él, PHP continuaría ejecutando el resto del script aunque el navegador
    // haya recibido el redirect. Esto podría exponer datos o lógica de la página
    // a un atacante que ignore la cabecera de redirección (por ejemplo, con curl).
}

// ── TÍTULO Y CABECERA ─────────────────────────────────────────────────────────

$titulo = "Panel principal";
// Define la variable $titulo que el header.php usa para el <title> de la pestaña.
// Cada página define su propio $titulo antes de incluir el header.

include "includes/header.php";
// Inserta el contenido de header.php aquí como si fuera parte de este archivo.
// Esto carga: DOCTYPE, navbar, Bootstrap CSS, y abre el <div class="container">
?>

<!-- ══════════════════════════════════════════════
     CABECERA DE LA PÁGINA
══════════════════════════════════════════════ -->

<div class="mb-4">
    <!-- mb-4 → margin-bottom de 4 unidades, separa este bloque de las tarjetas de abajo -->

    <h2 class="fw-bold">
        <!-- fw-bold → font-weight bold, título en negrita -->
        <i class="bi bi-grid me-2 text-primary"></i>Panel principal
        <!--
            bi-grid      → icono de cuadrícula (representa un panel/dashboard)
            me-2         → margen derecho para separar el icono del texto
            text-primary → color azul primario de Bootstrap
        -->
    </h2>

    <p class="text-muted">Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"]); ?></p>
    <!--
        text-muted → texto en gris suave, menos prominente que el título
        [SEGURIDAD] htmlspecialchars() protege contra XSS: convierte caracteres
        peligrosos como < > & en entidades HTML seguras (&lt; &gt; &amp;).
        Aunque el nombre venga de nuestra propia BD, siempre se debe escapar
        cualquier variable que se imprima en HTML.
    -->
</div>

<!-- ══════════════════════════════════════════════
     GRID DE TARJETAS
     Bootstrap Row + Columns
══════════════════════════════════════════════ -->

<div class="row g-3">
    <!--
        row  → fila del sistema de rejilla (grid) de Bootstrap. Convierte este div
               en un contenedor flex; los .col-* de dentro se colocan en línea
               y se ajustan automáticamente.
        g-3  → gutter de 3 unidades: espacio (hueco) entre columnas y filas del grid

        IMPORTANTE: esta apertura del .row debe imprimirse SIEMPRE, sin estar
        dentro de ningún if. Si se mete dentro de un "if admin", los usuarios
        normales se quedan sin contenedor .row y las tarjetas .col-md-4 caen
        cada una en su propia línea.

        ESTRATEGIA DE CENTRADO (las 2 últimas tarjetas):
        Bootstrap divide la fila en 12 columnas. Cada tarjeta col-md-4 ocupa 4.
        - Admin (5 tarjetas): fila 1 = Categorías + Recursos + Reservas (4+4+4=12,
          llena la fila exacta). Fila 2 = Gestión de Usuarios + Reserva de hoy
          (4+4=8). La primera de la fila 2 lleva offset-md-2: deja 2 columnas
          libres a la izquierda (y 2 a la derecha), centrando el par.
        - Normal (2 tarjetas): única fila = Reservas + Reserva de hoy (4+4=8).
          "Reservas" es la primera → lleva offset-md-2 solo si no es admin.
    -->

    <?php if ($_SESSION["rol"] == "admin"): ?>
        <!--
            [SEGURIDAD] Se comprueba el rol en el servidor (PHP), nunca en el cliente (JS/HTML).
            Un usuario no puede falsear $_SESSION["rol"] desde el navegador porque las
            variables de sesión viven en el servidor, no en el navegador.
            Se usan dos bloques if separados (Categorías y Recursos) para poder
            reorganizarlos o añadir lógica independiente en el futuro sin tocar el otro.
            [LIMPIEZA] Los dos if de admin previos (Categorías y Recursos) se fusionan
            en uno solo aquí: ambos comparten exactamente la misma condición, así que
            un único if que los envuelva es más limpio y evita repetir la comprobación.
        -->

        <!-- ── TARJETA: CATEGORÍAS (solo admin) ── -->
        <div class="col-12 col-md-4">
            <!--
                col-12   → en móvil ocupa las 12 columnas (ancho completo, una tarjeta por fila)
                col-md-4 → en pantallas medianas/grandes ocupa 4 de 12 columnas (3 tarjetas por fila)
                           Bootstrap divide la fila en 12 columnas. 4+4+4 = 12 → 3 tarjetas en una fila
            -->
            <div class="card h-100">
                <!--
                    card  → componente tarjeta de Bootstrap (fondo blanco, esquinas redondeadas, sombra)
                    h-100 → height 100%: todas las tarjetas de la fila tienen la misma altura,
                            aunque su contenido sea diferente
                -->
                <div class="card-body text-center py-4">
                    <!--
                        card-body   → área de contenido principal de la tarjeta (con padding automático)
                        text-center → centra todo el contenido horizontalmente
                        py-4        → padding vertical extra para que la tarjeta respire
                    -->
                    <i class="bi bi-tags fs-1 text-primary mb-3 d-block"></i>
                    <!--
                        bi-tags      → icono de etiquetas (representa categorías)
                        fs-1         → font-size más grande de Bootstrap
                        text-primary → color azul
                        mb-3         → margen inferior para separar el icono del título
                        d-block      → display block, necesario para que mb-3 funcione en un <i>
                                       (los <i> son inline por defecto y no respetan márgenes verticales)
                    -->
                    <h4 class="fw-bold">Categorías</h4>
                    <p class="text-muted">Gestiona los tipos de recursos.</p>
                    <a href="categorias.php" class="btn btn-primary w-100">
                        <!--
                            btn         → clase base que convierte el enlace en un botón con estilos
                            btn-primary → botón azul (color primario)
                            w-100       → width 100%: el botón ocupa todo el ancho de la tarjeta
                        -->
                        <i class="bi bi-arrow-right-circle me-1"></i>Gestionar
                    </a>
                </div>
            </div>
        </div>

        <!-- ── TARJETA: RECURSOS (solo admin) ── -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-box-seam fs-1 text-primary mb-3 d-block"></i>
                    <!-- bi-box-seam → icono de caja/paquete (representa recursos físicos) -->
                    <h4 class="fw-bold">Recursos</h4>
                    <p class="text-muted">Administra los recursos reservables.</p>
                    <a href="recurso.php" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-right-circle me-1"></i>Gestionar
                    </a>
                </div>
            </div>
        </div>

    <?php endif; ?>
    <!-- Fin del bloque exclusivo de admin (Categorías + Recursos) -->

    <!-- ── TARJETA: RESERVAS (visible para todos) ── -->
    <div class="col-12 col-md-4<?php echo ($_SESSION["rol"] != "admin") ? " offset-md-2" : ""; ?>">
        <!--
            offset-md-2 condicional:
            - Si el rol NO es admin: esta es la 1ª tarjeta de la única fila.
              Necesita offset-md-2 para centrar el par junto a "Reserva de hoy".
            - Si el rol ES admin: es la 3ª tarjeta (4+4+4=12, llena la fila).
              NO debe llevar offset.
            [SEGURIDAD] El rol se lee de $_SESSION (servidor), no de ningún
            parámetro de la URL que el usuario pudiera manipular.
        -->
        <div class="card h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-calendar3 fs-1 text-primary mb-3 d-block"></i>
                <!-- bi-calendar3 → icono de calendario (representa reservas) -->
                <h4 class="fw-bold">Reservas</h4>
                <p class="text-muted">Crea y revisa reservas.</p>
                <a href="reserva.php" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right-circle me-1"></i>Gestionar
                </a>
            </div>
        </div>
    </div>

    <?php if ($_SESSION["rol"] == "admin"): ?>
        <!-- ── TARJETA: GESTIÓN DE USUARIOS (solo admin) ── -->
        <!--
            [SEGURIDAD] Doble protección: aunque un usuario normal llegara a ver
            este HTML (imposible porque el PHP no lo imprime), el enlace a
            usuarios.php también tiene su propio control de acceso independiente.
            

            offset-md-2 fijo: 1ª tarjeta de la fila 2 (junto a Reserva de hoy,
            4+4=8 columnas). El offset centra el par dejando 2 columnas libres
            a cada lado.
        -->
        <div class="col-12 col-md-4 offset-md-2">
            <div class="card h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-gear fs-1 text-primary mb-3 d-block"></i>
                    <!-- bi-person-gear → icono de persona con engranaje (administración de usuarios) -->
                    <h4 class="fw-bold">Gestión de Usuarios</h4>
                    <p class="text-muted">Crea y revisa usuarios.</p>
                    <a href="usuarios.php" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-right-circle me-1"></i>Gestionar
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── TARJETA: RESERVA DE HOY (visible para todos) ── -->
    <div class="col-12 col-md-4">
        <!--
            Sin offset: siempre es la 2ª tarjeta de su fila (justo después de
            "Reservas" en vista normal, o de "Gestión de Usuarios" en vista admin).
            Se coloca automáticamente a continuación del offset de la primera.
        -->
        <div class="card h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-calendar-check fs-1 text-primary mb-3 d-block"></i>
                <!-- bi-calendar-check → icono de calendario con marca (distingue de Reservas general) -->
                <h4 class="fw-bold">Reserva del día de hoy</h4>
                <p class="text-muted">Mira tus reservas de hoy</p>
                <a href="reserva_hoy.php" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right-circle me-1"></i>Mira y verifica
                </a>
            </div>
        </div>
    </div>

</div>
<!-- Cierra el div.row g-3 abierto al principio del grid -->

<!-- ── BOTÓN: EDITAR PERFIL (visible para todos) ── -->
<div class="mb-4 mt-3">
    <!--
        mt-3 → margen superior para separar el botón del grid de tarjetas.
        Este botón vive FUERA del .row: no es una tarjeta del grid, así que no
        debe ser un elemento flex dentro de la fila de columnas.
    -->
    <a href="usuario_perfil.php" class="btn btn-outline-primary">
        <!--
            btn-outline-primary → botón con solo el borde azul y texto azul (sin fondo relleno).
                                  Visualmente menos prominente que btn-primary, lo que indica
                                  que es una acción secundaria respecto a las tarjetas principales.
        -->
        <i class="bi bi-person-circle me-1"></i> Editar mi perfil
    </a>
</div>

<?php include "includes/footer.php"; ?>
<!--
    Inserta el footer.php: cierra el </div> del container, muestra el pie de página
    con el copyright, carga el JS de Bootstrap y cierra </body> y </html>
-->