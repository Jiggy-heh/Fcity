<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Flix_Asari_CF7 {
	const DEFAULT_AGENT_ID      = 80087;
	const DEFAULT_COMMSTATUS_ID = 50018; // "Nie było kontaktu"

	public static function init() {
		if ( ! defined( 'WPCF7_VERSION' ) ) {
			return;
		}

		add_action( 'wpcf7_mail_sent', [ __CLASS__, 'handle_mail_sent' ], 10, 1 );
	}

	public static function register_async_hook() {
		add_action( 'flix_asari_cf7_push_lead', [ __CLASS__, 'send_to_asari' ], 10, 1 );
	}

	public static function send_to_asari( $payload ) {
		if ( ! is_array( $payload ) || empty( $payload ) ) {
			Flix_Asari_Plugin::log_error( 'CF7 -> ASARI async: empty payload, abort.' );
			return;
		}

		Flix_Asari_Plugin::log_error( 'CF7 -> ASARI async: START. Payload=' . substr( wp_json_encode( $payload ), 0, 600 ) );

		try {

			$res = Flix_Asari_API::full_api_request( 'POST', '/apiCustomer/create', $payload );

			if ( is_wp_error( $res ) ) {
				Flix_Asari_Plugin::log_error( 'CF7 -> ASARI FAIL (async): ' . $res->get_error_message() );
				return;
			}

			Flix_Asari_Plugin::log_error( 'CF7 -> ASARI OK (async): ' . substr( wp_json_encode( $res ), 0, 600 ) );

		} catch ( \Throwable $e ) {

			Flix_Asari_Plugin::log_error(
				'CF7 -> ASARI ERROR (async): '
				. $e->getMessage()
				. ' in '
				. $e->getFile()
				. ':'
				. $e->getLine()
			);

		}
	}
	public static function handle_mail_sent( $contact_form ) {
		try {
			if ( ! class_exists( 'WPCF7_Submission' ) ) {
				return;
			}

			$submission = \WPCF7_Submission::get_instance();
			if ( ! $submission ) {
				return;
			}

			$posted = (array) $submission->get_posted_data();



		$full_name = self::pick_first( $posted, [ 'your-name', 'name', 'imie', 'fullname', 'full-name' ] );
		$email     = self::pick_first( $posted, [ 'your-email', 'email', 'e-mail' ] );
		$phone     = self::pick_first( $posted, [ 'your-tel', 'tel', 'phone', 'telefon', 'your-phone' ] );
		$message   = self::pick_first( $posted, [ 'your-message', 'message', 'wiadomosc', 'twoja-wiadomosc' ] );
		$city = self::pick_first( $posted, [ 'city', 'miasto', 'your-city' ] );
		$agree_marketing  = self::is_truthy( $posted['marketing_cons'] ?? '' );
		$agree_newsletter = self::is_truthy( $posted['newsletter_cons'] ?? '' );
		$first_name_field = self::pick_first( $posted, [ 'firstname', 'first-name', 'imie' ] );
		$last_name_field  = self::pick_first( $posted, [ 'lastname', 'last-name', 'nazwisko' ] );

		
		
		$first_name_field = sanitize_text_field( (string) $first_name_field );
		$last_name_field  = sanitize_text_field( (string) $last_name_field );
		$full_name = sanitize_text_field( (string) $full_name );
		$email     = sanitize_email( (string) $email );
		$phone     = preg_replace( '/\s+/', '', sanitize_text_field( (string) $phone ) );
		$message   = sanitize_textarea_field( (string) $message );
		$city = sanitize_text_field( (string) $city );


		$page_url_raw = method_exists( $submission, 'get_meta' ) ? $submission->get_meta( 'url' ) : '';
		$page_url     = $page_url_raw ? esc_url_raw( (string) $page_url_raw ) : '';

		$remote_ip_raw = method_exists( $submission, 'get_meta' ) ? $submission->get_meta( 'remote_ip' ) : '';
		$remote_ip     = $remote_ip_raw ? sanitize_text_field( (string) $remote_ip_raw ) : '';

		$user_agent_raw = method_exists( $submission, 'get_meta' ) ? $submission->get_meta( 'user_agent' ) : '';
		$user_agent     = $user_agent_raw ? sanitize_text_field( (string) $user_agent_raw ) : '';
		$form_title = '';
		if ( is_object( $contact_form ) && method_exists( $contact_form, 'title' ) ) {
			$form_title = (string) $contact_form->title();
		}

		$first_name = '';
		$last_name  = '';

		// Formularz "houses" (osobne pola)
		if ( '' !== $first_name_field || '' !== $last_name_field ) {

			$first_name = $first_name_field;
			$last_name  = $last_name_field;

		} else {

			// Formularz hero (fullname)
			$first_name = $full_name;
			$last_name  = '';

			if ( false !== strpos( $full_name, ' ' ) ) {
				$parts      = preg_split( '/\s+/', $full_name, 2 );
				$first_name = trim( (string) ( $parts[0] ?? '' ) );
				$last_name  = trim( (string) ( $parts[1] ?? '' ) );
			}

		}

		if ( '' === $first_name ) {
			$first_name = 'Lead';
		}
		if ( '' === $last_name ) {
			$last_name = 'WWW';
		}

		$utm_source   = sanitize_text_field( (string) self::pick_first( $posted, [ 'utm_source' ] ) );
		$utm_medium   = sanitize_text_field( (string) self::pick_first( $posted, [ 'utm_medium' ] ) );
		$utm_campaign = sanitize_text_field( (string) self::pick_first( $posted, [ 'utm_campaign' ] ) );
		$utm_content  = sanitize_text_field( (string) self::pick_first( $posted, [ 'utm_content' ] ) );
		$utm_term     = sanitize_text_field( (string) self::pick_first( $posted, [ 'utm_term' ] ) );



		$desc  = '';
		$desc .= ( '' !== $message ) ? $message . "\n\n" : '';
		$desc .= "---\n";
		$desc .= 'Form: ' . $form_title . "\n";
		$desc .= ( '' !== $page_url ) ? 'URL: ' . $page_url . "\n" : '';
		$desc .= ( '' !== $remote_ip ) ? 'IP: ' . $remote_ip . "\n" : '';
		$desc .= ( '' !== $user_agent ) ? 'UA: ' . $user_agent . "\n" : '';

		if ( '' !== $utm_source || '' !== $utm_medium || '' !== $utm_campaign || '' !== $utm_content || '' !== $utm_term ) {
			$desc .= 'UTM: '
				. 'source=' . $utm_source
				. ', medium=' . $utm_medium
				. ', campaign=' . $utm_campaign
				. ', content=' . $utm_content
				. ', term=' . $utm_term
				. "\n";
		}

		$agent_id      = (int) apply_filters( 'flix_asari_cf7_agent_id', self::DEFAULT_AGENT_ID, $posted, $contact_form );
		$commstatus_id = (int) apply_filters( 'flix_asari_cf7_commstatus_id', self::DEFAULT_COMMSTATUS_ID, $posted, $contact_form );

		$payload = [
			'customerType'           => 'Lead',
			'firstName'              => $first_name,
			'lastName'               => $last_name,
			'customerFrom'           => 'Internet',
			'communicationStatus.id' => $commstatus_id,
			'assignedTo.id'          => $agent_id,
			'description'            => $desc,
		];

		$payload['agreToTradeInformation'] = $agree_marketing ? '1' : '0';
		$payload['agreeToSendEmail']        = $agree_newsletter ? '1' : '0';

		if ( '' !== $email ) {
			$payload['emails[0].email'] = $email;
		}
		if ( '' !== $phone ) {
			$payload['phones[0].phoneNumber'] = $phone;
		}
		if ( '' !== $city ) {
			$payload['customField_33612'] = $city;
		}

		// Sync: wysyłamy od razu (bez cron, bez reload)
		self::send_to_asari( $payload );
		Flix_Asari_Plugin::log_error( 'CF7 -> ASARI queued (single event).' );
		
		} catch ( \Throwable $e ) {
			Flix_Asari_Plugin::log_error(
				'CF7 handler ERROR: ' . $e->getMessage()
				. ' in ' . $e->getFile() . ':' . $e->getLine()
			);
		}
	}

	private static function is_truthy( $value ) {
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		$value = strtolower( trim( (string) $value ) );

		if ( '' === $value ) {
			return false;
		}

		return in_array( $value, [ '1', 'true', 'yes', 'on', 'tak', 'accepted', 'accept' ], true );
	}

	private static function pick_first( $posted, $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $posted[ $key ] ) ) {
				$val = $posted[ $key ];
				if ( is_array( $val ) ) {
					$val = implode( ' ', array_map( 'strval', $val ) );
				}
				$val = trim( (string) $val );
				if ( '' !== $val ) {
					return $val;
				}
			}
		}

		foreach ( $posted as $k => $v ) {
			$k_l = strtolower( (string) $k );
			foreach ( $keys as $needle ) {
				$n_l = strtolower( (string) $needle );
				if ( '' !== $n_l && false !== strpos( $k_l, $n_l ) ) {
					if ( is_array( $v ) ) {
						$v = implode( ' ', array_map( 'strval', $v ) );
					}
					$v = trim( (string) $v );
					if ( '' !== $v ) {
						return $v;
					}
				}
			}
		}

		return '';
	}
}