<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Flix_Asari_API {
	const TRANSIENT_THROTTLE_TS = 'flix_asari_last_request_ts';

	// SITE API (wg dokumentacji: /site + nagłówek SiteAuth: userId:Token)
	const API_BASE          = 'https://api.asari.pro/site';
	const API_APPENDIX_BASE = 'https://api.asari.pro/apiAppendix';
	const SITE_USER_ID      = 80087;

	const MIN_REQUEST_INTERVAL = 3;

	/**
	 * Request helper for Asari SITE API.
	 *
	 * @param string              $method
	 * @param string              $endpoint
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>|WP_Error
	 */
	public static function api_request( $method, $endpoint, $data = [] ) {
		$token = self::load_api_token();

		if ( '' === $token ) {
			return new WP_Error( 'missing_token', 'Brak tokenu ASARI FULL API (wp-content/as/as.txt).' );
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
				$args['body']                        = $data;
				$args['headers']['Content-Type']     = 'application/x-www-form-urlencoded; charset=utf-8';
			}

			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				Flix_Asari_Plugin::log_error( sprintf( 'HTTP request error on %s: %s', $endpoint, $response->get_error_message() ) );
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );

			Flix_Asari_Plugin::log_error( '--- API DEBUG START ---' );
			Flix_Asari_Plugin::log_error( 'URL: ' . $url );
			Flix_Asari_Plugin::log_error( 'METHOD: ' . strtoupper( $method ) );
			Flix_Asari_Plugin::log_error( 'HTTP: ' . $code );
			Flix_Asari_Plugin::log_error( 'BODY_LEN: ' . strlen( $body ) );
			Flix_Asari_Plugin::log_error( 'BODY: ' . self::truncate_for_log( $body ) );
			Flix_Asari_Plugin::log_error( '--- API DEBUG END ---' );

			if ( 429 === $code ) {
				if ( $attempts >= 3 ) {
					Flix_Asari_Plugin::log_error( sprintf( 'HTTP 429 on %s after max retries.', $endpoint ) );
					return new WP_Error( 'rate_limited', 'ASARI API zwróciło 429 (rate limit).' );
				}
				sleep( $backoff[ $attempts ] );
				$attempts++;
				continue;
			}

			if ( $code < 200 || $code >= 300 ) {
				Flix_Asari_Plugin::log_error( sprintf( 'HTTP %d on %s. Body: %s', $code, $endpoint, self::truncate_for_log( $body ) ) );
				return new WP_Error( 'http_error', sprintf( 'Błąd HTTP %d dla %s.', $code, $endpoint ) );
			}

			$decoded = json_decode( $body, true );
			if ( ! is_array( $decoded ) ) {
				Flix_Asari_Plugin::log_error( sprintf( 'Invalid JSON on %s. Body: %s', $endpoint, self::truncate_for_log( $body ) ) );
				return new WP_Error( 'invalid_json', 'Niepoprawna odpowiedź JSON z ASARI.' );
			}

			if ( array_key_exists( 'success', $decoded ) && false === $decoded['success'] ) {
				Flix_Asari_Plugin::log_error( sprintf( 'ASARI success=false on %s. Payload: %s', $endpoint, self::truncate_for_log( wp_json_encode( $decoded ) ) ) );
				return new WP_Error( 'api_error', 'ASARI API zwróciło success=false.' );
			}

			return $decoded;
		} while ( $attempts <= 3 );

		return new WP_Error( 'unknown_error', 'Nieznany błąd zapytania ASARI.' );
	}

	const FULL_API_BASE = 'https://api.asari.pro';

	/**
	 * Request helper for ASARI FULL API (Bearer token).
	 *
	 * @param string              $method
	 * @param string              $endpoint  e.g. '/apiCustomer/create'
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>|WP_Error
	 */
	public static function full_api_request( $method, $endpoint, $data = [] ) {
		$token = self::load_full_api_token();


		if ( '' === $token ) {
			return new WP_Error( 'missing_token', 'Brak tokenu ASARI FULL API (as/as.txt).' );
		}


		$endpoint = '/' . ltrim( (string) $endpoint, '/' );
		$url      = self::FULL_API_BASE . $endpoint;

		$attempts = 0;
		$backoff  = [ 3, 6, 12 ];

		do {
			self::throttle();

			$args = [
				'method'  => strtoupper( $method ),
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
					'User-Agent'    => 'flix-asari/' . ( defined( 'FLIX_ASARI_VERSION' ) ? FLIX_ASARI_VERSION : 'dev' ) . ' (WordPress)',
					'X-Request-Id'  => 'flix-' . wp_generate_uuid4(),
					'Expect'        => '',
				],
				'timeout' => 30,
			];

			if ( 'GET' === strtoupper( $method ) ) {
				if ( ! empty( $data ) ) {
					$url = add_query_arg( $data, $url );
				}
			} else {
				$args['body']                    = http_build_query( $data, '', '&' );
				$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
			}

			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				Flix_Asari_Plugin::log_error( sprintf( 'FULL API request error on %s: %s', $endpoint, $response->get_error_message() ) );
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );

			if ( 429 === $code ) {
				if ( $attempts >= 3 ) {
					Flix_Asari_Plugin::log_error( sprintf( 'FULL API HTTP 429 on %s after max retries.', $endpoint ) );
					return new WP_Error( 'rate_limited', 'ASARI FULL API zwróciło 429 (rate limit).' );
				}
				sleep( $backoff[ $attempts ] );
				$attempts++;
				continue;
			}

			if ( $code < 200 || $code >= 300 ) {
				Flix_Asari_Plugin::log_error( sprintf( 'FULL API HTTP %d on %s. Body: %s', $code, $endpoint, self::truncate_for_log( $body ) ) );
				return new WP_Error( 'http_error', sprintf( 'Błąd HTTP %d dla FULL API %s.', $code, $endpoint ) );
			}

			$decoded = json_decode( $body, true );
			if ( ! is_array( $decoded ) ) {
				Flix_Asari_Plugin::log_error( sprintf( 'FULL API Invalid JSON on %s. Body: %s', $endpoint, self::truncate_for_log( $body ) ) );
				return new WP_Error( 'invalid_json', 'Niepoprawna odpowiedź JSON z ASARI FULL API.' );
			}

			return $decoded;
		} while ( $attempts <= 3 );

		return new WP_Error( 'unknown_error', 'Nieznany błąd zapytania ASARI FULL API.' );
	}

	/**
	 * Throttle API requests globally using transient.
	 *
	 * @return void
	 */
	public static function throttle() {
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
	public static function load_api_token() {
		$env_token = getenv( 'ASARI_API_TOKEN' );
		if ( is_string( $env_token ) && '' !== trim( $env_token ) ) {
			return trim( $env_token );
		}

		if ( defined( 'ASARI_API_TOKEN' ) && '' !== trim( (string) ASARI_API_TOKEN ) ) {
			return trim( (string) ASARI_API_TOKEN );
		}

		// SITE token (as.php)
		$token_file = trailingslashit( WP_CONTENT_DIR ) . 'as/as.php';
		if ( file_exists( $token_file ) && is_readable( $token_file ) ) {
			$token = require $token_file;

			if ( is_string( $token ) ) {
				$token = preg_replace( '/^\xEF\xBB\xBF/', '', $token );
				$token = str_replace( [ "\r", "\n" ], '', $token );
				$token = trim( $token );

				if ( '' !== $token ) {
					return $token;
				}
			}
		}

		return '';
	}

	public static function load_full_api_token() {
		$env_token = getenv( 'ASARI_FULL_API_TOKEN' );
		if ( is_string( $env_token ) && '' !== trim( $env_token ) ) {
			return trim( $env_token );
		}

		if ( defined( 'ASARI_FULL_API_TOKEN' ) && '' !== trim( (string) ASARI_FULL_API_TOKEN ) ) {
			return trim( (string) ASARI_FULL_API_TOKEN );
		}

		// FULL token: wp-content/as/as.txt
		$token_file = trailingslashit( WP_CONTENT_DIR ) . 'as/as.txt';
		if ( file_exists( $token_file ) && is_readable( $token_file ) ) {
			$raw = file_get_contents( $token_file );
			if ( false !== $raw ) {
				$token = preg_replace( '/^\xEF\xBB\xBF/', '', $raw ); // BOM
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
	 * REST endpoint: proxy appendix file from ASARI apiAppendix.
	 *
	 * @param WP_REST_Request $request
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
	 * Build public ASARI image URL.
	 *
	 * @param int $image_id
	 * @return string
	 */
	public static function build_asari_image_url( $image_id ) {
		if ( $image_id <= 0 ) {
			return '';
		}

		return 'https://img.asariweb.pl/normal/' . (int) $image_id;
	}

	/**
	 * Extract exported listing IDs from /exportedListingIdList response.
	 *
	 * @param mixed $response
	 * @return array<int,int>
	 */
	public static function extract_exported_listing_ids( $response ) {
		$ids = [];

		if ( is_array( $response ) ) {
			$candidate = $response;

			if ( isset( $response['data'] ) ) {
				$candidate = $response['data'];
			}

			if ( is_array( $candidate ) ) {
				foreach ( $candidate as $row ) {
					if ( is_array( $row ) ) {
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
			Flix_Asari_Plugin::log_error( 'IDs sample: ' . implode( ',', array_slice( $ids, 0, 10 ) ) );
		}

		return $ids;
	}

	/**
	 * Extract Listing object from /listing response.
	 *
	 * @param mixed $response
	 * @return array<string,mixed>
	 */
	public static function extract_listing_object( $response ) {
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			return $response['data'];
		}

		return is_array( $response ) ? $response : [];
	}

	/**
	 * Try to extract first image id from listing payload.
	 *
	 * @param array<string,mixed> $listing
	 * @return int
	 */
	public static function extract_listing_image_id( $listing ) {
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
	 * Extract plot area from lotArea field (numeric).
	 *
	 * @param array<string,mixed> $listing
	 * @return float
	 */
	public static function extract_plot_area( $listing ) {
		if ( isset( $listing['lotArea'] ) ) {
			$field = $listing['lotArea'];

			if ( is_numeric( $field ) ) {
				return (float) $field;
			}

			if ( is_array( $field ) ) {
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
	 * @param array<string,mixed> $listing
	 * @param int                 $field_id
	 * @return string
	 */
	public static function extract_custom_field_text( $listing, $field_id ) {
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
	 * Extract customField_33480 dictionary id from listing payload.
	 *
	 * @param array<string,mixed> $listing
	 * @return int
	 */
	public static function extract_availability_id( $listing ) {
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
}