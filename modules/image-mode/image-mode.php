<?php
include_once('tileset.php');

function mpfy_im_map_modes($map_modes) {

	$map_modes['image'] = 'Image';

	return $map_modes;
}
add_filter('mpfy_map_modes', 'mpfy_im_map_modes');

// Enqueue admin assets
function mpfy_im_admin_behaviors($hook) {
    if ($hook !== 'post.php' || !isset($_GET['post'])) {
        return;
    }

    $map_id = intval($_GET['post']);

    $uploads_dir = wp_upload_dir();
    $image_source = mpfy_tileset_get_url($map_id);
	
    wp_register_script('mpfy-image-mode-admin', plugins_url('modules/image-mode/admin.js', MAPIFY_PLUGIN_FILE), array('jquery'), '1.0.0', true);
    wp_localize_script('mpfy-image-mode-admin', 'mpfy_image_mode', array(
    	'image_status'=>get_post_meta($map_id, '_map_tileset_status', true),
    	'image_source'=>$image_source,
    ));
    wp_enqueue_script('mpfy-image-mode-admin');
}
add_action('admin_enqueue_scripts', 'mpfy_im_admin_behaviors');

function mpfy_im_map_custom_fields($custom_fields) {

	$custom_fields = mpfy_array_push_key($custom_fields, 'position_after_ui', array(
		'map_separator_2'=>Carbon_Field::factory('separator', 'map_separator_2', 'Image Mode Settings'),
		'map_image_big'=>Carbon_Field::factory('image', 'map_image_big', 'Map Image')
			->help_text('Note: you must update your map and the image will be processed to support multiple zoom levels.'),
		'map_tileset'=>Carbon_Field::factory('tileset', 'map_tileset', 'Status'),
	));

	return $custom_fields;
}
add_filter('mpfy_map_custom_fields', 'mpfy_im_map_custom_fields');

function mpfy_im_map_settings_service($settings, $map_id) {

	$uploads_dir = wp_upload_dir();
	$settings['image_status'] = get_post_meta($map_id, '_map_tileset_status', true);
	$settings['image_source'] = mpfy_tileset_get_url($map_id);

	return $settings;
}
add_filter('mpfy_map_settings_service', 'mpfy_im_map_settings_service', 10, 2);

function mpfy_im_map_get_tileset($tileset, $map_id) {
	$map = new Mpfy_Map($map_id);

	if ($map->get_mode() == 'image') {
		$image_big = get_post_meta($map->get_id(), '_map_image_big', true);
		$status = get_post_meta($map->get_id(), '_map_tileset_status', true);
		$url = mpfy_tileset_get_url($map->get_id());
		
		if (!$image_big) {
			$tileset['message'] = 'Please select an image for the map.';
			return $tileset;
		}
		if ($status != 'ready') {
			$tileset['message'] = 'Your image has not been processed, yet.';
			return $tileset;
		}

		$tileset['url'] = $url;
	}

	return $tileset;
}
add_filter('mpfy_map_get_tileset', 'mpfy_im_map_get_tileset', 10, 2);
