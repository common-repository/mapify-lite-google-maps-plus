(function($, $window, $document){

var image_status = mpfy_image_mode.image_status;
var image_source = mpfy_image_mode.image_source;

$('body').on('mpfy_image_mode_status_changed', function(e) {
	image_status = e._response.status;
	$('body').trigger($.Event('mpfy_trigger_map_reload'));
});

$('body').on('mpfy_admin_map_updated', function(e) {
	// efo not initialized yet
	if (!e.mpfy.settings.efo) {
		return false;
	}

	e.mpfy.settings.efo.map.mapTypes.set('mpfy', new mpfy.mapType(image_source, e.mpfy.settings.bg_color));
	if (e.mpfy.settings.mode == 'image') {
		if (image_status == 'ready') {
			e.mpfy.settings.efo.map.setMapTypeId('mpfy');
		}
	}
});

$('body').on('mpfy_admin_map_location_map_updated', function(e) {
	var settings = e.mpfy.settings;

	var image_status = settings.response.image_status;
	var image_source = settings.response.image_source;

	settings.efo.map.mapTypes.set('mpfy', new mpfy.mapType(image_source, settings.bg_color));
	if (settings.mode == 'image') {
		if (image_status == 'ready') {
			settings.efo.map.setMapTypeId('mpfy');
		}
	}
});

})(jQuery, jQuery(window), jQuery(document));