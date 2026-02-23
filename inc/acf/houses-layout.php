<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'acf/init',
	static function() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key' => 'group_fc_sections_houses',
				'title' => 'Sekcja Domy oraz Standard Wykończenia',
				'fields' => [
					[
						'key' => 'field_fc_sections',
						'label' => 'Sekcje',
						'name' => 'fc_sections',
						'type' => 'flexible_content',
						'button_label' => 'Dodaj sekcję',
						'layouts' => [
							'layout_houses' => [
								'key' => 'layout_houses',
								'name' => 'houses',
								'label' => 'Wybierz dom',
								'display' => 'block',
								'sub_fields' => [
									[
										'key' => 'field_house_label',
										'label' => 'Label',
										'name' => 'house_label',
										'type' => 'text',
									],
									[
										'key' => 'field_house_title',
										'label' => 'Title',
										'name' => 'house_title',
										'type' => 'text',
									],
									[
										'key' => 'field_house_desc',
										'label' => 'Opis',
										'name' => 'house_desc',
										'type' => 'textarea',
									],
									[
										'key' => 'field_house_img',
										'label' => 'Obraz',
										'name' => 'house_img',
										'type' => 'image',
										'return_format' => 'array',
										'preview_size' => 'medium',
										'library' => 'all',
									],
									[
										'key' => 'field_house_section_id',
										'label' => 'Section ID',
										'name' => 'house_section_id',
										'type' => 'text',
										'default_value' => 'wybierz-dom',
									],
									[
										'key' => 'field_house_materials_label',
										'label' => 'Materiały – Label',
										'name' => 'house_materials_label',
										'type' => 'text',
									],
									[
										'key' => 'field_house_materials_title',
										'label' => 'Materiały – Title',
										'name' => 'house_materials_title',
										'type' => 'text',
									],
									[
										'key' => 'field_house_materials',
										'label' => 'Materiały – Przyciski',
										'name' => 'house_materials',
										'type' => 'repeater',
										'min' => 0,
										'max' => 3,
										'layout' => 'row',
										'button_label' => 'Dodaj przycisk',
										'sub_fields' => [
											[
												'key' => 'field_house_material_link',
												'label' => 'Link',
												'name' => 'material_link',
												'type' => 'link',
												'return_format' => 'array',
											],
										],
									],
									
								],
							],
						],
					],
				],
				'location' => [
					[
						[
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'inwestycje',
						],
					],
				],
			]
		);
	}
);
