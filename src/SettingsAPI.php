<?php

namespace WebKinder;

// Define the directory of this file for the assets
if (!defined('WK_OPTIONS_API_DIR')) {
	define('WK_OPTIONS_API_DIR', dirname(__DIR__));
}

// Return early if this class was already loaded as we want to be able to load the class for multiple plugins
if (class_exists('\WebKinder\SettingsAPI')) {
	return;
}

class SettingsAPI
{
	/**
	 * Stored class settings.
	 */
	public $class_settings;

	/**
	 * Stored options data.
	 */
	public $options;

	/**
	 * Settings sections array.
	 *
	 * @var array
	 */
	protected $settings_sections = [];

	/**
	 * Settings fields array.
	 *
	 * @var array
	 */
	protected $settings_fields = [];

	/**
	 * Multilingual settings fields array.
	 *
	 * @var array
	 */
	protected $multilingual_settings_fields = [];

	/**
	 * Load scripts needed in admin panel on init of class.
	 *
	 * @param mixed $class_settings
	 */
	public function __construct($class_settings)
	{
		$this->class_settings = $class_settings;

		// Check if we need network site handling
		if (!isset($this->class_settings['multisite'])) {
			$this->class_settings['multisite'] = is_multisite();
		} else {
			if (!is_multisite()) {
				$this->class_settings['multisite'] = false;
			}
		}

		if ($this->class_settings['multisite']) {
			add_action('network_admin_edit_wk_update_consent', [$this, 'saveMultisiteOptions']);
		}

		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
	}

