<?php
get_header();

while (have_posts()) {
    the_post();
    the_content(); // this will output your [enc_app] shortcode
}

get_footer();