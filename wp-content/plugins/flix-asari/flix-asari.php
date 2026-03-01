<?php
/**
 * Plugin Name: Flix Asari
 * Description: Foundation plugin for Asari SITE API integration and houses CPT.
 * Version: 0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Flix_Asari_Plugin {
	const OPTION_SETTINGS       = 'flix_asari_settings';
	const TRANSIENT_SYNC_LOCK   = 'flix_asari_sync_lock';
	const TRANSIENT_THROTTLE_TS = 'flix_asari_last_request_ts';
	const CRON_HOOK             = 'flix_asari_cron_sync';
	const CRON_SCHEDULE         = 'flix_asari_every_ten_minutes';

	// SITE API (wg dokumentacji: /site + nagłówek SiteAuth: userId:Token)
	const API_BASE              = 'https://api.asari.pro/site';
	const API_APPENDIX_BASE     = 'https://api.asari.pro/apiAppendix';
	const SITE_USER_ID          = 80087;

	const MIN_REQUEST_INTERVAL  = 3;

	/**
	 * Boot plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_house_post_type_and_meta' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_filter( 'cron_schedules', [ __CLASS__, 'register_cron_schedule' ] );
		add_action( 'admin_post_flix_asari_run_sync_now', [ __CLASS__, 'handle_run_sync_now' ] );		
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_cron_sync' ] );
		add_action( 'admin_menu', [ __CLASS__, 'register_admin_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_post_flix_asari_test_connection', [ __CLASS__, 'handle_test_connection' ] );
		add_action( 'add_meta_boxes', [ __CLASS__, 'register_house_metabox' ] );
		add_action( 'save_post_fc_house', [ __CLASS__, 'save_house_metabox' ] );
		register_activation_hook( __FILE__, [ __CLASS__, 'on_activation' ] );
		register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivation' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'asari sync', [ __CLASS__, 'cli_sync' ] );
		}
	}

	/**
	 * Register CPT and post meta.
	 *
	 * @return void
	 */


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
		$house_number   = (string) get_post_meta( $post->ID, 'house_number', true );
		$unit_side      = (string) get_post_meta( $post->ID, 'unit_side', true );
		$asari_id       = (string) get_post_meta( $post->ID, 'asari_id', true );
		$availability   = (string) get_post_meta( $post->ID, 'asari_availability', true );
		$appendix_id    = (string) get_post_meta( $post->ID, 'asari_card_appendix_id', true );

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
	}

	public static function handle_run_sync_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'flix-asari' ) );
		}

		check_admin_referer( 'flix_asari_run_sync_now', 'flix_asari_run_sync_now_nonce' );

		$result = self::sync_listings( false );

		$status = is_wp_error( $result ) ? 'fail' : 'ok';

		if ( is_wp_error( $result ) ) {
			self::log_error( 'Manual sync failed: ' . $result->get_error_message() );
		} else {
			self::log_error( 'Manual sync OK. Processed=' . (int) $result['processed'] );
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=flix-asari&flix_asari_sync=' . $status ) );
		exit;
	}

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
			'asari_id'                => 'integer',
			'asari_listing_id'         => 'string',
			'asari_last_updated'       => 'integer',

			'house_number'            => 'string',
			'unit_side'               => 'string',
			'status_override'         => 'string',
			'plan_url'                => 'string',
			'dims_url'                => 'string',
			'model_img'               => 'integer',

			'asari_status'            => 'string',
			'asari_availability'      => 'string',
			'asari_price_amount'      => 'number',
			'asari_price_currency'    => 'string',
			'asari_total_area'        => 'number',
			'asari_no_of_rooms'       => 'integer',
			'asari_plot_area'         => 'number',

			'asari_card_appendix_id'  => 'integer',
			'asari_card_file_name'    => 'string',
			'asari_card_url'          => 'string',

			'asari_last_sync'         => 'integer',
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
				'callback'            => [ __CLASS__, 'rest_proxy_appendix' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Activation logic.
	 *
	 * @return void
	 */
	public static function on_activation() {
		self::maybe_schedule_cron();
	}

	/**
	 * Deactivation logic.
	 *
	 * @return void
	 */
	public static function on_deactivation() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		delete_transient( self::TRANSIENT_SYNC_LOCK );
	}

	/**
	 * Add 10-minute cron schedule.
	 *
	 * @param array<string,mixed> $schedules Existing schedules.
	 * @return array<string,mixed>
	 */
	public static function register_cron_schedule( $schedules ) {
		$schedules[ self::CRON_SCHEDULE ] = [
			'interval' => 10 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 10 Minutes', 'flix-asari' ),
		];

		return $schedules;
	}

	/**
	 * Schedule cron only when enabled in settings.
	 *
	 * @return void
	 */
	public static function maybe_schedule_cron() {
		$settings = self::get_settings();

		if ( empty( $settings['cron_enabled'] ) ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, self::CRON_SCHEDULE, self::CRON_HOOK );
		}
	}

	/**
	 * Cron callback.
	 *
	 * @return void
	 */
	public static function run_cron_sync() {
		if ( ! self::acquire_sync_lock() ) {
			self::log_error( 'Cron sync skipped: lock already in place.' );
			return;
		}

		$result = self::sync_listings( false );

		if ( is_wp_error( $result ) ) {
			self::log_error( sprintf( 'Cron sync failed: %s', $result->get_error_message() ) );
		}

		self::release_sync_lock();
	}

	/**
	 * WP-CLI entrypoint.
	 *
	 * @param array<int,string>   $args Positional arguments.
	 * @param array<string,mixed> $assoc_args Flag arguments.
	 * @return void
	 */
	public static function cli_sync( $args, $assoc_args ) {
		unset( $args, $assoc_args );

		if ( ! self::acquire_sync_lock() ) {
			\WP_CLI::error( 'Sync już trwa (lock aktywny).' );
		}

		\WP_CLI::log( 'Start sync (SITE API).' );

		$result = self::sync_listings( true );

		self::release_sync_lock();

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		\WP_CLI::success( sprintf( 'Sync zakończony. Przetworzono: %d.', (int) $result['processed'] ) );
	}



	private static function diagnostic_ping() {
		$response = self::api_request(
			'GET',
			'/i18nMessages',
			[
				'locale' => 'pl',
			]
		);

		if ( is_wp_error( $response ) ) {
			self::log_error( 'Diagnostic ping FAIL: ' . $response->get_error_message() );
			return;
		}

		self::log_error( 'Diagnostic ping OK. Raw=' . self::truncate_for_log( wp_json_encode( $response ) ) );
	}

	/**
	 * Sync listings from Asari SITE API.
	 *
	 * Flow:
	 * 1) POST /exportedListingIdList -> lista ID + lastUpdated
	 * 2) dla każdego ID: POST /listing?id=ID -> pełny listing
	 * 3) zapis do CPT fc_house
	 *
	 * @param bool $verbose Output to CLI.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function sync_listings( $verbose ) {
		unset( $verbose );

		$settings = self::get_settings();

		$limit = isset( $settings['limit'] ) ? max( 1, (int) $settings['limit'] ) : 25;

		self::log_error( 'Sync start. limit=' . $limit );
		self::diagnostic_ping();		

		$id_list_response = self::api_request(
			'POST',
			'/exportedListingIdList',
			[]
		);

		if ( is_wp_error( $id_list_response ) ) {
			return $id_list_response;
		}

		$ids = self::extract_exported_listing_ids( $id_list_response );

		self::log_error( 'exportedListingIdList: ids_count=' . count( $ids ) );

		if ( empty( $ids ) ) {
			self::log_error( 'No listing ids returned. Raw=' . self::truncate_for_log( wp_json_encode( $id_list_response ) ) );
			return [
				'processed' => 0,
			];
		}

		$processed = 0;

		$ids = array_slice( $ids, 0, $limit );

		foreach ( $ids as $listing_id ) {
			self::log_error( 'Fetching listing id=' . (int) $listing_id );

			$listing = self::api_request(
				'POST',
				'/listing',
				[
					'id' => (int) $listing_id,
				]
			);

			if ( is_wp_error( $listing ) ) {
				self::log_error( 'Listing fetch failed id=' . (int) $listing_id . ' err=' . $listing->get_error_message() );
				continue;
			}

			if ( ! is_array( $listing ) ) {
				self::log_error( 'Listing invalid payload id=' . (int) $listing_id . ' raw=' . self::truncate_for_log( wp_json_encode( $listing ) ) );
				continue;
			}

			$listing_obj = self::extract_listing_object( $listing );

			if ( empty( $listing_obj ) || ! is_array( $listing_obj ) ) {
				self::log_error( 'Listing missing data object id=' . (int) $listing_id . ' raw=' . self::truncate_for_log( wp_json_encode( $listing ) ) );
				continue;
			}

			$result = self::sync_single_site_listing( $listing_obj );

			if ( is_wp_error( $result ) ) {
				self::log_error( 'Listing sync error id=' . (int) $listing_id . ' err=' . $result->get_error_message() );
				continue;
			}

			$processed++;
		}

		self::log_error( 'Sync done. processed=' . $processed );

		return [
			'processed' => $processed,
		];
	}

	private static function extract_exported_listing_ids( $response ) {
		$ids = [];

		if ( is_array( $response ) ) {
			// Najczęściej: ["data" => [...]] albo bezpośrednio lista.
			$candidate = $response;

			if ( isset( $response['data'] ) ) {
				$candidate = $response['data'];
			}

			if ( is_array( $candidate ) ) {
				foreach ( $candidate as $row ) {
					if ( is_array( $row ) ) {
						// Spotykane pola: listingId / id
						if ( isset( $row['listingId'] ) ) {
							$ids[] = (int) $row['listingId'];
						} elseif ( isset( $row['id'] ) ) {
							$ids[] = (int) $row['id'];
						}
					} elseif ( is_numeric( $row ) ) {
						$ids[] = (int) $row;
					}
				}
			}
		}

		$ids = array_values( array_filter( array_unique( $ids ) ) );

		if ( ! empty( $ids ) ) {
			self::log_error( 'IDs sample: ' . implode( ',', array_slice( $ids, 0, 10 ) ) );
		}

		return $ids;
	}

	private static function sync_single_site_listing( $listing ) {
		// SITE /listing zwraca “Listing object” (wg doc). My musimy wyciągnąć ID i name.
		$asari_id = 0;

		if ( isset( $listing['id'] ) ) {
			$asari_id = (int) $listing['id'];
		} elseif ( isset( $listing['listingId'] ) ) {
			$asari_id = (int) $listing['listingId'];
		}

		if ( $asari_id <= 0 ) {
			return new WP_Error( 'invalid_listing', 'Brak ID w payloadzie listingu.' );
		}

		$name = '';
		if ( isset( $listing['name'] ) ) {
			$name = sanitize_text_field( (string) $listing['name'] );
		}

		$title_from_custom = self::extract_custom_field_text( $listing, 33582 );
		$post_title        = '' !== $title_from_custom ? $title_from_custom : ( '' !== $name ? $name : sprintf( 'Listing %d', $asari_id ) );

		$post_id = self::find_house_by_asari_id( $asari_id );

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				[
					'post_type'   => 'fc_house',
					'post_status' => 'publish',
					'post_title'  => $post_title,
				]
			);

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			self::log_error( 'Created post_id=' . (int) $post_id . ' for asari_id=' . $asari_id );
		} else {
			self::log_error( 'Updating post_id=' . (int) $post_id . ' for asari_id=' . $asari_id );

			wp_update_post(
				[
					'ID'         => (int) $post_id,
					'post_title' => $post_title,
				]
			);
		}

		update_post_meta( $post_id, 'asari_id', $asari_id );
		update_post_meta( $post_id, 'asari_last_sync', time() );

		// Debug: zaloguj kilka kluczowych pól żeby widzieć co wchodzi.
		$debug = [
			'id'   => $asari_id,
			'name' => $name,
		];

		if ( isset( $listing['status'] ) ) {
			$debug['status'] = (string) $listing['status'];
			update_post_meta( $post_id, 'asari_status', sanitize_text_field( (string) $listing['status'] ) );
		}

		if ( isset( $listing['price'] ) && is_array( $listing['price'] ) ) {
			if ( isset( $listing['price']['amount'] ) ) {
				update_post_meta( $post_id, 'asari_price_amount', (float) $listing['price']['amount'] );
				$debug['price_amount'] = (string) $listing['price']['amount'];
			}
			if ( isset( $listing['price']['currency'] ) ) {
				update_post_meta( $post_id, 'asari_price_currency', sanitize_text_field( (string) $listing['price']['currency'] ) );
				$debug['price_currency'] = (string) $listing['price']['currency'];
			}
		}

		if ( isset( $listing['totalArea'] ) ) {
			update_post_meta( $post_id, 'asari_total_area', (float) $listing['totalArea'] );
		}

		if ( isset( $listing['noOfRooms'] ) ) {
			update_post_meta( $post_id, 'asari_no_of_rooms', (int) $listing['noOfRooms'] );
		}

		$availability_id = self::extract_availability_id( $listing );
		if ( $availability_id > 0 ) {
			update_post_meta( $post_id, 'asari_availability', (string) $availability_id );
		}

		$appendix_id = 0;

		$image_id = self::extract_listing_image_id( $listing );
		if ( $image_id > 0 ) {
			update_post_meta( $post_id, 'asari_image_id', $image_id );
			$debug['image_id'] = (string) $image_id;
		}

		$plot_area = self::extract_plot_area( $listing );
		if ( $plot_area > 0 ) {
			update_post_meta( $post_id, 'asari_plot_area', (float) $plot_area );
			$debug['plot_area'] = (string) $plot_area;
		}



		$debug['totalArea'] = isset( $listing['totalArea'] ) ? (string) $listing['totalArea'] : '—';
		$debug['rooms_key_present'] = isset( $listing['noOfRoomS'] ) ? 'noOfRoomS' : ( isset( $listing['noOfRooms'] ) ? 'noOfRooms' : '—' );
		$debug['availability_extracted'] = (string) $availability_id;
		$debug['appendix_id'] = (string) $appendix_id;

		$debug['has_customField_33480'] = isset( $listing['customField_33480'] ) ? 'yes' : 'no';
		$debug['has_customFields'] = isset( $listing['customFields'] ) && is_array( $listing['customFields'] ) ? 'yes' : 'no';
		$debug['customFields_count'] = isset( $listing['customFields'] ) && is_array( $listing['customFields'] ) ? (string) count( $listing['customFields'] ) : '0';
		$debug['title_from_custom_33582'] = $title_from_custom;
		self::log_error( 'Listing synced: ' . self::truncate_for_log( wp_json_encode( $debug ) ) );


		return [
			'post_id'  => (int) $post_id,
			'asari_id' => $asari_id,
		];
	}


	/**
	 * Try to extract first image id from listing payload.
	 *
	 * @param array<string,mixed> $listing Listing payload.
	 * @return int
	 */
	private static function extract_listing_image_id( $listing ) {
		$candidates = [
			'imageId',
			'mainImageId',
			'thumbnailId',
		];

		foreach ( $candidates as $key ) {
			if ( isset( $listing[ $key ] ) && is_numeric( $listing[ $key ] ) ) {
				return (int) $listing[ $key ];
			}
		}

		// Common patterns: images => [{id:...}], imageIds => [..]
		if ( isset( $listing['images'] ) && is_array( $listing['images'] ) ) {
			foreach ( $listing['images'] as $img ) {
				if ( is_array( $img ) && isset( $img['id'] ) && is_numeric( $img['id'] ) ) {
					return (int) $img['id'];
				}
			}
		}

		if ( isset( $listing['imageIds'] ) && is_array( $listing['imageIds'] ) ) {
			foreach ( $listing['imageIds'] as $id ) {
				if ( is_numeric( $id ) ) {
					return (int) $id;
				}
			}
		}

		return 0;
	}

	/**
	 * Extract plot area from customField_33573 (numeric).
	 *
	 * @param array<string,mixed> $listing Listing payload.
	 * @return float
	 */
	private static function extract_plot_area( $listing ) {
		$key = 'customField_33573';

		if ( isset( $listing[ $key ] ) ) {
			$field = $listing[ $key ];
			if ( is_numeric( $field ) ) {
				return (float) $field;
			}
			if ( is_array( $field ) ) {
				if ( isset( $field['value'] ) && is_numeric( $field['value'] ) ) {
					return (float) $field['value'];
				}
			}
		}

		// Fallback: if they ever return customFields array
		if ( isset( $listing['customFields'] ) && is_array( $listing['customFields'] ) ) {
			foreach ( $listing['customFields'] as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$field_id = isset( $field['id'] ) ? (int) $field['id'] : 0;
				if ( 33573 !== $field_id ) {
					continue;
				}

				if ( isset( $field['value'] ) && is_numeric( $field['value'] ) ) {
					return (float) $field['value'];
				}
			}
		}

		return 0.0;
	}

	/**
	 * Extract custom field text value.
	 *
	 * @param array<string,mixed> $listing Listing payload.
	 * @param int                 $field_id Custom field ID.
	 * @return string
	 */
	private static function extract_custom_field_text( $listing, $field_id ) {
		$key = 'customField_' . (int) $field_id;

		if ( isset( $listing[ $key ] ) ) {
			$field = $listing[ $key ];
			if ( is_scalar( $field ) ) {
				return sanitize_text_field( (string) $field );
			}

			if ( is_array( $field ) ) {
				$sub_keys = [ 'value', 'text', 'name', 'label' ];
				foreach ( $sub_keys as $sub_key ) {
					if ( isset( $field[ $sub_key ] ) && is_scalar( $field[ $sub_key ] ) ) {
						return sanitize_text_field( (string) $field[ $sub_key ] );
					}
				}
			}
		}

		if ( isset( $listing['customFields'] ) && is_array( $listing['customFields'] ) ) {
			foreach ( $listing['customFields'] as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$current_id = isset( $field['id'] ) ? (int) $field['id'] : 0;
				if ( $current_id !== (int) $field_id ) {
					continue;
				}

				$sub_keys = [ 'value', 'text', 'name', 'label' ];
				foreach ( $sub_keys as $sub_key ) {
					if ( isset( $field[ $sub_key ] ) && is_scalar( $field[ $sub_key ] ) ) {
						return sanitize_text_field( (string) $field[ $sub_key ] );
					}
				}
			}
		}

		return '';
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
			$post_id = (int) $post->ID;
			$number  = (string) get_post_meta( $post_id, 'house_number', true );
			$unit_side = sanitize_key( (string) get_post_meta( $post_id, 'unit_side', true ) );

			if ( '' === $number || ! in_array( $unit_side, [ 'left', 'right' ], true ) ) {
				continue;
			}

			$plan_url    = (string) get_post_meta( $post_id, 'plan_url', true );

			$status = self::resolve_house_status( $post_id );
			if ( ! isset( $buildings[ $number ] ) ) {
				$buildings[ $number ] = [];
			}
			$buildings[ $number ][] = $status;

			$data[] = [
				'number'    => $number,
				'unit_side' => $unit_side,
				'status'    => $status,
				'price'     => (float) get_post_meta( $post_id, 'asari_price_amount', true ),
				'currency'  => (string) get_post_meta( $post_id, 'asari_price_currency', true ),
				'area'      => (float) get_post_meta( $post_id, 'asari_total_area', true ),
				'rooms'     => (int) get_post_meta( $post_id, 'asari_no_of_rooms', true ),
				'plot'      => (float) get_post_meta( $post_id, 'asari_plot_area', true ),
				'plan_url'  => $plan_url,
				'dims_url'  => (string) get_post_meta( $post_id, 'dims_url', true ),
				'model_img' => self::build_asari_image_url( (int) get_post_meta( $post_id, 'asari_image_id', true ) ),
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
				'success' => true,
				'data'    => $data,
				'buildings' => $buildings_data,
			],
			200
		);
	}



	/**
	 * Build public ASARI image URL.
	 *
	 * @param int $image_id Image ID.
	 * @return string
	 */
	private static function build_asari_image_url( $image_id ) {
		if ( $image_id <= 0 ) {
			return '';
		}

		return 'https://img.asariweb.pl/normal/' . (int) $image_id;
	}

	/**
	 * REST endpoint: proxy appendix file from ASARI apiAppendix.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|void
	 */
	public static function rest_proxy_appendix( WP_REST_Request $request ) {
		$appendix_id = (int) $request->get_param( 'id' );
		$token       = self::load_api_token();

		if ( $appendix_id <= 0 ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid appendix id.' ], 400 );
		}

		if ( '' === $token ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Missing ASARI token.' ], 500 );
		}
		self::throttle();
		$response = wp_remote_get(
			add_query_arg( [ 'id' => $appendix_id ], self::API_APPENDIX_BASE . '/get' ),
			[
				'timeout' => 45,
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $response->get_error_message() ], 502 );
		}

		$code         = (int) wp_remote_retrieve_response_code( $response );
		$body         = wp_remote_retrieve_body( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		if ( $code < 200 || $code >= 300 ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Upstream returned HTTP ' . $code ], 502 );
		}

		if ( empty( $content_type ) ) {
			$content_type = 'application/pdf';
		}

		nocache_headers();
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: inline; filename="document-' . $appendix_id . '.pdf"' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Map post-level status with override support.
	 *
	 * @param int $post_id Post ID.
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
	 * Extract customField_33480 dictionary id from listing payload.
	 *
	 * @param array<string,mixed> $listing Listing payload.
	 * @return int
	 */
	private static function extract_availability_id( $listing ) {
		if ( isset( $listing['customField_33480'] ) ) {
			$field = $listing['customField_33480'];
			if ( is_numeric( $field ) ) {
				return (int) $field;
			}
			if ( is_array( $field ) ) {
				if ( isset( $field['id'] ) && is_numeric( $field['id'] ) ) {
					return (int) $field['id'];
				}
				if ( isset( $field['dictionaryItemId'] ) && is_numeric( $field['dictionaryItemId'] ) ) {
					return (int) $field['dictionaryItemId'];
				}
			}
		}

		if ( isset( $listing['customFields'] ) && is_array( $listing['customFields'] ) ) {
			foreach ( $listing['customFields'] as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$field_id = isset( $field['id'] ) ? (int) $field['id'] : 0;
				if ( 33480 !== $field_id ) {
					continue;
				}

				if ( isset( $field['value'] ) && is_numeric( $field['value'] ) ) {
					return (int) $field['value'];
				}
				if ( isset( $field['dictionaryItemId'] ) && is_numeric( $field['dictionaryItemId'] ) ) {
					return (int) $field['dictionaryItemId'];
				}
			}
		}

		return 0;
	}

	/**
	 * Fetch first PDF appendix id for listing.
	 *
	 * @param int $listing_id Listing ID.
	 * @return int
	 */
	private static function fetch_listing_pdf_appendix_id( $listing_id ) {
		$token = self::load_api_token();

		if ( '' === $token || $listing_id <= 0 ) {
			return 0;
		}

		$url      = add_query_arg(
			[
				'objectId'        => $listing_id,
				'objectClassName' => 'Listing',
				'start'           => 0,
				'limit'           => 50,
			],
			self::API_APPENDIX_BASE . '/list'
		);
		self::throttle();
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			self::log_error( 'Appendix list request failed listing=' . (int) $listing_id . ' err=' . $response->get_error_message() );
			return 0;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			self::log_error( 'Appendix list request HTTP=' . $code . ' listing=' . (int) $listing_id );
			return 0;
		}

		$payload = json_decode( $body, true );
		if ( ! is_array( $payload ) ) {
			return 0;
		}

		$items = [];
		if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			$items = $payload['data'];
			if ( isset( $payload['data']['items'] ) && is_array( $payload['data']['items'] ) ) {
				$items = $payload['data']['items'];
			}
		} elseif ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
			$items = $payload['items'];
		} elseif ( array_keys( $payload ) === range( 0, count( $payload ) - 1 ) ) {
			$items = $payload;
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$content_type = isset( $item['contentType'] ) ? (string) $item['contentType'] : '';
			$item_id      = isset( $item['id'] ) ? (int) $item['id'] : 0;

			if ( 'application/pdf' === strtolower( $content_type ) && $item_id > 0 ) {
				return $item_id;
			}
		}

		return 0;
	}


	/**
	 * Sync a single listing.
	 *
	 * @param array<string,mixed> $listing Listing payload (Listing object).
	 * @param int                 $last_updated lastUpdated z exportedListingIdList (jeśli jest).
	 * @return array<string,int>|WP_Error
	 */
	private static function sync_single_listing( $listing, $last_updated ) {
		$asari_id = isset( $listing['id'] ) ? (int) $listing['id'] : 0;

		if ( $asari_id <= 0 ) {
			return new WP_Error( 'invalid_listing', 'Brak poprawnego ID listingu.' );
		}

		$post_id = self::find_house_by_asari_id( $asari_id );

		if ( ! $post_id ) {
			$post_title = isset( $listing['name'] ) ? sanitize_text_field( (string) $listing['name'] ) : sprintf( 'Listing %d', $asari_id );

			$post_id = wp_insert_post(
				[
					'post_type'   => 'fc_house',
					'post_status' => 'publish',
					'post_title'  => $post_title,
				]
			);

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
		}

		$price_amount   = '';
		$price_currency = '';

		if ( isset( $listing['price'] ) && is_array( $listing['price'] ) ) {
			$price_amount   = isset( $listing['price']['amount'] ) ? (float) $listing['price']['amount'] : '';
			$price_currency = isset( $listing['price']['currency'] ) ? sanitize_text_field( (string) $listing['price']['currency'] ) : '';
		}

		$status       = isset( $listing['status'] ) ? sanitize_text_field( (string) $listing['status'] ) : '';
		$listing_id   = isset( $listing['listingId'] ) ? sanitize_text_field( (string) $listing['listingId'] ) : '';

		update_post_meta( $post_id, 'asari_id', $asari_id );
		update_post_meta( $post_id, 'asari_listing_id', $listing_id );
		update_post_meta( $post_id, 'asari_status', $status );
		update_post_meta( $post_id, 'asari_price_amount', $price_amount );
		update_post_meta( $post_id, 'asari_price_currency', $price_currency );

		if ( $last_updated > 0 ) {
			update_post_meta( $post_id, 'asari_last_updated', $last_updated );
		}

		update_post_meta( $post_id, 'asari_last_sync', time() );

		return [
			'post_id'  => (int) $post_id,
			'asari_id' => $asari_id,
		];
	}

	/**
	 * Find house post ID by asari_id.
	 *
	 * @param int $asari_id Listing ID.
	 * @return int
	 */
	private static function find_house_by_asari_id( $asari_id ) {
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

	/**
	 * Request helper for Asari SITE API.
	 *
	 * Authorization:
	 * Header "SiteAuth" => "userId:Token"
	 *
	 * @param string              $method HTTP method.
	 * @param string              $endpoint Endpoint.
	 * @param array<string,mixed> $data Data (dla POST leci jako JSON body).
	 * @return array<string,mixed>|WP_Error
	 */
	private static function api_request( $method, $endpoint, $data = [] ) {
		$token = self::load_api_token();

		if ( '' === $token ) {
			return new WP_Error( 'missing_token', 'Brak tokenu ASARI API.' );
		}

		$url = self::API_BASE . $endpoint;

		$attempts = 0;
		$backoff  = [ 3, 6, 12 ];

		do {
			self::throttle();

			$args = [
				'method'  => strtoupper( $method ),
				'headers' => [
					'SiteAuth' => self::SITE_USER_ID . ':' . $token,
					'Accept'   => 'application/json',
				],
				'timeout' => 30,
			];

			if ( 'GET' === strtoupper( $method ) ) {
				if ( ! empty( $data ) ) {
					$url = add_query_arg( $data, $url );
				}
			} else {
				// SITE API oczekuje form-data / x-www-form-urlencoded, nie JSON
				$args['body'] = $data;
				$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
			}

			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				self::log_error( sprintf( 'HTTP request error on %s: %s', $endpoint, $response->get_error_message() ) );
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );


			self::log_error( '--- API DEBUG START ---' );
			self::log_error( 'URL: ' . $url );
			self::log_error( 'METHOD: ' . strtoupper( $method ) );
			self::log_error( 'HTTP: ' . $code );
			self::log_error( 'BODY_LEN: ' . strlen( $body ) );
			self::log_error( 'BODY: ' . self::truncate_for_log( $body ) );
			self::log_error( '--- API DEBUG END ---' );

			if ( 429 === $code ) {
				if ( $attempts >= 3 ) {
					self::log_error( sprintf( 'HTTP 429 on %s after max retries.', $endpoint ) );
					return new WP_Error( 'rate_limited', 'ASARI API zwróciło 429 (rate limit).' );
				}
				sleep( $backoff[ $attempts ] );
				$attempts++;
				continue;
			}

			if ( $code < 200 || $code >= 300 ) {
				self::log_error( sprintf( 'HTTP %d on %s. Body: %s', $code, $endpoint, self::truncate_for_log( $body ) ) );
				return new WP_Error( 'http_error', sprintf( 'Błąd HTTP %d dla %s.', $code, $endpoint ) );
			}

			$data = json_decode( $body, true );
			if ( ! is_array( $data ) ) {
				self::log_error( sprintf( 'Invalid JSON on %s. Body: %s', $endpoint, self::truncate_for_log( $body ) ) );
				return new WP_Error( 'invalid_json', 'Niepoprawna odpowiedź JSON z ASARI.' );
			}

			if ( array_key_exists( 'success', $data ) && false === $data['success'] ) {
				self::log_error( sprintf( 'ASARI success=false on %s. Payload: %s', $endpoint, self::truncate_for_log( wp_json_encode( $data ) ) ) );
				return new WP_Error( 'api_error', 'ASARI API zwróciło success=false.' );
			}

			return $data;
		} while ( $attempts <= 3 );

		return new WP_Error( 'unknown_error', 'Nieznany błąd zapytania ASARI.' );
	}

	/**
	 * Throttle API requests globally using transient.
	 *
	 * @return void
	 */
	private static function throttle() {
		$last_ts = (int) get_transient( self::TRANSIENT_THROTTLE_TS );
		$now     = time();

		if ( $last_ts > 0 ) {
			$delta = $now - $last_ts;
			if ( $delta < self::MIN_REQUEST_INTERVAL ) {
				sleep( self::MIN_REQUEST_INTERVAL - $delta );
			}
		}

		set_transient( self::TRANSIENT_THROTTLE_TS, time(), MINUTE_IN_SECONDS );
	}

	/**
	 * Load API token from allowed locations.
	 *
	 * @return string
	 */
	private static function load_api_token() {
		$env_token = getenv( 'ASARI_API_TOKEN' );
		if ( is_string( $env_token ) && '' !== trim( $env_token ) ) {
			return trim( $env_token );
		}

		if ( defined( 'ASARI_API_TOKEN' ) && '' !== trim( (string) ASARI_API_TOKEN ) ) {
			return trim( (string) ASARI_API_TOKEN );
		}

		$token_file = trailingslashit( WP_CONTENT_DIR ) . 'as/as.php';
		if ( file_exists( $token_file ) && is_readable( $token_file ) ) {
			$token = require $token_file;

			if ( is_string( $token ) ) {
				$token = preg_replace( '/^\xEF\xBB\xBF/', '', $token ); // BOM
				$token = str_replace( [ "\r", "\n" ], '', $token );
				$token = trim( $token );

				if ( '' !== $token ) {
					return $token;
				}
			}
		}

		return '';
	}

	/**
	 * Extract exported id list from /exportedListingIdList response.
	 *
	 * @param array<string,mixed> $response API response.
	 * @return array<int,array<string,mixed>>
	 */
	private static function extract_exported_id_list( $response ) {
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			if ( isset( $response['data']['items'] ) && is_array( $response['data']['items'] ) ) {
				return array_values( array_filter( $response['data']['items'], 'is_array' ) );
			}

			$is_assoc = array_keys( $response['data'] ) !== range( 0, count( $response['data'] ) - 1 );
			if ( ! $is_assoc ) {
				return array_values( array_filter( $response['data'], 'is_array' ) );
			}
		}

		if ( isset( $response['items'] ) && is_array( $response['items'] ) ) {
			return array_values( array_filter( $response['items'], 'is_array' ) );
		}

		return [];
	}

	/**
	 * Extract Listing object from /listing response.
	 *
	 * @param array<string,mixed> $response API response.
	 * @return array<string,mixed>
	 */
	private static function extract_listing_object( $response ) {
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			return $response['data'];
		}

		return is_array( $response ) ? $response : [];
	}

	/**
	 * Log errors to uploads/asari-sync.log.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private static function log_error( $message ) {
		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['basedir'] ) ) {
			return;
		}

		$line = sprintf( "[%s] %s\n", gmdate( 'Y-m-d H:i:s' ), $message );
		$path = trailingslashit( $upload_dir['basedir'] ) . 'asari-sync.log';
		file_put_contents( $path, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Register admin page.
	 *
	 * @return void
	 */
	public static function register_admin_page() {
		add_options_page(
			'Flix ASARI',
			'Flix ASARI',
			'manage_options',
			'flix-asari',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	/**
	 * Register settings fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'flix_asari_settings_group',
			self::OPTION_SETTINGS,
			[
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
			]
		);

		add_settings_section(
			'flix_asari_main_section',
			'ASARI API Settings',
			'__return_null',
			'flix-asari'
		);

		add_settings_field(
			'cron_enabled',
			'Enable 10-min Cron Sync',
			[ __CLASS__, 'render_cron_enabled_field' ],
			'flix-asari',
			'flix_asari_main_section'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( $input ) {
		$sanitized                 = [];
		$sanitized['cron_enabled'] = ! empty( $input['cron_enabled'] ) ? 1 : 0;

		self::maybe_schedule_cron();

		return $sanitized;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$test_result = isset( $_GET['flix_asari_test'] ) ? sanitize_text_field( wp_unslash( $_GET['flix_asari_test'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Flix ASARI Settings', 'flix-asari' ); ?></h1>
			<?php if ( '' !== $test_result ) : ?>
				<div class="notice notice-<?php echo 'ok' === $test_result ? 'success' : 'error'; ?> is-dismissible">
					<p>
						<?php
						echo 'ok' === $test_result
							? esc_html__( 'ASARI connection test succeeded.', 'flix-asari' )
							: esc_html__( 'ASARI connection test failed. Check uploads/asari-sync.log.', 'flix-asari' );
						?>
					</p>
				</div>

			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'flix_asari_settings_group' );
				do_settings_sections( 'flix-asari' );
				submit_button();
				?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px;">
				<?php wp_nonce_field( 'flix_asari_test_connection', 'flix_asari_test_nonce' ); ?>
				<input type="hidden" name="action" value="flix_asari_test_connection" />
				<?php submit_button( __( 'Test connection', 'flix-asari' ), 'secondary', 'submit', false ); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;">
				<?php wp_nonce_field( 'flix_asari_run_sync_now', 'flix_asari_run_sync_now_nonce' ); ?>
				<input type="hidden" name="action" value="flix_asari_run_sync_now" />
				<?php submit_button( __( 'Run sync now', 'flix-asari' ), 'primary', 'submit', false ); ?>
			</form>

		</div>
		<?php
	}

	/**
	 * Render cron checkbox.
	 *
	 * @return void
	 */
	public static function render_cron_enabled_field() {
		$settings = self::get_settings();
		$checked  = ! empty( $settings['cron_enabled'] ) ? 'checked="checked"' : '';
		echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_SETTINGS ) . '[cron_enabled]" value="1" ' . $checked . ' /> ' . esc_html__( 'Run sync every 10 minutes', 'flix-asari' ) . '</label>';
	}

	/**
	 * Handle connection test.
	 *
	 * @return void
	 */
	public static function handle_test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'flix-asari' ) );
		}

		check_admin_referer( 'flix_asari_test_connection', 'flix_asari_test_nonce' );

		$response = self::api_request(
			'POST',
			'/exportedListingIdList',
			[
				'closedDays'  => 0,
				'blockedDays' => 0,
			]
		);

		$result = is_wp_error( $response ) ? 'fail' : 'ok';
		wp_safe_redirect( admin_url( 'options-general.php?page=flix-asari&flix_asari_test=' . $result ) );
		exit;
	}

	/**
	 * Get settings with defaults.
	 *
	 * @return array<string,mixed>
	 */
	private static function get_settings() {
		$settings = get_option( self::OPTION_SETTINGS, [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return wp_parse_args(
			$settings,
			[
				'cron_enabled' => 0,
			]
		);
	}

	/**
	 * Acquire sync lock.
	 *
	 * @return bool
	 */
	private static function acquire_sync_lock() {
		if ( get_transient( self::TRANSIENT_SYNC_LOCK ) ) {
			return false;
		}

		set_transient( self::TRANSIENT_SYNC_LOCK, 1, 15 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Release sync lock.
	 *
	 * @return void
	 */
	private static function release_sync_lock() {
		delete_transient( self::TRANSIENT_SYNC_LOCK );
	}

	/**
	 * Truncate log strings.
	 *
	 * @param string $message Message.
	 * @return string
	 */
	private static function truncate_for_log( $message ) {
		if ( strlen( $message ) <= 600 ) {
			return $message;
		}

		return substr( $message, 0, 600 ) . '...';
	}
}

Flix_Asari_Plugin::init();
