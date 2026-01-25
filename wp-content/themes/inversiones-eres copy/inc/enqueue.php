<?php
/**
 * Enqueue scripts and styles
 */

function diagnetix1_scripts() {
    wp_enqueue_style('diagnetix1-tailwind', get_template_directory_uri() . '/src/output.css', array(), '1.0.3');

    wp_enqueue_style('diagnetix1-custom', get_template_directory_uri() . '/assets/css/custom.css', array('diagnetix1-tailwind'), '1.0.1');
    
    wp_enqueue_script('diagnetix1-main', get_template_directory_uri() . '/assets/js/main.js', array(), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'diagnetix1_scripts');