;(function($, $window, $document){

	window.mpfy = {};
	mpfy.mapType = function(image_source, background_color) {
		imageMapType = new google.maps.ImageMapType({
            getTileUrl: function(coord, zoom) {
                var tilesCount = Math.pow(2, zoom);

                if (coord.x >= tilesCount || coord.x < 0 || coord.y >= tilesCount || coord.y < 0) {
                    return null;
                } else {
                    return image_source + 'z' + zoom + '-tile_' + coord.y + '_' + coord.x + '.png';
                }
                
                return null;
            },
            tileSize: new google.maps.Size(256, 256),
            maxZoom: 4,
            minZoom: 0,
            radius: 1738000,
            name: "mpfy"
        });
        return imageMapType;
	}

    $document.ready(function(){
        var is_post = $('#adminmenuwrap a.current');
        is_post = (is_post.attr('href') == 'edit.php');

        if (is_post) {
            var container = $('#' + script_settings.carbon_container);
            var field = $('select[name="_map_location_map"]:first, input[name="_map_location_map[]"]:first');
            var val = field.val();
            if (typeof val != 'undefined' && parseInt(val) > 0) {
                container.removeClass('closed');
            } else {
                container.addClass('closed');
            }
        }
    });

})(jQuery, jQuery(window), jQuery(document));