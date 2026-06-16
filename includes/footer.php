</div>
    <!-- Cierra el <div class="container py-4"> que se abrió en el header.
         Todo el contenido específico de cada página ha quedado dentro de este div -->

    
    <footer class="text-center py-4 mt-4 border-top">
    <!--
        text-center → centra el texto horizontalmente
        py-4        → padding vertical (arriba y abajo) de 4 unidades ≈ 24px
        mt-4        → margin-top de 4 unidades, separa el footer del contenido de arriba
        border-top  → dibuja una línea gris fina en la parte superior del footer
                      para separarlo visualmente del resto de la página
    -->

        <p class="mb-0">
        <!-- mb-0 → margin-bottom de 0, elimina el margen inferior por defecto del <p>
                    Evita espacio extra debajo del texto del footer -->

            <i class="bi bi-calendar2-check me-1"></i>Sistema de Reservas &copy;
            <!--
                bi bi-calendar2-check → mismo icono de calendario con check que aparece en el navbar,
                                        mantiene coherencia visual en toda la app
                me-1                  → margin-end (margen derecho) de 1 unidad, separa el icono del texto
                &copy;                → entidad HTML que muestra el símbolo © de copyright
            -->

            <?php echo date('Y'); ?>
            <!--
                PHP: La función date('Y') devuelve el año actual con 4 dígitos (ej: 2025).
                Así el año del copyright se actualiza solo cada año sin tocar el código.
                Resultado final en pantalla: "Sistema de Reservas © 2025"
            -->
        </p>
    </footer>
    <!-- ══ Fin del footer ══ -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!--
        Carga el JavaScript de Bootstrap 5.3.3 desde CDN.
        Se pone AL FINAL del body (no en el <head>) por dos razones:
          1. El HTML ya está cargado cuando se ejecuta el JS → no hay errores de "elemento no encontrado"
          2. La página se ve antes aunque el JS tarde en cargar → mejor rendimiento percibido

        "bundle" significa que incluye también Popper.js, que Bootstrap necesita para:
          - El menú hamburguesa (collapse del navbar en móvil)
          - Dropdowns, tooltips, modales, etc.
        Sin este script, los componentes interactivos de Bootstrap no funcionan.
    -->

</body>


</html>
