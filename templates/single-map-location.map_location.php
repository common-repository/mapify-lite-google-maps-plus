<?php
$post = get_post(get_the_ID());
$popup_directions = mpfy_meta_to_bool(get_the_ID(), '_map_location_popup_directions', true);
$popup_location_information = mpfy_meta_to_bool(get_the_ID(), '_map_location_popup_location_information', true);
$has_content = ($post->post_content != '' || $popup_directions || $popup_location_information);
?>
<div class="mpf-p-popup-holder">
	<?php do_action('mpfy_popup_before_section', get_the_ID(), $map_id); ?>
	
	<div class="mpfy-p-popup-background"></div>
	<section class="<?php echo implode(' ', $classes); ?>">
		<div class="mpfy-p-holder mpfy-p-color-popup-background">

			<div class="mpfy-p-top">
				<h1><?php the_title(); ?></h1>
				<a href="#" class="mpfy-p-close"><span>&nbsp;</span></a>
			</div>

			<div class="mpfy-p-bottom">
				<?php do_action('mpfy_popup_before_content_layout', get_the_ID()); ?>

				<?php if ($media_count > 0) : ?>
					<div class="mpfy-p-slider <?php echo $has_content ? '' : 'mpfy-p-full-width-slider'; ?>">
						<div class="mpfy-p-slider-top">
							<ul class="mpfy-p-slides">
								<?php if ($video_embed_code) : ?>
									<li>
										<div class="holder video-holder">
											<?php echo Mpfy_Carbon_Video::create($video_embed_code)->get_embed_code(624, 624); ?>
										</div>
									</li>
								<?php endif; ?>
								<?php
								if (!empty($images)) {
									foreach ($images as $image) {
										?>
										<li>
											<div class="holder">
												<img src="<?php echo mpfy_get_thumb($image['image'], 624, 624, false); ?>" alt="" width="624" height="624" />
											</div>
										</li>
										<?php
									}
								}
								?>
							</ul>
							<div class="cl">&nbsp;</div>
						</div>
						<?php if ( count($images) > 1 || (count($images) == 1 && $video_embed_code) ) : ?>
							<div class="mpfy-p-slider-bottom">
								<ul class="mpfy-p-slides">
									<?php $i = 0; ?>
									<?php if ($video_embed_code) : ?>
										<li>
											<a href="#" data-position="<?php echo $i; ?>">
												<img src="<?php echo mpfy_get_thumb($video_thumb, 108, 108); ?>" alt="" />
											</a>
										</li>
										<?php $i ++; ?>
									<?php endif; ?>
									<?php
									if (!empty($images)) {
										foreach ($images as $image) {
											?>
											<li>
												<a href="#" data-position="<?php echo $i; ?>">
													<img src="<?php echo mpfy_get_thumb($image['image'], 108, 108); ?>" alt="" />
												</a>
											</li>
											<?php
											$i ++;
										}
									}
									?>
								</ul>
							
								<div class="cl">&nbsp;</div>
								<a href="#" class="mpfy-p-arrow mpfy-p-arrow-previous">&nbsp;</a>
								<a href="#" class="mpfy-p-arrow mpfy-p-arrow-next">&nbsp;</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ($has_content) : ?>
					<div class="mpfy-p-content">
						<div class="mpfy-p-scroll">
							<div class="mpfy-p-holder">
								<?php do_action('mpfy_popup_location_information', get_the_ID(), $map_id); ?>
								<div class="cl">&nbsp;</div>
								<div class="mpfy-p-entry">
									<?php do_action('mpfy_popup_content_before', get_the_ID()); ?>
									<?php the_content(); ?>
									<?php do_action('mpfy_popup_content_after', get_the_ID()); ?>
								</div>
							</div>					
						</div>
					</div>
				<?php endif; ?>
			</div>		
		</div>
	</section>

	<?php /* Simple image preloading */ ?>
	<?php if ($images) : ?>
		<script type="text/javascript">
		var img = new Image();
		img.onLoad = function() {
			jQuery(window).trigger('mpfy_popup_load');
		};
		img.onload = img.onLoad;
		img.src = <?php echo json_encode(mpfy_get_thumb($image['image'], 624, 624, false)); ?>;
		</script>
	<?php endif; ?>
</div>