	/**
	 * Register option pages.
	 *
	 * @param string $page_title
	 * @param string $menu_title
	 * @param string $capability
	 * @param string $menu_slug
	 */
	public function register_page($page_title, $menu_title, $capability, $menu_slug)
	{
		if ($this->class_settings['multisite']) {
			add_action('network_admin_menu', function () use ($page_title, $menu_title, $capability, $menu_slug) {
				add_submenu_page('settings.php', $page_title, $menu_title, $capability, $menu_slug, function () {
					echo '<div class="wrap">';
					$this->show_navigation();
					$this->show_forms();
					echo '</div>';
				});
			});
		} else {
			add_action('admin_menu', function () use ($page_title, $menu_title, $capability, $menu_slug) {
				add_options_page($page_title, $menu_title, $capability, $menu_slug, function () {
					echo '<div class="wrap">';
					$this->show_navigation();
					$this->show_forms();
					echo '</div>';
				});
			});
		}

		return $this;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function admin_enqueue_scripts()
	{
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_media();
		wp_enqueue_script('wp-color-picker');
		wp_enqueue_script('jquery');
	}

	/**
	 * Set settings sections.
	 *
	 * @param array $sections setting sections array
	 */
	public function set_sections($sections)
	{
		$this->settings_sections = $sections;

		return $this;
	}

	/**
	 * Add a single section.
	 *
	 * @param array $section
	 */
	public function add_section($section)
	{
		$this->settings_sections[] = $section;

		return $this;
	}

	/**
	 * Set settings fields.
	 *
	 * @param array $fields settings fields array
	 */
	public function set_fields($fields)
	{
		$this->settings_fields = $fields;

		// Check for multilingual fields
		if (!empty($this->settings_fields)) {
			foreach ($this->settings_fields as $section_key => $section) {
				foreach ($section as $field) {
					if (isset($field['multilang']) && true === $field['multilang'] && function_exists('icl_object_id')) {
						$this->multilingual_settings_fields[$section_key][$field['name']] = $field['name'];
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Initialize and registers the settings sections and fieeds to WordPress.
	 *
	 * Usually this should be called at `admin_init` hook.
	 *
	 * This function gets the initiated settings sections and fields. Then
	 * registers them to WordPress and ready for use.
	 */
	public function admin_init()
	{
		add_action('admin_init', function () {
			// Double check if is admin area
			if (!is_admin()) {
				return;
			}

			// Get current language for wpml support (if available)
			$current_lang = apply_filters('wpml_current_language', false);
			$active_languages = apply_filters('wpml_active_languages', [], 'orderby=id&order=desc');

			// Register settings sections
			foreach ($this->settings_sections as $section) {
				if (isset($section['desc']) && !empty($section['desc'])) {
					$section['desc'] = '<div class="inside">'.$section['desc'].'</div>';
					$callback = function () use ($section) {
						echo str_replace('"', '\"', $section['desc']);
					};
				} elseif (isset($section['callback'])) {
					$callback = $section['callback'];
				} else {
					$callback = null;
				}

				add_settings_section($section['id'], $section['title'], $callback, $section['id']);
			}

			// Register settings fields
			foreach ($this->settings_fields as $section => $field) {
				foreach ($field as $option) {
					$name = $option['name'];
					$type = isset($option['type']) ? $option['type'] : 'text';
					$label = isset($option['label']) ? $option['label'] : '';
					$callback = isset($option['callback']) ? $option['callback'] : [$this, 'callback_'.$type];

					// Check if multilingual and overwrite name and labels
					$multilang = false;
					if (isset($option['multilang']) && true === $option['multilang'] && false !== $current_lang) {
						$multilang = $option['multilang'];

						$original_name = $name;
						$original_label = $label;

						$name = $name.'_'.$current_lang;
						$label = $label.' - '.strtoupper($current_lang).'';
					}

					// Add asterisk if required
					if (isset($option['required']) && true === $option['required']) {
						$label .= ' <span class="required">*</span>';
					}

					// Store possible field options and make them extendable
					$field_options = isset($option['options']) ? $option['options'] : '';
					$field_options = apply_filters('wk_options_api_field_options', $field_options, $option);

					$args = [
						'id' => $name,
						'class' => isset($option['class']) ? $option['class'] : $name,
						'label_for' => "{$section}[{$name}]",
						'desc' => isset($option['desc']) ? $option['desc'] : '',
						'name' => $label,
						'section' => $section,
						'size' => isset($option['size']) ? $option['size'] : null,
						'options' => $field_options,
						'std' => isset($option['default']) ? $option['default'] : '',
						'sanitize_callback' => isset($option['sanitize_callback']) ? $option['sanitize_callback'] : '',
						'type' => $type,
						'placeholder' => isset($option['placeholder']) ? $option['placeholder'] : '',
						'required' => isset($option['required']) ? $option['required'] : false,
						'min' => isset($option['min']) ? $option['min'] : '',
						'max' => isset($option['max']) ? $option['max'] : '',
						'step' => isset($option['step']) ? $option['step'] : '',
						'show_on' => isset($option['show_on']) ? $option['show_on'] : [],
						'disabled_on' => isset($option['disabled_on']) ? $option['disabled_on'] : [],
						'multilang' => $multilang,
					];

					// If field is multilang we have to render hidden fields of other translations
					if (false !== $current_lang && isset($option['multilang']) && true === $option['multilang']) {
						if (!empty($active_languages)) {
							// Add original field first before we alter the args
							add_settings_field("{$section}[{$name}]", $label, $callback, $section, $section, $args);

							foreach ($active_languages as $lang) {
								if ($lang['code'] !== $current_lang) {
									$name = $original_name.'_'.$lang['code'];
									$label = $original_label.' - '.strtoupper($lang['code']).'';

									// If page select field we have to change type to text to store the value, otherwise we won't have the options and it gets deleted
									if ('pages' === $type) {
										$type = 'text';
										$callback = [$this, 'callback_text'];
										$args['type'] = $type;
										unset($args['options'], $args['required']);
									}

									// Overwrite the arguments for the hidden translation fields
									$overwrite_args = $args;
									$overwrite_args['id'] = $name;
									$overwrite_args['name'] = $label;
									$overwrite_args['label_for'] = "{$section}[{$name}]";
									$overwrite_args['class'] = isset($option['class']) ? $option['class'] : $name;
									$overwrite_args['class'] .= ' wpml-hidden';

									add_settings_field("{$section}[{$name}}]", $label, $callback, $section, $section, $overwrite_args);
								}
							}
						}
					} else {
						// Non multilang procedure
						add_settings_field("{$section}[{$name}]", $label, $callback, $section, $section, $args);
					}
				}
			}

			// Creates our settings in the options table
			foreach ($this->settings_sections as $section) {
				register_setting($section['id'], $section['id'], [$this, 'sanitize_options']);
			}
		});
	}

	/**
	 * Get field description for display.
	 *
	 * @param array $args settings field args
	 */
	public function get_field_description($args)
	{
		if (!empty($args['desc'])) {
			$desc = sprintf('<div class="description">%s</div>', $args['desc']);
		} else {
			$desc = '';
		}

		return apply_filters('wk_options_api_field_description', $desc, $args);
	}

	/**
	 * Displays a text field for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_text($args)
	{
		$value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
		$type = isset($args['type']) ? $args['type'] : 'text';
		$placeholder = empty($args['placeholder']) ? '' : ' placeholder="'.$args['placeholder'].'"';
		$required = false === $args['required'] ? '' : ' required="required"';
		$required_class = '' !== $required ? ' is-required' : '';

		$html = sprintf('<input type="%1$s" class="%2$s-text%8$s" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $required, $required_class);
		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a url field for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_url($args)
	{
		$this->callback_text($args);
	}

	/**
	 * Displays a number field for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_number($args)
	{
		$value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
		$type = isset($args['type']) ? $args['type'] : 'number';
		$placeholder = empty($args['placeholder']) ? '' : ' placeholder="'.$args['placeholder'].'"';
		$min = ('' == $args['min']) ? '' : ' min="'.$args['min'].'"';
		$max = ('' == $args['max']) ? '' : ' max="'.$args['max'].'"';
		$step = ('' == $args['step']) ? '' : ' step="'.$args['step'].'"';
		$required = false === $args['required'] ? '' : ' required="required"';
		$required_class = '' !== $required ? ' is-required' : '';

		$html = sprintf('<input type="%1$s" class="%2$s-number%11$s" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s%10$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step, $required, $required_class);
		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a checkbox for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_checkbox($args)
	{
		$value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));

		$html = '<fieldset>';
		$html .= sprintf('<label for="wpuf-%1$s[%2$s]">', $args['section'], $args['id']);
		$html .= sprintf('<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id']);
		$html .= sprintf('<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked($value, 'on', false));
		$html .= sprintf('%1$s</label>', $args['desc']);
		$html .= '</fieldset>';

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a multicheckbox for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_multicheck($args)
	{
		$value = $this->get_option($args['id'], $args['section'], $args['std']);

		$html = '<fieldset>';
		$html .= sprintf('<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id']);
		foreach ($args['options'] as $key => $label) {
			$checked = isset($value[$key]) ? $value[$key] : '0';
			$html .= sprintf('<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key);
			$html .= sprintf('<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked($checked, $key, false));
			$html .= sprintf('%1$s</label><br>', $label);
		}

		$html .= $this->get_field_description($args);
		$html .= '</fieldset>';

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a radio button for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_radio($args)
	{
		$value = $this->get_option($args['id'], $args['section'], $args['std']);

		$html = '<fieldset>';
		foreach ($args['options'] as $key => $label) {
			$html .= sprintf('<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key);
			$html .= sprintf('<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked($value, $key, false));
			$html .= sprintf('%1$s</label><br>', $label);
		}
		$html .= $this->get_field_description($args);
		$html .= '</fieldset>';

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a selectbox for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_select($args)
	{
		$value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
		$required = false === $args['required'] ? '' : ' required="required"';
		$required_class = '' !== $required ? ' is-required' : '';

		$html = sprintf('<select class="%1$s%5$s" name="%2$s[%3$s]" id="%2$s[%3$s]"%4$s>', $size, $args['section'], $args['id'], $required, $required_class);

		foreach ($args['options'] as $key => $label) {
			$html .= sprintf('<option value="%s"%s>%s</option>', $key, selected($value, $key, false), $label);
		}

		$html .= sprintf('</select>');
		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a textarea for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_textarea($args)
	{
		$value = esc_textarea($this->get_option($args['id'], $args['section'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
		$placeholder = empty($args['placeholder']) ? '' : ' placeholder="'.$args['placeholder'].'"';
		$required = false === $args['required'] ? '' : ' required="required"';
		$required_class = '' !== $required ? ' is-required' : '';

		$html = sprintf('<textarea rows="5" cols="55" class="%1$s-text%7$s" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s%6$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value, $required, $required_class);
		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays the html for a settings field.
	 *
	 * @param array $args settings field args
	 *
	 * @return string
	 */
	public function callback_html($args)
	{
		echo $this->handle_conditions($this->get_field_description($args), $args);
	}

	/**
	 * Displays a rich text textarea for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_wysiwyg($args)
	{
		$value = $this->get_option($args['id'], $args['section'], $args['std']);
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : '500px';

		$html = '<div style="max-width: '.$size.';">';

		$editor_settings = [
			'teeny' => true,
			'textarea_name' => $args['section'].'['.$args['id'].']',
			'textarea_rows' => 10,
		];

		if (isset($args['options']) && is_array($args['options'])) {
			$editor_settings = array_merge($editor_settings, $args['options']);
		}

		ob_start();

		wp_editor($value, $args['section'].'-'.$args['id'], $editor_settings);

		$html .= ob_get_clean();
		$html .= '</div>';

		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a file upload field for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_file($args)
	{
		$value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
		$label = isset($args['options']['button_label']) ? $args['options']['button_label'] : __('Choose File');
		$required = false === $args['required'] ? '' : ' required="required"';
		$required_class = '' !== $required ? ' is-required' : '';

		$html = sprintf('<input type="text" class="%1$s-text wk-options-file-url%6$s" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"%5$s/>', $size, $args['section'], $args['id'], $value, $required, $required_class);
		$html .= '<input type="button" class="button wk-options-file-browse" value="'.$label.'" />';
		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a password field for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_password($args)
	{
		$value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
		$required = false === $args['required'] ? '' : ' required="required"';
		$required_class = '' !== $required ? ' is-required' : '';

		$html = sprintf('<input type="password" class="%1$s-text%6$s" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"%5$s/>', $size, $args['section'], $args['id'], $value, $required, $required_class);
		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a color picker field for a settings field.
	 *
	 * @param array $args settings field args
	 */
	public function callback_color($args)
	{
		$value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
		$required = false === $args['required'] ? '' : ' required="required"';
		$required_class = '' !== $required ? ' is-required' : '';

		$html = sprintf('<input type="text" class="%1$s-text wp-color-picker-field%7$s" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s"%6$s/>', $size, $args['section'], $args['id'], $value, $args['std'], $required, $required_class);
		$html .= $this->get_field_description($args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Displays a select box for creating the pages select box.
	 *
	 * @param array $args settings field args
	 */
	public function callback_pages($args)
	{
		$required_class = true === $args['required'] ? 'is-required' : '';

		$dropdown_args = [
			'selected' => esc_attr($this->get_option($args['id'], $args['section'], $args['std'])),
			'name' => $args['section'].'['.$args['id'].']',
			'id' => $args['section'].'['.$args['id'].']',
			'class' => $required_class,
			'echo' => 0,
			'required' => true === $args['required'] ? true : false,
		];

		$html = wp_dropdown_pages($dropdown_args);

		echo $this->handle_conditions($html, $args);
	}

	/**
	 * Adds data attribute so we can conditionally show/hide or disable/undisable fields.
	 *
	 * @param string $html
	 * @param array  $args
	 *
	 * @return string
	 */
	public function handle_conditions($html, $args)
	{
		$show_on_attributes = '';
		if (!empty($args['show_on'])) {
			foreach ($args['show_on'] as $show_on) {
				if ('' != $show_on_attributes) {
					$show_on_attributes .= '|';
				}

				$show_on_attributes .= '['.$show_on['key'].']:'.$show_on['compare'].':'.$show_on['value'].':'.$show_on['only_desc'];
			}
		}

		$disabled_on_attributes = '';
		if (!empty($args['disabled_on'])) {
			foreach ($args['disabled_on'] as $disabled_on) {
				if ('' != $disabled_on_attributes) {
					$disabled_on_attributes .= '|';
				}

				$disabled_on_attributes .= '['.$disabled_on['key'].']:'.$disabled_on['compare'].':'.$disabled_on['value'];
			}
		}

		$data_attributes = '';

		if ('' !== $show_on_attributes) {
			$data_attributes = ' data-wk-show-on="'.$show_on_attributes.'"';
		}

		if ('' !== $disabled_on_attributes) {
			$data_attributes = ' data-wk-disabled-on="'.$disabled_on_attributes.'"';
		}

		if ('' !== $data_attributes) {
			$html = '<div'.$data_attributes.'>'.$html.'</div>';
		}

		return $html;
	}

	/**
	 * Sanitize callback for Settings API.
	 *
	 * @param mixed $options
	 *
	 * @return mixed
	 */
	public function sanitize_options($options)
	{
		if (!$options) {
			return $options;
		}

		do_action('sanitize_options', $options);

		foreach ($options as $option_slug => $option_value) {
			$sanitize_callback = $this->get_sanitize_callback($option_slug);

			// If callback is set, call it
			if ($sanitize_callback) {
				$options[$option_slug] = call_user_func($sanitize_callback, $option_value);

				continue;
			}
		}

		return $options;
	}

	/**
	 * Get sanitization callback for given option slug.
	 *
	 * @param string $slug option slug
	 *
	 * @return mixed string or bool false
	 */
	public function get_sanitize_callback($slug = '')
	{
		if (empty($slug)) {
			return false;
		}

		// Iterate over registered fields and see if we can find proper callback
		foreach ($this->settings_fields as $section => $options) {
			foreach ($options as $option) {
				if ($option['name'] != $slug) {
					continue;
				}

				// Return the callback name
				return isset($option['sanitize_callback']) && is_callable($option['sanitize_callback']) ? $option['sanitize_callback'] : false;
			}
		}

		return false;
	}

	/**
	 * Set the options property.
	 *
	 * @param string $section
	 */
	public function set_options($section)
	{
		if (null === $this->options) {
			$this->options = [];
		}

		if (!isset($this->options[$section])) {
			if ($this->class_settings['multisite']) {
				$this->options[$section] = get_site_option($section);
			} else {
				$this->options[$section] = get_option($section);
			}
			$this->options[$section] = $this->add_current_language_option_value_as_default_option_value($section, $this->options[$section]);
			$this->options[$section] = apply_filters('wk_options_api_filter_options', $this->options[$section], $section);
		}
	}

	/**
	 * Get the value of a settings field.
	 *
	 * @param string $option  settings field name
	 * @param string $section the section name this field belongs to
	 * @param string $default default text if it's not found
	 *
	 * @return string
	 */
	public function get_option($option, $section, $default = '')
	{
		$this->set_options($section);

		if (isset($this->options[$section][$option])) {
			return $this->options[$section][$option];
		}

		return $default;
	}

	/**
	 * Get all options of a section properly with all handlings.
	 *
	 * @param string $section the section name this field belongs to
	 *
	 * @return string
	 */
	public function get_options($section)
	{
		$this->set_options($section);

		if (isset($this->options[$section])) {
			return $this->options[$section];
		}

		return false;
	}

	/**
	 * Add current language value for multilingual fields.
	 *
	 * @param string $section
	 * @param array  $options
	 *
	 * @return array
	 */
	public function add_current_language_option_value_as_default_option_value($section, $options)
	{
		// Check if the section has options
		if (empty($options)) {
			return $options;
		}

		// Check if current language can be accessed with WPML
		$current_language = apply_filters('wpml_current_language', false);
		if (false === $current_language) {
			return $options;
		}

		// Check if the section has translatable fields
		if (!isset($this->multilingual_settings_fields[$section])) {
			return $options;
		}

		// Add the option value without language key so we can access it as current language value
		foreach ($this->multilingual_settings_fields[$section] as $translatable_field_key) {
			if (isset($options[$translatable_field_key.'_'.$current_language])) {
				$options[$translatable_field_key] = $options[$translatable_field_key.'_'.$current_language];
			}
		}

		return $options;
	}

	/**
	 * Save multisite options.
	 */
	public function saveMultisiteOptions()
	{
		// Verify nonce
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wk_consent_basic_options-options')) {
			wp_nonce_ays('wk_consent_basic_options-options');
		}

		// Save options
		if (isset($_POST['wk_consent_basic_options'])) {
			update_site_option('wk_consent_basic_options', $_POST['wk_consent_basic_options']);
		}

		// Redirect back top options page
		wp_safe_redirect(
			add_query_arg(
				[
					'page' => 'wk-cookie-consent',
					'updated' => true,
				],
				network_admin_url('settings.php')
			)
		);

		exit;
	}

	/**
	 * Show navigations as tab.
	 *
	 * Shows all the settings section labels as tab
	 */
	public function show_navigation()
	{
		$html = '<h2 class="nav-tab-wrapper">';

		$count = count($this->settings_sections);

		// Don't show the navigation if only one section exists
		if (1 === $count) {
			return;
		}

		foreach ($this->settings_sections as $tab) {
			$html .= sprintf('<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title']);
		}

		$html .= '</h2>';

		echo $html;
	}

	/**
	 * Show the section settings forms.
	 *
	 * This function displays every sections in a different form
	 */
	public function show_forms()
	{
		?>
<div class="metabox-holder">
	<?php foreach ($this->settings_sections as $form) { ?>
	<div id="<?php echo $form['id']; ?>" class="group" style="display: none;">
		<form method="post" action="<?php echo ($this->class_settings['multisite']) ? add_query_arg('action', 'wk_update_consent', 'edit.php') : 'options.php'; ?>">
			<?php
		do_action('wk_options_form_top_'.$form['id'], $form);
		settings_fields($form['id']);
		do_settings_sections($form['id']);
		do_action('wk_options_form_bottom_'.$form['id'], $form);
		if (isset($this->settings_fields[$form['id']])) {
			?>
			<?php do_action('do_before_submit_button', $form); ?>
			<div>
				<?php submit_button(); ?>
			</div>
			<?php do_action('do_after_submit_button', $form); ?>
			<?php } ?>
		</form>
	</div>
	<?php } ?>
</div>
<?php
		$this->script();
		$this->style();
	}

	/**
	 * Render scripts for options page.
	 */
	public function script()
	{
		echo '<script id="wk-options-api-script">'.file_get_contents(WK_OPTIONS_API_DIR.'/assets/admin/main.js').'</script>';
	}

	/**
	 * Render styles for the options page.
	 */
	public function style()
	{
		echo '<style type="text/css" id="wk-options-api-style">'.file_get_contents(WK_OPTIONS_API_DIR.'/assets/admin/main.css').'</style>';
	}
}
