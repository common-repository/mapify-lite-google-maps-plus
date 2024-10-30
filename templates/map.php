<?php
global $wpdb;

$errors = array();

$mode = $map->get_mode();
$zoom_level = $map->get_zoom_level();
$zoom_enabled = $map->get_zoom_enabled();
$tileset = apply_filters('mpfy_map_get_tileset', array('url'=>'', 'message'=>''), $map->get_id());
if (!$tileset['url'] && $tileset['message']) {
	$errors[] = $tileset['message'];
}

$animate_tooltips = $map->get_animate_tooltips();
$animate_pinpoints = $map->get_animate_pinpoints();

$pins = $map->get_locations(false);

foreach ($pins as $index => $p) {
	$p = get_post($p->ID);
	$map_location = new Mpfy_Map_Location($p->ID);

	$p->animate_tooltips = $animate_tooltips;
	$p->animate_pinpoints = $animate_pinpoints;

	$p->google_coords = $map_location->get_coordinates();

	$p->pin_image = $map_location->get_pin_image();

	$p->pin_city = $map_location->get_city();
	$p->pin_zip = $map_location->get_zip();
	
	$p->data_tags = array();
	$tags = $map_location->get_tags();
	foreach ($tags as $t) {
		$p->data_tags[$t->term_id] = $t->term_id;
	}

	$p->popup_enabled = $map_location->get_popup_enabled();

	$p->tooltip_enabled = $map_location->get_tooltip_enabled();
	$p->tooltip_close = $map_location->get_tooltip_close_behavior();
	$p->tooltip_content = $map_location->get_tooltip_content();

	$pins[$index] = $p;
}

$map_id = $map->get_id();

$map_background_color = apply_filters('mpfy_map_background_color', '', $map->get_id());
$tooltip_background = apply_filters('mpfy_map_tooltip_background_color', array(0, 0, 0, 0.71), $map->get_id());

$google_map_mode = $map->get_google_map_mode();

$google_map_style = apply_filters('mpfy_google_map_style', 'default', $map->get_id());

$map_tags = $map->get_tags();
$search_enabled = $map->get_search_enabled();
$search_radius_unit_name = $map->get_search_radius_unit_name();
$search_radius_unit = $map->get_search_radius_unit();
$search_radius = $map->get_search_radius();
$search_region_bias = $map->get_search_region_bias();
$search_center = $map->get_search_center_behavior();
$filters_center = $map->get_filters_center_behavior();
$clustering_enabled = apply_filters('mpfy_clustering_enabled', false, $map->get_id());

$map_google_ui_enabled = $map->get_google_ui_enabled();

$filters_enabled = $map->get_filters_enabled();
$filters_list_enabled = $map->get_filters_list_enabled();

$center = implode(',', $map->get_center());

$routes = apply_filters('pretty_routes_load_routes', array(), $map->get_id());

ob_start();
include('map.html.php');
$html = ob_get_clean();

ob_start();
include('map.js.php');
$script = ob_get_clean();

return array('html'=>$html, 'script'=>$script);