<?php
/*
Plugin Name: MapifyLite
Plugin URI: http://www.mapifypro.com
Description: Hello there! We let you add beautiful, feature-rich maps to your site. <a href="https://mapifypro.com/product/mapifypro/" target="_blank">Get The Pro Version</a>
Version: 1.0.5
Author: Josh Sears
Author URI: http://www.mapifypro.com
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Required plugin utility functions
 */
require_once(ABSPATH . '/wp-admin/includes/plugin.php');

function mpfy_ml_load() {
	if (defined('MAPIFY_PLUGIN_NAME')) {
		add_action('admin_notices', 'mpfy_ml_plugin_conflict');
		return; // prevent plugin from loading
	}

	define('MAPIFY_PLUGIN_FILE', __FILE__);
	include_once('load.php');
}
add_action('plugins_loaded', 'mpfy_ml_load', 1600);

function mpfy_ml_plugin_conflict() {
	$plugin_data = get_file_data(__FILE__, array('Plugin Name'=>'Plugin Name', 'Version'=>'Version'));

	if (MAPIFY_PLUGIN_NAME) {
		$message = sprintf(__('The %s plugin will be inactive until you deactivate the %s plugin.'), $plugin_data['Plugin Name'], MAPIFY_PLUGIN_NAME);
	} else {
		$message = sprintf(__('The %s plugin will be inactive as there is a conflicting plugin.'), $plugin_data['Plugin Name']);
	}
	?>
	<div class="error">
		<p><?php echo $message; ?></p>
	</div>
	<?php
}