<?php

use WebKinder\SettingsAPI;

/*
 * WordPress settings API demo class.
 *
 * @author Tareq Hasan
 */
if (!class_exists('WeDevs_Settings_API_Test')) {
	class WeDevs_Settings_API_Test
	{
		public function __construct()
		{
			add_action('init', [$this, 'init']);
		}

		public function init()
		{
			(new SettingsAPI(['network_settings' => false]))
				->set_sections(
					[
						[
							'id' => 'wedevs_basics',
							'title' => __('Basic Settings', 'wedevs'),
						],
					]
				)
				->set_fields([
					'wedevs_basics' => [
						[
							'name' => 'text_val',
							'label' => __('Text Input', 'wedevs'),
							'desc' => __('Text input description', 'wedevs'),
							'placeholder' => __('Text Input placeholder', 'wedevs'),
							'type' => 'text',
							'default' => 'Title',
							'sanitize_callback' => 'sanitize_text_field',
						],
						[
							'name' => 'number_input',
							'label' => __('Number Input', 'wedevs'),
							'desc' => __('Number field with validation callback `floatval`', 'wedevs'),
							'placeholder' => __('1.99', 'wedevs'),
							'min' => 0,
							'max' => 100,
							'step' => '0.01',
							'type' => 'number',
							'default' => 'Title',
							'sanitize_callback' => 'floatval',
						],
					],
				])
				->register_page('Settings API', 'Settings API', 'delete_posts', 'settings_api_test')
				->admin_init()
			;
		}
	}
}
