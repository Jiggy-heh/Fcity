<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'acf/load_field/key=field_fc_sections',
	static function( $field ) {
		if ( empty( $field['layouts'] ) || ! is_array( $field['layouts'] ) ) {
			$field['layouts'] = [];
		}

		if ( isset( $field['layouts']['layout_standard_wykonczenia'] ) ) {
			return $field;
		}

		$field['layouts']['layout_standard_wykonczenia'] = [
			'key' => 'layout_standard_wykonczenia',
			'name' => 'standard_wykonczenia',
			'label' => 'Standard wykończenia',
			'display' => 'block',
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

		return $field;
	}
);
