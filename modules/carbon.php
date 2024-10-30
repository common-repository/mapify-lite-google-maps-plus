<?php
function mpfy_crb_load_settings() {
	if (!defined('CARBON_PLUGIN_ROOT')) {
		define('CARBON_PLUGIN_URL', plugins_url('lib/carbon-fields', MAPIFY_PLUGIN_FILE));
		include_once(MAPIFY_PLUGIN_DIR . '/lib/carbon-fields/carbon-fields.php');
	}

	add_image_size('mpfy_location_tag', 31, 27);
}
add_action('after_setup_theme', 'mpfy_crb_load_settings', 11);

function mpfy_crb_attach_carbon_custom_classes() {
	include_once(MAPIFY_PLUGIN_DIR . '/options/carbon.php');
}
add_action('carbon_register_fields', 'mpfy_crb_attach_carbon_custom_classes', 10);

function mpfy_crb_attach_carbon_declarations() {
	include_once(MAPIFY_PLUGIN_DIR . '/options/custom-fields.php');
}
add_action('carbon_register_fields', 'mpfy_crb_attach_carbon_declarations', 100);