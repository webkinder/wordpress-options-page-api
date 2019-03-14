+++
description = "How to use this class"
title = "Usage"
date = "2017-04-10T16:43:08+01:00"
draft = false
weight = 200
bref="Read how to use this class here"
script = 'animation'
+++

<h3 class="section-head" id="h-slide"><a href="#h-slide">Usage</a></h3>
```php
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
```