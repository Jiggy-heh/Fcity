<?php
/**
 * Plugin Name: Flix Asari
 * Description: Foundation plugin for Asari integration and houses CPT.
 * Version: 0.2.0
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
	const API_BASE              = 'https://api.asari.pro';
	const MIN_REQUEST_INTERVAL  = 3;

	/**
	 * Boot plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_house_post_type_and_meta' ] );
		add_filter( 'cron_schedules', [ __CLASS__, 'register_cron_schedule' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_cron_sync' ] );
		add_action( 'admin_menu', [ __CLASS__, 'register_admin_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_post_flix_asari_test_connection', [ __CLASS__, 'handle_test_connection' ] );
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
			'house_number'            => 'string',
			'status_override'         => 'string',
			'plan_url'                => 'string',
			'dims_url'                => 'string',
			'model_img'               => 'integer',
			'asari_status'            => 'string',
			'asari_availability'      => 'string',
			'asari_price_amount'      => 'number',
			'asari_price_currency'    => 'string',
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

		$settings = self::get_settings();
		$section  = isset( $settings['section'] ) ? $settings['section'] : '';
		$status   = isset( $settings['status'] ) ? $settings['status'] : 'Active';

		if ( '' === $section ) {
			self::log_error( 'Cron sync skipped: section is empty.' );
			self::release_sync_lock();
			return;
		}

		$result = self::sync_listings(
			[
				'section' => $section,
				'status'  => $status,
				'limit'   => 25,
				'page'    => 1,
			],
			false
		);

		if ( is_wp_error( $result ) ) {
			self::log_error( sprintf( 'Cron sync failed: %s', $result->get_error_message() ) );
		}

		self::release_sync_lock();
	}

	/**
	 * WP-CLI entrypoint.
	 *
	 * @param array<int,string> $args Positional arguments.
	 * @param array<string,mixed> $assoc_args Flag arguments.
	 * @return void
	 */
	public static function cli_sync( $args, $assoc_args ) {
		unset( $args );

		$section = isset( $assoc_args['section'] ) ? sanitize_text_field( (string) $assoc_args['section'] ) : '';
		$status  = isset( $assoc_args['status'] ) ? sanitize_text_field( (string) $assoc_args['status'] ) : 'Active';
		$limit   = isset( $assoc_args['limit'] ) ? max( 1, (int) $assoc_args['limit'] ) : 25;
		$page    = isset( $assoc_args['page'] ) ? max( 1, (int) $assoc_args['page'] ) : 1;

		if ( '' === $section ) {
			\WP_CLI::error( 'Parametr --section jest wymagany.' );
		}

		if ( ! self::acquire_sync_lock() ) {
			\WP_CLI::error( 'Sync już trwa (lock aktywny).' );
		}

		\WP_CLI::log( sprintf( 'Start sync: section=%s status=%s limit=%d page=%d', $section, $status, $limit, $page ) );
		$result = self::sync_listings(
			[
				'section' => $section,
				'status'  => $status,
				'limit'   => $limit,
				'page'    => $page,
			],
			true
		);
		self::release_sync_lock();

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		\WP_CLI::success( sprintf( 'Sync zakończony. Przetworzono: %d.', (int) $result['processed'] ) );
	}

	/**
	 * Sync listings from Asari.
	 *
	 * @param array<string,mixed> $params Filters.
	 * @param bool                $verbose Output to CLI.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function sync_listings( $params, $verbose ) {
		$api_response = self::api_request( 'GET', '/apiListing/list', $params );

		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		}

		$listings = self::extract_listings( $api_response );
		$processed = 0;

		foreach ( $listings as $listing ) {
			$result = self::sync_single_listing( $listing );
			if ( is_wp_error( $result ) ) {
				self::log_error( sprintf( 'Listing sync error: %s', $result->get_error_message() ) );
				continue;
			}
			$processed++;
			if ( $verbose && defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::log( sprintf( 'Zsynchronizowano listing ID %d -> post %d.', (int) $result['asari_id'], (int) $result['post_id'] ) );
			}
		}

		return [
			'processed' => $processed,
		];
	}

	/**
	 * Sync a single listing.
	 *
	 * @param array<string,mixed> $listing Listing payload.
	 * @return array<string,int>|WP_Error
	 */
	private static function sync_single_listing( $listing ) {
		$asari_id = isset( $listing['id'] ) ? (int) $listing['id'] : 0;

		if ( $asari_id <= 0 ) {
			return new WP_Error( 'invalid_listing', 'Brak poprawnego ID listingu.' );
		}

		$post_id = self::find_house_by_asari_id( $asari_id );
		if ( ! $post_id ) {
			$post_title = isset( $listing['name'] ) ? sanitize_text_field( (string) $listing['name'] ) : sprintf( 'Listing %d', $asari_id );
			$post_id    = wp_insert_post(
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

		$availability = isset( $listing['customField_33480'] ) ? sanitize_text_field( (string) $listing['customField_33480'] ) : '';
		$status       = isset( $listing['status'] ) ? sanitize_text_field( (string) $listing['status'] ) : '';

		update_post_meta( $post_id, 'asari_id', $asari_id );
		update_post_meta( $post_id, 'asari_status', $status );
		update_post_meta( $post_id, 'asari_availability', $availability );
		update_post_meta( $post_id, 'asari_price_amount', $price_amount );
		update_post_meta( $post_id, 'asari_price_currency', $price_currency );
		update_post_meta( $post_id, 'asari_last_sync', time() );

		$card_data = self::find_best_card_appendix( $asari_id );
		if ( ! is_wp_error( $card_data ) && ! empty( $card_data ) ) {
			update_post_meta( $post_id, 'asari_card_appendix_id', (int) $card_data['id'] );
			update_post_meta( $post_id, 'asari_card_file_name', (string) $card_data['file_name'] );
			update_post_meta( $post_id, 'asari_card_url', (string) $card_data['url'] );
		}

		return [
			'post_id'   => (int) $post_id,
			'asari_id'  => $asari_id,
		];
	}

	/**
	 * Find best matching appendix for listing card.
	 *
	 * @param int $listing_id Listing ID.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function find_best_card_appendix( $listing_id ) {
		$list_response = self::api_request(
			'GET',
			'/apiAppendix/list',
			[
				'objectClassName' => 'Listing',
				'objectId'        => $listing_id,
			]
		);

		if ( is_wp_error( $list_response ) ) {
			return $list_response;
		}

		$appendixes = self::extract_listings( $list_response );
		if ( empty( $appendixes ) ) {
			return [];
		}

		$keywords     = [ 'karta', 'lokal', 'rzut', 'plan' ];
		$best_appendix = null;
		$best_score    = -1;

		foreach ( $appendixes as $appendix ) {
			$file_name    = isset( $appendix['name'] ) ? mb_strtolower( (string) $appendix['name'] ) : '';
			$content_type = isset( $appendix['contentType'] ) ? mb_strtolower( (string) $appendix['contentType'] ) : '';
			$score        = 0;

			if ( 'application/pdf' === $content_type ) {
				$score += 10;
			}

			foreach ( $keywords as $keyword ) {
				if ( false !== strpos( $file_name, $keyword ) ) {
					$score += 5;
				}
			}

			if ( $score > $best_score ) {
				$best_score    = $score;
				$best_appendix = $appendix;
			}
		}

		if ( empty( $best_appendix['id'] ) ) {
			return [];
		}

		$appendix_id = (int) $best_appendix['id'];
		$get_response = self::api_request( 'GET', '/apiAppendix/get', [ 'id' => $appendix_id ] );
		if ( is_wp_error( $get_response ) ) {
			self::log_error( sprintf( 'Appendix detail fetch failed for ID %d: %s', $appendix_id, $get_response->get_error_message() ) );
		}

		$details  = is_wp_error( $get_response ) ? [] : self::extract_single_item( $get_response );
		$file_name = isset( $best_appendix['name'] ) ? sanitize_text_field( (string) $best_appendix['name'] ) : '';
		$url      = self::extract_appendix_url( $details, $appendix_id );

		return [
			'id'        => $appendix_id,
			'file_name' => $file_name,
			'url'       => $url,
		];
	}

	/**
	 * Extract appendix URL.
	 *
	 * @param array<string,mixed> $details Appendix details.
	 * @param int                 $appendix_id Appendix ID.
	 * @return string
	 */
	private static function extract_appendix_url( $details, $appendix_id ) {
		$url_keys = [ 'url', 'downloadUrl', 'fileUrl', 'href' ];
		foreach ( $url_keys as $key ) {
			if ( ! empty( $details[ $key ] ) ) {
				return esc_url_raw( (string) $details[ $key ] );
			}
		}

		return esc_url_raw( self::API_BASE . '/apiAppendix/get?id=' . $appendix_id );
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
	 * Request helper for Asari API.
	 *
	 * @param string              $method HTTP method.
	 * @param string              $endpoint Endpoint.
	 * @param array<string,mixed> $query Query args.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function api_request( $method, $endpoint, $query = [] ) {
		$token = self::load_api_token();

		if ( '' === $token ) {
			return new WP_Error( 'missing_token', 'Brak tokenu ASARI API.' );
		}

		$url = self::API_BASE . $endpoint;
		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$attempts = 0;
		$backoff  = [ 3, 6, 12 ];

		do {
			self::throttle();
			$response = wp_remote_request(
				$url,
				[
					'method'  => strtoupper( $method ),
					'headers' => [
						'Authorization' => 'Bearer ' . $token,
						'Accept'        => 'application/json',
					],
					'timeout' => 30,
				]
			);

			if ( is_wp_error( $response ) ) {
				self::log_error( sprintf( 'HTTP request error on %s: %s', $endpoint, $response->get_error_message() ) );
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );

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

		$token_file = trailingslashit( WP_CONTENT_DIR ) . 'flixcity-secrets/asari-token.php';
		if ( file_exists( $token_file ) && is_readable( $token_file ) ) {
			$token = require $token_file;
			if ( is_string( $token ) && '' !== trim( $token ) ) {
				return trim( $token );
			}
		}

		return '';
	}

	/**
	 * Extract list payload.
	 *
	 * @param array<string,mixed> $response API response.
	 * @return array<int,array<string,mixed>>
	 */
	private static function extract_listings( $response ) {
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
	 * Extract single item payload.
	 *
	 * @param array<string,mixed> $response API response.
	 * @return array<string,mixed>
	 */
	private static function extract_single_item( $response ) {
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			return $response['data'];
		}

		return $response;
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
			'section',
			'Section',
			[ __CLASS__, 'render_section_field' ],
			'flix-asari',
			'flix_asari_main_section'
		);

		add_settings_field(
			'status',
			'Status',
			[ __CLASS__, 'render_status_field' ],
			'flix-asari',
			'flix_asari_main_section'
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
		$sanitized['section']      = isset( $input['section'] ) ? sanitize_text_field( (string) $input['section'] ) : '';
		$sanitized['status']       = isset( $input['status'] ) ? sanitize_text_field( (string) $input['status'] ) : 'Active';
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
		</div>
		<?php
	}

	/**
	 * Render section field.
	 *
	 * @return void
	 */
	public static function render_section_field() {
		$settings = self::get_settings();
		$value    = isset( $settings['section'] ) ? (string) $settings['section'] : '';
		echo '<input type="text" name="' . esc_attr( self::OPTION_SETTINGS ) . '[section]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Render status field.
	 *
	 * @return void
	 */
	public static function render_status_field() {
		$settings = self::get_settings();
		$value    = isset( $settings['status'] ) ? (string) $settings['status'] : 'Active';
		echo '<input type="text" name="' . esc_attr( self::OPTION_SETTINGS ) . '[status]" value="' . esc_attr( $value ) . '" class="regular-text" />';
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
			'GET',
			'/apiListing/list',
			[
				'limit' => 1,
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
				'section'      => '',
				'status'       => 'Active',
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
