<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function flixcity_register_cpt_inwestycje() {

	$labels = [
		'name'               => 'Inwestycje',
		'singular_name'      => 'Inwestycja',
		'menu_name'          => 'Inwestycje',
		'name_admin_bar'     => 'Inwestycja',
		'add_new'            => 'Dodaj nową',
		'add_new_item'       => 'Dodaj nową inwestycję',
		'new_item'           => 'Nowa inwestycja',
		'edit_item'          => 'Edytuj inwestycję',
		'view_item'          => 'Zobacz inwestycję',
		'all_items'          => 'Wszystkie inwestycje',
		'search_items'       => 'Szukaj inwestycji',
		'not_found'          => 'Brak inwestycji',
		'not_found_in_trash' => 'Brak inwestycji w koszu',
	];

	$args = [
		'labels'             => $labels,
		'public'             => true,
		'has_archive'        => true,
		'rewrite'            => [
			'slug' => 'inwestycje',
		],
		'menu_icon'          => 'dashicons-building',
		'supports'           => [
			'title',
			'editor',
			'thumbnail',
		],
		'show_in_rest'       => true, // Gutenberg / API
	];

	register_post_type( 'inwestycje', $args );
}
add_action( 'init', 'flixcity_register_cpt_inwestycje' );
