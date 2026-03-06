<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Flix_Asari_Houses {

	/**
	 * Register CPT and post meta.
	 *
	 * @return void
	 */
	public static function register_house_post_type_and_meta() {
		register_post_type(
			'fc_house',
			[
				'labels'       => [
					'name'          => 'Domy',
					'singular_name' => 'Dom',
				],
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => [ 'title', 'thumbnail', 'custom-fields' ],
				'has_archive'  => false,
				'rewrite'      => [ 'slug' => 'domy' ],
			]
		);

		$meta_keys = [
			'asari_id'                 => 'integer',
			'asari_listing_id'         => 'string',
			'asari_last_updated'       => 'integer',

			'house_number'             => 'string',
			'unit_side'                => 'string',
			'status_override'          => 'string',
			'plan_url'                 => 'string',
			'dims_url'                 => 'string',
			'model_img'                => 'integer',

			'asari_status'             => 'string',
			'asari_availability'       => 'string',
			'asari_price_amount'       => 'number',
			'asari_price_currency'     => 'string',
			'asari_total_area'         => 'number',
			'asari_no_of_rooms'        => 'integer',
			'asari_plot_area'          => 'number',

			'asari_card_appendix_id'   => 'integer',
			'asari_card_file_name'     => 'string',
			'asari_card_url'           => 'string',

			'asari_last_sync'          => 'integer',
			'asari_image_id'           => 'integer',
		];

		foreach ( $meta_keys as $meta_key => $meta_type ) {
			register_post_meta(
				'fc_house',
				$meta_key,
				[
					'single'        => true,
					'type'          => $meta_type,
					'show_in_rest'  => true,
					'auth_callback' => static function() {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}
	}

	/**
	 * Register public REST routes used by front-end houses section.
	 *
	 * @return void
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'flix-asari/v1',
			'/houses',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_get_houses' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'flix-asari/v1',
			'/appendix/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ 'Flix_Asari_API', 'rest_proxy_appendix' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public static function register_house_metabox() {
		add_meta_box(
			'flix_asari_house_map',
			'Dane domu (mapowanie mapy)',
			[ __CLASS__, 'render_house_metabox' ],
			'fc_house',
			'side',
			'high'
		);
	}

	public static function render_house_metabox( $post ) {
		$house_number = (string) get_post_meta( $post->ID, 'house_number', true );
		$unit_side    = (string) get_post_meta( $post->ID, 'unit_side', true );
		$plan_url     = (string) get_post_meta( $post->ID, 'plan_url', true );
		$asari_id     = (string) get_post_meta( $post->ID, 'asari_id', true );
		$availability = (string) get_post_meta( $post->ID, 'asari_availability', true );
		$appendix_id  = (string) get_post_meta( $post->ID, 'asari_card_appendix_id', true );

		wp_nonce_field( 'flix_asari_house_metabox', 'flix_asari_house_metabox_nonce' );
		?>
		<p style="margin: 0 0 8px;">
			<label for="flix_house_number"><strong>Numer domu (1–8 z mapy)</strong></label>
			<input
				type="text"
				id="flix_house_number"
				name="flix_house_number"
				value="<?php echo esc_attr( $house_number ); ?>"
				style="width: 100%;"
				placeholder="np. 6"
			/>
		</p>

		<p style="margin: 0 0 8px;">
			<label for="flix_unit_side"><strong>Strona lokalu</strong></label>
			<select id="flix_unit_side" name="flix_unit_side" style="width: 100%;">
				<option value="">— Wybierz —</option>
				<option value="left" <?php selected( $unit_side, 'left' ); ?>>left</option>
				<option value="right" <?php selected( $unit_side, 'right' ); ?>>right</option>
			</select>
		</p>

		<p style="margin: 0 0 8px;">
			<label for="flix_plan_url"><strong>Rzut nieruchomości (PDF/URL)</strong></label>
			<input
				type="url"
				id="flix_plan_url"
				name="flix_plan_url"
				value="<?php echo esc_attr( $plan_url ); ?>"
				style="width: 100%;"
				placeholder="https://…"
			/>
		</p>

		<hr style="margin: 10px 0;" />
		<hr style="margin: 10px 0;" />

		<p style="margin: 0 0 6px;">
			<strong>ASARI ID:</strong>
			<span><?php echo esc_html( $asari_id ? $asari_id : '—' ); ?></span>
		</p>

		<p style="margin: 0 0 6px;">
			<strong>Dostępność (customField_33480):</strong>
			<span><?php echo esc_html( $availability ? $availability : '—' ); ?></span>
		</p>

		<p style="margin: 0;">
			<strong>PDF appendix id:</strong>
			<span><?php echo esc_html( $appendix_id ? $appendix_id : '—' ); ?></span>
		</p>
		<?php
	}

	public static function save_house_metabox( $post_id ) {
		if ( ! isset( $_POST['flix_asari_house_metabox_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( (string) $_POST['flix_asari_house_metabox_nonce'] ), 'flix_asari_house_metabox' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['flix_house_number'] ) ) {
			$value = sanitize_text_field( (string) $_POST['flix_house_number'] );
			update_post_meta( $post_id, 'house_number', $value );
		}

		if ( isset( $_POST['flix_unit_side'] ) ) {
			$unit_side = sanitize_key( (string) $_POST['flix_unit_side'] );
			if ( in_array( $unit_side, [ 'left', 'right' ], true ) ) {
				update_post_meta( $post_id, 'unit_side', $unit_side );
			} else {
				delete_post_meta( $post_id, 'unit_side' );
			}
		}

		if ( isset( $_POST['flix_plan_url'] ) ) {
			$plan_url = esc_url_raw( (string) wp_unslash( $_POST['flix_plan_url'] ) );
			if ( '' !== $plan_url ) {
				update_post_meta( $post_id, 'plan_url', $plan_url );
			} else {
				delete_post_meta( $post_id, 'plan_url' );
			}
		}
	}

	/**
	 * REST endpoint: return houses list for front-end map.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_get_houses() {
		$posts = get_posts(
			[
				'post_type'              => 'fc_house',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			]
		);

		$data      = [];
		$buildings = [];

		foreach ( $posts as $post ) {
			$post_id   = (int) $post->ID;
			$number    = (string) get_post_meta( $post_id, 'house_number', true );
			$unit_side = sanitize_key( (string) get_post_meta( $post_id, 'unit_side', true ) );

			if ( '' === $number || ! in_array( $unit_side, [ 'left', 'right' ], true ) ) {
				continue;
			}

			$plan_url = (string) get_post_meta( $post_id, 'plan_url', true );

			$status = self::resolve_house_status( $post_id );
			if ( ! isset( $buildings[ $number ] ) ) {
				$buildings[ $number ] = [];
			}
			$buildings[ $number ][] = $status;

			$data[] = [
				'number'    => $number,
				'unit_side' => $unit_side,
				'status'    => $status,
				'label'     => get_the_title( $post_id ),
				'price'     => (float) get_post_meta( $post_id, 'asari_price_amount', true ),
				'currency'  => (string) get_post_meta( $post_id, 'asari_price_currency', true ),
				'area'      => (float) get_post_meta( $post_id, 'asari_total_area', true ),
				'rooms'     => (int) get_post_meta( $post_id, 'asari_no_of_rooms', true ),
				'plot'      => (float) get_post_meta( $post_id, 'asari_plot_area', true ),
				'plan_url'  => $plan_url,
				'dims_url'  => (string) get_post_meta( $post_id, 'dims_url', true ),
				'model_img' => Flix_Asari_API::build_asari_image_url( (int) get_post_meta( $post_id, 'asari_image_id', true ) ),
			];
		}

		$buildings_data = [];
		for ( $house_index = 1; $house_index <= 8; $house_index++ ) {
			$house_number = (string) $house_index;
			$statuses     = isset( $buildings[ $house_number ] ) ? $buildings[ $house_number ] : [];

			if ( empty( $statuses ) ) {
				continue;
			}

			$building_status = in_array( 'available', $statuses, true ) ? 'available' : 'sold';

			$buildings_data[] = [
				'building_number' => $house_number,
				'status'          => $building_status,
			];
		}

		return new WP_REST_Response(
			[
				'success'   => true,
				'data'      => $data,
				'buildings' => $buildings_data,
			],
			200
		);
	}

	/**
	 * Map post-level status with override support.
	 *
	 * @param int $post_id
	 * @return string
	 */
	private static function resolve_house_status( $post_id ) {
		$override = sanitize_key( (string) get_post_meta( $post_id, 'status_override', true ) );

		if ( in_array( $override, [ 'available', 'sold' ], true ) ) {
			return $override;
		}

		$availability_id = (string) get_post_meta( $post_id, 'asari_availability', true );

		if ( '40800' === $availability_id ) {
			return 'sold';
		}

		if ( '40799' === $availability_id ) {
			return 'available';
		}

		return 'available';
	}

	/**
	 * Find house post ID by asari_id.
	 *
	 * @param int $asari_id
	 * @return int
	 */
	public static function find_house_by_asari_id( $asari_id ) {
		$posts = get_posts(
			[
				'post_type'              => 'fc_house',
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => [
					[
						'key'   => 'asari_id',
						'value' => (int) $asari_id,
						'type'  => 'NUMERIC',
					],
				],
			]
		);

		if ( empty( $posts ) ) {
			return 0;
		}

		return (int) $posts[0];
	}
}