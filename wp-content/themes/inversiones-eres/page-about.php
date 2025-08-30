<?php
/*
 * Template Name: About Page
 * Template Post Type: page
 */
get_header(); ?>

<!-- Hero Section -->
<section class="py-20 bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="container mx-auto px-4 lg:max-w-[1290px]">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-tight">
                Conectamos Moda y Servicios
            </h1>
            <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                Evolucionamos para facilitar tu vida: moda, servicios y canales digitales que te ponen primero. En
                Inversiones ERES, la excelencia se mide en tu tranquilidad.
            </p>
        </div>
    </div>
</section>

<!-- Our Story Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 lg:max-w-[1290px]">
        <div class="space-y-6">
            <h2 class="text-3xl font-bold text-gray-900 leading-tight">
                Fundados en Innovación y Excelencia
            </h2>
            <p class="text-gray-600 leading-relaxed">
                Inversiones ERES nació en 2010 con el propósito de enfrentar el desempleo y crear una fuente de
                ingresos propia, apostando por el emprendimiento y la autonomía. Desde el inicio, la empresa se
                distinguió por su enfoque en la comercialización de ropa nueva importada y la promoción de marcas
                hondureñas reconocidas, combinando oferta actualizada con identidad local.
            </p>
            <p class="text-gray-600 leading-relaxed">
                Con el paso del tiempo, la compañía evolucionó para responder a las exigencias de un mercado
                dinámico. Además de su actividad principal en vestimenta, diversificó sus
                servicios agregando agentes bancarios y puntos de cobro de servicios públicos y
                privados, abriendo nuevas líneas de ingreso y facilitando soluciones integrales a sus clientes en un
                solo lugar.
            </p>
            <p class="text-gray-600 leading-relaxed">
                Impulsada por la innovación y la excelencia, Inversiones ERES fortaleció sus procesos con estándares de
                calidad y atención al cliente, promoviendo una cultura basada en la responsabilidad, la honestidad y el
                trabajo en equipo. Esta visión operativa le ha permitido ofrecer una experiencia consistente—tanto en
                tienda física como en línea—centrada en la confianza y la cercanía
            </p>
            <p class="text-gray-600 leading-relaxed">
                En respuesta a la evolución del mercado, la empresa impulsó una expansión decidida en dos frentes:
                presencial y digital. Por un lado, extendió su presencia más allá de su localidad de origen; por otro,
                fortaleció su canal en línea mediante comercio electrónico e interacción activa en redes sociales,
                alcanzando nuevas audiencias y mejorando la disponibilidad de sus productos y servicios.
                De cara al futuro, Inversiones ERES mantiene un crecimiento con propósito: consolidar y diversificar su
                oferta de moda y servicios, profundizar su presencia digital y generar oportunidades que aporten un
                impacto positivo a la comunidad y a la economía local.
            </p>
        </div>
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