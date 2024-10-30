<script type="text/javascript">
jQuery(document).ready(function(){
	var map_instance = MapifyPro.Instance.create(<?php echo $mpfy_instances; ?>);
	map_instance.map_id = <?php echo json_encode($map_id) ?>;
	map_instance.routes = <?php echo json_encode($routes); ?>;
	map_instance.tooltip_background = <?php echo json_encode($tooltip_background); ?>;
	map_instance.default_pin_image = <?php echo json_encode($map->get_default_pin_image()); ?>;
	
	var center = <?php echo json_encode($center); ?>;
	var zoom = {
		'zoom': <?php echo $zoom_level; ?>,
		'enabled': <?php echo ($zoom_enabled) ? 'true' : 'false'; ?>
	};
	var settings = {
		'mapTypeId': <?php echo json_encode($google_map_mode); ?>,
		'map_mode': <?php echo json_encode($mode); ?>,
		'search_center': <?php echo json_encode($search_center); ?>,
		'filters_center': <?php echo json_encode($filters_center); ?>,
		'style': <?php echo json_encode($google_map_style); ?>,
		'clustering_enabled': <?php echo json_encode($clustering_enabled); ?>,
		'background': <?php echo json_encode($map_background_color); ?>,
		'ui_enabled': <?php echo json_encode($map_google_ui_enabled); ?>,
		'image_source': <?php echo json_encode($tileset['url']); ?>,
		'search_radius_unit_name': <?php echo json_encode($search_radius_unit_name); ?>,
		'search_radius_unit': <?php echo json_encode($search_radius_unit); ?>,
		'search_radius': <?php echo json_encode($search_radius); ?>,
		'search_region_bias': <?php echo json_encode($search_region_bias); ?>
	};
	var inst = new MapifyPro.Google(center, zoom, <?php echo json_encode($pins); ?>, map_instance, settings);

	<?php if (isset($_GET['mpfy-pin'])) : ?>
		var open_pin = <?php echo $_GET['mpfy-pin']; ?>;
		var open_tooltip = <?php echo json_encode(isset($_GET['mpfy-tooltip'])); ?>;
		(function(instance) {
			
			setTimeout(function() {
				var a = jQuery(instance.container).find('a.mpfy-pin[data-id="' + open_pin + '"]');
				if (a.length) {
					if (open_tooltip) {

						var marker_found = function(m) {
							instance.uncluster(m);
							google.maps.event.addListenerOnce(instance.map, 'center_changed', function() {
								for (var i = 0; i < instance.markers.length; i++) {
									instance.markers[i].setVisible(false);
								}
								m.setVisible(true);
								google.maps.event.trigger(m, 'mouseover');

								m._mpfy.tooltip_object.node().on('tooltip_closed', function(e) {
									for (var i = 0; i < instance.markers.length; i++) {
										instance.markers[i]._mpfy.refreshVisibility();
									}
								});
							});
							if (m.getMap()) {
								m.getMap().setCenter(m.getPosition());
							}
						}

					} else {

						var marker_found = function(m) {
							if (m.getMap()) {
								m.getMap().setCenter(m.getPosition());
							}
						}
						a.trigger('click');

					}

					google.maps.event.addListenerOnce(instance.map, 'idle', function() {
						for (var i = 0; i < instance.markers.length; i++) {
							var m = instance.markers[i];
							if (m._mpfy.pin_id == open_pin) {
								marker_found(m);
								break;
							}
						}
					});
				}
			}, 1);

		})(map_instance);
	<?php endif; ?>
});
</script>