<?php
/**
 * Plugin Name: Flix Asari
 * Description: Foundation plugin for Asari SITE API integration and houses CPT.
 * Version: 0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLIX_ASARI_VERSION', '0.3.0' );
define( 'FLIX_ASARI_PATH', plugin_dir_path( __FILE__ ) );
define( 'FLIX_ASARI_FILE', __FILE__ );

require_once FLIX_ASARI_PATH . 'includes/class-flix-asari-api.php';
require_once FLIX_ASARI_PATH . 'includes/class-flix-asari-houses.php';
require_once FLIX_ASARI_PATH . 'includes/class-flix-asari-cf7.php';

final class Flix_Asari_Plugin {
	const OPTION_SETTINGS     = 'flix_asari_settings';
	const TRANSIENT_SYNC_LOCK = 'flix_asari_sync_lock';
	const CRON_HOOK           = 'flix_asari_cron_sync';

	/**
	 * Boot plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ 'Flix_Asari_Houses', 'register_house_post_type_and_meta' ] );
		add_action( 'rest_api_init', [ 'Flix_Asari_Houses', 'register_rest_routes' ] );

		add_action( 'add_meta_boxes', [ 'Flix_Asari_Houses', 'register_house_metabox' ] );
		add_action( 'save_post_fc_house', [ 'Flix_Asari_Houses', 'save_house_metabox' ] );

		add_filter( 'cron_schedules', [ __CLASS__, 'register_cron_schedule' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_cron_sync' ] );

		add_action( 'admin_menu', [ __CLASS__, 'register_admin_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_post_flix_asari_test_connection', [ __CLASS__, 'handle_test_connection' ] );
		add_action( 'admin_post_flix_asari_run_sync_now', [ __CLASS__, 'handle_run_sync_now' ] );

		register_activation_hook( FLIX_ASARI_FILE, [ __CLASS__, 'on_activation' ] );
		register_deactivation_hook( FLIX_ASARI_FILE, [ __CLASS__, 'on_deactivation' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'asari sync', [ __CLASS__, 'cli_sync' ] );
		}
		
		// CF7 -> ASARI (FULL API)
		if ( class_exists( 'WPCF7' ) || defined( 'WPCF7_VERSION' ) ) {
			Flix_Asari_CF7::init();
			Flix_Asari_CF7::register_async_hook();
		}
	}

	/**
	 * Activation logic.
	 *
	 * @return void
	 */
	public static function on_activation() {
		self::maybe_schedule_cron( true );
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
	 * Cron schedule register - dynamic based on saved minutes.
	 *
	 * @param array<string,mixed> $schedules
	 * @return array<string,mixed>
	 */
	public static function register_cron_schedule( $schedules ) {
		$settings = self::get_settings();
		$minutes  = isset( $settings['cron_interval_minutes'] ) ? (int) $settings['cron_interval_minutes'] : 10;

		$minutes = max( 10, min( 1440, $minutes ) );

		$key = self::get_cron_schedule_key( $minutes );

		$schedules[ $key ] = [
			'interval' => $minutes * MINUTE_IN_SECONDS,
			'display'  => sprintf( __( 'Every %d minutes (Flix ASARI)', 'flix-asari' ), $minutes ),
		];

		return $schedules;
	}

	/**
	 * Ensure cron is scheduled according to settings.
	 *
	 * @param bool $force_reschedule
	 * @return void
	 */
	public static function maybe_schedule_cron( $force_reschedule = false ) {
		$settings = self::get_settings();

		if ( empty( $settings['cron_enabled'] ) ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
			return;
		}

		$minutes = isset( $settings['cron_interval_minutes'] ) ? (int) $settings['cron_interval_minutes'] : 10;
		$minutes = max( 10, min( 1440, $minutes ) );

		$schedule_key = self::get_cron_schedule_key( $minutes );

		$event = function_exists( 'wp_get_scheduled_event' ) ? wp_get_scheduled_event( self::CRON_HOOK ) : null;

		// If something is scheduled with different interval OR force, reschedule.
		if ( $force_reschedule || ( $event && isset( $event->schedule ) && $event->schedule !== $schedule_key ) ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $schedule_key, self::CRON_HOOK );
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
	 * @param array<int,string>   $args
	 * @param array<string,mixed> $assoc_args
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

	/**
	 * Admin: Run sync now.
	 *
	 * @return void
	 */
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

		wp_safe_redirect( self::admin_page_url( [ 'flix_asari_sync' => $status ] ) );
		exit;
	}

	/**
	 * Admin: connection test.
	 *
	 * @return void
	 */
	public static function handle_test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'flix-asari' ) );
		}

		check_admin_referer( 'flix_asari_test_connection', 'flix_asari_test_nonce' );

		$response = Flix_Asari_API::api_request(
			'POST',
			'/exportedListingIdList',
			[
				'closedDays'  => 0,
				'blockedDays' => 0,
			]
		);

		$result = is_wp_error( $response ) ? 'fail' : 'ok';
		wp_safe_redirect( self::admin_page_url( [ 'flix_asari_test' => $result ] ) );
		exit;
	}

	/**
	 * Register admin page under: Domy (fc_house) -> Flix ASARI
	 *
	 * @return void
	 */
	public static function register_admin_page() {
		add_submenu_page(
			'edit.php?post_type=fc_house',
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
			'ASARI Sync Settings',
			'__return_null',
			'flix-asari'
		);

		add_settings_field(
			'cron_enabled',
			'Enable Cron Sync',
			[ __CLASS__, 'render_cron_enabled_field' ],
			'flix-asari',
			'flix_asari_main_section'
		);

		add_settings_field(
			'cron_interval_minutes',
			'Cron interval (minutes)',
			[ __CLASS__, 'render_cron_interval_field' ],
			'flix-asari',
			'flix_asari_main_section'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string,mixed> $input
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( $input ) {
		$sanitized                 = [];
		$sanitized['cron_enabled'] = ! empty( $input['cron_enabled'] ) ? 1 : 0;

		$minutes = isset( $input['cron_interval_minutes'] ) ? (int) $input['cron_interval_minutes'] : 10;
		$minutes = max( 10, min( 1440, $minutes ) );
		$sanitized['cron_interval_minutes'] = $minutes;

		// Re-schedule immediately on save.
		self::maybe_schedule_cron( true );

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
		$sync_result = isset( $_GET['flix_asari_sync'] ) ? sanitize_text_field( wp_unslash( $_GET['flix_asari_sync'] ) ) : '';
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

			<?php if ( '' !== $sync_result ) : ?>
				<div class="notice notice-<?php echo 'ok' === $sync_result ? 'success' : 'error'; ?> is-dismissible">
					<p>
						<?php
						echo 'ok' === $sync_result
							? esc_html__( 'Sync succeeded. Check uploads/asari-sync.log for details.', 'flix-asari' )
							: esc_html__( 'Sync failed. Check uploads/asari-sync.log for details.', 'flix-asari' );
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
		echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_SETTINGS ) . '[cron_enabled]" value="1" ' . $checked . ' /> ' . esc_html__( 'Enable scheduled sync', 'flix-asari' ) . '</label>';
	}

	/**
	 * Render interval field (10–180).
	 *
	 * @return void
	 */
	public static function render_cron_interval_field() {
		$settings = self::get_settings();
		$value    = isset( $settings['cron_interval_minutes'] ) ? (int) $settings['cron_interval_minutes'] : 10;

		echo '<input type="number" min="10" max="1440" step="1" name="' . esc_attr( self::OPTION_SETTINGS ) . '[cron_interval_minutes]" value="' . esc_attr( $value ) . '" style="width:120px;" /> ';
		echo '<span class="description">' . esc_html__( 'Allowed: 10-1440 minutes (e.g. 10, 30, 60, 120, 180)', 'flix-asari' ) . '</span>';
	}

	/**
	 * Sync listings from Asari SITE API.
	 *
	 * @param bool $verbose
	 * @return array<string,mixed>|WP_Error
	 */
	private static function sync_listings( $verbose ) {
		unset( $verbose );

		$settings = self::get_settings();
		$limit    = isset( $settings['limit'] ) ? max( 1, (int) $settings['limit'] ) : 25;

		self::log_error( 'Sync start. limit=' . $limit );
		self::diagnostic_ping();

		$id_list_response = Flix_Asari_API::api_request(
			'POST',
			'/exportedListingIdList',
			[]
		);

		if ( is_wp_error( $id_list_response ) ) {
			return $id_list_response;
		}

		$ids = Flix_Asari_API::extract_exported_listing_ids( $id_list_response );

		self::log_error( 'exportedListingIdList: ids_count=' . count( $ids ) );

		if ( empty( $ids ) ) {
			self::log_error( 'No listing ids returned. Raw=' . self::truncate_for_log( wp_json_encode( $id_list_response ) ) );
			return [
				'processed' => 0,
			];
		}

		$processed = 0;
		$ids       = array_slice( $ids, 0, $limit );

		foreach ( $ids as $listing_id ) {
			self::log_error( 'Fetching listing id=' . (int) $listing_id );

			$listing = Flix_Asari_API::api_request(
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

			$listing_obj = Flix_Asari_API::extract_listing_object( $listing );

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

	/**
	 * Sync one listing to CPT.
	 *
	 * @param array<string,mixed> $listing
	 * @return array<string,int>|WP_Error
	 */
	private static function sync_single_site_listing( $listing ) {
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

		$title_from_custom = Flix_Asari_API::extract_custom_field_text( $listing, 33582 );
		$post_title        = '' !== $title_from_custom ? $title_from_custom : ( '' !== $name ? $name : sprintf( 'Listing %d', $asari_id ) );

		$post_id = Flix_Asari_Houses::find_house_by_asari_id( $asari_id );

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

		$availability_id = Flix_Asari_API::extract_availability_id( $listing );
		if ( $availability_id > 0 ) {
			update_post_meta( $post_id, 'asari_availability', (string) $availability_id );
		}

		$appendix_id = 0;

		$image_id = Flix_Asari_API::extract_listing_image_id( $listing );
		if ( $image_id > 0 ) {
			update_post_meta( $post_id, 'asari_image_id', $image_id );
			$debug['image_id'] = (string) $image_id;
		}

		$plot_area = Flix_Asari_API::extract_plot_area( $listing );
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
	 * Diagnostic ping (unchanged).
	 *
	 * @return void
	 */
	private static function diagnostic_ping() {
		$response = Flix_Asari_API::api_request(
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
				'cron_enabled'          => 0,
				'cron_interval_minutes' => 10,
				'limit'                 => 25,
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
	 * Log errors to uploads/asari-sync.log.
	 *
	 * @param string $message
	 * @return void
	 */
	public static function log_error( $message ) {
		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['basedir'] ) ) {
			return;
		}

		$line = sprintf( "[%s] %s\n", gmdate( 'Y-m-d H:i:s' ), $message );
		$path = trailingslashit( $upload_dir['basedir'] ) . 'asari-sync.log';
		file_put_contents( $path, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Truncate log strings.
	 *
	 * @param string $message
	 * @return string
	 */
	private static function truncate_for_log( $message ) {
		if ( strlen( $message ) <= 600 ) {
			return $message;
		}

		return substr( $message, 0, 600 ) . '...';
	}

	/**
	 * Build cron schedule key.
	 *
	 * @param int $minutes
	 * @return string
	 */
	private static function get_cron_schedule_key( $minutes ) {
		return 'flix_asari_every_' . (int) $minutes . '_minutes';
	}

	/**
	 * Admin page URL under Domy menu.
	 *
	 * @param array<string,string> $args
	 * @return string
	 */
	private static function admin_page_url( $args = [] ) {
		$base = admin_url( 'edit.php?post_type=fc_house&page=flix-asari' );
		if ( empty( $args ) ) {
			return $base;
		}

		return add_query_arg( $args, $base );
	}
}

Flix_Asari_Plugin::init();
