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
				'key' => 'group_homepage_settings',
				'title' => 'Homepage settings',
				'fields' => [
					[
						'key' => 'field_home_hero_tab',
						'label' => 'Hero',
						'name' => '',
						'type' => 'tab',
					],
					[
						'key' => 'field_home_hero_title',
						'label' => 'Hero title',
						'name' => 'home_hero_title',
						'type' => 'text',
					],
					[
						'key' => 'field_home_hero_subtitle',
						'label' => 'Hero subtitle',
						'name' => 'home_hero_subtitle',
						'type' => 'text',
					],
					[
						'key' => 'field_home_hero_description',
						'label' => 'Hero description',
						'name' => 'home_hero_description',
						'type' => 'wysiwyg',
						'tabs' => 'all',
						'toolbar' => 'basic',
						'media_upload' => 0,
					],
					[
						'key' => 'field_home_hero_highlight',
						'label' => 'Hero highlight',
						'name' => 'home_hero_highlight',
						'type' => 'text',
					],
					[
						'key' => 'field_home_hero_scroll_label',
						'label' => 'Hero scroll label',
						'name' => 'home_hero_scroll_label',
						'type' => 'text',
					],
					[
						'key' => 'field_home_hero_video',
						'label' => 'Hero video',
						'name' => 'home_hero_video',
						'type' => 'file',
						'return_format' => 'array',
						'library' => 'all',
						'mime_types' => 'mp4,webm',
					],
					[
						'key' => 'field_home_hero_usp',
						'label' => 'Hero USP items',
						'name' => 'home_hero_usp',
						'type' => 'repeater',
						'layout' => 'row',
						'button_label' => 'Add USP item',
						'min' => 0,
						'max' => 3,
						'sub_fields' => [
							[
								'key' => 'field_home_hero_usp_icon',
								'label' => 'Icon',
								'name' => 'icon',
								'type' => 'image',
								'return_format' => 'array',
								'preview_size' => 'thumbnail',
								'library' => 'all',
							],
							[
								'key' => 'field_home_hero_usp_text',
								'label' => 'Text',
								'name' => 'text',
								'type' => 'text',
							],
						],
					],
					[
						'key' => 'field_home_about_tab',
						'label' => 'About',
						'name' => '',
						'type' => 'tab',
					],
					[
						'key' => 'field_home_about_image',
						'label' => 'About image',
						'name' => 'home_about_image',
						'type' => 'image',
						'return_format' => 'array',
						'preview_size' => 'medium',
						'library' => 'all',
					],
					[
						'key' => 'field_home_about_label',
						'label' => 'About label',
						'name' => 'home_about_label',
						'type' => 'text',
					],
					[
						'key' => 'field_home_about_title',
						'label' => 'About title',
						'name' => 'home_about_title',
						'type' => 'text',
					],
					[
						'key' => 'field_home_about_description',
						'label' => 'About description',
						'name' => 'home_about_description',
						'type' => 'wysiwyg',
						'tabs' => 'all',
						'toolbar' => 'basic',
						'media_upload' => 0,
					],
					[
						'key' => 'field_home_about_button_text',
						'label' => 'About button text',
						'name' => 'home_about_button_text',
						'type' => 'text',
					],
					[
						'key' => 'field_home_about_button_url',
						'label' => 'About button URL',
						'name' => 'home_about_button_url',
						'type' => 'url',
					],
					[
						'key' => 'field_home_investments_tab',
						'label' => 'Investments',
						'name' => '',
						'type' => 'tab',
					],
					[
						'key' => 'field_home_investments_label',
						'label' => 'Investments label',
						'name' => 'home_investments_label',
						'type' => 'text',
					],
					[
						'key' => 'field_home_investments_title',
						'label' => 'Investments title',
						'name' => 'home_investments_title',
						'type' => 'text',
					],
					[
						'key' => 'field_home_investments_desc',
						'label' => 'Investments description',
						'name' => 'home_investments_desc',
						'type' => 'textarea',
						'rows' => 3,
					],
					[
						'key' => 'field_home_investments_image_swieciechowa',
						'label' => 'Investments image: Święciechowa',
						'name' => 'home_investments_image_swieciechowa',
						'type' => 'image',
						'return_format' => 'array',
						'preview_size' => 'medium',
						'library' => 'all',
					],
					[
						'key' => 'field_home_investments_image_przesocin',
						'label' => 'Investments image: Przęsocin',
						'name' => 'home_investments_image_przesocin',
						'type' => 'image',
						'return_format' => 'array',
						'preview_size' => 'medium',
						'library' => 'all',
					],
				],
				'location' => [
					[
						[
							'param' => 'page_type',
							'operator' => '==',
							'value' => 'front_page',
						],
					],
				],
			]
		);
	}
);
