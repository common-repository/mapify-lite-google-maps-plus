<?php
add_action('admin_print_scripts', 'mpfy_carbon_extend_js', 11);
function mpfy_carbon_extend_js() {
	wp_enqueue_script('mpfy_carbon_extend', plugins_url('assets/js/carbon-extend.js', MAPIFY_PLUGIN_FILE));
}

class Carbon_Field_Map_Mpfy extends Carbon_Field_Map {
	function save() {
		$this->store->save($this);
		
		$original_name = $this->get_name();
		$original_value = $this->get_value();

		$value = explode(',', $this->get_value());
		if ( count($value) >= 2 ) {
			$lat = floatval($value[0]);
			$lng = floatval($value[1]);
			$zoom = intval($value[2]);
		} else {
			$lat = $lng = '';
			$zoom = 5;
		}

		$this->set_name($original_name . '-lat');
		$this->set_value($lat);
		$this->store->save($this);

		$this->set_name($original_name . '-lng');
		$this->set_value($lng);
		$this->store->save($this);

		$this->set_name($original_name . '-zoom');
		$this->set_value($zoom);
		$this->store->save($this);

		$this->set_name($original_name);
		$this->set_value($original_value);

		return true;
	}

	function load() {
		$original_name = $this->get_name();

		$lat = $lng = '';

		$this->set_name($original_name . '-lat');
		$this->store->load($this);
		$lat = $this->get_value();

		$this->set_name($original_name . '-lng');
		$this->store->load($this);
		$lng = $this->get_value();

		$this->set_name($original_name . '-zoom');
		$this->store->load($this);
		$zoom = $this->get_value();
		$zoom = ($zoom === false) ? 5 : $zoom;
		$this->zoom = $zoom;

		$this->set_name($original_name);
		$this->set_value($lat . ',' . $lng . ',' . $zoom);
	}


	function set_value_from_input($input = null) {
		if ( is_null($input) ) {
			$input = $_POST;
		}

		if ( !isset($input[$this->name]) ) {
			$this->set_value(null);
		} else {
			$value = stripslashes_deep($input[$this->name]);

			if ( is_array($value) && isset($value['lat']) && isset($value['lng']) ) {
				$value = $value['lat'] . ',' . $value['lng'];
			}

			$this->set_value( $value );
		}
	}
}

class Carbon_Field_Select_Location extends Carbon_Field_Select {
	static $attached_scripts = false;

	function admin_init() {
		if ( !self::$attached_scripts ) {
			self::$attached_scripts = true;

			add_action('admin_footer', array($this, 'admin_enqueue_scripts'));
		}
		parent::admin_init();
	}

	function admin_enqueue_scripts() {
		$raw = get_posts('post_type=map-location&posts_per_page=-1&orderby=title&order=asc');
		$locations = array();
		foreach ($raw as $r) {
			$ml = new Mpfy_Map_Location($r->ID);
			$coords = $ml->get_coordinates();
			if ($coords) {
				$locations[$r->ID] = $coords;
			}
		}
		?>
		<script type="text/javascript">
		(function($){
			var location_data = <?php echo json_encode($locations); ?>;
			$(document).ready(function(){
				$('select[name="<?php echo $this->name; ?>"]').change(function() {
					var efo = $('.carbon-map[data-name="_map_google_center"]').data('exposed_field_object');
					var value = location_data[$(this).val()];
					efo.update_marker_position(new google.maps.LatLng(value[0], value[1]));
				});
			});
		})(jQuery);
		</script>
		<?php
	}
}

class Carbon_Field_Map_Mode extends Carbon_Field_Select {
	static $attached_scripts = false;

	function admin_init() {
		if ( !self::$attached_scripts ) {
			self::$attached_scripts = true;

			add_action('admin_footer', array($this, 'admin_enqueue_scripts'));
		}
		parent::admin_init();
	}

	function admin_enqueue_scripts() {
		$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
		?>
		<script type="text/javascript">
		(function($){
			var styles = <?php echo json_encode(apply_filters('mpfy_google_map_styles', (object) array())); ?>;
			for (var k in styles) {
				styles[k] = eval(styles[k]);
			}

			function mpfy_update_map_display() {
				// mode
				var mode = $('select[name="<?php echo $this->name; ?>"]').val();
				mode = (mode) ? mode : 'google_maps';

				var settings = {
					'efo': $('.carbon-map[data-name="_map_google_center"]').data('exposed_field_object'),
					'div': $('.carbon-map[data-name="_map_google_center"] .carbon-map-canvas:first'),
					'mode': mode,
					'bg_color': '',
					'style': 'default',
					'map_options': []
				};

				$('body').trigger($.Event('mpfy_admin_map_updated', {
					mpfy: {
						'settings': settings
					}
				}));

				// background
				settings.div.css('background-color', settings.bg_color);

				if (settings.mode == 'google_maps') {
					// terrain mode
					var map_mode = $('select[name="_map_google_mode"]').val();
					map_mode = map_mode ? map_mode : 'ROADMAP';
					settings.efo.map.setMapTypeId(google.maps.MapTypeId[map_mode]);

					// snazzymaps style
					settings.map_options['styles'] = styles[settings.style];
				}
				
				settings.efo.map.setOptions(settings.map_options);
			}

			$(document).ready(function(){
				$('body').on('mpfy_trigger_map_reload', function(e) {
					mpfy_update_map_display();
				});
				$('select[name="<?php echo $this->name; ?>"], select[name="_map_google_mode"]').change(function() {
					$('body').trigger($.Event('mpfy_trigger_map_reload'));
				});
				setTimeout(function() {
					$('select[name="<?php echo $this->name; ?>"]').trigger('change');
				},1);
			});
		})(jQuery);
		</script>
		<?php
	}
}

