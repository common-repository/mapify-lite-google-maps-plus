<?php
$map_choices = array(0=>'None') + Mpfy_Map::get_all_maps();

$map_location_custom_fields = array(
	'position_start'=>'',

	'map_location_map'=>Carbon_Field::factory('select', 'map_location_map', 'Map')
		->add_options($map_choices)
		->help_text('Need more than one map? <a href="http://www.mapifypro.com/" target="_blank">Upgrade to MapifyPro!</a>'),

	'position_before_popup'=>'',
	'map_location_tooltip_enabled'=>Carbon_Field::factory('select', 'map_location_tooltip_enabled', 'Enable Popup') // mislabeled - this refers to the popup, not tooltip
		->add_options(array( 'yes' => 'Yes', 'no' => 'No' )),
	'position_after_popup'=>'',

	'map_location_tooltip_show'=>Carbon_Field::factory('select', 'map_location_tooltip_show', 'Enable Tooltip')
		->add_options(array( 'yes' => 'Yes', 'no' => 'No' )),
	'map_location_tooltip_close'=>Carbon_Field::factory('select', 'map_location_tooltip_close', 'Close Tooltip')
		->add_options(array( 'auto' => 'Automatically', 'manual' => 'Manually' ))
		->help_text('<strong>Automatically:</strong> This will cause the tooltip to automatically hide when the user hovers off the location.<br /><strong>Manually:</strong> Good if you want to add links to the tooltip.'),
	'map_location_tooltip'=>Carbon_Field::factory('textarea', 'map_location_tooltip', 'Tooltip')
		->help_text('This is the brief text that appears in the tooltip when a user hovers over this location. You may use basic html text formatting, such as &lt;br&gt;, &lt;i&gt;, etc.'),
	'position_after_tooltip'=>'',

	'position_before_gallery'=>'',
	'map_location_separator_2'=>Carbon_Field::factory('separator', 'map_location_separator_2', 'Gallery Settings'),	
	'map_location_video_embed'=>Carbon_Field::factory('textarea', 'map_location_video_embed', 'Video Embed Code')
		->help_text('Use Vimeo or YouTube iframe video embed codes to include a video in your location’s pop-up gallery. If left blank, the gallery will revert to an image gallery only.'),
	'position_after_video'=>'',
	'map_location_gallery_images'=>Carbon_Field::factory('image_list', 'map_location_gallery_images', 'Gallery Images')
		->setup_labels(array(
			'singular_name'=>'Image',
			'plural_name'=>'Images',
		))
		->add_fields(array(
			Carbon_Field::factory('image', 'image', 'Image'),
		)),
	'position_after_gallery'=>'',
	
	'position_before_map'=>'',
	'map_location_google_location'=>Carbon_Field::factory('map_with_address', 'map_location_google_location', 'Location'),
	'position_after_map'=>'',

	'position_before_address'=>'',
	'map_location_separator_4'=>Carbon_Field::factory('separator', 'map_location_separator_4', 'Address Details'),
	'map_location_address'=>Carbon_Field::factory('text', 'map_location_address', 'Address Line 1'),
	'map_location_address_2'=>Carbon_Field::factory('text', 'map_location_address_2', 'Address Line 2'),
	'map_location_city'=>Carbon_Field::factory('text', 'map_location_city', 'City'),
	'map_location_state'=>Carbon_Field::factory('text', 'map_location_state', 'State'),
	'map_location_zip'=>Carbon_Field::factory('text', 'map_location_zip', 'Zip'),
	'map_location_country'=>Carbon_Field::factory('text', 'map_location_country', 'Country'),
	'position_after_address'=>'',

	'position_end'=>'',
);

$map_location_custom_fields = apply_filters('mpfy_map_location_custom_fields', $map_location_custom_fields);
$map_location_custom_fields = array_filter($map_location_custom_fields); // clear positions

Carbon_Container::factory('custom_fields', MAPIFY_PLUGIN_NAME . ' Options')
	->show_on_post_type(mpfy_get_supported_post_types())
	->add_fields(array_values($map_location_custom_fields));

