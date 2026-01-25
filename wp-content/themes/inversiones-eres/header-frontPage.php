<!DOCTYPE html>
<html <?php language_attributes(); ?>>
  <head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
    <div class="w-full fixed top-0 z-50 transition-all duration-300" id="header">
      <div class="bg-transparent text-white w-full h-[70px] md:h-[70px] sm:h-[70px] transition-all duration-300">
        <div class="flex items-center justify-between h-full px-4 lg:max-w-[1290px] lg:mx-auto w-full">
            <img src="<?php echo get_template_directory_uri(); ?>/images/inversionesERES.png" alt="Inversiones ERES Logo" class="h-10 w-auto">
            
            <!-- Mobile menu button -->
            <button class="md:hidden text-white hover:text-[#4B7EDD] focus:outline-none transition-colors duration-200 cursor-pointer" id="mobile-menu-button">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile menu -->
        <div class="md:hidden bg-black bg-opacity-90 border-t border-gray-600 hidden" id="mobile-menu">
            <div class="px-4 py-2 space-y-2">
                <a href="<?php echo home_url('/home'); ?>" class="block text-white hover:text-[#4B7EDD] py-2">Home</a>
                <a href="<?php echo home_url('/sobre-nosotros'); ?>" class="block text-white hover:text-[#4B7EDD] py-2">Sobre Nosotros</a>
            </div>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.getElementById('header');
        const headerInner = header.querySelector('div');
        
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
                headerInner.classList.remove('bg-transparent');
                headerInner.classList.add('bg-[#1F2530]', 'shadow-lg');
            } else {
                header.classList.remove('scrolled');
                headerInner.classList.add('bg-transparent');
                headerInner.classList.remove('bg-[#1F2530]', 'shadow-lg');
            }
        });
    });
    </script>


