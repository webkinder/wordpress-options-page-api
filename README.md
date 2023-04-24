What is this?
---------------

It's a PHP class wrapper for handling WordPress [Settings API](http://codex.wordpress.org/Settings_API). This is an easily accessible OOP API for developers to use in their own plugins.

## Package Installation (via Composer)

To install this package, edit your `composer.json` file:

```js
{
    "require": {
        "webkinder/wordpress-options-page-api": "2.0.0"
    }
}
```

Now run:

`$ composer install`

Usage Example
---------------

Checkout the [examples](https://github.com/tareq1988/wordpress-settings-api-class/tree/master/example) folder for OOP and procedural example.

#### Retrieving saved options

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

## Documentation

Documentation can be found at [https://webkinder.github.io/wordpress-options-page-api/](https://webkinder.github.io/wordpress-options-page-api/)

Screenshot
----------------------

![Option Panel](https://github.com/webkinder/wordpress-options-page-api/raw/master/screenshot-1.png "The options panel build on the fly using the PHP Class")

## Actions

Action to add code before submit button: do_before_submit_button
Action to add code after submit button: do_after_submit_button


## Contributing

Contributions are welcome. Send us your PR's.

## Generate the docs
`cd docs && bash generate-docs.sh -d . && cd -`

Frequently Asked Questions
---------------

#### What this plugin for?

This is an API for the WordPress Settings API

## Acknowledgments
This is an extended version of [tareq1988](https://github.com/tareq1988/wordpress-settings-api-class) with more features and opinionated changes and additions.

Changelog:

----------------------
```
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