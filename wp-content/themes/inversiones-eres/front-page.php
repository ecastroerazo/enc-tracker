<?php get_header(); ?>
<section class="relative flex items-center min-h-screen overflow-hidden">
    <!-- Background Image -->
    <img src="<?php echo get_template_directory_uri(); ?>/images/heroBanner1.jpeg" alt="Winston Background"
        class="absolute inset-0 w-full h-full object-cover z-0" />
    <div class="absolute inset-0 bg-black/40 z-0"></div>

    <div class="container mx-auto px-4 lg:max-w-[1350px] relative z-10">
        <div class="text-center max-w-4xl mx-auto text-white">

            <h1 id="hero-title" class="text-5xl md:text-5xl font-bold mb-6 leading-tight opacity-0 translate-y-8 transition-all duration-1000">
                ¡DE NUESTRA TIENDA A TU PUERTA!
            </h1>

            <p class="text-xl mb-8 leading-relaxed">
                En ERES Internacional también estamos a un clic de distancia. A través de nuestra venta en línea,
                ofrecemos la comodidad de elegir tus prendas favoritas desde cualquier lugar, con opciones de entrega
                seguras y rápidas que se adaptan a tu estilo de vida.
            </p>
        </div>
        <script>
        window.addEventListener('DOMContentLoaded', function () {
            const title = document.getElementById('hero-title');
            if (title) {
                setTimeout(() => {
                    title.classList.remove('opacity-0', 'translate-y-8');
                    title.classList.add('opacity-100', 'translate-y-0');
                }, 200);
            }
        });
        </script>
    </div>
</section>
<section class="py-8 bg-white">
    <div class="container mx-auto px-4 lg:max-w-[1290px]">
        <div class="space-y-4 order-1 lg:order-2">
            <h2 class="text-4xl font-bold text-gray-900 leading-tight">
                Sobre Nosotros
            </h2>
            <p class="text-lg text-gray-600 leading-relaxed">
                En ERES Internacional trabajamos para acercar a nuestros clientes lo mejor en moda y productos de
                calidad, siempre con un enfoque en la tendencia, la accesibilidad y la confianza.
            </p>
            <p class="text-lg text-gray-600 leading-relaxed">
                Nuestra empresa nació con la idea de que vestir bien y sentirse bien debe ser sencillo, por eso
                ofrecemos colecciones actualizadas, precios competitivos y un servicio personalizado que nos distingue.
            </p>
            <p class="text-lg text-gray-600 leading-relaxed">
                Cada día seguimos creciendo gracias a la preferencia de nuestros clientes y al esfuerzo de un equipo
                comprometido, que comparte la pasión por la moda y el buen servicio.
            </p>
            <p class="text-lg text-gray-600 leading-relaxed">
                En ERES Internacional, cada compra representa más que un producto: es la experiencia de recibir estilo,
                calidad y atención con amor.
            </p>

        </div>
    </div>
</section>

<section class="py-10 bg-gray-50">
    <div class="container mx-auto px-4 lg:max-w-[1150px]">
        <div class="text-center mb-6">
            <h2 class="text-4xl md:text-4xl font-bold text-gray-900 mb-4">
                Quienes Somos?
            </h2>
            <p class="text-lg text-gray-600">
                Inversiones ERES está comprometida a marcar la diferencia e impactar positivamente en
                la sociedad. A través de actividades socio legales, éticas y sociales, la empresa contribuye al
                desarrollo sostenible de la comunidad y al bienestar de sus empleados y clientes. La
                implementación de normas éticas garantiza que todos los colaboradores actúen de acuerdo
                con los valores que representan a la empresa, promoviendo un ambiente de respeto,
                honestidad y responsabilidad.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="p-3 text-center">
                <div class="flex items-center justify-center mx-auto mb-2">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/vision.png" alt="Innovative Solutions"
                        class="w-8 h-8">
                </div>
                <h3 class="text-2xl font-semibold mb-2">Visión</h3>
                <p class="text-gray-600 text-lg">Convertirnos en una empresa que brinde múltiples fuentes de trabajo a
                    el país. Así mismo,
                    desplazarnos de ser una tienda únicamente física y explotar el mercado virtual por medio del
                    uso de las redes sociales y la venta en línea.</p>
            </div>
            <div class="p-3 text-center">
                <div class="flex items-center justify-center mx-auto mb-2">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/mission.png" alt="Expert Team"
                        class="w-8 h-8">
                </div>
                <h3 class="text-2xl font-semibold mb-2">Misión</h3>
                <p class="text-gray-600 text-lg">Brindar la mejor atención y servicio durante la experiencia de compra
                    en nuestra tienda,
                    ya sea en su compra física o virtual, además de garantizar una excelente calidad de cada uno
                    de nuestros productos.</p>
            </div>
            <div class="p-3 text-center">
                <div class="flex items-center justify-center mx-auto mb-2">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/values.png" alt="Customer-Centric"
                        class="w-8 h-8">
                </div>
                <h3 class="text-2xl font-semibold mb-2">Nuestros Valores</h3>
                <p class="text-gray-600 text-lg">Nuestros valores se fundamentan en la calidad, responsabilidad,
                    amabilidad, lealtad y honestidad, impulsando la competitividad, el trabajo en equipo y la
                    productividad en todo lo que hacemos.</p>
            </div>
        </div>
    </div>
