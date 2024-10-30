;(function($){

var logError = function(message, data) {
	if (typeof console == 'undefined' || typeof console.error == 'undefined' || typeof console.log == 'undefined') {
		return;
	}
	console.error(message);

	if (typeof data != 'undefined') {
		console.log(data);
	}
}

var $win = $(window);
var $doc = $(document);
var _scroll_timer = null;

$.mpfy = function(action, call_options) {
	var all_instances = window.mpfy_instances;
	var target = all_instances;

	if (!$.isFunction(this)) {
		instance = $(this).data('mpfy_instance');
		target = instance;
		if (!instance) {
			return this; // no map instance found for the selector
		}
	}
	if (!target) {
		return this; // no target is available
	}
	
	var method = 'action' + action.charAt(0).toUpperCase() + action.slice(1);
	if (typeof $.mpfy[method] != 'undefined') {
		$.mpfy[method](target, call_options);
	} else {
		logError('Mapify: Unknown action called: ' + action);
	}
	return this;
}
$.fn.mpfy = $.mpfy;

$.mpfy.actionRefresh = function(target) {
	if (typeof target.map == 'undefined') {
		for (var id in target) {
			google.maps.event.trigger(target[id].map, 'resize');
			target[id].refreshLayout();
		}
	} else {
		google.maps.event.trigger(target.map, 'resize');
		target.refreshLayout();
	}
}

$.mpfy.actionRecenter = function(target) {
	if (typeof target.map == 'undefined') {
		for (var id in target) {
			target[id].recenter();
		}
	} else {
		target.recenter();
	}
}

$.mpfy.actionSetStrings = function(target, strings) {
	if (typeof target.map == 'undefined') {
		for (var id in target) {
			target[id].strings = $.extend(target[id].strings, strings);
		}
	} else {
		target.strings = $.extend(target.strings, strings);
	}
}

window.MapifyPro = {
	classes: {
		popUpOpen : 'mpfy-p-popup-active',
		scrollShow : 'mpfy-p-scroll-show-scroll',
		openBodyClass : 'mpfy-popup-open'
	},
	ajaxUrl: window.wp_ajax_url,
	meters2unit: function(meters, unit) {
		var divisor = unit == 'km' ? 1 : 1.609344;
		var km = meters / 1000;
		return km / divisor;
	},
	isPhone: function() {
		var phone_dimension = Math.min($(window).width(), $(window).height());
		return (phone_dimension <= 360);
	},
	isTablet: function() {
		return is_tablet = ($(window).width() <= 1050);
	},
	isTouchDevice: function() {
		return ('ontouchstart' in window || navigator.msMaxTouchPoints);
	},
	initPopupSlider: function() {
		if ($('.mpfy-p-slider').length == 0 || $('.mpfy-p-slider-top ul.mpfy-p-slides > li').length < 2) {
			return;
		}

		$('.mpfy-p-slider-top ul.mpfy-p-slides').carouFredSel({
			auto 	: false,
			responsive : true,
    		height: 'variable',
			items : {
				visible : 1,
				height: 'variable'
			},
			scroll : {
				items : 1,
				fx : 'crossfade',
				onAfter: function() {
					MapifyPro.updatePopupScroll();
				}
			},
			swipe : {
				onTouch : true
			}
		});
		$('.mpfy-p-slider-bottom ul.mpfy-p-slides').carouFredSel({
			auto 	: false,
			prev	: $('.mpfy-p-arrow-previous'),
			next	: $('.mpfy-p-arrow-next'),
			items : {
				visible :  {
					min : 1,
					height : 6
				}
			},
			scroll : {
				items : 1
			},
			swipe : {
				onTouch : true
			}
		});
	},
	closeTooltips: function() {
		$('.mpfy-tooltip').trigger({
    		'type': 'tooltip_close'
    	});
	},
	updatePopupScroll: function() {
		if ($('.mpfy-p-slider').length == 0) {
			return;
		}

		$('.mpfy-p-scroll').css({
			'height' : $('.mpfy-p-slider').height() - 30
		});
	},
	updateSidebarForMobile: function() {
		if ($('#mpfy-p-sidebar-top').length == 0) {
			return false;
		}

		if ($(window).width() <= 985) {
			if ($('#mpfy-p-sidebar-top > *').length > 0) {
				$('#mpfy-p-sidebar-top > *').remove().appendTo($('#mpfy-p-sidebar-bottom'));
			}
		} else if ($('#mpfy-p-sidebar-bottom > *').length > 0) {
			$('#mpfy-p-sidebar-bottom > *').remove().appendTo($('#mpfy-p-sidebar-top'));
		}
	},
	onDocReady: function() {
		$('a.mpfy-pin').bind('click', function(e) {
			e.preventDefault();
			$doc.trigger($.Event('mpfy_action_open_popup', {
				settings: {
					value: $(this).attr('data-id')
				}
			}));
		});

		$doc.on('click', 'a[data-mpfy-action]', function(e) {
			e.preventDefault();

			var action = $(this).attr('data-mpfy-action');
			var value = $(this).attr('data-mpfy-value');
			$doc.trigger({
	    		'type': 'mpfy_action_' + action,
	    		'settings': {
	    			'value': value
	    		}
	    	});
		});

		$doc.on('mpfy_action_set_map_tag', function(e) {
			MapifyPro.closePopup();
			$('select[name="mpfy_tag"] option[value="' + encodeURIComponent(e.settings.value) + '"]').each(function() {
				$(this).closest('select').val($(this).val()).trigger('change');
			});
		});

		$doc.on('mpfy_action_open_popup', function(e) {
			var a = $('a.mpfy-pin-id-' +  e.settings.value + ':first');
			if (a.length === 0) {
				return;
			}

			if (a.hasClass('mpfy-external-link')) {
				var target = a.attr('target');
				if (target == '_self') {
					window.location = a.attr('href');
				} else {
					window.open(a.attr('href'));
				}
			} else {
				if (a.attr('href') && a.attr('href') !== '#') {
					MapifyPro.openPopup(a.attr('href'));
				}
			}
		});

		$(document).on('click', '.mpfy-p-popup-background', function(e) {
			MapifyPro.closePopup();
		});
	},
	onWinLoad: function() {

	},
	showLoading: function() {
		var loading = $('.mpfy-p-loading');
		loading.show();
	},
	hideLoading: function() {
		var loading = $('.mpfy-p-loading');
		loading.hide();
	},
	openPopup: function(url, callback) {
		MapifyPro.closeTooltips();
		MapifyPro.closePopup();

		var popup = $('.mpfy-p-popup');

		MapifyPro.showLoading();
		$.get(url, function(response) {
			MapifyPro.hideLoading();
			$('html, body').addClass(MapifyPro.classes.openBodyClass);

			var popup = $(response);
			popup.appendTo('body');

			popup.find('.mpfy-p-slider-top img').fullscreener();

			popup.find('.mpfy-p-close').on('click touchstart', function(e){
				e.preventDefault();
				MapifyPro.closePopup();
			});

			$('body').trigger($.Event('mpfy_popup_opened', {
				mpfy: {
					'popup': popup
				}
			}));

			// slider
			popup.on('click touchstart', '.mpfy-p-slider-bottom a', function(e){
				e.preventDefault();

				var _pos = parseInt($(this).data('position'));
				if (!isNaN(_pos)) {
					$('.mpfy-p-slider-top ul.mpfy-p-slides').triggerHandler('slideTo', _pos);
				}
			});

			// show the popup
			popup.addClass(MapifyPro.classes.popUpOpen);

			setTimeout(function() {
				// scrollbar
				if ($('.mpfy-p-slider').length > 0) {
					popup.find('.mpfy-p-scroll').jScrollPane({
						autoReinitialise: true
					});
					popup.find('.mpfy-p-scroll').bind('jsp-scroll-y', function(event, scrollPositionY, isAtTop, isAtBottom){
						$('.mpfy-p-scroll').addClass(MapifyPro.classes.scrollShow);
						clearTimeout(_scroll_timer);
						_scroll_timer = setTimeout(function(){
							$('.mpfy-p-scroll').removeClass(MapifyPro.classes.scrollShow);
						}, 2000);
					});
				}
				
				MapifyPro.initPopupSlider();
				MapifyPro.updatePopupScroll();
				MapifyPro.updateSidebarForMobile();
			}, 100);

			if (typeof stButtons != 'undefined') {
				stButtons.locateElements();
			}

			if (callback != 'undefined' && callback) {
				callback();
			}
		}, 'html');
	},
	closePopup: function() {
		if ($('.mpfy-p-popup').length == 0) {
			return false;
		}

		$('html, body').removeClass(MapifyPro.classes.openBodyClass);
		$('.mpf-p-popup-holder').remove();
	},
	showSearchPopup: function(map_instance, content) {
		if (typeof map_instance.search_tooltip == 'undefined') {
			map_instance.search_tooltip = new MapifyPro.Tooltip({
				'rgba': map_instance.tooltip_background,
				'content': content,
				'close_behavior': 'manual',
				'animate': true
			});
			map_instance.search_tooltip.node().addClass('mpfy-tooltip mpfy-flip-tooltip');
			map_instance.search_tooltip.setGoogleMap(map_instance.map);
		} else {
			map_instance.search_tooltip.setContent(content);
		}

		var form = $(map_instance.container).find('.mpfy-search-form:first');

		var l = form.offset().left + form.width() - map_instance.search_tooltip.node().width() / 2;
		var t = form.offset().top + 10 + form.height() + map_instance.search_tooltip.node().height();
		map_instance.search_tooltip.showCentered(l, t);
	}
};

MapifyPro.Instance = {
	create: function(instance_start) {
		if (typeof window.mpfy_instances == 'undefined') {
			window.mpfy_instances = {};
			window.mpfy_instances_count = instance_start - 1;
		}
		window.mpfy_instances_count ++;

		window.mpfy_instances[window.mpfy_instances_count] = {
			'instance_id': window.mpfy_instances_count,
			'container': '#mpfy-map-' + window.mpfy_instances_count,
			'lastSearchPosition': null
		};

		window.mpfy_current_instance = window.mpfy_instances[window.mpfy_instances_count];

		var inst = window.mpfy_instances[window.mpfy_instances_count];

		inst.strings = mpfy_script_settings.strings;
		inst.proprietary_data = JSON.parse($(inst.container).attr('data-proprietary'));
		inst.getProprietaryData = function(key) {
			if (typeof inst.proprietary_data[key] == 'undefined') {
				return null;
			}
			return inst.proprietary_data[key];
		};

		$(inst.container).data('mpfy_instance', inst);

		inst.clear_search = function(recenter_on_blank) {
			recenter_on_blank = (typeof recenter_on_blank == 'undefined') ? true : recenter_on_blank;

			$(this.container).find('input[name="mpfy_search"]').val('').trigger('change');
			$(this.container).find('.mpfy-search-form').trigger($.Event('submit', {
				'mpfy': {
					recenter_on_blank: recenter_on_blank
				}
			}));
		}

		$(inst.container).find('.mpfy-clear-search').click(function(e) {
			e.preventDefault();
			inst.clear_search();
		});

		$(inst.container).find('input[name="mpfy_search"]').bind('keyup change', function() {
			if ($(this).val()) {
				$(inst.container).find('.mpfy-clear-search').show();
			} else {
				$(inst.container).find('.mpfy-clear-search').hide();
			}
			return true;
		});

		if ("ontouchstart" in window || navigator.msMaxTouchPoints) {
			$(inst.container).addClass('mpfy-touch-device');
		}

		inst.refreshLayout = function() {
			var controls = $(inst.container).find('.mpfy-controls');
			var breaking_width = 650;
			controls.toggleClass('mpfy-controls-mobile', controls.width() <= breaking_width);
			$(inst.container).toggleClass('mpfy-layout-mobile', $(inst.container).width() <= breaking_width);
		}
		inst.refreshLayout();

		$(window).on('load resize orientationchange', inst.refreshLayout);

		(function(ref){
			$doc.one('ready', function(){
				$('body').trigger('mpfy_instance_created', ref);
			});
		})(inst);

		return window.mpfy_current_instance;
	}
};

MapifyPro.Google = function(center, zoom, pins, map_instance, settings) {
	if (!document.getElementById("custom-mapping-google-map-" + map_instance.instance_id)) {
		return; // map div not rendered - do not attempt to load the map
	}

	this.settings = settings;
	map_instance.mpfy_mode = 'google';
	map_instance.mouse = {
		x: 0,
		y: 0
	};
	map_instance.geocoder = new google.maps.Geocoder();
	map_instance.search_radius_unit_name = settings.search_radius_unit_name;
	map_instance.search_radius_unit = settings.search_radius_unit;
	map_instance.search_radius = settings.search_radius;
	map_instance.search_region_bias = settings.search_region_bias;

	$(document).mousemove(function(evt){
		map_instance.mouse.x = evt.pageX;
		map_instance.mouse.y = evt.pageY;
	});

	center = center.split(',');
	center = new google.maps.LatLng(center[0], center[1]);
	map_instance.center = center;
	var opts = {
        zoom: zoom.zoom,
        center: center,
        mapTypeId: google.maps.MapTypeId[settings.mapTypeId],
        disableDoubleClickZoom: true,
        scrollwheel: zoom.enabled,
        backgroundColor: settings.background,

        panControl: false,
        mapTypeControl: true,
        mapTypeControlOptions: {
        	position: google.maps.ControlPosition.RIGHT_BOTTOM
        },
        scaleControl: true,
        streetViewControl: false,
        zoomControl: false,
        overviewMapControl: true
    };
    // (settings.background) ? settings.background : 'none' // for transparent map background

    if (settings.style.length > 0) {
    	opts.styles = settings.style;
    }
    if (!settings.ui_enabled) {
    	opts.scaleControl = false;
    	opts.mapTypeControl = false;
    	opts.overviewMapControl = false;
    }

    var mpfy_map = new google.maps.Map(document.getElementById("custom-mapping-google-map-" + map_instance.instance_id), opts);
    var overlay = new google.maps.OverlayView();
	overlay.draw = function() {};
	overlay.setMap(mpfy_map);
	mpfy_map.custom_overlay = overlay;
    map_instance.map = mpfy_map;
    this.map = mpfy_map;

    // RIGHT_BOTTOM control offset
	var emptyDiv = document.createElement('div');
	emptyDiv.className = 'mpfy-empty-google-control';
	emptyDiv.index = 0;
	mpfy_map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(emptyDiv);

    if (settings.map_mode == 'image') {
    	this.set_image_source(settings.image_source);
    	mpfy_map.setOptions({
    		mapTypeControl: false,
    		overviewMapControl: false,
    		scaleControl: false,
    		streetViewControl: false
    	});
    }

    map_instance.markers = [];
    for (var i = 0; i < pins.length; i++) {
    	var p = pins[i];
    	opts = {
            position: new google.maps.LatLng(p.google_coords[0], p.google_coords[1]),
            draggable: false,
            map: mpfy_map
        }
        if (p.pin_image.url) {
        	opts.icon = p.pin_image.url;
        }
        if (p.animate_pinpoints) {
            opts.animation = google.maps.Animation.DROP;
        }
        if (settings.map_mode == 'image') {
        	opts.optimized = false;
        }
		
    	var marker = new google.maps.Marker(opts);
    	marker._mpfy = {};
    	marker._mpfy.marker = marker;
    	marker._mpfy.pin_id = p.ID;
    	marker._mpfy.pin_anchor = p.pin_image.anchor;
        marker._mpfy.pin_tags = p.data_tags;
        marker._mpfy.pin_city = (p.pin_city == null) ? "" : p.pin_city.toLowerCase(); // lower case for easier searching
        marker._mpfy.pin_zip = p.pin_zip;
        marker._mpfy.animate = p.animate_pinpoints;
        if (marker.getIcon()) {
	        marker._mpfy.image_object = new Image();
	        marker._mpfy.image_object.src = marker.getIcon();
        }

        var tooltip = null;
        if (p.tooltip_enabled) {
        	tooltip = new MapifyPro.Tooltip({
				'rgba': map_instance.tooltip_background,
				'content': p.tooltip_content,
				'close_behavior': p.tooltip_close,
				'animate': p.animate_tooltips
			});
			tooltip.node().addClass('mpfy-tooltip');
			tooltip.setGoogleMap(mpfy_map);
        }
		marker._mpfy.tooltip_object = tooltip;

        marker._mpfy.visibilityOptions = {};
        marker._mpfy.refreshVisibility = function() {
        	var visibility_final = true;
        	for (option in this.visibilityOptions) {
        		var visible = this.visibilityOptions[option];
        		if (!visible) {
        			visibility_final = false;
        			break;
        		}
        	}
        	this.marker.setVisible(visibility_final);
        	if (!settings.clustering_enabled && this.animate) {
        		this.marker.setAnimation(google.maps.Animation.DROP);
        	}
        }

        marker._mpfy.mapifyGetPosition = function() {
        	var proj = map_instance.map.custom_overlay.getProjection();
			var pos = this.getPosition();
			var p = proj.fromLatLngToContainerPixel(pos);

        	var anchor_x = 0;
        	var anchor_y = -10;
        	if (this.getIcon()) {
        		// anchor_x = Math.ceil(this.image_object.width / 2);
        		anchor_y -= this._mpfy.image_object.height;
        	} else {
        		anchor_y -= 40; // default pin size
        	}

        	var position = {
        		'x': $(map_instance.container).find('.mpfy-mode-google_maps:first').offset().left + p.x + anchor_x,
        		'y': $(map_instance.container).find('.mpfy-mode-google_maps:first').offset().top + p.y + anchor_y
        	}
        	return position;
        }

        google.maps.event.addListener(marker, 'click', function() {
        	var settings = this._mpfy;
	    	var tooltip = settings.tooltip_object;
	    	var a = $(map_instance.container).find('.mpfy-pin-id-' + this._mpfy.pin_id + ':first');

	    	var self = this;
	    	var hover = function() {
	    		google.maps.event.trigger(self, 'mouseover');
	    	}
	    	var click = function() {
		    	if (a.length > 0) {
	   				a.trigger('click');
		    	}
	    	}

	    	if (tooltip) {
	    		// stop if mouseover happend this frame
	    		if ( MapifyPro.isTouchDevice() && this._mpfy._mouseover ) {
					return;
				} else {
					if ( tooltip.node().is(':visible') ) {
				    	click();
					} else {
						hover();
					}
					return;
				}
	    	}
	    	click();
        });

        google.maps.event.addListener(marker, 'mouseover', function(){
    		this._mpfy._mouseover = true;
    		var self = this;
    		setTimeout(function() {
    			self._mpfy._mouseover = false;
    		}, 1);

    		if (!this.getMap()) {
    			return false;
    		}

    		var marker = this;
	    	var settings = this._mpfy;
	    	var tooltip = settings.tooltip_object;
	    	var proj = this.getMap().custom_overlay.getProjection();
			var pos = this.getPosition();
			var p = proj.fromLatLngToContainerPixel(pos);
			var map = this.getMap();

	    	if (tooltip) {
		    	var anchor_x = 0;
		    	var anchor_y = -10;
		    	if (this.getIcon()) {
		    		anchor_y -= settings.pin_anchor[1];
		    	} else {
		    		anchor_y -= 40; // default google pin height
		    	}

	    		var shwtltp = function() {
	    			if ( tooltip.node().is(':visible') ) {
	    				return false;
	    			}
	    			var t = $(map_instance.container).offset().top + p.y - tooltip.node().height() + anchor_y;
		    		var l = $(map_instance.container).offset().left + p.x - Math.ceil(tooltip.node().width() / 2) + anchor_x;
		    		setTimeout(function() {
				    	tooltip.node().trigger({
				    		'type': 'tooltip_mouseover',
				    		'settings': {
				    			'left': l,
				    			'top': t
				    		}
				    	});
		    		}, 100);
	    		}

	    		if (MapifyPro.isPhone()) {
		    		google.maps.event.addListenerOnce(map, 'center_changed', function() {
						setTimeout(function() {
							// recalculate
							pos = marker.getPosition();
							p = proj.fromLatLngToContainerPixel(pos);
							shwtltp();
						}, 100);
					});
					// offset the position slightly so that the map is centered lower so there is more vertical space for the tooltip on mobile
					var offsetAmount = 128 / Math.pow(2, map.getZoom());
					var offsetPosition = new google.maps.LatLng(this.getPosition().lat() + offsetAmount, this.getPosition().lng());
	    			map.setCenter(offsetPosition);
	    		} else {
		    		shwtltp();
	    		}
	    	}
	    });
	    google.maps.event.addListener(marker, 'mouseout', function(){
	    	var settings = this._mpfy;
	    	var tooltip = settings.tooltip_object;
	    	if (tooltip) {
	    		tooltip.node().trigger({
		    		'type': 'tooltip_mouseout'
		    	});
	    	}
	    });

        map_instance.markers.push(marker);
    };
    map_instance.getMarkerByLocationId = function(location_id) {
    	for (var i = 0; i < map_instance.markers.length; i++) {
    		var marker = map_instance.markers[i];
    		if (marker._mpfy.pin_id == location_id) {
    			return marker;
    		}
    	}
    };

    if (settings.clustering_enabled) {
    	map_instance.clusterer = new MarkerClusterer(mpfy_map, map_instance.markers, {
    		maxZoom: 15,
    		gridSize: 50
    	});
    }

    map_instance.refresh_markers = function() {
    	var markers = this.markers;
    	for (var i = markers.length - 1; i >= 0; i--) {
			markers[i]._mpfy.refreshVisibility();
		};
    }

    map_instance.get_visible_markers = function() {
    	var markers = this.markers;
    	var visible = [];
    	for (var i = markers.length - 1; i >= 0; i--) {
			if (markers[i].getVisible()) {
				visible.push(markers[i]);
			}
		}
		return visible;
    }

    map_instance.uncluster = function(marker) {
    	if (!this.clusterer) {
			return false;
		}

    	var markers = this.markers;
    	var other_markers = [];
    	for (var i = 0; i < markers.length; i++) {
    		var m = markers[i];
    		if (m._mpfy.pin_id == marker._mpfy.pin_id) {
    			continue;
    		}
    		other_markers.push(m);
    	}

		this.clusterer.clearMarkers();
		this.clusterer.addMarkers(other_markers);

		marker.setMap(this.map);
    }

    map_instance.selectTagDropdown = $(map_instance.container).find('select[name="mpfy_tag"]');
    map_instance.selectTag = function(tag_id) {
		var markers = window.mpfy_instances[map_instance.instance_id].markers;

		if (settings.clustering_enabled) {
			map_instance.clusterer.clearMarkers();
			map_instance.refresh_markers();
		}

		setTimeout(function() {
			if (tag_id == 0) {
				// reset view
				mpfy_map.setCenter(center);
			    mpfy_map.setZoom(zoom.zoom);

				for (var i = markers.length - 1; i >= 0; i--) {
					markers[i]._mpfy.visibilityOptions['tag'] = true;
				}
				map_instance.refresh_markers();
				var visible_markers = map_instance.get_visible_markers();
			} else {
				for (var i = markers.length - 1; i >= 0; i--) {
					markers[i]._mpfy.visibilityOptions['tag'] = (typeof markers[i]._mpfy.pin_tags[tag_id.toString()] != 'undefined');
				}
				map_instance.refresh_markers();
				var visible_markers = map_instance.get_visible_markers();

				if (settings.filters_center) {
					if (visible_markers.length > 0) {
						var bounds = new google.maps.LatLngBounds();
						for (var i = 0; i < visible_markers.length; i++) {
							bounds.extend(visible_markers[i].getPosition());
						}

						if (visible_markers.length == 1) {
							map_instance.map.panTo(visible_markers[0].getPosition());
						} else {
							var current_zoom = map_instance.map.getZoom();
							google.maps.event.addListenerOnce(map_instance.map, 'center_changed', function() {
								map_instance.map.setZoom(Math.min(map_instance.map.getZoom(), current_zoom));
							});
							map_instance.map.fitBounds(bounds);
						}
					}
				}
			}

			if (settings.clustering_enabled) {
				map_instance.clusterer.clearMarkers();
				map_instance.clusterer.addMarkers(visible_markers);
		    }

		    $(map_instance.container).trigger($.Event('mpfy_filter_applied', {
				mpfy: {
					'tag_id': tag_id
				}
			}));
		});
    }
    map_instance.recenter = function() {
    	map_instance.selectTag(0);
    }

    $(map_instance.container).on('mpfy_filter_applied', function(e) {
    	var tag_id = e.mpfy.tag_id;
		var tag_label = map_instance.selectTagDropdown.find('option[value="' + tag_id + '"]');
		map_instance.selectTagDropdown.val(tag_id);
    });

    $(map_instance.container).find('.mpfy-selecter-wrap').find('select:first').selecter();
    map_instance.selectTagDropdown.change(function() {
		var tag_id = $(this).val();  
		map_instance.selectTag(tag_id);
	}).change();
	
	map_instance.afterSearch = function() {
		var markers = window.mpfy_instances[map_instance.instance_id].markers;
		var bounds = new google.maps.LatLngBounds();
		map_instance.refresh_markers();

		if (settings.clustering_enabled) {
			map_instance.clusterer.clearMarkers();
		}

		var visible_markers = map_instance.get_visible_markers();
		for (var i = 0; i < visible_markers.length; i++) {
			bounds.extend(visible_markers[i].getPosition());
		}

		if (settings.search_center) {
			if (visible_markers.length > 0) {
				google.maps.event.addListenerOnce(map_instance.map, 'center_changed', function() {
					setTimeout(function() {
						var z = map_instance.map.getZoom();
						z = Math.min(10, z);
						map_instance.map.setZoom(z);
					}, 100);
				});
				map_instance.map.fitBounds(bounds);
			} else {
				map_instance.map.setCenter(center);
			}
		}

		if (settings.clustering_enabled) {
			map_instance.clusterer.clearMarkers();
			map_instance.clusterer.addMarkers(visible_markers);
	    }

	    $(map_instance.container).trigger('mpfy_search');
	}

	$(map_instance.container).find('.mpfy-search-form').submit(function(e) {
		e.preventDefault();
		var recenter_on_blank = (typeof e.mpfy == 'undefined' || typeof e.mpfy.recenter_on_blank == 'undefined') ? true : e.mpfy.recenter_on_blank;

		var q = $(this).find('input[name="mpfy_search"]').val().toLowerCase();
		var markers = window.mpfy_instances[map_instance.instance_id].markers;
		var button = $(this).find('input[type="submit"]');

		if (settings.clustering_enabled) {
			map_instance.clusterer.clearMarkers();
		}

		map_instance.lastSearchPosition = null;
		if (!q) {
			for (var i = 0; i < markers.length; i++) {
				markers[i]._mpfy.visibilityOptions['search'] = true;
			}

			if (recenter_on_blank) {
				mpfy_map.setCenter(center);
				mpfy_map.setZoom(zoom.zoom);
			}

			if (settings.clustering_enabled) {
				map_instance.clusterer.clearMarkers();
				map_instance.clusterer.addMarkers(markers);
		    }

		    map_instance.refresh_markers();
		    $(map_instance.container).trigger('mpfy_search');
		} else {
			if (settings.map_mode == 'image') {
				for (var i = 0; i < markers.length; i++) {
					var m = markers[i];
					m._mpfy.visibilityOptions['search'] = false;
					if (m._mpfy.pin_city.indexOf(q) !== -1 || m._mpfy.pin_zip.indexOf(q) !== -1) {
						m._mpfy.visibilityOptions['search'] = true;
					}
				}
				map_instance.afterSearch();

				if (map_instance.get_visible_markers().length == 0) {
					MapifyPro.showSearchPopup(map_instance, map_instance.strings.no_search_results);
				}
			} else {
				button.val('Searching').attr('disabled', 'disabled');
				var geocodingQuery = {'address': q, 'region': map_instance.search_region_bias};
			    map_instance.geocoder.geocode(geocodingQuery, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						var closest = {
							'marker': null,
							'distance': 9999
						}
						var center = results[0].geometry.location;
						map_instance.lastSearchPosition = center;
						for (var i = 0; i < markers.length; i++) {
							var m = markers[i];
							m._mpfy.visibilityOptions['search'] = false;
							var distance = MapifyPro.meters2unit(google.maps.geometry.spherical.computeDistanceBetween(center, m.getPosition()), map_instance.search_radius_unit);
							if (distance < closest.distance) {
								closest.distance = distance;
								closest.marker = m;
							}
							if (distance <= map_instance.search_radius) {
								m._mpfy.visibilityOptions['search'] = true;
							}
							m._mpfy.refreshVisibility();
							if (m.getVisible()) {
								has_results = true;
							}
						}
						map_instance.afterSearch();

						if (map_instance.get_visible_markers().length == 0) {
							MapifyPro.showSearchPopup(map_instance, map_instance.strings.no_search_results_with_closest);

							$('.mpfy-closest-pin').not('.mpfy-activated').each(function() {
								$(this).addClass('mpfy-activated');
								$(this).click(function(e) {
									e.preventDefault();

									if (closest.marker) {
										(function(mrkr, minst) {

											var highlight_timeout = null;
											var highlight_pin = function() {
												clearTimeout(highlight_timeout);
												highlight_timeout = setTimeout(function() {
													google.maps.event.trigger(mrkr, 'mouseover');
												}, 250);
											}

											minst.clear_search(false);
											if (mrkr._mpfy.animate) {
												google.maps.event.addListenerOnce(mrkr, 'animation_changed', highlight_pin);
											} else {
												google.maps.event.addListenerOnce(minst.map, 'center_changed', highlight_pin);
											}
											minst.map.setCenter(mrkr.getPosition());
											
										})(closest.marker, map_instance);
									}
								});
							});
						}
					} else {
						logError('Google Maps Geocoding returned an error: ' + status.toString(), geocodingQuery);
						MapifyPro.showSearchPopup(map_instance, map_instance.strings.search_geolocation_failure);
					}
					button.val('Search').removeAttr('disabled');
			    });
			}
		}

		map_instance.refresh_markers();
	});

	$(map_instance.container).find('.mpfy-zoom-in').click(function(e) {
		e.preventDefault();
		mpfy_map.setZoom(mpfy_map.getZoom() + 1);
	});

	$(map_instance.container).find('.mpfy-zoom-out').click(function(e) {
		e.preventDefault();
		mpfy_map.setZoom(Math.max(0, mpfy_map.getZoom() - 1));
	});

	$(map_instance.container).find('.mpfy-tl-item').click(function(e) {
		e.preventDefault();
		map_instance.selectTagDropdown.val($(this).attr('data-tag-id')).trigger('change');
	});

	$(map_instance.container).on('mpfy_highlight_pin', function(e) {
		var pin_id = e.settings.location_id;
		var minst = $(this).data('mpfy_instance');
		var marker = false;
		for (var i = 0; i < minst.markers.length; i++) {
			if (minst.markers[i]._mpfy.pin_id == pin_id) {
				marker = minst.markers[i];
				break;
			}
		}

		if (!marker) {
			return;
		}

		google.maps.event.addListenerOnce(minst.map, 'idle', function(){
			google.maps.event.trigger(marker, 'mouseover');
		});
		minst.map.setCenter(marker.getPosition());
	});

	$('body').trigger({
		'type': 'mpfy_google_map_loaded',
		'map_instance': map_instance
	});
};

MapifyPro.Google.prototype.set_image_source = function(image_source) {
    var mpfyImageMapType = new google.maps.ImageMapType({
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

    this.map.mapTypes.set('mpfy', mpfyImageMapType);
    this.map.setMapTypeId('mpfy');
}

$doc.ready(MapifyPro.onDocReady);
$win.load(MapifyPro.onWinLoad);

$win.on('resize orientationchange mpfy_popup_load', function(){
	MapifyPro.initPopupSlider();
	MapifyPro.updatePopupScroll();
	MapifyPro.updateSidebarForMobile();
});

})(jQuery);