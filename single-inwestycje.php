<?php
get_header();

$slug = get_post_field( 'post_name', get_queried_object_id() );

$template = locate_template( [
    "templates/inwestycje/{$slug}.php",
    "templates/inwestycje/default.php",
] );

if ( $template ) {
    include $template;
}

get_footer();
