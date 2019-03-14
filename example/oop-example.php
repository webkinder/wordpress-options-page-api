<?php

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
if ( !class_exists('WeDevs_Settings_API_Test' ) ):
class WeDevs_Settings_API_Test {

    function __construct() {
        add_action( 'init', array($this, 'init') );
    }

    function init() {
        (new \WebKinder\SettingsAPI())
            ->set_sections(
                [
                    [
                        'id'    => 'wedevs_basics',
                        'title' => __( 'Basic Settings', 'wedevs' )
                    ]
                ]
            )
            ->set_fields([
                'wedevs_basics' => [
                    array(
                        'name'              => 'text_val',
                        'label'             => __( 'Text Input', 'wedevs' ),
                        'desc'              => __( 'Text input description', 'wedevs' ),
                        'placeholder'       => __( 'Text Input placeholder', 'wedevs' ),
                        'type'              => 'text',
                        'default'           => 'Title',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    array(
                        'name'              => 'number_input',
                        'label'             => __( 'Number Input', 'wedevs' ),
                        'desc'              => __( 'Number field with validation callback `floatval`', 'wedevs' ),
                        'placeholder'       => __( '1.99', 'wedevs' ),
                        'min'               => 0,
                        'max'               => 100,
                        'step'              => '0.01',
                        'type'              => 'number',
                        'default'           => 'Title',
                        'sanitize_callback' => 'floatval'
                    )
                ]
            ])
            ->register_page('Settings API', 'Settings API', 'delete_posts', 'settings_api_test')
            ->admin_init();
    }
}
endif;