$raw = get_posts('post_type=map-location&posts_per_page=-1&orderby=title&order=asc');
$locations = array('0'=>'Select One');
foreach ($raw as $r) {
	$ml = new Mpfy_Map_Location($r->ID);
	$coords = $ml->get_coordinates();

	if ($coords) {
		$locations[$ml->get_id()] = $ml->get_title();
	} else {
		// Dynamically update coordinates meta field
		$lat = carbon_get_post_meta($r->ID, 'map_location_google_location_lat');
		$lng = carbon_get_post_meta($r->ID, 'map_location_google_location_lng');

		$coords = array( $lat, $lng );
		$coords = array_filter( $coords );

		if ( ! empty( $coords ) ) {
			$coords = implode( ',', $coords );
			update_post_meta( $r->ID, '_map_location_google_location', $coords );

			$locations[$r->ID] = $r->post_title;
		}
	}
}

$map_custom_fields = array(
	'position_start'=>'',

	'position_before_map_id'=>'',
	'map_id'=>Carbon_Field::factory('text', 'map_id', 'Map ID')
		->help_text( (isset($_GET['post']) ? $_GET['post'] : '') ),
	'position_after_map_id'=>'',

	'position_before_pin_image'=>'',
	'map_pin'=>Carbon_Field::factory('image_pin', 'map_pin', 'Default Pin Image')
		->attach_to_map('map_google_center')
		->help_text('This image will serve as a default pinpoint for your map. PNG file with transparent background is suggested (supports jpg, gif, png).'),
	'position_after_pin_image'=>'',

	'position_before_ui'=>'',
	'map_enable_zoom'=>Carbon_Field::factory('select', 'map_enable_zoom', 'Enable Zoom')
		->add_options(array( 'yes' => 'Yes', 'no' => 'No' )),
	'map_google_ui_enabled'=>Carbon_Field::factory('select', 'map_google_ui_enabled', 'Enable Google Controls')
		->add_options(array( 'yes' => 'Yes', 'no' => 'No', )),
	'map_animate_tooltips'=>Carbon_Field::factory('select', 'map_animate_tooltips', 'Animated Tooltips?')
		->add_options(array( 'yes' => 'Yes', 'no' => 'No' ))
		->help_text('Add a subtle animation to the tooltip that appears when hovering on a location.'),
	'map_animate_pinpoints'=>Carbon_Field::factory('select', 'map_animate_pinpoints', 'Animated Pinpoints?')
		->add_options(array( 'yes' => 'Yes', 'no' => 'No' ))
		->help_text('Add a subtle animation to the pinpoints as they populate the map.'),
	'position_after_ui'=>'',

	'position_before_google_maps'=>'',
	'map_separator_3'=>Carbon_Field::factory('separator', 'map_separator_3', 'Google Maps Mode Settings'),
	'map_main_location'=>Carbon_Field::factory('select_location', 'map_main_location', 'Main Location')
		->add_options($locations),
	'map_google_center'=>Carbon_Field::factory('map_mpfy', 'map_google_center', 'Initial Zoom and Style Preview')
		->help_text('This will be the initial zoom level of the map in Google Maps mode.'),
	'position_after_google_maps'=>'',

	'position_end'=>'',
);

$map_modes = Mpfy_Map::get_map_modes();
if (count($map_modes) > 1) {
	$map_custom_fields = mpfy_array_push_key($map_custom_fields, 'position_after_map_id', array(
		'map_mode'=>Carbon_Field::factory('map_mode', 'map_mode', 'Mode')
			->add_options($map_modes),
	));
}

$map_custom_fields = apply_filters('mpfy_map_custom_fields', $map_custom_fields);
$map_custom_fields = array_filter($map_custom_fields); // clear positions

Carbon_Container::factory('custom_fields', 'Map Options')
	->show_on_post_type('map')
	->add_fields(array_values($map_custom_fields));

Carbon_Container::factory('term_meta', 'Category Options')
	->show_on_taxonomy('location-tag')
	->add_fields(array(
		Carbon_Field::factory('attachment', 'mpfy_location_tag_image', 'Image')
	));

$plugin_settings = array(
	'mpfy_google_api_key'=>Carbon_Field::factory('text', 'mpfy_google_api_key', 'Google Maps API Key')
		->help_text('Specify the Google Maps API Key you wish MapifyPro to use when rendering maps.'),
);

$plugin_settings = apply_filters('mpfy_plugin_settings', $plugin_settings);

if ($plugin_settings) {
	Carbon_Container::factory('theme_options', MAPIFY_PLUGIN_NAME . ' Settings')
		->set_page_parent('mapify.php')
		->add_fields($plugin_settings);
}