</section>
<section class="container mx-auto px-4 py-10 lg:max-w-[1290px]">
    <h2 class="text-4xl md:text-4xl font-bold text-gray-900 mb-4 text-center">
        Nuestras Marcas
    </h2>
    <div class="relative">

        <!-- Left button -->
        <button id="marcas-left"
            class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white/70 hover:bg-white rounded-full p-3 shadow transition-all text-3xl font-bold text-gray-700">
            ‹
        </button>

        <!-- Viewport -->
        <div id="marcas-viewport" class="overflow-hidden">
            <div id="marcas-track" class="flex space-x-4 py-2 transition-transform duration-300 will-change-transform">
                <img src="<?php echo get_template_directory_uri(); ?>/images/Brenda.jpeg" alt="Brenda"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/vanheusen.jpeg" alt="Van Heusen"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/pacer.jpeg" alt="Pacer"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/difer.png" alt="Cinque Terre"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/pepe.jpeg" alt="Forest"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/proman.png" alt="Northern Lights"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/wanted.png" alt="Mountains"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/girl.png" alt="Mountains"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/giuliany.jpeg" alt="Mountains"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/pink1.png" alt="Mountains"
                    class="inline-block rounded shadow select-none" draggable="false" />
                <img src="<?php echo get_template_directory_uri(); ?>/images/leo1.png" alt="Mountains"
                    class="inline-block rounded shadow select-none" draggable="false" />
            </div>
        </div>

        <!-- Right button -->
        <button id="marcas-right"
            class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white/70 hover:bg-white rounded-full p-3 shadow transition-all text-3xl font-bold text-gray-700">
            ›
        </button>

        <script>
            (function () {
                const viewport = document.getElementById('marcas-viewport');
                const track = document.getElementById('marcas-track');
                const left = document.getElementById('marcas-left');
                const right = document.getElementById('marcas-right');
                const images = Array.from(track.querySelectorAll('img'));

                let position = 0; // index of the first visible image
                const GAP = 20;   // px gap from Tailwind space-x-4 (4*4)

                // Sum widths up to a given index
                function getOffsetFor(index) {
                    let total = 0;
                    for (let i = 0; i < index; i++) {
                        total += images[i].offsetWidth + GAP;
                    }
                    return total;
                }

                function update() {
                    track.style.transform = `translateX(${-getOffsetFor(position)}px)`;
                }

                // How many images fit in the viewport starting at `position`
                function getVisibleCount() {
                    let count = 0;
                    let width = 0;
                    const maxWidth = viewport.clientWidth;
                    while (position + count < images.length) {
                        const w = images[position + count].offsetWidth;
                        if (width + w > maxWidth) break;
                        width += w + GAP;
                        count++;
                    }
                    return Math.max(1, count);
                }

                right.addEventListener('click', () => {
                    const visible = getVisibleCount();
                    if (position + visible >= images.length) {
                        // reached or passed the last page ⇒ jump to start
                        position = 0;
                    } else {
                        position += visible; // page forward
                    }
                    update();
                });

                left.addEventListener('click', () => {
                    const visible = getVisibleCount();
                    if (position === 0) {
                        // at start ⇒ jump to the last full page
                        let lastStart = images.length - visible;
                        if (lastStart < 0) lastStart = 0;
                        position = lastStart;
                    } else {
                        position = Math.max(0, position - visible); // page back
                    }
                    update();
                });

                // Recalculate on resize/load (after images have real sizes)
                window.addEventListener('resize', update);
                window.addEventListener('load', update);
            })();
        </script>
    </div>
</section>

<section class="py-12 bg-[#1F2530] text-white">
    <div class="container mx-auto px-4 lg:max-w-[1290px]">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
            <!-- Useful Links -->
            <div>
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Enlaces útiles
                </h2>
                <ul class="space-y-2">
                    <li><a href="<?php echo home_url('/home'); ?>" class="hover:text-amber-400 transition">Home</a></li>
                    <li><a href="<?php echo home_url('/sobre-nosotros'); ?>"
                            class="hover:text-amber-400 transition">Sobre nosotros</a></li>
                </ul>
            </div>
            <!-- About Us -->
            <div>
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Sobre Nosotros
                </h2>
                <p class="text-gray-300">Somos una empresa dedicada a ofrecer los mejores productos y servicios a
                    nuestros clientes.</p>
            </div>
            <!-- Contact -->
            <div>
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 10.5V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2v-4.5" />
                    </svg>
                    Contáctanos
                </h2>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 12H8m8 0a4 4 0 11-8 0 4 4 0 018 0zm0 0v1a4 4 0 01-8 0v-1" />
                        </svg>
                        <a href="mailto:andreas_bazar@yahoo.com" class="hover:underline">andreas_bazar@yahoo.com</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10l1.5 1.5a2 2 0 002.5 0l1.5-1.5M19 10l-1.5 1.5a2 2 0 01-2.5 0L14 10" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12v2a4 4 0 004 4h0a4 4 0 004-4v-2" />
                        </svg>
                        <a href="tel:31400392" class="hover:underline">Phone 31400392</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>