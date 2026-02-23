<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uzupełnia brakujące defaulty ACF (wrapper/required/instructions/_name itd.)
 * Rekurencyjnie dla sub_fields.
 */
function flixcity_acf_normalize_field( $field ) {

	if ( function_exists( 'acf_get_valid_field' ) ) {
		$field = acf_get_valid_field( $field );
	}

	if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
		foreach ( $field['sub_fields'] as $i => $sub ) {
			$field['sub_fields'][ $i ] = flixcity_acf_normalize_field( $sub );
		}
	}

	return $field;
}

/**
 * Normalizacja layoutu Flexible Content (min/max + sanity).
 */
function flixcity_acf_normalize_layout( $layout ) {

	if ( ! is_array( $layout ) ) {
		return $layout;
	}

	$layout['min'] = isset( $layout['min'] ) ? (int) $layout['min'] : 0;
	$layout['max'] = isset( $layout['max'] ) ? (int) $layout['max'] : 0;

	// Opcjonalnie, ale bezpiecznie dla różnych wersji ACF:
	if ( ! isset( $layout['display'] ) ) {
		$layout['display'] = 'block';
	}
	if ( ! isset( $layout['label'] ) ) {
		$layout['label'] = '';
	}
	if ( ! isset( $layout['name'] ) ) {
		$layout['name'] = '';
	}
	if ( ! isset( $layout['key'] ) ) {
		$layout['key'] = '';
	}

	// Normalizuj sub_fields
	if ( ! empty( $layout['sub_fields'] ) && is_array( $layout['sub_fields'] ) ) {
		$layout['sub_fields'] = array_map( 'flixcity_acf_normalize_field', $layout['sub_fields'] );
	}

	return $layout;
}

add_filter(
	'acf/load_field/name=fc_sections',
	static function( $field ) {

		if ( empty( $field['layouts'] ) || ! is_array( $field['layouts'] ) ) {
			$field['layouts'] = [];
		}

		// 1) Najpierw: znormalizuj WSZYSTKIE istniejące layouty (to ubija Twoje warningi min/max)
		foreach ( $field['layouts'] as $k => $existing_layout ) {
			$field['layouts'][ $k ] = flixcity_acf_normalize_layout( $existing_layout );
		}

		// 2) Nie dubluj layoutu
		if ( isset( $field['layouts']['layout_standard_wykonczenia'] ) ) {
			return $field;
		}

		$layout = [
			'key' => 'layout_standard_wykonczenia',
			'name' => 'standard_wykonczenia',
			'label' => 'Standard wykończenia',
			'display' => 'block',
			'min' => 0,
			'max' => 0,
			'sub_fields' => [
				[
					'key' => 'field_finish_standard_section_id',
					'label' => 'Section ID',
					'name' => 'section_id',
					'type' => 'text',
					'default_value' => 'standard-wykonczenia',
				],
				[
					'key' => 'field_finish_standard_label_text',
					'label' => 'Label',
					'name' => 'label_text',
					'type' => 'text',
					'default_value' => 'Wykończenie domów',
				],
				[
					'key' => 'field_finish_standard_title',
					'label' => 'Title',
					'name' => 'title',
					'type' => 'text',
					'default_value' => 'Standard wykończenia',
				],
				[
					'key' => 'field_finish_standard_intro',
					'label' => 'Intro',
					'name' => 'intro',
					'type' => 'wysiwyg',
					'tabs' => 'all',
					'toolbar' => 'full',
					'media_upload' => 0,
				],
				[
					'key' => 'field_finish_standard_checks',
					'label' => 'Checks',
					'name' => 'checks',
					'type' => 'repeater',
					'layout' => 'table',
					'button_label' => 'Dodaj check',
					'sub_fields' => [
						[
							'key' => 'field_finish_standard_check_text',
							'label' => 'Check text',
							'name' => 'check_text',
							'type' => 'text',
						],
					],
				],
				[
					'key' => 'field_finish_standard_bottom_text',
					'label' => 'Bottom text',
					'name' => 'bottom_text',
					'type' => 'wysiwyg',
					'tabs' => 'all',
					'toolbar' => 'full',
					'media_upload' => 0,
				],
				[
					'key' => 'field_finish_standard_button_text',
					'label' => 'Button text',
					'name' => 'button_text',
					'type' => 'text',
					'default_value' => 'Zapytaj o ofertę',
				],
				[
					'key' => 'field_finish_standard_button_url',
					'label' => 'Button URL',
					'name' => 'button_url',
					'type' => 'url',
					'default_value' => 'https://flixcity.kreatorzybiznesu.pl/inwestycje/swieciechowa/#kontakt',
				],
			],
		];

		// Finalnie: normalizuj layout (min/max + sub_fields defaulty)
		$layout = flixcity_acf_normalize_layout( $layout );

		$field['layouts']['layout_standard_wykonczenia'] = $layout;

		return $field;
	}
);