class Carbon_Field_Image_Pin extends Carbon_Field_Image {
	function admin_init() {
		add_action('admin_footer', array($this, 'admin_enqueue_scripts'));
		parent::admin_init();
	}

	function attach_to_map($map_field_name) {
		$this->map_field_name = $map_field_name;
		return $this;
	}

	function admin_enqueue_scripts() {
		?>
		<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$('input[name="<?php echo $this->name; ?>"]').change(function() {
					var efo = $('.carbon-map[data-name="_<?php echo $this->map_field_name; ?>"], .carbon-map-with-address[data-name="_<?php echo $this->map_field_name; ?>"]').data('exposed_field_object');
					if (!efo) {
						return true;
					}
					for (var i = 0; i < efo.map.crb.markers.length; i++) {
						var m = efo.map.crb.markers[i];
						m.setOptions({
      						icon: $(this).val()
						});
					}
				});
				setTimeout(function() {
					$('input[name="<?php echo $this->name; ?>"]').change();
				},1);
			});
		})(jQuery);
		</script>
		<?php
	}
}

class Carbon_Field_Tileset extends Carbon_Field_Text {
	function render() {
		$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
		if (!$post_id) {
			return false;
		}
		?>
		<em class="map_tileset_status">Checking status ...</em>
		<img src="<?php echo plugins_url('assets/images/tileset-loader.gif', MAPIFY_PLUGIN_FILE); ?>" alt="" style="position: relative; top: 1px;" />
		<script type="text/javascript">
		(function($, $window, $document){
			function _check_tileset_status() {
				$.get(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
					'action': 'mpfy_ajax_tileset_status'
					, 'id': <?php echo json_encode($post_id); ?>
				}, function(response) {
					$('.map_tileset_status').html(response.message);
					if (response.status != 'processing') {
						$('.map_tileset_status').next().hide();
						clearInterval(_tileset_interval);
						$('body').trigger({
							type: "mpfy_image_mode_status_changed",
							_response: response
						});
					} else {
						$('.map_tileset_status').next().show();
					}
				}, 'json');
			}

			$document.ready(function(){
				_check_tileset_status();
				_tileset_interval = setInterval(_check_tileset_status, 5*1000);
			});
		})(jQuery, jQuery(window), jQuery(document));
		</script>
		<?php
	}
}

class Carbon_Field_Relationship_Mpfy extends Carbon_Field_Relationship {
	function set_value_from_input($input = null) {
		if ( is_null($input) ) {
			$input = $_POST;
		}

		if ( !isset($input[$this->name]) ) {
			$this->set_value(null);
		} else {
			$val = implode(',', stripslashes_deep($input[$this->name]));
			$this->set_value($val);
		}
	}

