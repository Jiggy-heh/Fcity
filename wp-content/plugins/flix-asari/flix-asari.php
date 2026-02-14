<?php
/**
 * Plugin Name: Flix Asari
 * Description: Foundation plugin for Asari integration and houses CPT.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function() {
		register_post_type(
			'fc_house',
			[
				'labels' => [
					'name' => 'Domy',
					'singular_name' => 'Dom',
				],
				'public' => true,
				'show_in_rest' => true,
				'supports' => [ 'title', 'thumbnail', 'custom-fields' ],
				'has_archive' => false,
				'rewrite' => [ 'slug' => 'domy' ],
			]
		);

		$meta_keys = [
			'asari_id' => 'string',
			'house_number' => 'string',
			'status_override' => 'string',
			'plan_url' => 'string',
			'dims_url' => 'string',
			'model_img' => 'integer',
		];

		foreach ( $meta_keys as $meta_key => $meta_type ) {
			register_post_meta(
				'fc_house',
				$meta_key,
				[
					'single' => true,
					'type' => $meta_type,
					'show_in_rest' => true,
					'auth_callback' => static function() {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}
	}
);
