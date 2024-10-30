;jQuery(function($) {

window.carbon_field.Map = function(element, field_obj) {
	element.data('exposed_field_object', field_obj);
	var field = element.find('.carbon-map-field'),
		map_container = element.find('.carbon-map-canvas'),
		exists = 0,
		marker = false,
		zoom = field.data('zoom'),
		coords = field.val();

	if (coords !== '' || coords.split(',').length == 2) {
		temp = coords.split(',');
		lat = parseFloat(temp[0]);
		lng = parseFloat(temp[1]);

		exists = temp[0] !== '0' && temp[1] !== '0';
	}

	if ( !exists || isNaN(lat) ||isNaN(lng)  ) {
		lat = field.data('default-lat');
		lng = field.data('default-lng');
	};

	//draw a map
	var map = new google.maps.Map(map_container.get(0), {
		zoom: zoom,
		center: new google.maps.LatLng(lat, lng),
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		disableDoubleClickZoom: true
	});
	map.crb = {'markers': []};
	field_obj.map = map;

	// export the map object
	field_obj.update_marker_position = function(point) {
		var latLng = point.latLng || point;
		if ( marker ) {
			marker.setPosition(latLng);
			map.setCenter(latLng)
		} else {
			marker = new google.maps.Marker({
				position: latLng,
				map: map,
				draggable: true
			});
			map.crb.markers.push(marker);

			google.maps.event.addListener(marker, "dragend", function (mEvent) { 
				update_value();
			});
		}
		update_value();
	}

	// if we had coords in input field, put a marker on that spot
	if(exists == 1) {
		field_obj.update_marker_position(new google.maps.LatLng(lat, lng))
	}

	// on click move marker and set new position
	google.maps.event.addListener(map, 'dblclick', function(point) {
		lat = point.latLng.lat();
		lng = point.latLng.lng();
		field_obj.update_marker_position(point);
	});
	google.maps.event.addListener(map, 'zoom_changed', update_value);

	function update_value() {
		field.val(marker.getPosition().lat() + ',' + marker.getPosition().lng() + ',' + map.getZoom());
	}

	// If we are in a widget container, resize the map when the widget is revealed.
	// This is a workaround since maps don't initialize in a hidden div (widget)
	map_container.closest('div.widget').bind('click.widgets-toggle', function(e){
		if ( $(e.target).parents('.widget-inside').length > 0 ) {
			return;
		};

		setTimeout(function() {
			google.maps.event.trigger(map, 'resize');
			field_obj.update_marker_position(new google.maps.LatLng(lat, lng))
		}, 1);
	});
}
window.carbon_field.Map_Mpfy = window.carbon_field.Map;

window.carbon_field.Image_Pin = function(element, field_obj) {
	element.find('.button:not(.carbon-file-remove)').click(function() {
		window.carbon_active_field = element;
		tb_show('','media-upload.php?post_id=0&type=image&carbon_type=image&TB_iframe=true');
	});

	element.find('.carbon-file-remove').click(function() {
		element.find('img').addClass('blank').attr('src', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
		element.find('.carbon-image-field').val('');
		$(this).hide();
		return false;
	});
}

window.carbon_field.Image_Pin = function(element, field_obj) {
	if (typeof(crb_media_types) == 'undefined') {
		var crb_media_types = {};
	}

	// Runs when the image button is clicked.
	$(element).find('.c2_open_media').click(function (e) {
		e.preventDefault();
		
		var row = $(this).closest('.carbon-field'),
			input_field = row.find('input.carbon-file-field'),
			button_label = $(this).attr('data-window-button-label'),
			window_label = $(this).attr('data-window-label'),
			value_type = $(this).attr('data-value-type'),
			file_type = $(this).attr('data-type'); // audio, video, image
		
		if (typeof(crb_media_types[element.data('type')] == 'undefined')) {
			crb_media_types[element.data('type')] = wp.media.frames.crb_media_field = wp.media({
				title: window_label ? window_label : crbl10n.title,
				library: { type: file_type }, // autio, video, image
				button: { text: button_label },
				multiple: false
			});
			
			var crb_media_field = crb_media_types[element.data('type')];
			
			// Runs when an image is selected.
			crb_media_field.on('select', function () {
				// Grabs the attachment selection and creates a JSON representation of the model.
				var media_attachment = crb_media_field.state().get('selection').first().toJSON();
				//Object:
				// alt, author, caption, dateFormatted, description, editLink, filename, height, icon, id, link, menuOrder, mime, name, status, subtype, title, type, uploadedTo, url, width
				
				// Sends the attachment URL to our custom image input field.
				var media_value = media_attachment[value_type];

				input_field.val(media_value).trigger('change');

				switch (file_type) {
					case 'image':
						// image field type
						row.find('.carbon-view_image').attr( 'src', media_value );
						row.find('.carbon-view_file').attr( 'href', media_value );
						row.find('.carbon-description, img').show();
						break;
					case 'audio':
					case 'video':
					default:
						if (parseInt(media_value)==media_value) {
							// attachment field type
							if (media_attachment.type=='image') {
								row.find('.carbon-view_image').attr( 'src', media_attachment.url );
								row.find('.carbon-description, img').show();
							}else{
								// all other file types
								row.find('.carbon-description, img').hide();
							};
						}else{
							// file field type
						};
						row.find('span.attachment_url').html( media_attachment.url );
						row.find('.carbon-view_file').attr('href', media_attachment.url);
						row.find('.carbon-description').show();
				}
			});
		}
		
		var crb_media_field = crb_media_types[element.data('type')];
		
		// Opens the media library frame
		crb_media_field.open();
	});

	$(element).find('.carbon-file-remove').click(function (e) {
		var fieldContainer = $(this).closest('.carbon-field');
		
		fieldContainer.find('.carbon-description').hide();
		fieldContainer.find('input.carbon-file-field').attr('value', '');
		fieldContainer.find('span.attachment_url').html('');
		fieldContainer.find('img').hide();
	});
}

window.carbon_field.Relationship_Mpfy = window.carbon_field.Relationship;

carbon_field.Image_List = function(element, field_obj) {
	carbon_field.Complex.call(this, element, field_obj);

	if (typeof(crb_media_types) == 'undefined') {
		var crb_media_types = {};
	}

	element.find('> .carbon-subcontainer > tbody > tr.carbon-actions a[data-action=add_multiple]:first').click(function(e) {
		e.preventDefault();
		
		field_obj.new_row_type = $(this).data('group');

		crb_media_field = wp.media.frames.crb_media_field = wp.media({
			title: 'Add images to Gallery',
			library: { type: 'image' }, // autio, video, image
			button: { text: 'Add Images' },
			multiple: true
		});
		
		// Runs when an image is selected.
		crb_media_field.on('select', function () {
			// Grabs the attachment selection and creates a JSON representation of the model.
			var media_attachment = crb_media_field.state().get('selection').toJSON();
			//Object:
			// alt, author, caption, dateFormatted, description, editLink, filename, height, icon, id, link, menuOrder, mime, name, status, subtype, title, type, uploadedTo, url, width
			
			for (var i = 0; i < media_attachment.length; i++) {
				var ma = media_attachment[i];

				field_obj.group_selector.find('a:first').trigger('click');
				var row = element.find('.carbon-group-row input.carbon-file-field:last').closest('.carbon-group-row');

				row.find('input.carbon-file-field').val( ma.url );
				row.find('.carbon-view_image').attr( 'src', ma.url );
				row.find('.carbon-view_file').attr( 'href', ma.url );
				row.find('.carbon-description, img').show();
				if (!row.find('.carbon-file-remove').length) {
					row.find('.carbon-preview').append('<span class="carbon-file-remove"></span>');
				}
			}
		});
		
		// Opens the media library frame
		crb_media_field.open();
	});
}

});