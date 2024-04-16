What is this?
---------------

It's a PHP class wrapper for handling WordPress [Settings API](http://codex.wordpress.org/Settings_API). This is an easily accessible OOP API for developers to use in their own plugins.

## Package Installation (via Composer)

To install this package, edit your `composer.json` file:

```js
{
    "require": {
        "webkinder/wordpress-options-page-api": "2.1.1"
    }
}
```

Now run:

`$ composer install`

## Usage Example

### Registering options
```php
use WebKinder\SettingsAPI;

(new SettingsAPI(['network_settings' => false]))
	->set_sections(
		[
			[
				'id' => 'custom_options',
				'title' => __('Custom Options'),
			],
		]
	)
	->set_fields([
		'custom_options' => [
			[
				'name' => 'information_text',
				'label' => __('Information Text'),
				'type' => 'textarea',
			],
			[
				'name' => 'radio_option',
				'label' => __('Radio Option'),
				'type' => 'radio',
				'options' => [
					'top' => __('Top'),
					'bottom' => __('Bottom'),
					'bottom-right' => __('Bottom Right'),
				],
			],
			[
				'name' => 'checkbox_option',
				'label' => __('Checkbox Option'),
				'type' => 'checkbox',
				'desc' => __('aktivieren'),
			],
			[
				'name' => 'multicheck_option',
				'label' => __('Multicheck Options'),
				'type' => 'multicheck',
				'options' => [
					'top' => __('Top'),
					'bottom' => __('Bottom'),
					'bottom-right' => __('Bottom Right'),
				],
			],
		],
	])
	->register_page('Options page', 'Options page', 'manage_options', 'custom-options-page')
	->admin_init()
;

```
### Retrieving saved options

```php
/**
 * Get the value of a settings field
 *
 * @param string $option settings field name
 * @param string $section the section name this field belongs to
 * @param string $default default text if it's not found
 *
 * @return mixed
 */
function prefix_get_option( $option, $section, $default = '' ) {

    $options = get_option( $section );

    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }

    return $default;
}
```

## What this plugin for?

This is an API for the WordPress Settings API

## Acknowledgments
This is an extended version of [tareq1988](https://github.com/tareq1988/wordpress-settings-api-class) with more features and opinionated changes and additions.

Changelog:

----------------------
```
2.1.2 (16 April, 2024)
------------------------
- Hotfix: Improve backwards compatibility

2.1.1 (19 March, 2024)
------------------------
- Update docs
- More dynamic handling

2.1.0 (19 March, 2024)
------------------------
- Add support for network options

2.0.2 (24 April, 2023)
------------------------
- Better check for multilang

2.0.1 (29 March, 2023)
------------------------
- Fix hidden translation fields that were required and could be empty

2.0.0 (28 March, 2023)
------------------------
- Add proper versioning
- Add conditional support for hiding and disabling fields
- Add option for required fields
- Add multilang support (WPML only)

1.0.0 (14 March, 2019)
------------------------
- First version published
```