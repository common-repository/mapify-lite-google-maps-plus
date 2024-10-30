<?php
$has_controls = ( ($filters_enabled && $map_tags) || $search_enabled );
$controls_classes = array();
if (($filters_enabled && $map_tags) && $search_enabled) {
	$controls_classes[] = 'mpfy-controls-all';
}
if (!$has_controls) {
	$controls_classes[] = 'mpfy-hidden';
}

$wrap_classes = array('mpfy-fullwrap');
$wrap_classes = apply_filters('mpfy_map_wrap_classes', $wrap_classes, $map_id);
$map_proprietary_data = apply_filters('mpfy_map_proprietary_data', array(), $map_id);
?>
<div id="mpfy-map-<?php echo $mpfy_instances; ?>" class="<?php echo implode(' ', $wrap_classes); ?>" data-proprietary="<?php echo esc_attr(json_encode($map_proprietary_data)) ?>">
	<?php if ($errors) : ?>
		<p>
			<?php foreach ($errors as $e) : ?>
				<?php echo $e; ?><br />
			<?php endforeach; ?>
		</p>
	<?php else : ?>
		
		<div class="mpfy-controls-wrap">
			<div class="mpfy-controls <?php echo implode(' ', $controls_classes); ?>">
				<form class="mpfy-search-form" method="post" action="" style="<?php echo (!$search_enabled) ? 'display: none;' : ''; ?>">
					<div class="mpfy-search-wrap">
						<div class="mpfy-search-field">
							<input type="text" name="mpfy_search" class="mpfy_search" value="" placeholder="<?php echo esc_attr(__('Enter city or zip code', 'mpfy')); ?>" />
							<a href="#" class="mpfy-clear-search">&nbsp;</a>
						</div>
						<?php do_action('mpfy_template_after_search_field', $map->get_id()); ?>
						<input type="submit" name="" value="<?php echo esc_attr(__('Search', 'mpfy')); ?>" class="mpfy_search_button" />
					</div>
				</form>

				<div class="mpfy-filter mpfy-selecter-wrap" style="<?php echo (!$filters_enabled || !$map_tags) ? 'display: none;' : ''; ?>">
					<select name="mpfy_tag" class="mpfy_tag_select">
						<option value="0"><?php echo esc_html(__('Default View', 'mpfy')); ?></option>
						<?php foreach ($map_tags as $t) : ?>
							<option value="<?php echo $t->term_id; ?>"><?php echo $t->name; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<?php if ($map->get_mode() == 'google_maps' && $map_google_ui_enabled && !$has_controls) : ?>
				<div class="mpfy-no-controls-padder"></div>
			<?php endif; ?>

			<?php if ($zoom_enabled || $map_google_ui_enabled) : ?>
				<a href="#" class="mpfy-zoom-in"></a>
				<a href="#" class="mpfy-zoom-out"></a>
			<?php endif; ?>
		</div>

		<div class="mpfy-map-canvas mpfy-mode-<?php echo $mode ?> <?php echo ($map_tags || $search_enabled) ? 'with-controls' : ''; ?>">
			<div style="display: none;">
				<?php foreach ($pins as $p) : ?>
					<?php
					$settings = array(
						'href'=>'#',
						'classes'=>array('mpfy-pin', 'mpfy-pin-id-' . $p->ID, 'no_link'),
					);
					if ($p->popup_enabled) {
						$settings['href'] = add_query_arg('mpfy_map', $map->get_id(), get_permalink($p->ID));
					}
					$settings = apply_filters('mpfy_pin_trigger_settings', $settings, $p->ID);
					?>
					<a
						target="<?php echo esc_attr($settings['target']); ?>"
						href="<?php echo esc_attr($settings['href']); ?>"
						data-id="<?php echo $p->ID; ?>"
						class="<?php echo esc_attr(implode(' ', $settings['classes'])); ?>">
						&nbsp;
					</a>
				<?php endforeach; ?>
			</div>

			<div class="mpfy-map-canvas-wrap">
				<?php
				$style = array('overflow: hidden');
				if ($width !== 0) {
					$style[] = 'width: ' . $width;
				}
				if ($height !== 0) {
					$style[] = 'height: ' . $height;
				}
				?>
				<div id="custom-mapping-google-map-<?php echo $mpfy_instances; ?>" style="<?php echo implode('; ', $style); ?>"></div>
			</div>

			<?php if ($filters_list_enabled) : ?>
				<div class="mpfy-tags-list">
					<div class="cl">&nbsp;</div>
					<a href="#" class="mpfy-tl-item" data-tag-id="0">
						<span class="mpfy-tl-i-icon"></span>
						<?php echo esc_html(__('Default View', 'mpfy')); ?>
					</a>
					<?php foreach ($map_tags as $t) : ?>
						<?php
						$image = wp_get_attachment_image_src(carbon_get_term_meta($t->term_id, 'mpfy_location_tag_image'), 'mpfy_location_tag');
						?>
						<a href="#" class="mpfy-tl-item" data-tag-id="<?php echo $t->term_id; ?>">
							<?php if ($image) : ?>
								<span class="mpfy-tl-i-icon" style="background-image: url('<?php echo $image[0]; ?>');"></span>
							<?php endif; ?>
							<?php echo $t->name; ?>
						</a>
					<?php endforeach; ?>
					<div class="cl">&nbsp;</div>
				</div>
			<?php endif; ?>

			<?php do_action('mpfy_template_after_map', $map->get_id()); ?>
		</div>
	<?php endif; ?>
</div>