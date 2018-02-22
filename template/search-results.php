<?php

// Copy this file to /wp-content/YOUR-THEME-FOLDER/envato-affiliate/search-results.php if you wish to modify it.

defined( 'ABSPATH' ) || die;


if( isset($items) && is_array($items) && isset( $settings ) && $settings ) {
	?>

	<div class="envato-affiliate-<?php echo esc_attr($settings['layout']);?>">
		<ul class="envato-affiliate-<?php echo esc_attr($settings['layout']);?>--list">
			<?php foreach($items as $item){
				if( $item && !empty( $item['id'])) {
					$thumbnail_url = !empty($item['thumbnail_url']) ? $item['thumbnail_url'] : '';
					$preview_graphic_url = !empty($item['preview_graphic_url']) ? $item['preview_graphic_url'] : '';
					$image_size = !empty($item['preview_graphic_size']) ? $item['preview_graphic_size'] : 'full';
					$url       = EnvatoAffiliate::get_instance()->affiliate_link( $item['url'], $settings );
					$title     = $item['title'];
					if ( $url && $preview_graphic_url ) {
						?>
						<li class="envato-affiliate-<?php echo esc_attr( $settings['layout'] ); ?>--list-item">
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="nofollow"
							   class="envato-affiliate-<?php echo esc_attr( $settings['layout'] ); ?>--link">
								<img src="<?php echo esc_url( $preview_graphic_url ); ?>" alt="<?php echo esc_attr( $title ); ?>"
								     class="envato-affiliate-<?php echo esc_attr( $settings['layout'] ); ?>--image --size-<?php echo $image_size;?>">
							</a>
						</li>
						<?php
					}
				}
			} ?>
		</ul>
	</div>

	<?php
}