	function render() {
		global $sitepress;
		$this->value = explode(',', $this->value);
		
		$this->value = maybe_unserialize($this->value);
		if (!is_array($this->value)) {
			$this->value = array($this->value);
		}

		// Exclude the current post from the list
		$screen = get_current_screen();
		$exclude_id = '';
		if ( is_object($screen) && $screen->base == 'post' && isset($_GET['post']) ) {
			$exclude_id = intval($_GET['post']);
		}

		$this->post_type = (array) $this->post_type;

		if ($sitepress) {
			$current_lang = $sitepress->get_current_language(); //save current language
			$sitepress->switch_lang($sitepress->get_default_language());
		}
		$query = new WP_Query(array(
			'post_type' => $this->post_type,
			'posts_per_page' => 10,
			'orderby' => 'title',
			'order' => 'ASC',
			'exclude' => $exclude_id
		));
		$posts = $query->posts;
		if ($sitepress) {
			$sitepress->switch_lang($current_lang); //restore previous language  
		}

		$post_types = get_post_types('','objects');
		?>
		<div class="carbon-relationship" data-post-type="<?php echo implode(',', $this->post_type) ?>" data-max-values="<?php echo $this->get_max() ?>" data-exclude="<?php echo $exclude_id ?>" data-paged="1" data-name="<?php echo $this->get_name() ?>[]">
			<div class="relationship-left">
				<table class="widefat">
					<thead>
						<tr>
							<th>
								<input type="text" placeholder="<?php esc_attr_e('Search', 'crb') ?>" />
							</th>
						</tr>
					</thead>
				</table>

				<ul class="relationship-list">
					<?php foreach ($posts as $post): 
					$type_title = $post->post_type;
					$type_title = isset($post_types[$type_title]->labels->singular_name) ? $post_types[$type_title]->labels->singular_name: $type_title;
					?>
						<li <?php if(in_array($post->ID, $this->value)) echo 'class="inactive"' ?>>
							<a href="#" data-post_id="<?php echo $post->ID ?>">
								<em><?php echo $type_title ?></em>
								<span><!-- plus --></span>
								<?php echo get_the_title($post->ID); ?>
							</a>
						</li>
					<?php endforeach ?>
					<li class="load-more"><span class="spinner"></span></li>
				</ul>
			</div>

			<div class="relationship-right">
				<label><?php _e('Associated:', 'crb'); ?></label>
				
				<ul class="relationship-list">
					<?php
					foreach ($this->value as $post_id): 
						$post = get_post($post_id);
						if ( !$post || !$post_id ) {
							continue;
						}
						$type_title = $post->post_type;
						$type_title = isset($post_types[$type_title]->labels->singular_name) ? $post_types[$type_title]->labels->singular_name: $type_title;
					?>
						<li>
							<a href="#" data-post_id="<?php echo $post->ID ?>"><em><?php echo $type_title ?></em><?php echo get_the_title($post->ID); ?> <span><!-- minus --></span></a>
							<input type="hidden" name="<?php echo $this->get_name() ?>[]" value="<?php echo $post->ID ?>" />
						</li>
					<?php endforeach ?>
				</ul>
			</div>
			
			<div class="cl">&nbsp;</div>
		</div>
		<?php
	}
}

class Carbon_Field_Image_List extends Carbon_Field_Complex {
	function _render() {
		$container_tag_class_name = get_class($this);
		include 'field_image_list.php';
	}
}

function mpfy_custom_admin_scripts() {
	?>
	<script type="text/javascript">
	(function($, $window, $document){
		$(document).ready(function(){
			$('#tagsdiv-location-tag .inside:first').append('<p>You may assign filters to this map, which will allow your users to filter location results via a dropdown. When you create specific locations within this map, you can then assign these filters to the location.</p>');
			var map_id = $('input[name="_map_id"]');
			map_id.hide().next().remove();
		});

		var styles = <?php echo json_encode(apply_filters('mpfy_google_map_styles', (object) array())); ?>;
		for (var k in styles) {
			styles[k] = eval(styles[k]);
		}

		var current_map_id = -1;
		function mpfy_update_map_layout() {
			var f = '_map_location_google_location';
			var efo = $('.carbon-map-with-address[data-name="' + f +'"]').data('exposed_field_object');
			if (!efo) {
				return false; // map object has not been initialized
			}
			
			google.maps.event.trigger(efo.map, 'resize');
			
			new_map_id = $('select[name="_map_location_map"], input[name="_map_location_map[]"]:first').val();
			if (new_map_id == current_map_id) {
				return false; // map has not been changed
			}

			current_map_id = new_map_id;
			if (!current_map_id) {
				return false; // no map selected
			}
			
			$('.carbon-field[data-name="' + f +'"]').after('<div id="map-loading"><em>Loading your map settings ...</em></div>');

			var promise = $.get("<?php echo admin_url('admin-ajax.php'); ?>", {
				'action': 'mpfy_get_map_settings',
				'pid': current_map_id
			}, 'json');
			promise.done(function(response) {
				response = $.parseJSON(response);

				var settings = {
					'efo': efo,
					'div': $('.carbon-map[data-name="_map_location_google_location"] .carbon-map-canvas:first'),
					'mode': response.mode,
					'bg_color': response.background,
					'map_options': [],

					'style': response.style,
					'terrain_mode': response.terrain_mode,
					'response': response
				};

				$('body').trigger($.Event('mpfy_admin_map_location_map_updated', {
					mpfy: {
						'settings': settings
					}
				}));

				// background
				settings.div.css('background-color', settings.bg_color);

				if (settings.mode == 'google_maps') {
					// terrain mode
					settings.efo.map.setMapTypeId(google.maps.MapTypeId[ settings.terrain_mode ]);

					// snazzymaps style
					settings.map_options['styles'] = settings.style;
				}
				
				settings.efo.map.setOptions(settings.map_options);
			});
			promise.always(function() {
				$('#map-loading').remove();
			});
			promise.error(function() {
				alert('There was a problem loading the selected map\'s settings.');
			});
		}

		if ($('.carbon-map-with-address[data-name="_map_location_google_location"]').length > 0) {
			setInterval(mpfy_update_map_layout, 2000);
		}

	})(jQuery, jQuery(window), jQuery(document));
	</script>
	<?php
}
add_action('admin_footer', 'mpfy_custom_admin_scripts');