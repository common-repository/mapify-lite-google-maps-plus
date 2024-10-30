<?php
// Add relevant admin fields
function mpfy_leu_map_location_custom_fields($custom_fields) {
	
	$custom_fields = mpfy_array_push_key($custom_fields, 'position_after_popup', array(
		'map_location_external_url_enable'=>Carbon_Field::factory('select', 'map_location_external_url_enable', 'Take visitor to another page on clicking location?')
			->add_options(array( 'no' => 'No', 'yes' => 'Yes' )),
		'map_location_external_url_url'=>Carbon_Field::factory('text', 'map_location_external_url_url', 'Enter URL')
			->help_text('Clicking the location marker will redirect the user to the entered url.'),
		'map_location_external_url_target'=>Carbon_Field::factory('select', 'map_location_external_url_target', 'Open in')
			->add_options(array( '_blank' => 'New Window', '_self' => 'Current Window' )),
	));

	return $custom_fields;
}
add_filter('mpfy_map_location_custom_fields', 'mpfy_leu_map_location_custom_fields');

function mpfy_leu_pin_trigger_settings_filter($settings, $pin_id) {
	$enabled = mpfy_meta_to_bool($pin_id, '_map_location_external_url_enable', true);
	$url = get_post_meta($pin_id, '_map_location_external_url_url', true);
	$target = get_post_meta($pin_id, '_map_location_external_url_target', true);
	$target = ($target) ? $target : '_blank';

	if ($enabled && $url) {
		$settings['href'] = esc_url($url);
		$settings['target'] = $target;
		$settings['classes'][] = 'mpfy-external-link';
	}

	return $settings;
}
add_filter('mpfy_pin_trigger_settings', 'mpfy_leu_pin_trigger_settings_filter', 10, 2);
