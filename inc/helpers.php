<?php
/**
 * Helpers – flixcity-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizacja problematycznych znaków whitespace
 * (Figma / Docs / U+2028 / nbsp itd.)
 */
if ( ! function_exists( 'flix_normalize_text' ) ) {

	function flix_normalize_text( $text ) {

		if ( ! is_string( $text ) ) {
			return $text;
		}

		// Zamiana problematycznych znaków Unicode na zwykłą spację
		$text = preg_replace(
			'/[\x{2028}\x{2029}\x{00A0}\x{202F}\x{2007}]/u',
			' ',
			$text
		);

		// Redukcja wielokrotnych whitespace
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( $text );
	}
}