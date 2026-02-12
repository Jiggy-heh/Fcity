<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Homepage -> ładuje gotowy template inwestycji (Święciechowa)
 */

get_header();

// Jeśli chcesz mieć LP nav na home:
get_template_part( 'template-parts/components/lp-nav' );

// Ładujesz CAŁY gotowy template:
include get_template_directory() . '/templates/inwestycje/swieciechowa.php';

get_footer();
